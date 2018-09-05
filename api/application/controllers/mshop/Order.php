<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 订单处理接口（H5用户端）
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponSettingDao;
class Order extends mshop_controller
{
    /**
     * 订单列表
     */
    public function index()
    {
        if (!isset($this->s_user->uid)) {
            $this->json_do->set_error('004', '用户ID不存在');
        }

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query(['aid'=>$this->aid,'uid' => $this->s_user->uid])->get();

        // 服务器时间
        $order['server_time'] = date('Y-m-d H:i:s');

        $this->json_do->set_data($order);
        $this->json_do->out_put();
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $tradeno = (int) $this->input->post_get('tradeno');

        if (!$this->s_user->uid) {
            $this->json_do->set_error('004', '用户ID不存在');
        }

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query(['aid'=>$this->aid,'uid' => $this->s_user->uid, 'tradeno' => $tradeno])->get();

        if (count($order['rows']) > 1) {
            log_message('error', '存在重复订单号 => ' . $tradeno);
        }

        $order = $order['rows'][0];

        // 拼单订单进行订单详情重组
        if ($order['type'] == 3) {
            // 分组子订单
            $order_ext = [];
            // 点餐人订单金额统计
            $order_ext_puid_pay = [];
            // 点餐人信息
            $puid_info = [];
            foreach ($order['order_ext'] as $key => $row) {
                $order_ext[$row['puid']][] = $row;
                $order_ext_puid_pay[$row['puid']] += $row['order_money'];
                if (!isset($puid_info[$row['puid']])) {
                    $puid_info[$row['puid']] = ['puid' => $row['puid'], 'nickname' => $row['nickname']];
                }
            }

            // 总订单金额
            $sum_order_money = array_sum($order_ext_puid_pay);

            // 重新组装的数据结构
            $new_order_ext = [];
            foreach ($order_ext as $puid => $row) {
                $new_order_ext[] = [
                    'pindan_user' => $puid_info[$puid],
                    'aa_money' => round($order_ext_puid_pay[$puid] / $sum_order_money * $order['pay_money'], 2),
                    'items' => $row,
                ];
            }

            $order['order_ext'] = $new_order_ext;
        }

        $wmShopDao = WmShopDao::i($this->aid);
        $order['shop'] = $wmShopDao->getOneArray(['id' => $order['shop_id'], 'aid'=>$this->aid]);

        // 服务器时间
        $order['server_time'] = date('Y-m-d H:i:s');

        // 获取当前商家的活动设置
        $wmCouponSettingDao = WmCouponSettingDao::i($this->aid);
        $coupon_setting = $wmCouponSettingDao->getOneArray(['aid' => $this->aid, 'type' => 1]);

        // 活动已结束
        if (!$coupon_setting || $coupon_setting['is_open'] != 1 || time() < $coupon_setting['start_time'] || time() > $coupon_setting['end_time']) {
            $order['share_redpacket'] = 0;
        } else {
            // 判断该订单是否允许分享优惠券
            $order['share_redpacket'] = $order['pay_time']['value'] > 0 && $order['pay_time']['value'] + 86400 > time() ? 1 : 0;
        }

        $this->json_do->set_data($order);
        $this->json_do->out_put();
    }

    /**
     * 预加载订单
     */
    public function preOrder()
    {
        $rule = [
            ['field' => 'is_self_pick', 'label' => '是否到店自取', 'rules' => 'trim|in_list[0,1]'],
            ['field' => 'self_pick_info', 'label' => '到店自取信息', 'rules' => 'trim'],
            ['field' => 'is_pindan', 'label' => '是否拼单', 'rules' => 'trim|in_list[0,1]'],
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|numeric'],
            ['field' => 'longitude', 'label' => '收货经度', 'rules' => 'trim|required'],
            ['field' => 'latitude', 'label' => '收货纬度', 'rules' => 'trim|required'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required'],
            ['field' => 'items', 'label' => '订单JSON', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $input['uid'] = $this->s_user->uid;
        $input['openid'] = $this->s_user->openid;
        $input['items'] = json_decode($input['items'], true);
        $input['aid'] = $this->aid;
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->orderCreate($input, true);
    }

    /**
     * 创建订单 items [{,pro_attr}]
     */
    public function create()
    {
        $rule = [
            ['field' => 'is_self_pick', 'label' => '是否到店自取', 'rules' => 'trim|in_list[0,1]'],
            ['field' => 'self_pick_info', 'label' => '到店自取信息', 'rules' => 'trim'],
            ['field' => 'is_pindan', 'label' => '是否拼单', 'rules' => 'trim|in_list[0,1]'],
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|numeric'],
            ['field' => 'rec_addr_id', 'label' => '收货地址ID', 'rules' => 'trim'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required'],
            ['field' => 'tableware', 'label' => '餐具份数', 'rules' => 'trim'],
            ['field' => 'remark', 'label' => '订单留言', 'rules' => 'trim'],
            ['field' => 'coupon_id', 'label' => '优惠券ID', 'rules' => 'trim|numeric'], // 系统内优惠券
            ['field' => 'card_id', 'label' => '代金券ID', 'rules' => 'trim'], // 微信代金券
            ['field' => 'items', 'label' => '订单JSON', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $input['uid'] = $this->s_user->uid;
        $input['openid'] = $this->s_user->openid;
        $input['items'] = json_decode($input['items'], true);
        $input['aid'] = $this->aid;
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->orderCreate($input);
    }

    /**
     * 取消订单
     */
    public function cancel()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['uid'] = $this->s_user->uid;
        $fdata['aid'] = $this->aid;
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->cancelOrder($fdata);
    }

}

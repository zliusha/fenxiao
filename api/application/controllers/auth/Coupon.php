<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 优惠券接口（小程序用户端）
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponWxbindDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponSettingDao;
class Coupon extends xcx_user_controller
{

    /**
     * 个人优惠券列表
     */
    public function index()
    {
        $rule = [
            ['field' => 'status', 'label' => '状态', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $params = [
            'mobile' => $this->s_user->mobile,
            'aid' => $this->aid,
        ];

        if ($fdata['status']) {
            $params['status'] = $fdata['status'];
        }

        $wm_coupon_query_bll = new wm_coupon_query_bll();
        $list = $wm_coupon_query_bll->query($params)->get();

        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 加载领券信息
     */
    public function load()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单号', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 校验当前网址aid和订单号aid是否一致
        $wmOrderDao = WmOrderDao::i($this->aid);
        $order = $wmOrderDao->getOneArray(['aid' => $this->aid,'tid' => $fdata['tradeno'], 'pay_time >' => 0], 'aid');

        if (!$order) {
            $this->json_do->set_error('004', '非法请求');
        }

        $wm_coupon_query_bll = new wm_coupon_query_bll();

        // 初始化返回数组
        $ret = [];

        // 获取绑定的手机号
        $wmCouponWxbindDao = WmCouponWxbindDao::i($this->aid);
        $coupon_wxbind = $wmCouponWxbindDao->getOneArray(['open_id' => $this->s_user->ext['open_id'], 'aid' => $this->aid]);

        if (!$coupon_wxbind) {
            $ret['mobile'] = null;
        } else {
            $ret['mobile'] = $coupon_wxbind['mobile'] ? $coupon_wxbind['mobile'] : null;
        }

        // 获取当前商家的活动设置
        $wmCouponSettingDao = WmCouponSettingDao::i($this->aid);
        $ret['setting'] = $wmCouponSettingDao->getOneArray(['aid' => $this->aid, 'type' => 1]);

        // 获取已领红包记录
        $wmCouponDao = WmCouponDao::i($this->aid);
        $ret['list'] = $wmCouponDao->getAllArray(['aid' => $this->aid, 'tid' => $fdata['tradeno'], 'type' => 1], '*', 'id asc');

        foreach ($ret['list'] as $key => $row) {
            $ret['list'][$key]['time_show'] = $wm_coupon_query_bll->pickup_time_formatter($row['time']);
        }

        // 查找用户领取的红包
        $ret['coupon'] = null;
        foreach ($ret['list'] as $key => $row) {
            if ($this->s_user->ext['open_id'] == $row['open_id']) {
                $ret['coupon'] = $row;
            }
        }

        if ($ret['coupon']) {
            $ret['coupon']['expire_time_show'] = $wm_coupon_query_bll->expire_time_formatter($ret['coupon']['use_start_time'], $ret['coupon']['use_end_time']);
        }

        // log_message('error', json_encode($this->s_user));
        // log_message('error', $this->s_user->ext['open_id']);
        // log_message('error', json_encode($ret));

        $this->json_do->set_data($ret);

        // 每日限领5张
        if ($ret['mobile']) {
            $_start_time = strtotime(date('Y-m-d'));
            $_end_time = $_start_time + 86400;
            if ($wmCouponDao->getCount(['aid' => $this->aid, 'tid' => $fdata['tradeno'], 'open_id' => $this->s_user->ext['open_id'], 'type' => 1, 'time >=' => $_start_time, 'time <=' => $_end_time]) >= 5) {
                $this->json_do->set_error('004', '今日领的太多了');
            }
        }

        // 活动已结束
        if (!$ret['setting']) {
            $this->json_do->set_error('004', '活动已结束');
        } else if ($ret['setting']['is_open'] != 1) {
            $this->json_do->set_error('004', '活动已结束');
        } else if (time() < $ret['setting']['start_time']) {
            $this->json_do->set_error('004', '活动未开始');
        } else if (time() > $ret['setting']['end_time']) {
            $this->json_do->set_error('004', '活动已结束');
        }

        // 优惠券已领完
        if ($wmCouponDao->getCount(['tid' => $fdata['tradeno'], 'aid' => $this->aid, 'type' => 1, 'open_id !=' => $this->s_user->ext['open_id']]) >= $ret['setting']['quantity']) {
            $this->json_do->set_error('004', '优惠券已领完');
        }

        $this->json_do->out_put();
    }

    public function _changeMobile($mobile)
    {
        if(!$this->s_user->ext['open_id']) {
            return '尚未授权登录';
        }

        // 查询是否绑定
        $wmCouponWxbindDao = WmCouponWxbindDao::i($this->aid);


        if ($wmCouponWxbindDao->getCount(['open_id' => $this->s_user->ext['open_id'], 'aid' => $this->aid]) > 0) {
            // 更新
            $result = $wmCouponWxbindDao->update(['mobile' => $mobile, 'update_time' => time()], ['open_id' => $this->s_user->ext['open_id'], 'aid' => $this->aid]);
        } else {
            // 创建
            $result = $wmCouponWxbindDao->create([
                'mobile' => $mobile,
                'open_id' => $this->s_user->ext['open_id'],
                'union_id' => '',
                'aid' => $this->aid,
                'update_time' => 0,
            ]);
        }

        if ($result !== false) {
            return true;
        } else {
            return '绑定失败，请稍候重试';
        }

    }

    /**
     * 修改领券手机号
     */
    public function changeMobile()
    {
        $rule = [
            ['field' => 'mobile', 'label' => '领券手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $result = $this->_changeMobile($fdata['mobile']);

        if ($result === true) {
            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', $result);
        }
    }

    /**
     * 领取裂变优惠券
     */
    public function pickup()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单号', 'rules' => 'trim|required|numeric'],
            ['field' => 'mobile', 'label' => '领券手机号', 'rules' => 'trim|preg_key[MOBILE]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 绑定收集
        if ($fdata['mobile']) {
            $cm_result = $this->_changeMobile($fdata['mobile']);
            if ($cm_result !== true) {
                $this->json_do->set_error('004', $cm_result);
            }
        }

        // 校验当前网址aid和订单号aid是否一致
        $wmOrderDao = WmOrderDao::i($this->aid);
        $order = $wmOrderDao->getOneArray(['aid' => $this->aid,'tid' => $fdata['tradeno']], 'aid');

        if (!$order) {
            $this->json_do->set_error('004', '非法请求');
        }

        // 获取绑定的手机号
        $wmCouponWxbindDao = WmCouponWxbindDao::i($this->aid);
        $coupon_wxbind = $wmCouponWxbindDao->getOneArray(['open_id' => $this->s_user->ext['open_id'], 'aid' => $this->aid]);

        if (!$coupon_wxbind) {
            $this->json_do->set_error('004', '手机号未绑定');
        } else {
            if (empty($coupon_wxbind['mobile'])) {
                $this->json_do->set_error('004', '手机号未绑定');
            }
        }

        // 检查是否已经领取
        $wmCouponDao = WmCouponDao::i($this->aid);
        if ($wmCouponDao->getCount(['tid' => $fdata['tradeno'], 'aid' => $this->aid, 'open_id' => $this->s_user->ext['open_id'], 'type' => 1]) > 0) {
            $this->json_do->set_error('004', '已领取过优惠券');
        }

        // 获取当前商家的活动设置
        $wmCouponSettingDao = WmCouponSettingDao::i($this->aid);
        $coupon_setting = $wmCouponSettingDao->getOneArray(['aid' => $this->aid, 'type' => 1]);

        if (!$coupon_setting) {
            $this->json_do->set_error('004', '活动未设置');
        } else {
            if ($coupon_setting['is_open'] != 1) {
                $this->json_do->set_error('004', '活动未开启');
            }

            if (time() < $coupon_setting['start_time']) {
                $this->json_do->set_error('004', '活动未开始');
            }

            if (time() > $coupon_setting['end_time']) {
                $this->json_do->set_error('004', '活动已结束');
            }
        }

        if ($wmCouponDao->getCount(['tid' => $fdata['tradeno'], 'aid' => $this->aid, 'type' => 1]) >= $coupon_setting['quantity']) {
            $this->json_do->set_error('004', '优惠券已领完');
        }

        // 优惠券金额计算
        if ($coupon_setting['amount_type'] == 1) {
            $coupon_amount = $coupon_setting['amount'];
        } else if ($coupon_setting['amount_type'] == 2) {
            $amount_region = explode('-', $coupon_setting['amount_region']);
            $coupon_amount = mt_rand($amount_region[0] * 10, $amount_region[1] * 10) / 10;
        } else {
            $this->json_do->set_error('004', '优惠券设置异常');
        }

        $wmUserDao = WmUserDao::i($this->aid);
        $wm_user = $wmUserDao->getOne(['id' => $this->s_user->uid, 'aid' => $this->aid]);

        $result = $wmCouponDao->create([
            'aid' => $this->aid,
            'tid' => $fdata['tradeno'],
            'wx_avatar' => $wm_user->img ? $wm_user->img : '',
            'wx_nick' => $wm_user->username ? $wm_user->username : '',
            'open_id' => $this->s_user->ext['open_id'],
            'mobile' => $coupon_wxbind['mobile'],
            'title' => $coupon_setting['title'],
            'type' => 1,
            'amount' => $coupon_amount,
            'use_start_time' => $coupon_setting['use_start_time'],
            'use_end_time' => $coupon_setting['use_end_time'],
            'condition_limit' => $coupon_setting['condition_limit'],
        ]);

        if ($result !== false) {
            $this->json_do->set_msg('领取成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '领取失败');
        }

    }

    /**
     * 读取关注领优惠券
     */
    public function loadFollow()
    {
        $wm_coupon_query_bll = new wm_coupon_query_bll();

        // 初始化返回数组
        $ret = [];

        // 获取绑定的手机号
        $wmCouponWxbindDao = WmCouponWxbindDao::i($this->aid);
        $coupon_wxbind = $wmCouponWxbindDao->getOneArray(['open_id' => $this->s_user->ext['open_id'], 'aid' => $this->aid]);

        if (!$coupon_wxbind) {
            $ret['mobile'] = null;
        } else {
            $ret['mobile'] = $coupon_wxbind['mobile'] ? $coupon_wxbind['mobile'] : null;
        }

        // 获取当前商家的活动设置
        $wmCouponSettingDao = WmCouponSettingDao::i($this->aid);
        $ret['setting'] = $wmCouponSettingDao->getOneArray(['aid' => $this->aid, 'type' => 2]);

        // 获取已领红包记录
        $wmCouponDao = WmCouponDao::i($this->aid);
        $ret['coupon'] = $wmCouponDao->getOneArray(['open_id' => $this->s_user->ext['open_id'], 'aid' => $this->aid, 'type' => 2], '*', 'id asc');

        if ($ret['coupon']) {
            $ret['coupon']['time_show'] = $wm_coupon_query_bll->pickup_time_formatter($ret['coupon']['time']);
            $ret['coupon']['expire_time_show'] = $wm_coupon_query_bll->expire_time_formatter($ret['coupon']['use_start_time'], $ret['coupon']['use_end_time']);
        }

        $this->json_do->set_data($ret);

        // 活动已结束
        if (!$ret['setting']) {
            $this->json_do->set_error('004', '活动已结束');
        } else if ($ret['setting']['is_open'] != 1) {
            $this->json_do->set_error('004', '活动已结束');
        } else if (time() < $ret['setting']['start_time']) {
            $this->json_do->set_error('004', '活动未开始');
        } else if (time() > $ret['setting']['end_time']) {
            $this->json_do->set_error('004', '活动已结束');
        }

        $this->json_do->out_put();
    }

    /**
     * 领取关注领优惠券
     */
    public function pickupFollow()
    {
        $rule = [
            ['field' => 'mobile', 'label' => '领券手机号', 'rules' => 'trim|preg_key[MOBILE]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 绑定收集
        if ($fdata['mobile']) {
            $cm_result = $this->_changeMobile($fdata['mobile']);
            if ($cm_result !== true) {
                $this->json_do->set_error('004', $cm_result);
            }
        }

        // 获取绑定的手机号
        $wmCouponWxbindDao = WmCouponWxbindDao::i($this->aid);
        $coupon_wxbind = $wmCouponWxbindDao->getOneArray(['open_id' => $this->s_user->ext['open_id'], 'aid' => $this->aid]);

        if (!$coupon_wxbind) {
            $this->json_do->set_error('004', '手机号未绑定');
        } else {
            if (empty($coupon_wxbind['mobile'])) {
                $this->json_do->set_error('004', '手机号未绑定');
            }
        }

        // 检查是否已经领取
        $wmCouponDao = WmCouponDao::i($this->aid);
        if ($wmCouponDao->getCount(['aid' => $this->aid, 'open_id' => $this->s_user->ext['open_id'], 'type' => 2]) > 0) {
            $this->json_do->set_error('004', '已领取过优惠券');
        }

        // 获取当前商家的活动设置
        $wmCouponSettingDao = WmCouponSettingDao::i($this->aid);
        $coupon_setting = $wmCouponSettingDao->getOneArray(['aid' => $this->aid, 'type' => 2]);

        if (!$coupon_setting) {
            $this->json_do->set_error('004', '活动未设置');
        } else {
            if ($coupon_setting['is_open'] != 1) {
                $this->json_do->set_error('004', '活动未开启');
            }

            if (time() < $coupon_setting['start_time']) {
                $this->json_do->set_error('004', '活动未开始');
            }

            if (time() > $coupon_setting['end_time']) {
                $this->json_do->set_error('004', '活动已结束');
            }
        }

        $result = $wmCouponDao->create([
            'aid' => $this->aid,
            'tid' => 0,
            'wx_avatar' => $this->s_user->img ? $this->s_user->img : '',
            'wx_nick' => $this->s_user->nickname,
            'open_id' => $this->s_user->ext['open_id'],
            'mobile' => $coupon_wxbind['mobile'],
            'title' => $coupon_setting['title'],
            'type' => 2,
            'amount' => $coupon_setting['amount'],
            'use_start_time' => $coupon_setting['use_start_time'],
            'use_end_time' => $coupon_setting['use_end_time'],
            'condition_limit' => $coupon_setting['condition_limit'],
        ]);

        if ($result !== false) {
            $this->json_do->set_msg('领取成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '领取失败');
        }
    }

}

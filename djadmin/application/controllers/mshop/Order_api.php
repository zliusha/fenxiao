<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 订单处理接口（商家后台）
 */

use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPromotionDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPaymentRecordDao;

class Order_api extends wm_service_controller
{

    /**
     * 订单列表（外卖订单）
     */
    public function index()
    {
        $input = ['aid' => $this->s_user->aid, 'type' => '1,3'];
        //子账号门店
        if(!$this->is_zongbu)
            $input['shop_id'] = $this->currentShopId;

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query($input)->get();

        $this->json_do->set_data($order);
        $this->json_do->out_put();
    }

    /**
     * 订单列表（门店自取订单）
     */
    public function self_pick_up_list()
    {
        $input = ['aid' => $this->s_user->aid, 'type' => 2];
        //子账号门店
        if(!$this->is_zongbu)
            $input['shop_id'] = $this->currentShopId;

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query($input)->get();

        $this->json_do->set_data($order);
        $this->json_do->out_put();
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $tradeno = (int) $this->input->post_get('tradeno');

        $input = ['aid' => $this->s_user->aid, 'tradeno' => $tradeno];
        //子账号门店
        if(!$this->is_zongbu)
            $input['shop_id'] = $this->currentShopId;

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query($input)->get();

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

        $this->json_do->set_data($order);
        $this->json_do->out_put();
    }

    /**
     * 导出订单列表
     */
    public function export()
    {
        $input = ['aid' => $this->s_user->aid];
        //子账号门店
        if(!$this->is_zongbu)
            $input['shop_id'] = $this->currentShopId;

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query($input)->get();

        foreach ($order['rows'] as $key => $row) {
            $_row = [];

            foreach ($row['order_ext'] as $order_ext) {
                $_row['goods_name'][] = $order_ext['goods_title'];
            }
            $_row['goods_name'] = implode('|', $_row['goods_name']);
            $_row['tid'] = $row['tid'];
            $_row['shop_name'] = $row['shop_name'];
            $_row['time'] = $row['time']['alias'];
            $_row['pay_time'] = $row['pay_time']['alias'];
            $_row['pay_money'] = $row['pay_money'];
            $_row['logistics_type'] = $row['logistics_type']['alias'];
            $_row['status'] = $row['status']['alias'];

            $data[] = $_row;
        }

        // 字段，一定要以$data字段顺序
        $fields = [
            'goods_name' => '商品名称',
            'tid' => '订单编号',
            'shop_name' => '所属门店',
            'time' => '下单时间',
            'pay_time' => '付款时间',
            'pay_money' => '实付款',
            'logistics_type' => '配送方式',
            'status' => '状态',
        ];
        // 文件名尽量使用英文
        $filename = 'WmOrder_' . mt_rand(100, 999) . '_' . date('Y-m-d');

        ci_phpexcel::down($fields, $data, $filename);
    }
    /**
     * 导出订单列表-自担
     */
    public function export_pick_up()
    {
        $input = ['aid' => $this->s_user->aid, 'type' => 2];
        //子账号门店
        if(!$this->is_zongbu)
            $input['shop_id'] = $this->currentShopId;

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query($input)->get();
        $data = [];
        foreach ($order['rows'] as $key => $row) {
            $_row = [];

            // foreach ($row['order_ext'] as $order_ext) {
            //     $_row['goods_name'][] = $order_ext['goods_title'];
            // }
            // $_row['goods_name'] = implode('|', $_row['goods_name']);
            $_row['tid'] = ' ' . $row['tid'];
            $_row['shop_name'] = $row['shop_name'];
            //订单总金额 = 商品金额+餐盒+运费
            $_row['total_money'] = ' ' . sprintf('%.2f', $row['total_money'] + $row['freight_money'] + $row['package_money']);
            $_row['discount_money'] = ' ' . $row['discount_money'];
            $_row['pay_money'] = ' ' . $row['pay_money'];
            $_row['tk_money'] = ' ' . $row['tk_money'];
            $_row['logistics_type'] = $row['logistics_type']['alias'];
            $_row['status'] = $row['status']['alias'];
            $_row['pay_type'] = empty($row['pay_type']['alias']) ? '-' : $row['pay_type']['alias'];
            $_row['logistics_code'] = ' ' . $row['logistics_code'];
            $_row['pick_phone'] = ' ' . $row['logistics_detail']['pick_phone'];
            $_row['pick_time'] = date('Y-m-d H:i:s', $row['logistics_detail']['pick_time']/1000);
            $_row['pay_time'] = $row['pay_time']['alias'];
            $_row['time'] = $row['time']['alias'];
            array_push($data, $_row);
        }

        // 字段，一定要以$data字段顺序
        $fields = [
            // 'goods_name' => '商品名称',
            'tid' => '订单编号',
            'shop_name' => '所属门店',
            'total_money' => '总金额',
            'discount_money' => '优惠金额',
            'pay_money' => '实付款',
            'tk_money' => '退款金额',
            'logistics_type' => '配送方式',
            'status' => '状态',
            'pay_type' => '支付方式',
            'logistics_code' => '取货码',
            'pick_phone' => '取货手机号',
            'pick_time' => '取货时间',
            'pay_time' => '付款时间',
            'time' => '下单时间',
        ];
        // 文件名尽量使用英文
        $filename = 'ZtOrder_' . date('Y-m-d') . '_' . mt_rand(100, 999);
        ob_end_clean();
        ci_phpexcel::down($fields, $data, $filename);
    }
    /**
     * 导出订单列表 wm
     */
    public function export_wm()
    {
        $input = ['aid' => $this->s_user->aid, 'type' => 1];
        //子账号门店
        if(!$this->is_zongbu)
            $input['shop_id'] = $this->currentShopId;

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query($input)->get();
        $data = [];
        foreach ($order['rows'] as $key => $row) {
            $_row = [];

            // foreach ($row['order_ext'] as $order_ext) {
            //     $_row['goods_name'][] = $order_ext['goods_title'];
            // }
            // $_row['goods_name'] = implode('|', $_row['goods_name']);
            $_row['tid'] = ' ' . $row['tid'];
            $_row['shop_name'] = $row['shop_name'];
            //订单总金额 = 商品金额+餐盒+运费
            $_row['total_money'] = ' ' . sprintf('%.2f', $row['total_money'] + $row['freight_money'] + $row['package_money']);
            $_row['discount_money'] = ' ' . $row['discount_money'];
            $_row['pay_money'] = ' ' . $row['pay_money'];
            $_row['tk_money'] = ' ' . $row['tk_money'];
            $_row['logistics_type'] = $row['logistics_type']['alias'];
            $_row['status'] = $row['status']['alias'];
            $_row['pay_type'] = empty($row['pay_type']['alias']) ? '-' : $row['pay_type']['alias'];
            $_row['receiver_address'] = $row['receiver_site'] . $row['receiver_address'];
            $_row['receiver_name'] = $row['receiver_name'];
            $_row['receiver_phone'] = ' ' . $row['receiver_phone'];
            $_row['pay_time'] = $row['pay_time']['alias'];
            $_row['time'] = $row['time']['alias'];
            array_push($data, $_row);
        }

        // 字段，一定要以$data字段顺序
        $fields = [
            // 'goods_name' => '商品名称',
            'tid' => '订单编号',
            'shop_name' => '所属门店',
            'total_money' => '总金额',
            'discount_money' => '优惠金额',
            'pay_money' => '实付款',
            'tk_money' => '退款金额',
            'logistics_type' => '配送方式',
            'status' => '状态',
            'pay_type' => '支付方式',
            'receiver_address' => '收货人-地址',
            'receiver_name' => '收货人-姓名',
            'receiver_phone' => '收货人-手机号',
            'pay_time' => '付款时间',
            'time' => '下单时间',
        ];
        // 文件名尽量使用英文
        $filename = 'order_' . date('Y-m-d') . '_' . mt_rand(100, 999);
        ob_end_clean();
        ci_phpexcel::down($fields, $data, $filename);
    }

    /**
     * 打印订单
     */
    public function printOrder()
    {
        if ($this->is_zongbu) {
            $this->json_do->set_error('004', '总账号不支持打印服务');
        }

        $tradeno = (int) $this->input->post_get('tradeno');

        $wm_print_order_bll = new wm_print_order_bll();
        $wm_print_order_bll->printOrder(['tid' => $tradeno, 'aid' => $this->s_user->aid]);
    }

    /**
     * 达达推单
     */
    public function pushDadaOrder()
    {
        $tradeno = (int) $this->input->post_get('tradeno');

        try {
            // 达达发单,目前只有达达
            $wm_dada_bll = new wm_dada_bll();
            $wm_dada_bll->push(['tradeno' => $tradeno,'aid'=>$this->s_user->aid], false);

            $this->json_do->set_msg('推单成功');
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('004', $e->getMessage());
            // log_message('error', __METHOD__ . '--' . $e->getMessage());
        }
    }

    /**
     * 达达重新推单
     */
    public function rePushDadaOrder()
    {
        $tradeno = (int) $this->input->post_get('tradeno');

        try {
            // 达达发单,目前只有达达
            $wm_dada_bll = new wm_dada_bll();
            $wm_dada_bll->push(['tradeno' => $tradeno,'aid'=>$this->s_user->aid], true);

            $wmOrderDao = WmOrderDao::i($this->s_user->aid);
            $wmOrderDao->update(['last_repush_time' => time()], ['tid' => $tradeno]);

            $this->json_do->set_msg('推单成功');
            $this->json_do->out_put();
        } catch (Exception $e) {
            // code:2061，msg:只有已取消、已过期、投递异常的订单才能重发
            $msg = explode('，', $e->getMessage());
            $msg = str_replace('msg:', '', $msg[1]);
            $this->json_do->set_error('004', $msg);
            // log_message('error', __METHOD__ . '--' . $e->getMessage());
        }
    }

    /**
     * 推单
     */
    public function pushOrder()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $mWmOrder = $wmOrderDao->getOne(['aid'=>$this->s_user->aid, 'tid'=>$fdata['tradeno']]);
        if(!$mWmOrder)
            $this->json_do->set_error('004', '订单不存在');

        try{
            switch($mWmOrder->logistics_type)
            {
                case 2:// 达达
                    $wm_dada_bll = new wm_dada_bll();
                    $wm_dada_bll->push(['tradeno' => $fdata['tradeno'],'aid'=>$this->s_user->aid], false);
                    break;
                case 3:// 点我达
                    $wm_dianwoda_bll = new wm_dianwoda_bll();
                    $wm_dianwoda_bll->push(['tradeno' => $fdata['tradeno'],'aid'=>$this->s_user->aid], false);
                    break;
                case 5:// 风达
                    $wm_fengda_bll = new wm_fengda_bll();
                    $wm_fengda_bll->push(['tradeno' => $fdata['tradeno'],'aid'=>$this->s_user->aid], false);
                    break;
                case 6:// 餐厅宝
                    $wm_cantingbao_bll = new wm_cantingbao_bll();
                    $wm_cantingbao_bll->push(['tid' => $fdata['tradeno'],'aid'=>$this->s_user->aid], false);
                    break;
                default:
                    $this->json_do->set_error('004', '该订单不支持推单');
                    break;
            }

            $this->json_do->set_msg('推单成功');
            $this->json_do->out_put();
        }catch(Exception $e){
            log_message('error', __METHOD__.'msg:'.$e->getMessage());
            $this->json_do->set_error('004', '推单失败');
        }
    }
    /**
     * 重新推单
     */
    public function rePushOrder()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $mWmOrder = $wmOrderDao->getOne(['aid'=>$this->s_user->aid, 'tid'=>$fdata['tradeno']]);
        if(!$mWmOrder)
            $this->json_do->set_error('004', '订单不存在');

        try{
            switch($mWmOrder->logistics_type)
            {
                case 2:// 达达
                    $wm_dada_bll = new wm_dada_bll();
                    $wm_dada_bll->push(['tradeno' => $fdata['tradeno'],'aid'=>$this->s_user->aid], true);
                    break;
                case 3:// 点我达
                    $wm_dianwoda_bll = new wm_dianwoda_bll();
                    $wm_dianwoda_bll->push(['tradeno' => $fdata['tradeno'],'aid'=>$this->s_user->aid], true);
                    break;
                case 5:// 风达
                    $wm_fengda_bll = new wm_fengda_bll();
                    $wm_fengda_bll->push(['tradeno' => $fdata['tradeno'],'aid'=>$this->s_user->aid], true);
                    break;
                case 6:// 餐厅宝
                    $wm_cantingbao_bll = new wm_cantingbao_bll();
                    $wm_cantingbao_bll->push(['tid' => $fdata['tradeno'],'aid'=>$this->s_user->aid], true);
                    break;
                default:
                    $this->json_do->set_error('004', '该订单不支持重新推单');
                    break;
            }

            $wmOrderDao->update(['last_repush_time' => time()], ['tid' => $fdata['tradeno']]);
            $this->json_do->set_msg('推单成功');
            $this->json_do->out_put();
        }catch(Exception $e){
            log_message('error', __METHOD__.'msg:'.$e->getMessage());
            $this->json_do->set_error('004', '重推失败');
        }

    }

    /**
     * 订单改为商家配送
     */
    public function turnSellerDelivery()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['aid'] = $this->s_user->aid;

        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->turnSellerDelivery($fdata);
    }

    /**
     * 商家接受订单
     */
    public function orderAgree()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['aid'] = $this->s_user->aid;

        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->sellerAgreeOrder($fdata);
    }

    /**
     * 商家拒绝订单
     */
    public function orderRefuse()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['aid'] = $this->s_user->aid;

        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->sellerRefuseOrder($fdata);
    }

    // /**
    //  * 发货[商家配送预留]
    //  */
    // public function sendOut()
    // {
    //     $rule = [
    //         ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
    //         ['field' => 'send_type', 'label' => '配送方式', 'rules' => 'trim|required|numeric|in_list[1,2,3]'],
    //         ['field' => 'logistics_company', 'label' => '快递公司', 'rules' => 'trim'],
    //         ['field' => 'logistics_company_code', 'label' => '快递公司代号', 'rules' => 'trim'],
    //         ['field' => 'logistics_code', 'label' => '运单号', 'rules' => 'trim'],
    //         ['field' => 'send_contact_name', 'label' => '配送人姓名', 'rules' => 'trim'],
    //         ['field' => 'send_contact_phone', 'label' => '配送人电话', 'rules' => 'trim'],
    //     ];
    //     $this->check->check_ajax_form($rule);
    //     $fdata = $this->form_data($rule);

    //     $fdata['aid'] = $this->s_user->aid;

    //     $wm_order_event_bll = new wm_order_event_bll();
    //     $wm_order_event_bll->orderSendOut($fdata);
    // }

    /**
     * 确认送达
     */
    public function confirmDelivered()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['aid'] = $this->s_user->aid;

        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->sellerOrderDelivered($fdata);
    }

    /**
     * 修改订单收货地址
     */
    public function editRecAddr()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required'],
            ['field' => 'receiver_state', 'label' => '省', 'rules' => 'trim|required'],
            ['field' => 'receiver_city', 'label' => '市', 'rules' => 'trim|required'],
            ['field' => 'receiver_district', 'label' => '区', 'rules' => 'trim|required'],
            ['field' => 'receiver_address', 'label' => '地址', 'rules' => 'trim|required'],
            ['field' => 'receiver_name', 'label' => '收货人', 'rules' => 'trim|required'],
            ['field' => 'receiver_phone', 'label' => '收货手机号', 'rules' => 'trim|required'],
            ['field' => 'receiver_zip', 'label' => '邮编', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地区编码', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $tradeno = $fdata['tradeno'];
        unset($fdata['tradeno']);

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $result = $wmOrderDao->update($fdata, ['tid' => $tradeno]);

        if ($result !== false) {
            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '修改失败');
        }
    }

    /**
     * 商城首页订单统计
     */
    public function order_statistics()
    {
        $shop_id = intval($this->input->get('shop_id'));
        $service = '';

        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }
        $where = " aid={$this->s_user->aid} ";
        if ($shop_id > 0) {
            $where .= " AND (shop_id={$shop_id} OR shop_id=0)  ";
        }

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
        $time = strtotime(date('Y-m-d'));

        $data['pay_order_money'] = 0;
        $data['pay_order_count'] = 0;
        $data['wm_new_order_count'] = 0;
        $data['wm_selfpick_order_count'] = 0;
        $data['wm_delivery_order_count'] = 0;
        $data['ls_pay_order_count'] = 0;
        $data['meal_audit_order_count'] = 0;
        $data['meal_cooked_order_money'] = 0;
        $data['meal_done_order_count'] = 0;
        //外卖
        if($this->_valid_service($service, module_enum::WM_MODULE))
        {
            $wm_data = $wmOrderDao->orderStatistics($where." AND time>{$time}");
            $data['pay_order_money'] += $wm_data[0]['pay_order_money'];
            $data['pay_order_count'] += $wm_data[0]['pay_order_count'];

            //待接单 待自取 接单（配送中）
            $data['wm_new_order_count'] = $wm_data[0]['new_order_count'];
            $data['wm_selfpick_order_count'] = $wm_data[0]['selfpick_order_count'];
            $data['wm_delivery_order_count'] = $wm_data[0]['delivery_order_count'];

        }
        //零售
        if($this->_valid_service($service, module_enum::LS_MODULE))
        {
            $ls_statement_data = $mealStatementsDao->statistics($where." AND `source_type`=3 AND time>{$time}");
            $data['pay_order_money'] += $ls_statement_data[0]['pay_order_money'];
            $data['pay_order_count'] += $ls_statement_data[0]['total_count'];
            // 支付流水
            $data['ls_pay_order_count'] = $ls_statement_data[0]['total_count'];
        }
        //堂食
        if($this->_valid_service($service, module_enum::MEAL_MODULE))
        {
            $meal_statement_data = $mealStatementsDao->statistics($where." AND `source_type`=1 AND time>{$time}");
            $meal_order_data = $mealOrderDao->orderStatistics($where." AND time>{$time}");

            $data['pay_order_money'] += $meal_statement_data[0]['pay_order_money'];
            $data['pay_order_count'] += $meal_statement_data[0]['total_count'];
            // 待审核订单 已下厨（待支付)订单  已支付订单
            $data['meal_audit_order_count'] = $meal_order_data[0]['audit_order_count'];
            $data['meal_cooked_order_money'] = $meal_order_data[0]['cooked_order_money'];
            $data['meal_done_order_count'] = $meal_order_data[0]['done_order_count'];

        }

        $wmPromotionDao = WmPromotionDao::i($this->s_user->aid);
        // 未开始活动 已开始活动
        $data['not_start_promotion_count'] = $wmPromotionDao->getCount("{$where} AND status=1");
        $data['ing_promotion_count'] = $wmPromotionDao->getCount("{$where} AND status=2");
        $data['average_order_price'] = $data['pay_order_count']>0 ? bcdiv($data['pay_order_money'], $data['pay_order_count'], 2) : '0.00';

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 验证获取权限
     * @param string $service
     * @param string $module_enum
     */
    private function _valid_service($service='', $module_enum='')
    {
        //如果有具体服务名称
        if($service)
        {
            if($service == $module_enum && power_exists($module_enum,$this->service->power_keys)) return true;
            return false;
        }
        else
        {
            return power_exists($module_enum,$this->service->power_keys);
        }
    }

    /**
     * 查询订单退款详情
     */
    public function getRefundInfo()
    {
        $aid = $this->input->get('aid');
        $tid = $this->input->get('tid');
        $wmPaymentRecordDao = WmPaymentRecordDao::i($aid);
        $record = $wmPaymentRecordDao->getOneArray(['aid'=>$aid, 'code'=>$tid]);
        if ($record['source'] == 'xcx' && $record['appid']) {
            ci_wxpay::load('jsapi', $record['aid'], $xcxAppid = $record['appid']);
        } else {
            ci_wxpay::load('jsapi', $record['aid']);
        }
        $wx_input = new WxPayRefundQuery();
        $wx_input->SetTransaction_id($record['trade_no']);
        $result = WxPayApi::refundQuery($wx_input);

        var_dump($result);
    }

}

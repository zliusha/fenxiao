<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/21
 * Time: 14:24
 */
use Service\Support\FLock;
use Service\Bll\Hcity\OrderBll;
use Service\Bll\Hcity\OrderEventBll;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderHxDetailDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserOrderDao;

class Order extends xhcity_user_controller
{
    /**
     * 用户订单列表
     */
    public function get_list()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|numeric'],
            ['field' => 'status', 'label' => '订单状态', 'rules' => 'trim'],
            ['field' => 'current_page', 'label' => '当前页数', 'rules' => 'numeric'],
            ['field' => 'page_size', 'label' => '分页大小', 'rules' => 'numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $orderBll = new OrderBll();;
        $data = $orderBll->userQuery($this->s_user->uid, $fdata);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        $rules = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $order = ShcityUserOrderDao::i(['uid'=>$this->s_user->uid])->getOne(['uid'=>$this->s_user->uid, 'tid'=>$fdata['tid']]);
        if(!$order)
            $this->json_do->set_error('005', '订单不存在');

        $orderBll = new OrderBll();
        $data = $orderBll->detailByTid($order->aid, $fdata['tid']);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 核销码列表
     */
    public function hx_code_list()
    {
        $rules = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|numeric'],
            ['field' => 'aid', 'label' => '商户AID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $shcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid'=>$fdata['aid']]);
        $data['rows'] = $shcityOrderHxDetailDao->getAllArray(['aid'=>$fdata['aid'],'tid'=>$fdata['tid']]);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 订单预加载
     * aid uid source_type shop_id goods_id pay_type is_user_packet items 必须
     * items = [[goods_id sku_id num],[goods_id sku_id num]]
     * pay_type 1会员卡储值，2账户余额（佣金），3微信支付，4嘿卡币
     * source_type 1商圈订单 2一店一码订单 3福利池订单
     */
    public function pre_order()
    {

        $rule = [
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim'],
            ['field' => 'source_type', 'label' => '下单入口', 'rules' => 'trim|in_list[1,2,3]'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'is_user_packet', 'label' => '是否使用红包', 'rules' => 'trim|numeric'],
            ['field' => 'items', 'label' => '订单JSON', 'rules' => 'trim|required|is_json'],
            ['field' => 'activity_type', 'label' => '活动类型', 'rules' => 'trim|numeric'],
            ['field' => 'activity_id', 'label' => '活动ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $input['uid'] = $this->s_user->uid;
        $input['aid'] = $fdata['aid'];
        $input['items'] = json_decode($fdata['items'], true);
        $input['shop_id'] = $fdata['shop_id'];
        $input['goods_id'] = $fdata['goods_id'];
        $input['source_type'] = $fdata['source_type'];
        $input['is_user_packet'] = $fdata['is_user_packet'];
        $input['pay_type'] = 2;
        $input['activity_type'] = $fdata['activity_type'];
        $input['activity_id'] = $fdata['activity_id'];

        try{
            $orderEventBll = new OrderEventBll();
            $data = $orderEventBll->createOrder($input, true);

            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }catch(Exception $e){
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 订单预加载
     * aid uid source_type shop_id goods_id pay_type is_user_packet items 必须
     * items = [[goods_id sku_id num],[goods_id sku_id num]]
     */
    public function create()
    {
        $rule = [
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'goods_id', 'label' => '到店自取信息', 'rules' => 'trim'],
            ['field' => 'source_type', 'label' => '下单入口', 'rules' => 'trim|in_list[1,2,3]'],
            ['field' => 'pay_type', 'label' => '支付类型', 'rules' => 'trim|in_list[1,2,3,4]'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'is_user_packet', 'label' => '是否使用红包', 'rules' => 'trim|numeric'],
            ['field' => 'items', 'label' => '订单JSON', 'rules' => 'trim|required|is_json'],
            ['field' => 'activity_type', 'label' => '活动类型', 'rules' => 'trim|numeric'],
            ['field' => 'activity_id', 'label' => '活动ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        //校验用户状态
        $this->check_user_status();

        $input['uid'] = $this->s_user->uid;
        $input['aid'] = $fdata['aid'];
        $input['items'] = json_decode($fdata['items'], true);// TODO 严格则校验json参数
        $input['shop_id'] = $fdata['shop_id'];
        $input['goods_id'] = $fdata['goods_id'];
        $input['pay_type'] = $fdata['pay_type'];
        $input['source_type'] = $fdata['source_type'];
        $input['is_user_packet'] = $fdata['is_user_packet'];
        $input['activity_type'] = $fdata['activity_type'];
        $input['activity_id'] = $fdata['activity_id'];

        if(!FLock::getInstance()->lock('OrderEventBll:createOrder:goods_id:' . $fdata['goods_id']))
        {
            $this->json_do->set_error('005', '系统繁忙,请稍后再试。');
        }

        try{
            $orderEventBll = new OrderEventBll();
            $order_data = $orderEventBll->createOrder($input);

            $this->json_do->set_data($order_data);
            $this->json_do->out_put();
        }catch(Exception $e){
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 取消订单
     */
    public function cancel()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单编号', 'rules' => 'trim|required|numeric'],
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $input['tid'] = $fdata['tid'];
        $input['aid'] = $fdata['aid'];

        if(!FLock::getInstance()->lock('OrderEventBll:cancelOrder:tid:' . $fdata['tid']))
        {
            $this->json_do->set_error('005', '系统繁忙,请稍后再试。');
        }

        try{
            $orderEventBll = new OrderEventBll();
            $data = $orderEventBll->cancelOrder($input);

            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }catch(Exception $e){
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 过期订单
     */
    public function expiredOrder()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单编号', 'rules' => 'trim|required|numeric'],
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $input['tid'] = $fdata['tid'];
        $input['aid'] = $fdata['aid'];

        if(!FLock::getInstance()->lock('OrderEventBll:notifyExpiredOrder:tid:' . $fdata['tid']))
        {
            $this->json_do->set_error('005', '系统繁忙,请稍后再试。');
        }

        try{
            $orderEventBll = new OrderEventBll();
            $data = $orderEventBll->notifyExpiredOrder($input);

            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }catch(Exception $e){
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

}
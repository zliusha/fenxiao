<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/13
 * Time: 11:18
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;

class wm_cantingbao_bll extends base_bll
{

    // 订单对象
    public $order = null;

    /**
     * 加载订单信息      必填：aid tid
     */
    private function _loadOrder($input = [])
    {
        // 自动加载订单
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $this->order  = $wmOrderDao->getOne($input);

        if (!$this->order) {
            throw new Exception("订单读取失败", 500);
        }
    }

    /**
     * 推送订单
     * @param array $input
     * @param bool $repush
     * @throws Exception
     */
    public function push($input = [], $repush = false)
    {
        // 加载主订单
        $this->_loadOrder(['tid' => $input['tid'], 'aid' => $input['aid']]);

        // 预计送达时间段
        if (isset($this->shop->arrive_time) && $this->shop->arrive_time > 0) {
            $time_ready_for_arrive = (time() + $this->shop->arrive_time * 60) ;
        } else {
            $time_ready_for_arrive = time();
        }
        // 门店取餐时间备餐时间计算
        $prepare_time = 0;
        if (isset($this->shop->prepare_time) && $this->shop->prepare_time > 0) {
            $prepare_time = $this->shop->prepare_time * 60;
            $time_ready_for_arrive += $prepare_time;
        }
        // 子订单商品数据
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);
        $order_ext_list = $wmOrderExtDao->getAllArray(['tid' => $input['tid'], 'aid' => $input['aid']]);
        $foodList = [];
        foreach($order_ext_list as $order_ext)
        {
            $tmp['FoodName'] = $order_ext['goods_title'];
            $tmp['FoodPrice'] = $order_ext['price'];
            $tmp['FoodCount'] = $order_ext['num'];

            $foodList[] = $tmp;
        }

        $params = [
            'OrderId' => $this->order->tid,
            'ShopId' => $this->order->shop_id,
            'ShopName' => $this->order->shop_name,
            'OrderUserName' => $this->order->receiver_name,
            'OrderUserPhone' => $this->order->receiver_phone,
            'OrderUserAddress' => $this->order->receiver_site . ' ' . $this->order->receiver_address,
            'OrderRemark' => $this->order->remark,
            'PayType' => 1,
            'BoxFee' => $this->order->package_money,
            'Freight' => $this->order->freight_money,
            'ActivityMoney' => $this->order->discount_money,
            'Invoice' => 0,
            'InvoiceTitle' => '',
            'UserGPSCoordinate' => $this->order->receiver_lng . '|' . $this->order->receiver_lat,
            'ReachTime'=> date('Y-m-d H:i:s', $time_ready_for_arrive),
            'FoodList' => $foodList
        ];

        try{
            $ci_cantingbao = new ci_cantingbao(['aid'=>$this->order->aid]);
            $result = $ci_cantingbao->post($params, ci_cantingbao::SEND_ORDER);

            //推单状态
            WmOrderDao::i($input['aid'])->update(['is_push'=>1], ['aid' => $input['aid'],'tid'=>$input['tid']]);

            return $result;
        }catch(Exception $e){
            log_message('error', __METHOD__.'msg:'.$e->getMessage());
            throw new Exception($e->getMessage());
        }

    }

    /**
     * 更新订单信息
     * @param $input aid tid 必须
     * @throws Exception
     */
    public function updateOrder($input)
    {
        // 加载主订单
        $this->_loadOrder(['tid' => $input['tid'], 'aid' => $input['aid']]);

        // 预计送达时间段
        if (isset($this->shop->arrive_time) && $this->shop->arrive_time > 0) {
            $time_ready_for_arrive = (time() + $this->shop->arrive_time * 60) ;
        } else {
            $time_ready_for_arrive = time();
        }
        // 门店取餐时间备餐时间计算
        $prepare_time = 0;
        if (isset($this->shop->prepare_time) && $this->shop->prepare_time > 0) {
            $prepare_time = $this->shop->prepare_time * 60;
            $time_ready_for_arrive += $prepare_time;
        }

        $params = [
            'OrderId' => $this->order->tid,
            'OrderUserName' => $this->order->receiver_name,
            'OrderUserPhone' => $this->order->receiver_phone,
            'OrderUserAddress' => $this->order->receiver_site . ' ' . $this->order->receiver_address,
            'OrderRemark' => $this->order->remark,
            'Invoice' => 0,
            'InvoiceTitle' => '',
            'ReachTime'=> date('Y-m-d H:i:s', $time_ready_for_arrive)
        ];
        try{
            $ci_cantingbao = new ci_cantingbao(['aid'=>$this->order->aid]);
            $result = $ci_cantingbao->post($params, ci_cantingbao::UPDATE_ORDER);

            return $result;
        }catch(Exception $e){
            log_message('error', __METHOD__.'msg:'.$e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 取消订单
     * @param array $input
     * @param string $remark
     * @throws Exception
     */
    public function cancel($input = [], $remark = '用户取消订单')
    {
        // 加载主订单
        $this->_loadOrder(['tid' => $input['tid'], 'aid' => $input['aid']]);

        $params = [
            'OrderId' => $this->order->tid,
            'Reason' => $remark
        ];
        try{
            $ci_cantingbao = new ci_cantingbao(['aid'=>$this->order->aid]);
            $result = $ci_cantingbao->post($params, ci_cantingbao::CLOSE_ORDER);

            return $result;
        }catch(Exception $e){
            log_message('error', __METHOD__.'msg:'.$e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 更新商户信息
     * @param array $input
     * @throws Exception
     */
    public function updateKey($input = [])
    {
        try{
            $ci_cantingbao = new ci_cantingbao(['aid'=>$input['aid']]);
            $result = $ci_cantingbao->post("", ci_cantingbao::UPDATE_KEY);

            return $result;
        }catch(Exception $e){
            log_message('error', __METHOD__.'msg:'.$e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 推送接受通知路由
     * @param array $input
     * @throws Exception
     */
    public function updatePushUrl($input = [])
    {
        try{
            $params['OrderStateUrl'] = DJADMIN_URL . 'notify_cantingbao/index/'.$input['aid'];
            $ci_cantingbao = new ci_cantingbao(['aid'=>$input['aid']]);
            $result = $ci_cantingbao->post($params, ci_cantingbao::UPDATE_PUSH_URL);

            return $result;
        }catch(Exception $e){
            log_message('error', __METHOD__.'msg:'.$e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $result
     */
    private function _doResult($result)
    {
        if(empty($result))
        {
            log_message('error', __METHOD__.'data:'.$result);
            throw new Exception('JSON解析出错');
        }
        if($result['StateCode']!= 0)
        {
            log_message('error', __METHOD__.'data:'.json_encode($result));
            throw new Exception($result['StateMsg']);
        }
    }
}
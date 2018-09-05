<?php
use Service\Cache\WmDadaCompanyCache;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\Cache\WmShopCache;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;
/**
 * 达达推单对接
 */
/**
 * 点我达订单操作对接
 */
class wm_fengda_bll extends base_bll
{

    // 订单对象
    public $order = null;
    // 门店点我达配置信息
    public $shop_setting = null;
    // 门店信息
    public $shop = null;



    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 加载订单信息       [description]
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
     * 加载门店配置信息
     */
    private function _loadShop()
    {
        $wmShopCache = new WmShopCache(['aid' => $this->order->aid, 'shop_id' => $this->order->shop_id]);
        $m_wm_shop = $wmShopCache->getDataASNX();
        $this->shop = $m_wm_shop;
    }

    /**
     * 推送订单
     * @param  array $input 参数 aid tid必须
     * @param  boolean $repush 是否重发订单
     * @return array         返回结果
     */
    public function push($input = [], $repush = false)
    {
        // 加载订单
        $this->_loadOrder(['tid' => $input['tradeno'], 'aid'=>$input['aid']]);

        // 加载门店信息
        $this->_loadShop();



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

        $ci_fengda = new ci_fengda();
        $params = [
            'orderNum' => $this->order->tid,
            'orderPrice' => $this->order->pay_money,
            'merchantId' => $this->order->aid,
            'shopId' => $this->order->shop_id,
            'shopName' => $this->shop->shop_name,
            'shopPhone' => $this->shop->contact,
            'shopAddress' => $this->shop->shop_state.$this->shop->shop_city.$this->shop->shop_district.$this->shop->shop_address,
            'shopLongitude' => $this->shop->longitude,
            'shopLatitude' => $this->shop->latitude,
            'buyerName' => $this->order->receiver_name,
            'buyerPhone' => $this->order->receiver_phone,
            'buyerLongitude' => $this->order->receiver_lng,
            'buyerLatitude' => $this->order->receiver_lat,
            'buyerAddress' => $this->order->receiver_site . ' ' . $this->order->receiver_address,
            'time' => $this->order->time,
            'maybeArrivedTime' => $time_ready_for_arrive,
            'readyTime' => $prepare_time,
            'status' => 1,
            'notifyUrl' => DJADMIN_URL . 'notify_fengda/index/'
        ];

        try{
            $result = $ci_fengda->request($params, ci_fengda::NEW_ORDER);

            // 订单配送扣款
            $freight = $this->freight(['tradeno'=>$this->order->tid, 'aid'=>$input['aid']]);
            // 记录运费详情
            $wmOrderFreightDao = WmOrderFreightDao::i($this->order->aid);
            $wmOrderFreightDao->add($this->order, $freight, 4);
            return $result;

        }catch(Exception $e) {
            $errMsg = $e->getMessage();
            log_message('error', __METHOD__ . '--' . "风达发送订单失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
            log_message('error', __METHOD__ . '--' . "风达发送订单失败-" . $errMsg);
            throw new Exception($errMsg);
        }
    }

    /**
     * 风达运费查询
     * @param  array $input  参数 aid tid必须
     * @return array          返回结果
     */
    public function freight($input = [])
    {
        // 加载订单
        $this->_loadOrder(['tid' => $input['tradeno'], 'aid'=>$input['aid']]);

        $ci_fengda = new ci_fengda();
        $params = [
            'orderNum' => $this->order->tid,
        ];


        try{
            $result = $ci_fengda->request($params, ci_fengda::TRANSPORT_COST);
            if(isset($result['data']) && is_numeric($result['data']))
            {
                //推单状态
                WmOrderDao::i($input['aid'])->update(['is_push' => 1], ['aid' => $input['aid'], 'tid' => $input['tid']]);
                return $result['data'];
            }
            else
            {
                $errMsg = '风达查询运费失败';
                log_message('error', __METHOD__ . '--' . "风达查询运费失败-" . json_encode($result));
                throw new Exception($errMsg);
            }

        }catch(Exception $e) {
            $errMsg = $e->getMessage();
            log_message('error', __METHOD__ . '--' . "风达查询运费失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
            log_message('error', __METHOD__ . '--' . "风达查询运费失败-" . $errMsg);
            throw new Exception($errMsg);
        }
    }

    /**
     * 风达订单取消
     * @param  array $input  参数 aid tid必须
     * @return array          返回结果
     */
    public function cancel($input = [], $remark = '用户取消订单')
    {
        // 加载订单
        $this->_loadOrder(['tid' => $input['tradeno'], 'aid'=>$input['aid']]);


        $ci_fengda = new ci_fengda();
        $params = [
            'orderNum' => $this->order->tid,
            'merchantId' => $this->order->aid,
            'shopId' => $this->order->shop_id,
            'cancelTime' => time(),
            'status' => 1
        ];

        try{
            $result = $ci_fengda->request($params, ci_fengda::UPDATE_ORDER);

            $freight = $this->freight(['tradeno'=>$this->order->tid, 'aid'=>$input['aid']]);;//取消成功，配送费为0

            // 记录最终运费
            $wmOrderFreightDao = WmOrderFreightDao::i($this->order->aid);
            $wmOrderFreightDao->finish($this->order->tid, $freight);

            return $result;
        }catch(Exception $e)
        {
            $errMsg = $e->getMessage();
            log_message('error', __METHOD__ . '--' . "风达发送订单失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
            log_message('error', __METHOD__ . '--' . "风达发送订单失败-" . $errMsg);
            throw new Exception($errMsg);
        }
    }

    public function status()
    {

    }
}

<?php
use Service\Cache\WmDianwodaShopCache;
use Service\Cache\WmShopCache;
use Service\DbFrame\DataBase\MainDbModels\MainUniqueDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaBalanceDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopReportDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopReportDetailDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;

/**
 * 点我达订单操作对接
 */
class wm_dianwoda_bll extends base_bll
{

    // 订单对象
    public $order = null;
    // 门店点我达配置信息
    public $shop_setting = null;
    // 门店信息
    public $shop = null;

    public function __construct()
    {

    }

    /**
     * 加载订单信息       必填：aid
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
    private function _loadShopSetting()
    {
        $wmDianwodaShopCache = new WmDianwodaShopCache(['aid' => $this->order->aid, 'shop_id' => $this->order->shop_id]);
        $m_wm_dianwoda_shop  = $wmDianwodaShopCache->getDataASNX();
        $this->shop_setting = $m_wm_dianwoda_shop;
    }

    /**
     * 加载门店配置信息
     */
    private function _loadShop()
    {
        $wmShopCache = new WmShopCache(['aid'=>$this->order->aid,'shop_id'=>$this->order->shop_id]);
        $m_wm_shop = $wmShopCache->getDataASNX();
        $this->shop = $m_wm_shop;
    }

    /**
     * 推送订单
     * @param  array  $input  参数 必填:aid,tradeno
     * @param  boolean $repush 是否重发订单
     * @return array         返回结果
     */
    public function push($input = [], $repush = false)
    {
        // 加载订单
        $this->_loadOrder(['tid' => $input['tradeno'],'aid'=>$input['aid']]);
        // 加载门店点我达配置信息
        $this->_loadShopSetting();
        // 加载门店信息
        $this->_loadShop();
        $wmDianwodaBalanceDao = WmDianwodaBalanceDao::i($input['aid']);

        // 检查余额是否充足
        $account = $wmDianwodaBalanceDao->getOne(['aid' => $this->order->aid]);

        if (!isset($account->balance) || $account->balance < 20) {
            log_message('error', __METHOD__ . '--' . "点我达发送订单失败,余额不足,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
            return false;
        }

        // 门店取餐时间备餐时间计算
        if (isset($this->shop->prepare_time) && $this->shop->prepare_time > 0) {
            $time_ready_for_deliver = (time() + $this->shop->prepare_time * 60) * 1000;
        } else {
            $time_ready_for_deliver = time() * 1000;
        }

        $ci_dianwoda = new ci_dianwoda();
        $params      = [
            'order_original_id'        => $this->order->tid,
            'order_create_time'        => $ci_dianwoda->timestamp(),
            'order_remark'             => $this->order->remark,
            'order_price'              => $this->order->total_money * 100,
            'cargo_weight'             => 0,
            'cargo_num'                => 1,
            'city_code'                => $this->shop_setting->city_code,
            'seller_id'                => $this->shop_setting->shop_id,
            'seller_name'              => $this->order->shop_name,
            'seller_mobile'            => $this->shop->contact,
            'seller_address'           => $this->shop->shop_district . $this->shop->shop_address,
            'seller_lat'               => $this->shop->latitude,
            'seller_lng'               => $this->shop->longitude,
            'consignee_name'           => $this->order->receiver_name,
            'consignee_mobile'         => $this->order->receiver_phone,
            'consignee_address'        => $this->order->receiver_site . ' ' . $this->order->receiver_address,
            'consignee_lat'            => $this->order->receiver_lat,
            'consignee_lng'            => $this->order->receiver_lng,
            'money_rider_needpaid'     => 0,
            'money_rider_prepaid'      => 0,
            'money_rider_charge'       => 0,
            'time_ready_for_deliver'   => $time_ready_for_deliver,
            'time_waiting_at_seller'   => 300,
            'delivery_fee_from_seller' => 0, // 渠道支付配送费（分）
        ];
        //中间转换, unique_key=>aid
        $mainUniqueDao = MainUniqueDao::i();
        $uniqueData =['unique_key' => $this->order->tid,'aid'=>$this->order->aid];
        $mainUniqueDao->create($uniqueData);
        
        $result = $ci_dianwoda->call(ci_dianwoda::ADD_ORDER, $params);

        // {"errorCode":"0","result":{"dwd_order_id":"484780","skycon":"","distance":484},"success":true}
        // {"errorCode":"3001","message":"业务逻辑出错:订单超距，配送距离为11908米","success":false}
        if (!isset($result['errorCode'])) {
            $errMsg = '点我达接口调用异常';
            log_message('error', __METHOD__ . '--' . $errMsg . ' ' . json_encode($result));
            throw new Exception($errMsg, 500);
        } else {
            if ($result['errorCode'] == 0) {
                // 订单配送扣款
                $freight    = $this->freight(['tradeno' => $input['tradeno'],'aid'=>$input['aid']]);
                $pay_result = $wmDianwodaBalanceDao->payFreight($this->order, $freight);
                if (!$pay_result) {
                    log_message('error', __METHOD__ . '--' . "点我达订单支付失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
                }
                // 记录运费详情
                $wmOrderFreightDao = WmOrderFreightDao::i($input['aid']);
                $wmOrderFreightDao->add($this->order, $freight, 3);
                // 点我达详细订单 - 推送订单
                $wmDianwodaShopReportDetailDao = WmDianwodaShopReportDetailDao::i($input['aid']);
                $wmDianwodaShopReportDetailDao->pushOrder($this->order, $result['result'], $freight);
                // 运费日报统计 - 推送订单
                $wmDianwodaShopReportDao = WmDianwodaShopReportDao::i($input['aid']);
                $wmDianwodaShopReportDao->pushOrder($this->order->shop_id);
                log_message('error', __METHOD__ . '--' . "点我达发送订单成功,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");

                //推单状态
                WmOrderDao::i($input['aid'])->update(['is_push'=>1], ['aid' => $input['aid'],'tid'=>$input['tid']]);

                return $result;
            } else {
                log_message('error', __METHOD__ . '--' . "点我达发送订单失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
                $errMsg = sprintf('code:%s，msg:%s', $result['errorCode'], $result['message']);
                log_message('error', __METHOD__ . '--' . "点我达发送订单失败-" . $errMsg);
                throw new Exception($errMsg, 500);
            }
        }
    }

    /**
     * 点我达运费查询
     * @param  array  $input  参数 必填:tradeno,aid
     * @return array          返回结果
     */
    public function freight($input = [])
    {
        // 加载订单
        $this->_loadOrder(['tid' => $input['tradeno'],'aid'=>$input['aid']]);

        $ci_dianwoda = new ci_dianwoda();
        $params      = [
            'order_original_id' => $this->order->tid,
        ];
        $result = $ci_dianwoda->call(ci_dianwoda::FREIGHT_QUERY, $params);

        // array(3) {
        //   ["errorCode"] => string(1) "0"
        //   ["result"] => array(3) {
        //     ["receivable_price"] => int(800)
        //     ["receivable_info"] => array(1) {
        //       ["delivery_price"] => int(800)
        //     }
        //     ["price_type"] => int(1)
        //   }
        //   ["success"] => bool(true)
        // }
        if (!isset($result['errorCode'])) {
            $errMsg = '点我达接口调用异常';
            log_message('error', __METHOD__ . '--' . $errMsg . ' ' . json_encode($result));
            throw new Exception($errMsg, 500);
        } else {
            if ($result['errorCode'] == 0) {
                return $result['result']['receivable_price'] / 100;
            } else {
                log_message('error', __METHOD__ . '--' . "点我达查询运费失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
                $errMsg = sprintf('code:%s，msg:%s', $result['errorCode'], $result['message']);
                log_message('error', __METHOD__ . '--' . "点我达查询运费失败-" . $errMsg);
                throw new Exception($errMsg, 500);
            }
        }
    }

    /**
     * 点我达订单取消
     * @param  array  $input  参数 必填:tradeno,aid
     * @return array          返回结果
     */
    public function cancel($input = [], $remark = '用户取消订单')
    {
        // 加载订单
        $this->_loadOrder(['tid' => $input['tradeno'],'aid'=>$input['aid']]);

        $freight = 5;

        $ci_dianwoda = new ci_dianwoda();
        $params      = [
            'order_original_id' => $this->order->tid,
            'cancle_reason'     => $remark,
        ];
        $result = $ci_dianwoda->call(ci_dianwoda::FORMAL_CANCEL, $params);

        // {"errorCode":"0","result":{"dwd_order_id":"484780","skycon":"","distance":484},"success":true}
        // {"errorCode":"3001","message":"业务逻辑出错:订单超距，配送距离为11908米","success":false}
        if (!isset($result['errorCode'])) {
            $errMsg = '点我达接口调用异常';
            log_message('error', __METHOD__ . '--' . $errMsg . ' ' . json_encode($result));
            throw new Exception($errMsg, 500);
        } else {
            if ($result['errorCode'] == 0) {
                // 取消订单退款
                $freight                 = $this->freight(['tradeno' => $input['tradeno'],'aid'=>$input['aid']]);
                $wmDianwodaBalanceDao = WmDianwodaBalanceDao::i($input['aid']);
                $refund_result           = $wmDianwodaBalanceDao->refundFreight($this->order, $freight);
                // 记录最终运费
                $wmOrderFreightDao = WmOrderFreightDao::i($input['aid']);
                $wmOrderFreightDao->finish($this->order->tid, $freight, 3);
                // 点我达详细订单 - 推送取消
                $wmDianwodaShopReportDetailDao = WmDianwodaShopReportDetailDao::i($input['aid']);
                $wmDianwodaShopReportDetailDao->cancelOrder($this->order->tid, $freight);
                // 运费日报统计 - 推送取消
                $wmDianwodaShopReportDao = WmDianwodaShopReportDao::i($input['aid']);
                $wmDianwodaShopReportDao->cancelOrder($this->order->shop_id);
                log_message('error', __METHOD__ . '--' . "点我达取消订单成功,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
                return $result;
            } else {
                log_message('error', __METHOD__ . '--' . "点我达取消订单失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
                $errMsg = sprintf('code:%s，msg:%s', $result['errorCode'], $result['message']);
                log_message('error', __METHOD__ . '--' . "点我达取消订单失败-" . $errMsg);
                throw new Exception($errMsg, 500);
            }
        }
    }
}

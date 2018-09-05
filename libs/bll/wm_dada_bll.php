<?php
use Service\Cache\WmDadaCompanyCache;
use Service\Cache\WmDadaShopCache;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaBalanceDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopReportDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopReportDetailDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;
/**
 * 达达推单对接
 */
class wm_dada_bll extends base_bll
{

    // 订单对象
    public $order = null;
    //达达商户号
    public $source_id = '';
    // 达达门店配置
    public $dadaShop = null;
    public function __construct()
    {
        
    }
    /**
     * 加载订单信息      必填：aid
     */
    private function _loadOrder($input = [])
    {
        $wmOrderDao = WmOrderDao::i($input['aid']);
        // 自动加载订单
        $this->order = $wmOrderDao->getOne($input);

        if (!$this->order) {
            throw new Exception("订单读取失败", 500);
        }
    }
    /**
     * 加载达达商户号
     */
    private function _loadSourceId()
    {
        $wmDadaCompanyCache = new WmDadaCompanyCache(['aid'=>$this->order->aid]);
        $m_dada_company = $wmDadaCompanyCache->getDataASNX();
        if(empty($m_dada_company->source_id))
            throw new Exception("达达商户号未配置-aid:".$this->order->aid, 500);
        $this->source_id = $m_dada_company->source_id;
    }
    /**
     * 加载达达门店信息
     * @return [type] [description]
     */
    private function _loadDadaShop()
    {
        $wmDadaShopCache = new WmDadaShopCache(['aid'=>$this->order->aid,'shop_id'=>$this->order->shop_id]);
        $m_wm_dada_shop = $wmDadaShopCache->getDataASNX();
        $this->dadaShop = $m_wm_dada_shop;
    }
    /**
     * [push description]
     * @param  array  $input  必选参数 tradeno,aid
     * @param  boolean $repush 是否重发订单
     * @return array         返回结果
     */
    public function push($input = [], $repush = false)
    {
        // 加载订单
        $this->_loadOrder(['tid' => $input['tradeno'],'aid'=>$input['aid']]);
        // 加载达达商户信息
        $this->_loadSourceId();
        // 加载达达门店信息
        $this->_loadDadaShop();


        // 初始化达达商户api
        // 测试source_id 73753
        $url = $repush ? ci_dada::RE_ADD_ORDER : ci_dada::ADD_ORDER;
        $obj = ci_dada::init_api($url, $this->source_id);

        // 发单数据
        // $data = array(
        //     'shop_no' => '11047059', //固定测试账号 门店编号
        //     'origin_id' => '201711021755126',
        //     'city_code' => '021', //门店订单所属区域
        //     'cargo_price' => 10, //价格
        //     'is_prepay' => 0, //固定值
        //     'expected_fetch_time' => 1509625962, //期望取货时间 =当前时间+备货时间
        //     'receiver_name' => '测试',
        //     'receiver_address' => '上海市崇明岛',
        //     'receiver_lat' => 31.63,
        //     'receiver_lng' => 121.41,
        //     'receiver_phone' => '18588888888',
        //     'callback' => 'http://fw.waimaishop.com/notify_dada/index/1226', //1226为{$aid}
        // );

        $data = array(
            'shop_no' => $this->dadaShop->shop_no, // 固定测试账号 门店编号
            'origin_id' => $this->order->tid,
            'city_code' => $this->dadaShop->city_code, // 门店订单所属区域
            'cargo_price' => $this->order->pay_money, // 价格
            'is_prepay' => 0, //固定值
            'expected_fetch_time' => $this->order->pay_time + 10 * 60, // 期望取货时间 = 当前时间+备货时间
            'receiver_name' => $this->order->receiver_name,
            'receiver_address' => $this->order->receiver_site . ' ' . $this->order->receiver_address,
            'receiver_lat' => $this->order->receiver_lat,
            'receiver_lng' => $this->order->receiver_lng,
            'receiver_phone' => $this->order->receiver_phone,
            'callback' => DJADMIN_URL . 'notify_dada/index/' . $this->order->aid, // 1226为{$aid}
        );

        $reqStatus = $obj->makeRequest($data);
        if (!$reqStatus) {
            //接口请求正常，判断接口返回的结果，自定义业务操作
            if ($obj->getCode() == 0) {
                // {"distance":168653.56,"fee":34,"deliverFee":34}
                $result = $obj->getResult();
                // 记录运费详情
                $wmOrderFreightDao = WmOrderFreightDao::i($input['aid']);
                $wmOrderFreightDao->add($this->order, $result['fee'], 2);
                log_message('error', __METHOD__ . '--' . "达达发送订单成功,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");

                //推单状态
                WmOrderDao::i($input['aid'])->update(['is_push'=>1], ['aid' => $input['aid'],'tid'=>$input['tid']]);
                return $result;
            } else {
                log_message('error', __METHOD__ . '--' . "达达发送订单失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
                $errMsg = sprintf('code:%s，msg:%s', $obj->getCode(), $obj->getMsg());
                log_message('error', __METHOD__ . '--' . "达达发送订单失败-" . $errMsg);
                throw new Exception($errMsg, 500);
            }
        } else {
            log_message('error', __METHOD__ . '--' . "达达发送订单失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
            $errMsg = '达达发单请求异常或者失败';
            log_message('error', __METHOD__ . '--' . $errMsg);
            throw new Exception($errMsg, 500);
        }
    }

    /**
     * 达达订单取消
     * @param  array  $input  参数 必填:tradeno,aid
     * @return array          返回结果
     */
    public function cancel($input = [])
    {
        // 加载订单
        $this->_loadOrder(['tid' => $input['tradeno'],'aid'=>$input['aid']]);
        // 加载达达商户信息
        $this->_loadSourceId();

        // [{"reason":"没有配送员接单","id":1},{"reason":"配送员没来取货","id":2},{"reason":"配送员态度太差","id":3},{"reason":"顾客取消订单","id":4},{"reason":"订单填写错误","id":5},{"reason":"配送员让我取消此单","id":34},{"reason":"配送员不愿上门取货","id":35},{"reason":"我不需要配送了","id":36},{"reason":"配送员以各种理由表示无法完成订单","id":37},{"reason":"其他","id":10000}]

        $url = ci_dada::FORMAL_CANCEL;
        $obj = ci_dada::init_api($url, $this->source_id);

        $data = [
            'order_id' => $this->order->tid,
            'cancel_reason_id' => 36,
        ];
        $reqStatus = $obj->makeRequest($data);
        if (!$reqStatus) {
            //接口请求正常，判断接口返回的结果，自定义业务操作
            if ($obj->getCode() == 0) {
                $result = $obj->getResult();
                // 记录最终运费
                $wmOrderFreightDao = WmOrderFreightDao::i($input['aid']);
                $wmOrderFreightDao->finish($this->order->tid, $result['deduct_fee'], 2);
                log_message('error', __METHOD__ . '--' . "达达取消订单成功,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
                return $result;
            } else {
                log_message('error', __METHOD__ . '--' . "达达取消订单失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
                $errMsg = sprintf('code:%s，msg:%s', $obj->getCode(), $obj->getMsg());
                log_message('error', __METHOD__ . '--' . "达达取消订单失败-" . $errMsg);
                throw new Exception($errMsg, 500);
            }
        } else {
            log_message('error', __METHOD__ . '--' . "达达取消订单失败,订单号:{$this->order->tid},门店id:{$this->order->shop_id}");
            $errMsg = '达达取消订单请求异常或者失败';
            log_message('error', __METHOD__ . '--' . $errMsg);
            throw new Exception($errMsg, 500);
        }
    }
}

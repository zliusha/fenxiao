<?php
use Service\DbFrame\DataBase\MainDbModels\MainUniqueDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDianwodaDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopReportDetailDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopReportDao;/**
 * 点我达配送回调
 */
class Notify_dianwoda extends CI_Controller
{

    public function index()
    {
        // log_message('error', 'notify_dianwoda - ' . json_encode($_REQUEST));
        // $str = file_get_contents("php://input");
        // log_message('error', $str);
        // $this->_success();

        $str = file_get_contents("php://input");
        log_message('error', __METHOD__ . '--' . $str);

        // {"rider_code":1,"sig":"6E3DD43D4DE7C3570BFC95FF000C864FDBF3F6FF","order_status":99,"cancel_reason":"系统取消（长时间未离店）","order_original_id":"18030515450252","rider_name":"绿巨人","time_status_update":1520240885991,"rider_mobile":"13731943546"}

        $fdata = @json_decode($str, true);
        $this->_valid_sign($fdata);
        unset($fdata['sig']);

        //unique_key => aid
        $mainUniqueDao = MainUniqueDao::i();
        $mMainUnique = $mainUniqueDao->getOne(['unique_key'=>$fdata['order_original_id']],'aid','id desc');
        if(!$mMainUnique)
            $this->_error();

        $this->_business($fdata,$mMainUnique->aid);
        // 校验来源
        if (empty($fdata)) {
            $this->_error();
        }
        $wmOrderDianwodaDao = WmOrderDianwodaDao::i($mMainUnique->aid);
        $m_wm_order_dianwoda = $wmOrderDianwodaDao->getOne(['order_original_id' => $fdata['order_original_id']]);
        if ($m_wm_order_dianwoda) {
            if ($wmOrderDianwodaDao->update($fdata, ['id' => $m_wm_order_dianwoda->id]) === false) {
                $this->_error();
            } else {
                $this->_success();
            }
        } else {
            $fdata['aid'] = $mMainUnique->aid;
            if ($wmOrderDianwodaDao->create($fdata)) {
                $this->_success();
            } else {
                $this->_error();
            }
        }

    }
    /**
     * 其它业务
     * @return [type] [description]
     */
    public function _business($fdata,$aid)
    {
        // 0   派单中 渠道已下单，系统派单中
        // 3   已转单 （此后，订单状态回到“派单中”，但接口不会回传“派单中”状态） 骑手已转单 （3.6.4新增）
        // 5   取餐中 骑手已接单
        // 10  已到店 骑手已到店，等待商家出餐
        // 15  配送中 骑手已离店，配送途中
        // 100 已完成 骑手已妥投
        // 98  异常   订单出现异常，骑手无法完成
        // 99  已取消 订单已取消
        if ($fdata['order_status'] == 5) {
            // 骑手接单
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->systemOrderTaking(['aid'=>$aid,'tradeno' => $fdata['order_original_id'], 'tracking_no' => $fdata['order_original_id']], true);
            // 点我达详细订单 - 骑手接单
            $wmDianwodaShopReportDetailDao = WmDianwodaShopReportDetailDao::i($aid);
            $wmDianwodaShopReportDetailDao->acceptOrder($wm_order_event_bll->order->tid);
        } else if ($fdata['order_status'] == 15) {
            // 骑手取货
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->systemRiderTaking(['aid'=>$aid,'tradeno' => $fdata['order_original_id'], 'tracking_no' => $fdata['order_original_id']], true);
            // 点我达详细订单 - 骑手取货
            $wmDianwodaShopReportDetailDao = WmDianwodaShopReportDetailDao::i($aid);
            $wmDianwodaShopReportDetailDao->takeOrder($wm_order_event_bll->order->tid);
        } else if ($fdata['order_status'] == 100) {
            // 订单送达
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->systemOrderDelivered(['aid'=>$aid,'tradeno' => $fdata['order_original_id']], true);
            // 记录最终运费
            $wmOrderFreightDao = WmOrderFreightDao::i($aid);
            $order_freight = $wmOrderFreightDao->getOne(['tid' => $fdata['order_original_id']], 'money');
            $wmOrderFreightDao->finish($fdata['order_original_id'], $order_freight->money, 3);
            // 点我达详细订单 - 确认送达
            $wmDianwodaShopReportDetailDao = WmDianwodaShopReportDetailDao::i($aid);
            $wmDianwodaShopReportDetailDao->finishOrder($wm_order_event_bll->order->tid);
            // 运费日报统计 - 配送完成
            $wmDianwodaShopReportDao = WmDianwodaShopReportDao::i($aid);
            $wmDianwodaShopReportDao->finishOrder($wm_order_event_bll->order->shop_id);
        } else if ($fdata['order_status'] == 98 || $fdata['order_status'] == 99) {
            // 订单过期
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->orderExpressFail(['aid'=>$aid,'tradeno' => $fdata['order_original_id']], true);
        }
    }

    /**
     * 点我达回调签名验证
     */
    private function _valid_sign($fdata)
    {
        $_config = &ini_config('dianwoda');
        $secret = $_config['app_secret'];

        $params = $fdata;
        unset($params['sig']);

        ksort($params);
        $sign = $secret;
        foreach ($params as $key => $val) {
            $sign .= $key . $val;
        }
        $sign .= $secret;
        $sign = strtoupper(sha1($sign));

        if ($sign == $fdata['sig']) {
            return;
        } else {
            log_message('error', __METHOD__ . '--' . "d-{$fdata['sig']}--f-{$sign}");
            $this->_error();
        }
    }
    /**
     * 失败
     */
    private function _error()
    {
        echo 'error';
        exit;
    }
    /*
     * 成功
     */
    private function _success()
    {
        echo 'success';
        exit;
    }

}

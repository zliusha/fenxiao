<?php
/**
 * @Author: binghe
 * @Date:   2017-11-02 17:27:30
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:50:28
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFengdaDao;
/**
 * 风达配送回调
 */
class Notify_fengda extends CI_Controller
{

    public function index()
    {
//        $str = file_get_contents("php://input");
//        log_message('error', __METHOD__ . '-input-' . $str);
//        $fdata = @json_decode($str, true);
        $fdata = $this->input->post();
        log_message('error', __METHOD__ . '-fdata-' . json_encode($fdata));
        $this->_valid_sign($fdata);
        unset($fdata['sign']);

        //映射字段
        $data = $this->_fieldMap($fdata);

        $this->_business($data);
        //校验来源
        if (empty($data)) {
            $this->_error();
        }

        $wmOrderFengdaDao = WmOrderFengdaDao::i($data['aid']);
        $m_wm_order_fengda = $wmOrderFengdaDao->getOne(['aid' => $data['aid'], 'order_no' => $data['order_no']]);

        if ($m_wm_order_fengda) {
            if ($wmOrderFengdaDao->update($data, ['id' => $m_wm_order_fengda->id]) === false) {
                $this->_error();
            } else {
                $this->_success();
            }

        } else {
            if ($wmOrderFengdaDao->create($data)) {
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
    public function _business($fdata)
    {
        // 订单状态(1，骑手接单，未取货；2，骑手接单，已取货；3，骑手已送达，4未接单，5异常）
        if ($fdata['order_status'] == 1) {
            // 骑手接单
            $wm_order_event_bll = new wm_order_event_bll();
            $result = $wm_order_event_bll->systemOrderTaking(['tradeno' => $fdata['order_no'], 'tracking_no' => $fdata['tracking_no'],'aid'=>$fdata['aid']], true);
        } else if ($fdata['order_status'] == 2) {
            // 骑手取货
            $wm_order_event_bll = new wm_order_event_bll();
            $result = $wm_order_event_bll->systemRiderTaking(['tradeno' => $fdata['order_no'], 'tracking_no' => $fdata['tracking_no'],'aid'=>$fdata['aid']], true);
        } else if ($fdata['order_status'] == 3) {
            // 订单送达
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->systemOrderDelivered(['tradeno' => $fdata['order_no'],'aid'=>$fdata['aid']], true);
            // 记录最终运费

            try{
                $wm_fengda_bll = new wm_fengda_bll();
                // 订单配送扣款
                $freight = $wm_fengda_bll->freight(['tradeno'=>$fdata['order_no'], 'aid'=>$fdata['aid']]);
                $wmOrderFreightDao = WmOrderFreightDao::i($fdata['aid']);
                $wmOrderFreightDao->finish($fdata['order_no'], $freight);
            }catch(Exception $e) {
                log_message('error', __METHOD__ . '-tid-' . $fdata['order_no'] .'--' .$e->getMessage());

               $wmOrderFreightDao = WmOrderFreightDao::i($fdata['aid']);
                $order_freight = $wmOrderFreightDao->getOne(['tid' => $fdata['order_no'],'aid'=>$fdata['aid']], 'money');
                $wmOrderFreightDao->finish($fdata['order_no'], $order_freight->money);
            }

        } else if ($fdata['order_status'] == 4) {
            // 订单过期
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->orderExpressFail(['tradeno' => $fdata['order_no'],'aid'=>$fdata['aid']], true);
        }else if ($fdata['order_status'] == 5) {
            // 异常
            log_message('error', __METHOD__ . '--' . json_encode($fdata));
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->orderExpressFail(['tradeno' => $fdata['order_no'],'aid'=>$fdata['aid']], true);
        }
    }
    /**
     * 达达回调签名验证
     * @param  [type] $fdata [description]
     * @return [type]        [description]
     */
    private function _valid_sign($fdata)
    {

        $signature = $fdata['sign'];
        unset($fdata['sign']);

        ksort($fdata);
        $str = '';
        foreach ($fdata as $k => $v) {
            $str .= $k .'='. $v;
        }
        $sign = md5($str.'qs');
        if ($sign == $signature) {
            return;
        } else {
            log_message('error', __METHOD__ . '--' . "d-{$signature}--f-{$sign}");
            $this->_error();
        }
    }

    /**
     * 字段映射
     */
    private function _fieldMap($data)
    {
        $_data = [];
        $_data['order_no'] = $data['orderNum'];
        $_data['aid'] = $data['merchantId'];
        $_data['shop_id'] = $data['shopId'];
        $_data['order_status'] = $data['status'];
        isset($data['transportNum']) && $_data['tracking_no'] = $data['transportNum'];
        isset($data['riderId']) && $_data['rider_id'] = $data['riderId'];
        isset($data['riderName']) && $_data['rider_name'] = $data['riderName'];
        isset($data['riderPhone']) && $_data['rider_phone'] = $data['riderPhone'];
        isset($data['remark']) && $_data['remark'] = $data['remark'];

        return $_data;
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

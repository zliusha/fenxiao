<?php
/**
 * @Author: binghe
 * @Date:   2017-11-02 17:27:30
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:49
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDadaDao;
/**
 * 达达配送回调
 */
class Notify_dada extends CI_Controller
{

    public function index($aid)
    {
        if (!is_numeric($aid)) {
            $this->_error();
        }

        $str = file_get_contents("php://input");
        log_message('error', __METHOD__ . '--' . $str);

        $fdata = @json_decode($str, true);
        $this->_valid_sign($fdata);
        unset($fdata['signature']);
        $this->_business($fdata,$aid);
        //校验来源
        if (empty($fdata)) {
            $this->_error();
        }
        $wmOrderDadaDao = WmOrderDadaDao::i($aid);
        $m_wm_order_dada = $wmOrderDadaDao->getOne(['aid' => $aid, 'order_id' => $fdata['order_id']]);
        if ($m_wm_order_dada) {
            if ($wmOrderDadaDao->update($fdata, ['id' => $m_wm_order_dada->id]) === false) {
                $this->_error();
            } else {
                $this->_success();
            }

        } else {
            $fdata['aid'] = $aid;
            if ($wmOrderDadaDao->create($fdata)) {
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
        // 订单状态(待接单＝1 待取货＝2 配送中＝3 已完成＝4 已取消＝5 已过期＝7 指派单=8 妥投异常之物品返回中=9 妥投异常之物品返回完成=10 创建达达运单失败=1000 可参考文末的状态说明）
        if ($fdata['order_status'] == 2) {
            // 骑手接单
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->systemOrderTaking(['aid'=>$aid,'tradeno' => $fdata['order_id'], 'tracking_no' => $fdata['client_id']], true);
        } else if ($fdata['order_status'] == 3) {
            // 骑手取货
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->systemRiderTaking(['aid'=>$aid,'tradeno' => $fdata['order_id'], 'tracking_no' => $fdata['client_id']], true);
        } else if ($fdata['order_status'] == 4) {
            // 订单送达
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->systemOrderDelivered(['aid'=>$aid,'tradeno' => $fdata['order_id']], true);
            // 记录最终运费
            $wmOrderFreightDao = WmOrderFreightDao::i($aid);
            $order_freight = $wmOrderFreightDao->getOne(['tid' => $fdata['order_id']], 'money');
            $wmOrderFreightDao->finish($fdata['order_id'], $order_freight->money, 2);
        } else if ($fdata['order_status'] == 5) {
            // 达达取消订单
            // 自动重新发单
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->systemRiderCancel(['aid'=>$aid,'tradeno' => $fdata['order_id']], true);

            try {
                // 达达发单,目前只有达达
                $wm_dada_bll = new wm_dada_bll();
                $wm_dada_bll->push(['tradeno' => $fdata['order_id'],'aid'=>$aid], true);
                log_message('error', __METHOD__ . '--2');
            } catch (Exception $e) {
                log_message('error', __METHOD__ . '--' . $e->getMessage());
            }
        } else if ($fdata['order_status'] == 7) {
            // 订单过期
            $wm_order_event_bll = new wm_order_event_bll();
            $wm_order_event_bll->orderExpressFail(['aid'=>$aid,'tradeno' => $fdata['order_id']], true);
        }
    }
    /**
     * 达达回调签名验证
     * @param  [type] $fdata [description]
     * @return [type]        [description]
     */
    private function _valid_sign($fdata)
    {

        $signature = $fdata['signature'];
        $data[] = $fdata['client_id'];
        $data[] = $fdata['order_id'];
        $data[] = $fdata['update_time'];
        sort($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $v;
        }
        $sign = md5($str);
        if ($sign == $signature) {
            return;
        } else {
            log_message('error', __METHOD__ . '--' . "d-{$signature}--f-{$sign}");
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

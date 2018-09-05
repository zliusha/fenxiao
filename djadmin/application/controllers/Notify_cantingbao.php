<?php
/**
 * @Author: binghe
 * @Date:   2017-11-02 17:27:30
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:50:28
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderCantingbaoDao;
use Service\Cache\Wm\WmCantingbaoConfigCache;
/**
 * 风达配送回调
 */
class Notify_cantingbao extends CI_Controller
{


    public function index($aid)
    {
        $fdata = $this->input->post();
        log_message('error', __METHOD__ . '-aid-' . $aid.'-action-'.$fdata['action']);
        log_message('error', __METHOD__ . '-fdata-' . json_encode($fdata));
        $fdata['content'] = str_replace(' ','+',$fdata['content']);
        $this->_valid_sign($aid, $fdata);

        $data = json_decode(base64_decode($fdata['content']), true);
        //校验数据
        if (empty($data)) {
            $this->_response(['StateCode'=>3,'StateMsg'=>'Content对象解析错误']);
        }
        $action = $fdata['action'];
        switch($action)
        {
            // 订单信息推送
            case 'SendOrderState':
                $this->_business($aid, $data);
                break;
            // 骑手信息推送
            case 'SendWLUserInfo':
                $this->_sendUserInfo($aid, $data);
                break;

        }
    }
    /**
     * 订单业务
     * @return [type] [description]
     */
    private function _business($aid, $data)
    {
        // 订单状态 1为已处理 21为已进入物流系统等待分配骑手 22为已分配骑手，正在取餐路上 23为已取餐，配送途中 100为同意退款 101为拒绝退款 254为已完成
        try{
            $order = $this->_loadOrder(['aid'=>$aid, 'tid'=>$data['OrderId']]);

            if ($data['OrderState'] == 22) {
                // 骑手接单
                $wm_order_event_bll = new wm_order_event_bll();
                $result = $wm_order_event_bll->systemOrderTaking(['tradeno' => $data['OrderId'], 'aid'=>$aid], true);
            } else if ($data['OrderState'] == 23) {
                // 骑手取货
                $wm_order_event_bll = new wm_order_event_bll();
                $result = $wm_order_event_bll->systemRiderTaking(['tradeno' => $data['OrderId'], 'aid'=>$aid], true);
            } else if ($data['OrderState'] == 254) {
                // 订单送达
                $wm_order_event_bll = new wm_order_event_bll();
                $wm_order_event_bll->systemOrderDelivered(['tradeno' => $data['OrderId'], 'aid'=>$aid], true);
                // 记录最终运费

            } else {
                log_message('error', __METHOD__.json_encode($data));
            }
            // 更新餐厅宝订单状态
            $params['order_no'] = $data['OrderId'];
            $params['order_status'] = $data['OrderState'];

            $wmOrderCantingbaoDao = WmOrderCantingbaoDao::i($aid);
            $mOrderCantingBao = $wmOrderCantingbaoDao->getOne(['order_no'=>$data['OrderId']]);
            if($mOrderCantingBao)
            {
                $wmOrderCantingbaoDao->update($params, ['id'=>$mOrderCantingBao->id]);
            }
            else
            {
                $params['aid'] = $aid;
                $params['shop_id'] = $order->shop_id;
                $wmOrderCantingbaoDao->create($params);
            }

            $this->_response(['StateCode'=>0,'StateMsg'=>'处理成功']);
        }catch(Exception $e){
            log_message('error', __METHOD__.'餐厅宝订单处理异常：'.$e->getMessage());
            $this->_response(['StateCode'=>255,'StateMsg'=>'业务处理异常']);
        }

    }

    /**
     * 骑手信息处理
     * @param $aid
     * @param $data
     */
    private function _sendUserInfo($aid, $data)
    {
        try{
            log_message('error', __METHOD__.'餐厅宝骑手信息start1：'.json_encode($data));
            $order = $this->_loadOrder(['aid'=>$aid, 'tid'=>$data['OrderId']]);
            $params['rider_sex'] = $data['UserSex'];
            $params['rider_name'] = $data['UserName'];
            $params['rider_phone'] = $data['UserPhone'];
            $params['rider_photo'] = $data['UserPhoto'];
            $params['rider_is_idcard'] = $data['IsIDCard'];
            $params['order_no'] = $data['OrderId'];

            $wmOrderCantingbaoDao = WmOrderCantingbaoDao::i($aid);
            $mOrderCantingBao = $wmOrderCantingbaoDao->getOne(['order_no'=>$data['OrderId']]);
            if($mOrderCantingBao)
            {
                $wmOrderCantingbaoDao->update($params, ['id'=>$mOrderCantingBao->id]);
                log_message('error', __METHOD__.'餐厅宝骑手信息start1--1：'.$data['OrderId']);
            }
            else
            {
                $params['aid'] = $aid;
                $params['shop_id'] = $order->shop_id;
                $wmOrderCantingbaoDao->create($params);
                log_message('error', __METHOD__.'餐厅宝骑手信息start1--2：'.$data['OrderId']);
            }
            log_message('error', __METHOD__.'餐厅宝骑手信息start2：'.$data['OrderId']);
            $this->_response(['StateCode'=>0,'StateMsg'=>'处理成功']);
        }catch(Exception $e){
            log_message('error', __METHOD__.'餐厅宝骑手信息处理异常：'.$e->getMessage());
            $this->_response(['StateCode'=>255,'StateMsg'=>'业务处理异常']);
        }

    }

    /**
     * 加载订单信息      必填：aid tid
     */
    private function _loadOrder($input = [])
    {
        // 自动加载订单
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $order  = $wmOrderDao->getOne($input);

        if (!$order) {
            throw new Exception("订单读取失败", 500);
        }
        return $order;
    }

    /**
     * 餐厅宝回调签名验证
     * @param  [type] $fdata [description]
     * @return [type]        [description]
     */
    private function _valid_sign($aid, $fdata)
    {

        $signature = $fdata['sign'];
        unset($fdata['sign']);

        // 获取配置
        $wmCantingbaoConfigCache = new WmCantingbaoConfigCache(['aid'=>$aid]);
        $inc = $wmCantingbaoConfigCache->getDataASNX();

        $signStr = $fdata['key'].$fdata['content'].$inc->app_secret.$fdata['ts'];
        $sign =  strtolower(sha1($signStr));
        if ($sign == $signature) {
            return;
        } else {
            log_message('error', __METHOD__ . '--' . "d-{$signature}--f-{$sign}");
            $this->_response(['StateCode'=>2,'StateMsg'=>'签名验证失败']);
        }
    }


    /**
     * 返回信息
     */
    private function _response($data)
    {
        echo json_encode($data);
        exit;
    }

}

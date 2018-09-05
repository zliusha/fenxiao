<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/14
 * Time: 11:04
 */

use Service\Bll\Hcity\OrderBll;
use Service\Bll\Hcity\OrderEventBll;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderHxDetailDao;

class Dj_order extends xhcity_dj_controller
{
    /**
     * 核销订单
     */
    public function hx_order()
    {
        $rules = [
            ['field' => 'hx_code', 'label' => '核销码', 'rules' => 'trim|numeric'],
            ['field' => 'aid', 'label' => '商户AID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $shcityOrderDao = ShcityOrderDao::i(['aid'=>$fdata['aid']]);
        $shcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid'=>$fdata['aid']]);
        $mShcityHxDetail = $shcityOrderHxDetailDao->getOne(['aid'=>$fdata['aid'], 'hx_code'=>$fdata['hx_code']]);
        if(!$mShcityHxDetail)
            $this->json_do->set_error('005','无核销码记录');
        $mShcityOrder = $shcityOrderDao->getOne(['aid'=>$fdata['aid'], 'tid'=>$mShcityHxDetail->tid]);
        if(!$mShcityOrder)
        $this->json_do->set_error('005','无订单记录');

        // 验证商检权限
        $this->check_dj_match($mShcityOrder->aid, $mShcityOrder->shop_id);

        // 核销订单
        $orderEventBll = new OrderEventBll();
        $orderEventBll->hxOrder($fdata);

        $this->json_do->set_msg('核销成功');
        $this->json_do->out_put();
    }

    /**
     * 核销订单
     */
    public function hx_all()
    {
        $rules = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|numeric'],
            ['field' => 'aid', 'label' => '商户AID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $shcityOrderDao = ShcityOrderDao::i(['aid'=>$fdata['aid']]);
        $shcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid'=>$fdata['aid']]);
        $mShcityOrder = $shcityOrderDao->getOne(['aid'=>$fdata['aid'], 'tid'=>$fdata['tid']]);
        if(!$mShcityOrder)
            $this->json_do->set_error('005','无订单记录');

        // 验证商检权限
        $this->check_dj_match($mShcityOrder->aid, $mShcityOrder->shop_id);

        $shcityHxDetails = $shcityOrderHxDetailDao->getAllArray(['aid'=>$fdata['aid'], 'tid'=>$fdata['tid'], 'hx_status'=>0]);
        if(!$shcityHxDetails)
            $this->json_do->set_error('005','无待核销码记录');

        // 核销订单
        $orderEventBll = new OrderEventBll();
        $is_hx = true;
        $msg = '';
        foreach($shcityHxDetails as $hxDetail)
        {
            try{
                $orderEventBll->hxOrder(['aid'=>$fdata['aid'], 'hx_code'=>$hxDetail['hx_code']]);
            }catch(Exception $e){
                $is_hx = false;
                $msg .= "{$hxDetail['hx_code']},";
            }
        }

        if($is_hx==false)
        {
            $message = "核销码 ".trim($msg, ','). "核销失败";
            $this->json_do->set_msg($message);
            $this->json_do->out_put();
        }
        $this->json_do->set_msg('核销成功');
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

        $shcityOrderDao = ShcityOrderDao::i(['aid'=>$fdata['aid']]);
        $shcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid'=>$fdata['aid']]);
        $mShcityOrder = $shcityOrderDao->getOne(['aid'=>$fdata['aid'], 'tid'=>$fdata['tid']]);
        if(!$mShcityOrder)
            $this->json_do->set_error('005','无订单记录');

        // 验证商检权限
        $this->check_dj_match($mShcityOrder->aid, $mShcityOrder->shop_id);

        $data['rows'] = $shcityOrderHxDetailDao->getAllArray(['aid'=>$fdata['aid'],'tid'=>$fdata['tid']]);

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
            ['field' => 'aid', 'label' => '商户AID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $shcityOrderDao = ShcityOrderDao::i(['aid'=>$fdata['aid']]);
        $mShcityOrder = $shcityOrderDao->getOne(['aid'=>$fdata['aid'], 'tid'=>$fdata['tid']]);
        if(!$mShcityOrder)
            $this->json_do->set_error('005','无订单记录');

        // 验证商检权限
        $this->check_dj_match($mShcityOrder->aid, $mShcityOrder->shop_id);

        $orderBll = new OrderBll();
        $data = $orderBll->detailByTid($fdata['aid'], $fdata['tid']);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
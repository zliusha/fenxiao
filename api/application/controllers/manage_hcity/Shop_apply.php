<?php

/**
 * 门店申请
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/16
 * Time: 晚上20:05
 */
use Service\Bll\Hcity\ShopExtBll;

class Shop_apply extends hcity_manage_controller
{

    /**
     * 获取门店列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rules = [
            ['field' => 'hcity_audit_status', 'label' => '审核状态', 'rules' => 'trim|in_list[1,2,3]'],
            ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim'],
            ['field' => 'contact', 'label' => '门店联系方式', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $sUser=$this->s_user;
        $list = (new ShopExtBll())->shopApplyList($sUser,$data);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 商品上架申请详情
     * @author yize<yize@iyenei.com>
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '上架申请id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ShopExtBll())->shopDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 门店审核
     * @author yize<yize@iyenei.com>
     */
    public function edit_apply_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '门店申请列表id', 'rules' => 'integer|required'],
            ['field' => 'hcity_audit_status', 'label' => '审核状态', 'rules' => 'integer|required|in_list[2,3]'],

        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new ShopExtBll())->editApplyStatus($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

}
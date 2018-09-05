<?php

/**
 * 门店
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/16
 * Time: 晚上20:05
 */
use Service\Bll\Hcity\ShopExtBll;

class Shop_ext extends hcity_manage_controller
{

    /**
     * 门店列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rules = [
            ['field' => 'hcity_show_status', 'label' => '入住商圈状态', 'rules' => 'integer'],
            ['field' => 'hcity_audit_status', 'label' => '申请状态', 'rules' => 'integer'],
            ['field' => 'barcode_status', 'label' => '一店一码状态', 'rules' => 'integer'],
            ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim'],
            ['field' => 'contact', 'label' => '门店联系方式', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $sUser=$this->s_user;
        $list = (new ShopExtBll())->shopList($sUser,$fdata);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 门店详情
     * @author yize<yize@iyenei.com>
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '门店列表id', 'rules' => 'integer|required'],
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
     * 清退门店
     * @author yize<yize@iyenei.com>
     */
    public function shop_clear()
    {
        $rule = [
            ['field' => 'id', 'label' => '门店列表id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new ShopExtBll())->shopClear($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 清退门店恢复
     * @author yize<yize@iyenei.com>
     */
    public function shop_recover()
    {
        $rule = [
            ['field' => 'id', 'label' => '门店列表id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new ShopExtBll())->shopRecover($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 某一个门店财务记录
     * @author yize<yize@iyenei.com>
     */
    public function shop_bill()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'integer|required'],
            ['field' => 'fid', 'label' => '流水号', 'rules' => 'integer'],
            ['field' => 'type', 'label' => '类型', 'rules' => 'integer|in_list[1,2,3,4,5,6]'],
            ['field' => 'time', 'label' => '时间段 例如：2018-02 - 2018-08', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ShopExtBll())->shopBill($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

}
<?php

/**
 * 商圈商品申请
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/13
 * Time: 上午11:40
 */
use Service\Bll\Hcity\GoodsApplyBll;
use Service\Exceptions\Exception;

class Goods_apply extends hcity_manage_controller
{
    /**
     * 获取所有商品申请上架列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '门店分类id', 'rules' => 'integer'],
            ['field' => 'audit_status', 'label' => '审核状态', 'rules' => 'integer|in_list[1,2,3]'],
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $data = (new GoodsApplyBll())->goodsApplyList($sUser,$fdata);
        $this->json_do->set_data($data);
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
            $data = (new GoodsApplyBll())->goodsApplyDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 商圈商品上架审核
     * @author yize<yize@iyenei.com>
     */
    public function edit_audit_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '商品申请id', 'rules' => 'integer|required'],
            ['field' => 'audit_status', 'label' => '审核状态', 'rules' => 'integer|required|in_list[2,3]'],
            ['field' => 'hcard_price', 'label' => '黑卡价', 'rules' => 'trim'],
            ['field' => 'refuse_remark', 'label' => '拒绝原因', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new GoodsApplyBll())->editAuditStatus($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }








}
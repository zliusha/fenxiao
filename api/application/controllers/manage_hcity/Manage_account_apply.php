<?php

/**
 * 城市合伙人财务管理
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/19
 * Time: 上午11:43
 */
use Service\Bll\Hcity\ManageAccountApplyBll;
use Service\Exceptions\Exception;

class Manage_account_apply extends hcity_manage_controller
{
    /**
     * 获取申请列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'username', 'label' => '名称', 'rules' => 'trim'],
            ['field' => 'status', 'label' => '审核状态', 'rules' => 'integer|in_list[0,1,2]'],
            ['field' => 'mobile', 'label' => '手机', 'rules' => 'preg_key[MOBILE]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $data = (new ManageAccountApplyBll())->manageApplyList($fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 申请详情
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
            $data = (new ManageAccountApplyBll())->manageApplyDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 修改城市合伙人审核状态
     * @author yize<yize@iyenei.com>
     */
    public function edit_apply_status()
    {
        $rule = [
            ['field' => 'id', 'label' => 'id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '审核状态', 'rules' => 'integer|required|in_list[1,2]'],
            ['field' => 'expire_time', 'label' => '到期时间', 'rules' => 'trim'],
            ['field' => 'refuse_remark', 'label' => '拒绝原因', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ManageAccountApplyBll())->editApplyStatus($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }










}
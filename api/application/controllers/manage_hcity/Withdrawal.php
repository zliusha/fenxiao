<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/20
 * Time: 10:46
 */
use Service\Bll\Hcity\WithdrawalBll;
use Service\Exceptions\Exception;


class Withdrawal extends hcity_manage_controller
{

    /**
     * 提现列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'type', 'label' => '申请类型 个人/门店/城市合伙人', 'rules' => 'integer|in_list[1,2,3]'],
            ['field' => 'status', 'label' => '提现状态', 'rules' => 'integer|in_list[1,2,3]'],
            ['field' => 'phone', 'label' => '手机号', 'rules' => 'trim'],
            ['field' => 'apply_time', 'label' => '申请时间', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $data = (new WithdrawalBll())->withdraList($fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 提现详情
     * @author yize<yize@iyenei.com>
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '订单id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new WithdrawalBll())->withdraDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 提现订单
     * @author yize<yize@iyenei.com>
     */
    public function edit()
    {
        $rule = [
            ['field' => 'id', 'label' => '订单id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '提现状态', 'rules' => 'integer|required|in_list[2,3]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            (new WithdrawalBll())->editWithdra($fdata);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }


    /**
     * 已经提现的财务列表
     * @author yize<yize@iyenei.com>
     */
    public function bill_list()
    {
        $rule = [
            ['field' => 'type', 'label' => '申请类型 个人/门店/城市合伙人', 'rules' => 'integer|in_list[1,2,3]'],
            ['field' => 'fid', 'label' => '流水号', 'rules' => 'integer'],
            ['field' => 'verify_time', 'label' => '时间段 例如：2018-02 - 2018-08', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $data = (new WithdrawalBll())->billList($fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }

    public function delete()
    {

    }


}
<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/19
 * Time: 16:05
 */
use Service\Bll\Hcity\ManagerBalanceRecordBll;
use Service\Exceptions\Exception;

class Manager_balance_record extends hcity_manage_controller
{

    /**
     * 获取城市合伙人财务列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'fid', 'label' => '流水号', 'rules' => 'integer'],
            ['field' => 'type', 'label' => '财务类型', 'rules' => 'integer'],
            ['field' => 'time', 'label' => '时间', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $data = (new ManagerBalanceRecordBll())->managerBalancelist($sUser,$fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 城市合伙人申请提现
     * @author yize<yize@iyenei.com>
     */
    public function apply_money()
    {
        $sUser=$this->s_user;
        $rules = [
            ['field' => 'money', 'label' => '提现金额', 'rules' => 'trim|required|numeric'],
            ['field' => 'payment_method', 'label' => '收款方式', 'rules' => 'trim|required'],
            ['field' => 'payment_account', 'label' => '收款账户', 'rules' => 'trim|required'],
            ['field' => 'applicant_name', 'label' => '申请人名称', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $ret = (new ManagerBalanceRecordBll())->applyMoney($sUser, $fdata);
        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '提现失败');
        }
    }

    /**
     * 城市合伙人申请提现银行卡接口
     * @author yize<yize@iyenei.com>
     */
    public function card_info()
    {
        $sUser=$this->s_user;
        $ret = (new ManagerBalanceRecordBll())->cardInfo($sUser);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }











}
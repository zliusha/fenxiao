<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/29
 * Time: 下午6:08
 */
use Service\Bll\Hcity\WithdrawalBll;
use Service\Bll\Hcity\AccountBll;

class Income extends xhcity_user_controller
{
    /**
     * 获取收入明细
     * @author ahe<ahe@iyenei.com>
     */
    public function get_list()
    {
        $list = (new AccountBll())->getIncomeList($this->s_user->uid);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 获取提现记录
     * @author ahe<ahe@iyenei.com>
     */
    public function get_withdrawal_list()
    {
        $fdata = [
            'applicant_id' => $this->s_user->uid,
            'type' => 1//个人提现
        ];
        $list = (new WithdrawalBll())->withdraList($fdata);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 收入信息
     * @author ahe<ahe@iyenei.com>
     */
    public function get_info()
    {
        $list = (new AccountBll())->getIncomeInfo($this->s_user->uid);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }
}
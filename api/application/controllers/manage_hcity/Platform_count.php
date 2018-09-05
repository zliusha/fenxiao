<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/31
 * Time: 9:56
 */
use Service\Bll\Hcity\PlatformCountBll;
use Service\Exceptions\Exception;

class Platform_count extends hcity_manage_controller
{

    /**
     * 平台统计
     * @author yize<yize@iyenei.com>
     */
    public function data_count()
    {

        $data = (new PlatformCountBll())->platformCount();
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 平台收入财务记录
     * @author yize<yize@iyenei.com>
     */
    public function platform_bill()
    {
        $rule = [
            ['field' => 'fid', 'label' => '流水号', 'rules' => 'integer'],
            ['field' => 'income_type', 'label' => '类型', 'rules' => 'integer|in_list[1,2,3,4,5]'],
            ['field' => 'time', 'label' => '时间段 例如：2018-02 - 2018-08', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new PlatformCountBll())->platformBill($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 平台充值财务记录
     * @author feiying<feiying@iyenei.com>
     */
    public function platform_recharge()
    {
        $rule = [
            ['field' => 'pay_tid', 'label' => '流水号', 'rules' => 'trim'],
            ['field' => 'time', 'label' => '时间段 例如：2018-02 - 2018-08', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '用户手机号码', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new PlatformCountBll())->platformRecharge($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }






}
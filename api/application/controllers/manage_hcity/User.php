<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/19
 * Time: 16:05
 */
use Service\Bll\Hcity\UserBll;
use Service\Exceptions\Exception;

class User extends hcity_manage_controller
{

    /**
     * 获取用户列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'status', 'label' => '订单状态', 'rules' => 'integer'],
            ['field' => 'username', 'label' => '商品名称筛选', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '手机', 'rules' => 'integer'],
            ['field' => 'level', 'label' => '等级', 'rules' => 'integer'],
            ['field' => 'is_qs', 'label' => '是否骑士', 'rules' => 'integer'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $data = (new UserBll())->userList($fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
 * 用户详情
 * @author yize<yize@iyenei.com>
 */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '用户id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new UserBll())->userDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }




    /**
     * 修改状态
     * @author yize<yize@iyenei.com>
     */
    public function edit_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '用户id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '状态', 'rules' => 'integer|required|in_list[0,1]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new UserBll())->userEditStatus($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }


    /**
     * 某一个用户财务记录
     * @author yize<yize@iyenei.com>
     */
    public function user_bill()
    {
        $rule = [
            ['field' => 'uid', 'label' => '用户id', 'rules' => 'integer|required'],
            ['field' => 'fid', 'label' => '流水号', 'rules' => 'integer'],
            ['field' => 'type', 'label' => '类型', 'rules' => 'integer|in_list[1,3,4,5,6]'],
            ['field' => 'time', 'label' => '时间段 例如：2018-02 - 2018-08', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new UserBll())->userBill($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }


}
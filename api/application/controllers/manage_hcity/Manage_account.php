<?php

/**
 * 城市合伙人
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/19
 * Time: 上午11:50
 */
use Service\Bll\Hcity\ManageAccountBll;
use Service\Exceptions\Exception;


class Manage_account extends hcity_manage_controller
{
    /**
     * 城市合伙人列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'username', 'label' => '名称', 'rules' => 'trim'],
            ['field' => 'status', 'label' => '状态', 'rules' => 'integer|in_list[0,1,2]'],
            ['field' => 'mobile', 'label' => '手机', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区id', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $data = (new ManageAccountBll())->manageList($fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 城市合伙人详情
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
            $data = (new ManageAccountBll())->manageDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 修改城市合伙人状态
     * @author yize<yize@iyenei.com>
     */
    public function edit_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '商品申请id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '状态', 'rules' => 'integer|required|in_list[0,1,2]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ManageAccountBll())->editManageStatus($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 添加城市合伙人
     * @author yize<yize@iyenei.com>
     */
    public function add()
    {
        $rule = [
            ['field' => 'username', 'label' => '城市合伙人名称', 'rules' => 'trim|required'],
            ['field' => 'mobile', 'label' => '手机', 'rules' => 'required|preg_key[MOBILE]'],
            ['field' => 'region', 'label' => '地区 多个用-拼接', 'rules' => 'trim|required'],
            ['field' => 'expire_time', 'label' => '到期时间', 'rules' => 'trim|required'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|min_length[6]|max_length[25]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ManageAccountBll())->addManage($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 修改城市合伙人
     * @author yize<yize@iyenei.com>
     */
    public function edit()
    {
        $rule = [
            ['field' => 'id', 'label' => '城市合伙人列表id', 'rules' => 'trim|required'],
            ['field' => 'username', 'label' => '城市合伙人名称', 'rules' => 'trim|required'],
            ['field' => 'mobile', 'label' => '手机', 'rules' => 'required|preg_key[MOBILE]'],
            ['field' => 'region', 'label' => '地区 多个用-拼接', 'rules' => 'trim|required'],
            ['field' => 'expire_time', 'label' => '到期时间', 'rules' => 'trim|required'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|min_length[6]|max_length[25]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ManageAccountBll())->editManage($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 某个合伙人财务记录
     * @author yize<yize@iyenei.com>
     */
    public function manage_bill()
    {
        $rule = [
            ['field' => 'manage_id', 'label' => '合伙人id', 'rules' => 'integer|required'],
            ['field' => 'fid', 'label' => '流水号', 'rules' => 'integer'],
            ['field' => 'type', 'label' => '类型', 'rules' => 'integer|in_list[1,3,4,5,6]'],
            ['field' => 'time', 'label' => '时间段 例如：2018-02 - 2018-08', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ManageAccountBll())->manageBill($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }











}
<?php

/**
 * 总后台集赞活动管理
 * Created by PhpStorm.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/8/21
 * Time: 上午11:40
 */
use Service\Bll\Hcity\ActivityJzBll;
use Service\Bll\Hcity\ActivityGoodsJzBll;
use Service\Exceptions\Exception;

class Activity_jz extends hcity_manage_controller
{
    /**
     * 集赞活动上架审核
     * @author feiying<feiying@iyenei.com>
     */
    public function edit_audit_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '商品申请id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '审核状态', 'rules' => 'integer'],
            ['field' => 'audit_status', 'label' => '审核状态', 'rules' => 'integer|in_list[2,3]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ActivityJzBll())->editAuditStatus($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 集赞活动列表
     * @author feiying<feiying@iyenei.com>
     */
    public function activity_list()
    {
        $rule = [
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim'],
            ['field' => 'source_type', 'label' => '审核状态', 'rules' => 'trim|in_list[1,2]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $ret = (new ActivityJzBll())->ActivityList($sUser, $fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 集赞活动审核列表
     * @author feiying<feiying@iyenei.com>
     */
    public function activity_listsh()
    {
        $rule = [
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim'],
            ['field' => 'source_type', 'label' => '审核状态', 'rules' => 'trim|in_list[1,2]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $ret = (new ActivityJzBll())->ActivityListsh($sUser, $fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 集赞活动用户购买列表
     * @author feiying<feiying@iyenei.com>
     */
    public function activityuser_list()
    {
        $rule = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '助力活动状态', 'rules' => 'integer'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityGoodsJzBll())->activityuserListz($data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

     /**
     * 集赞活动详情
     * @author feiying<feiying@iyenei.com>
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '助力活动状态', 'rules' => 'integer']
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityGoodsJzBll())->activityDetailz($data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

}
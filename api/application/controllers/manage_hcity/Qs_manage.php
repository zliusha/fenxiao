<?php

/**
 * 骑士管理
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/8/27
 * Time: 上午9:40
 */
use Service\Bll\Hcity\QsManageBll;
use Service\Exceptions\Exception;

class Qs_manage extends hcity_manage_controller
{
    /**
     * 获取所有骑士申请上架列表
     * @author feiying<feiying@iyenei.com>
     */
    public function apply_list()
    {
        $rule = [
       		['field' => 'audit_status', 'label' => '账号状态', 'rules' => 'trim'],
       		['field' => 'experience_type', 'label' => '骑士经历类型', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'], 
            ['field' => 'time', 'label' => '账号添加时间', 'rules' => 'trim'],
            ['field' => 'salename', 'label' => '邀请人账号', 'rules' => 'trim'],
            ['field' => 'name', 'label' => '骑士姓名', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $ret = (new QsManageBll())->applyList($sUser, $fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 骑士申请用户审核
     * @author feiying<feiying@iyenei.com>
     */
    public function edit_audit_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '活动申请id', 'rules' => 'integer|required'],
            ['field' => 'audit_status', 'label' => '审核状态', 'rules' => 'integer|in_list[2,3]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new QsManageBll())->editAuditStatus($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 骑士申请详情
     * @author feiying<feiying@iyenei.com>
     */
    public function apply_detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '助力活动状态', 'rules' => 'integer']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $ret = (new QsManageBll())->applyDetail($fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 获取所有骑士申请上架列表
     * @author feiying<feiying@iyenei.com>
     */
    public function qs_list()
    {
        $rule = [
       		['field' => 'status', 'label' => '账号状态', 'rules' => 'trim'],
       		['field' => 'experience_type', 'label' => '骑士经历类型', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'], 
            ['field' => 'time', 'label' => '账号添加时间', 'rules' => 'trim'],
            ['field' => 'salename', 'label' => '邀请人账号', 'rules' => 'trim'],
            ['field' => 'name', 'label' => '骑士姓名', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $ret = (new QsManageBll())->qsList($sUser, $fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 骑士申请用户审核
     * @author feiying<feiying@iyenei.com>
     */
    public function qs_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '活动申请id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '审核状态', 'rules' => 'integer|in_list[1,2]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new QsManageBll())->qsStatus($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 获取邀请骑士列表
     * @author feiying<feiying@iyenei.com>
     */
    public function invite_qs()
    {
        $rule = [
        	['field' => 'id', 'label' => '账号id', 'rules' => 'trim|required'],
       		['field' => 'status', 'label' => '账号状态', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'], 
            ['field' => 'time', 'label' => '账号添加时间', 'rules' => 'trim'],
            ['field' => 'name', 'label' => '骑士姓名', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $ret = (new QsManageBll())->inviteQs($fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 获取邀请商圈列表
     * @author feiying<feiying@iyenei.com>
     */
    public function invite_hcity()
    {
        $rule = [
        	['field' => 'id', 'label' => '账号id', 'rules' => 'trim|required'],
       		['field' => 'qs_shop_status', 'label' => '有效状态', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'], 
            ['field' => 'time', 'label' => '账号添加时间', 'rules' => 'trim'],
            ['field' => 'shop_name', 'label' => '店铺名称', 'rules' => 'trim'],
            ['field' => 'contact', 'label' => '手机号', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $ret = (new QsManageBll())->inviteHcity($fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 获取邀请一店一码列表
     * @author feiying<feiying@iyenei.com>
     */
    public function invite_ydym()
    {
        $rule = [
            ['field' => 'id', 'label' => '账号id', 'rules' => 'trim|required'],
            ['field' => 'qs_shop_status', 'label' => '有效状态', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'], 
            ['field' => 'time', 'label' => '账号添加时间', 'rules' => 'trim'],
            ['field' => 'shop_name', 'label' => '店铺名称', 'rules' => 'trim'],
            ['field' => 'contact', 'label' => '手机号', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $ret = (new QsManageBll())->inviteYdym($fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }




}

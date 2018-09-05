<?php

/**
 * 总后台砍价动管理
 * Created by PhpStorm.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/9/3
 * Time: 下午17:40
 */
use Service\Bll\Hcity\ManageBargainBll;
use Service\Bll\Hcity\ActivityBargainBll;
use Service\Exceptions\Exception;

class Activity_kj extends hcity_manage_controller
{
    /**
     * 砍价活动审核列表
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午17:40
     */
    public function activity_listsh()
    {
        $rule = [
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'],
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
            ['field' => 'shop_name', 'label' => '店铺名称', 'rules' => 'trim'],
            ['field' => 'source_type', 'label' => '来源状态', 'rules' => 'trim|in_list[1,2]'],
            ['field' => 'audit_status', 'label' => '审核状态', 'rules' => 'trim|in_list[1,3]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $ret = (new ManageBargainBll())->ActivityListsh($sUser, $fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 砍价活动审核详情
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午18:47
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '助力活动状态', 'rules' => 'integer']
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityBargainBll())->ActivityDetailKj($data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 砍价活动上架审核
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午19:37
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
            $data = (new ManageBargainBll())->editAuditStatus($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 砍价活动配置文件
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午19:37
     */
    public function kj_config()
    {
        $data = (new ManageBargainBll())->kjConfig();
        $this->json_do->set_data($data);
        $this->json_do->out_put();     
    }

    /**
     * 砍价活动编辑配置文件
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午19:37
     */
    public function edit_config()
    {
        $rule = [
            ['field' => 'config1', 'label' => '活动配置', 'rules' => 'trim|required'],
            ['field' => 'config2', 'label' => '活动配置', 'rules' => 'trim|required'],
            ['field' => 'config3', 'label' => '活动配置', 'rules' => 'trim|required'],
            ['field' => 'config4', 'label' => '活动配置', 'rules' => 'trim|required'],
            ['field' => 'config5', 'label' => '活动配置', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        try {
            $ret = (new ManageBargainBll())->editConfig($data);
            if ($ret) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('001', '砍价活动编辑配置文件错误');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }       
    }

    /**
     * 砍价活动审核列表
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/3
     * Time: 下午17:40
     */
    public function activity_list()
    {
        $rule = [
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'],
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
            ['field' => 'shop_name', 'label' => '店铺名称', 'rules' => 'trim'],
            ['field' => 'source_type', 'label' => '来源状态', 'rules' => 'trim|in_list[1,2]'],
            ['field' => 'status', 'label' => '活动状态', 'rules' => 'trim|in_list[1,2]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $ret = (new ManageBargainBll())->ActivityList($sUser, $fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }
    
    /**
     * 砍价活动详情轮次
     * @author feiying<feiying@iyenei.com>
     * Date: 2018/9/4
     * Time: 下午16:47
     */
    public function activityturns_list()
    {
        $rule = [
            ['field' => 'activity_id', 'label' => '活动id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ActivityBargainBll())->activityturnsList($data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }
    
}
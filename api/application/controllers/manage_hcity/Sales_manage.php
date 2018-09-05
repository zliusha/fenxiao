<?php

/**
 * 销售管理
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/8/28
 * Time: 上午9:50
 */
use Service\Bll\Hcity\SalesManageBll;
use Service\Bll\Hcity\QsManageBll;
use Service\Exceptions\Exception;

class Sales_manage extends hcity_manage_controller
{
    /**
     * 添加销售
     * @author feiying<feiying@iyenei.com>
     */
    public function create()
    {
        $rules = [
            ['field' => 'name', 'label' => '姓名', 'rules' => 'trim|required'],
            ['field' => 'mobile', 'label' => '销售手机号', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地理位置', 'rules' => 'trim|required'],
            ['field' => 'region_name', 'label' => '省市区名', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $ret = (new SalesManageBll())->create($fdata);
        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('001', '添加销售错误');
        }
    }

    /**
     * 编辑销售
     * @author feiying<feiying@iyenei.com>
     */
    public function edit()
    {
        $rules = [
            ['field' => 'id', 'label' => '销售id', 'rules' => 'trim|required'],
            ['field' => 'name', 'label' => '姓名', 'rules' => 'trim|required'],
            ['field' => 'mobile', 'label' => '销售手机号', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地理位置', 'rules' => 'trim|required'],
            ['field' => 'region_name', 'label' => '省市区名', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $ret = (new SalesManageBll())->edit($fdata);
        if ($ret) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('001', '添加销售错误');
        }
    }

    /**
     *  销售详情
     * @author feiying<feiying@iyenei.com>
     */
    public function detail()
    {
        $rules = [
            ['field' => 'id', 'label' => '销售id', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $ret = (new SalesManageBll())->detail($fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 获取销售列表
     * @author yize<yize@iyenei.com>
     */
    public function sales_search()
    {
        $rule = [
            ['field' => 'name', 'label' => '姓名', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '销售手机号', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地理位置', 'rules' => 'trim'],
            ['field' => 'time', 'label' => '时间', 'rules' => 'trim'],
            ['field' => 'status', 'label' => '状态', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $data = (new SalesManageBll())->salesSearch($fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 销售用户修改
     * @author feiying<feiying@iyenei.com>
     */
    public function sales_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '活动申请id', 'rules' => 'integer|required'],
            ['field' => 'status', 'label' => '审核状态', 'rules' => 'integer|in_list[1,2]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new SalesManageBll())->salesStatus($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     *  销售推荐详情
     * @author feiying<feiying@iyenei.com>
     */
    public function sales_invite()
    {
        $rules = [
            ['field' => 'id', 'label' => '销售id', 'rules' => 'trim|required'],
            ['field' => 'status', 'label' => '状态', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'], 
            ['field' => 'time', 'label' => '账号添加时间', 'rules' => 'trim'],
            ['field' => 'name', 'label' => '骑士姓名', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $ret = (new QsManageBll())->salesInvite($fdata);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

}

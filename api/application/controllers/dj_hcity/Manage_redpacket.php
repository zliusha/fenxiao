<?php

/**
 * 红包管理
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/7/18
 * Time: 上午11:47
 */
use Service\Bll\Hcity\ManageRedPacketBll;
use Service\Exceptions\Exception;

class Manage_redpacket extends dj_hcity_controller
{

    /**
     * 红包列表
     * @author feiying<feiying@iyenei.com>
     */
    public function redpacket_search()
    {
        $rules = [
            ['field' => 'status', 'label' => '红包状态', 'rules' => 'integer'],
            ['field' => 'username', 'label' => '红包拥有人昵称', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '红包拥有人手机号', 'rules' => 'trim'],
            ['field' => 'current_page', 'label' => '当前页', 'rules' => 'trim|numeric|required'],
            ['field' => 'page_size', 'label' => '单页个数', 'rules' => 'trim|numeric|required'],
        ];
        $this->check->check_ajax_form($rules);
        $data = $this->form_data($rules);
        $data['shop_id'] = $this->currentShopId;
        $ret = (new ManageRedPacketBll())->redpacketSearch($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put(); 
    }

    /**
     * 红包使用详情
     * @author feiying<feiying@iyenei.com>
     */
    public function redpacket_detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '红包id', 'rules' => 'integer|required'],
            ['field' => 'current_page', 'label' => '当前页', 'rules' => 'trim|numeric|required'],
            ['field' => 'page_size', 'label' => '单页个数', 'rules' => 'trim|numeric|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ManageRedPacketBll())->redDetail($this->s_user->aid,$data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();   
    }
}
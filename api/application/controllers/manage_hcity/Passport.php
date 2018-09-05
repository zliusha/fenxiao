<?php

/**
 * @Author: binghe
 * @Date:   2018-07-18 19:38:44
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-23 10:47:09
 */
use Service\Bll\Hcity\ManageAccountBll;
/**
 * Passport
 */
class Passport extends hcity_manage_controller
{
	/**
	 * 登录
	 * @return [type] [description]
	 */
	public function login()
	{
		//验证
        $rule = [
            ['field' => 'account', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|min_length[6]|max_length[25]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser = (new ManageAccountBll())->getHmSUser($fdata['account'],$fdata['password']);
        //cookie保存登录信息保存7天
        $c_value=$this->encryption->encrypt(serialize($sUser));
        set_cookie('hm_user',$c_value,3600*24*7);
		$data['type']=$sUser->type;
		$data['username']=$sUser->username;
		$data['region_type']=$sUser->region_type;
		$data['region']=$sUser->region;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
	}
	/**
	 * 退出
	 * @return [type] [description]
	 */
	public function logout()
	{
		delete_cookie('hm_user');
		$this->json_do->out_put();
	}
}
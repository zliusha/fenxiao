<?php

/**
 * @Author: binghe
 * @Date:   2018-07-17 15:25:58
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-25 16:16:31
 */
use Service\Bll\Main\MainCompanyAccountBll;
/**
 * 登录
 */
class Passport extends dj_controller
{
	public function login()
	{
		//验证
        $rule = [
            ['field' => 'account', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|min_length[6]|max_length[25]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser = (new MainCompanyAccountBll())->getSaasSUser($fdata['account'],$fdata['password']);
        //cookie保存登录信息保存7天
        $c_value=$this->encryption->encrypt(serialize($sUser));
        set_cookie('saas_user',$c_value,3600*24*7);
        $this->json_do->set_msg('登录成功');
        $this->json_do->out_put();
	}
	/**
	 * 安全退出
	 */
    public function logout()
    {
        
        //删除登录cookie
        delete_cookie('saas_user');
    	$this->json_do->out_put();
	}
}
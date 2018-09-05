<?php

/**
 * @Author: binghe
 * @Date:   2018-07-25 17:47:28
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-25 20:37:07
 */
/**
 * passport
 */
class Passport extends dj_controller
{
	
	/**
	 * 安全退出
	 */
    public function logout()
    {
        
        //删除登录cookie
        delete_cookie('saas_user');
        delete_cookie('c_hcity_shop_id');
    	$this->json_do->out_put();
	}
}
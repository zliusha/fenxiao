<?php

/**
 * @Author: binghe
 * @Date:   2018-07-25 17:49:03
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-25 20:38:59
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
        delete_cookie('c_ydym_shop_id');
    	$this->json_do->out_put();
	}
}
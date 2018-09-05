<?php
/**
 * @Author: binghe
 * @Date:   2018-04-17 15:17:54
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-17 17:55:36
 */
/**
* shop user 用户登录，且绑定手机号
*/
class mshop_user_controller extends mshop_controller
{
   
    function __construct()
    {
        parent::__construct();
        $this->_validUser();
    }
    //验证用户登录,且验证用户手机号
    private function _validUser()
    {
        if(is_null($this->s_user) || empty($this->s_user->mobile))
            $this->json_do->set_error('004-2');
    }
    
}
<?php
/**
 * @Author: binghe
 * @Date:   2018-04-02 13:55:22
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 11:00:00
 */
use Service\Cache\ApiAccessTokenCache;
/**
* 收银
*/
class sy_controller extends auth_controller
{
    const LIMIT_VERSION = '1.0.0';//限制版本
    const MODULE = 'ydb_sy';//当前模块
    public $version;    //当前版本
    public $s_user;
    function __construct()
    {
        parent::__construct();
        //加载来源
        $this->_init_source();
        //加载用户
        $this->_load_user();
    }
    /**
     * 初始化来源
     * @return [type] [description]
     */
    private function _init_source()
    {
        if($this->source != self::MODULE)
            $this->json_do->set_error('001','非法来源请求');
        $version = $this->input->get_request_header('Version'); 
        if(empty($version))
            $this->json_do->set_error('001','非法请求-version');
        $this->version = $version;
//        if((int)self::LIMIT_VERSION <= (int)$version)
//            $this->version = $version;
//        else
//            $this->json_do->set_error('003-2');//app版本过低
    }
    /**
     * 加载用户
     * @return [type] [description]
     */
    private function _load_user()
    {
        //无需验证的类
        $no_auths = ['passport'];
        //是否验证
        if(in_array($this->url_class,$no_auths))
            return;
        $access_token = $this->input->get_request_header('Access-Token');
        if(empty($access_token))
            $this->json_do->set_error('004','access_token 无效');

        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$access_token]);
        $s_user = $apiAccessTokenCache->get();
        
        if($s_user && get_class($s_user) =='s_sy_user_do')
        {
            $this->s_user = $s_user;
            //权限验证
            $this->_check_service(module_enum::SHOUYIN_MODULE,$this->s_user->aid);
        }
        else
            $this->json_do->set_error('004-1','登录过期,请重新登录');
    }
}
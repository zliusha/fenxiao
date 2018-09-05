<?php

/**
 * @Author: binghe
 * @Date:   2018-07-05 09:54:00
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-05 10:39:09
 */
/**
 * 商圈小程序基类
 */
use Service\Cache\ApiAccessTokenCache;

class xhcity_controller extends auth_controller
{
	
	const MODULE = 'ydb_hcity_xcx';
    public $version='1.0.0';//请求的版本
    /** @var  s_xhcity_user_do */
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
        //当前请求版本,没有时使用默认版本,以后应强制
        $version = $this->input->get_request_header('Version');
        if(!empty($version))
            $this->version = $version;
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
        $accessToken = $this->input->get_request_header('Access-Token');
        if(empty($accessToken))
            $this->json_do->set_error('004','no access_token');
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$accessToken]);
        $s_user = $apiAccessTokenCache->get();
        if($s_user && get_class($s_user) =='s_xhcity_user_do')
            $this->s_user = $s_user;
        else
            $this->json_do->set_error('004-1','登录过期,请重新登录');
    }
}
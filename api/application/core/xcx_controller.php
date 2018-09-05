<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 14:44:06
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 17:29:25
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\XcxAppInfoCache;
/**
*  小程序接口授权
*/
class xcx_controller extends auth_controller
{
    const MODULE = 'ydb_xcx';
    public $aid;
    public $app_id;
    public $version='1.0.0';//请求的版本
    public $visit_id;
    public $s_user;
    
    function __construct()
    {
        parent::__construct();
        //加载来源
        $this->_init_source();
        //加载用户
        $this->_load_user();
        //权限验证
        $this->_check_service(module_enum::XCX_WM_MODULE,$this->aid);
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
        $appId = $this->input->get_request_header('Appid'); 
        if(empty($appId))
            $this->json_do->set_error('001','非法请求-app_id');
        $this->app_id = $appId;
        //获取小程序信息
        $arr=$this->_getAppInfo($appId);
        $this->aid = $arr['aid'];
        $this->visit_id = $arr['visit_id'];
    }

    /**
     * 加载用户
     * @return [type] [description]
     */
    private function _load_user()
    {
        //无需验证的类
        $no_auths = ['passport','test', 'common_file'];
        //是否验证
        if(in_array($this->url_class,$no_auths))
            return;
        $accessToken = $this->input->get('access_token');
        if(empty($accessToken))
            $this->json_do->set_error('004','no access_token');
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$accessToken]);
        $s_user = $apiAccessTokenCache->get();
        if($s_user && get_class($s_user) =='s_xcx_user_do' && $s_user->aid == $this->aid)
            $this->s_user = $s_user;
        else
            $this->json_do->set_error('004-1','登录过期,请重新登录');
    }
    /**
     * 获取appInfo
     * @param  [type] $app_id [description]
     * @return array         ['aid','visit_id']
     */
    private function _getAppInfo($appId)
    {
        $xcxAppInfoCache = new XcxAppInfoCache(['app_id'=>$appId]);
        $appInfo = $xcxAppInfoCache->getDataASNX();
        return $appInfo;
    }
    /*
     * 验证用户 游客身份不能访问
     * @param  array $filterMethods 过滤无需验证的方法,转为小写
     * @return [type]                [description]
     */
    protected function valide_user($filterMethods=[])
    {
        if(in_array($this->url_method,$filterMethods) || $this->s_user->uid!=0)
            return;
        else
            $this->json_do->set_error('004-2','请绑定手机号');
            
    }

}
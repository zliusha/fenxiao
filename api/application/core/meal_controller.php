<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 15:14:47
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:38:30
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\CompanyDomainCache;
use Service\Cache\MealShopAreaTableCache;
use Service\Cache\WmGzhConfigCache;

/**
 * 点餐
 */
class meal_controller extends auth_controller
{
    const MODULE = 'ydb_meal';
    public $aid;
    public $visit_id;
    public $s_user;
    public function __construct()
    {
        parent::__construct();
        //加载来源
        $this->_init_source();
        //加载用户
        $this->_init_user();
        //权限验证
        $this->_check_service(module_enum::MEAL_MODULE,$this->aid);
    }

    /**
     * 初始化来源
     * @return [type] [description]
     */
    private function _init_source()
    {
        if ($this->source != self::MODULE) {
            $this->json_do->set_error('001', '非法来源请求');
        }

        // 本地环境
        if (in_array(ENVIRONMENT, ['development'])) {
            $this->aid = 1226;
            $this->visit_id;
            return;
        }
        $domain = strtolower($this->input->get_request_header('Domain'));
        if (!$domain) {
            $domain = strtolower($this->input->get_request_header('Host'));
        }
        if (!$domain) {
            $domain = strtolower($this->input->get_request_header('Origin'));
            $domain = str_replace(['http://', 'https://'], '', $domain);
        }
        if (empty($domain)) {
            $this->json_do->set_error('001', 'no domain');
        }

        //正则校验
        $preg = '/^[a-z0-9]+\.[a-z]+\.[a-z]+\.[a-z]+$/';
        if (!preg_match($preg, $domain)) {
            $this->json_do->set_error('001', 'domain error');
        }

        $domain = substr($domain, 0, strpos($domain, '.'));
        $companyDomainCache = new CompanyDomainCache(['domain' => $domain]);
        $cData = $companyDomainCache->getDataASNX();
        if (!$cData) {
            $this->json_do->set_error('004', '域名不存在');
        }

        $this->aid = $cData['aid'];
        $this->visit_id = $cData['visit_id'];

    }

    /**
     * 初始化用户
     */
    private function _init_user()
    {
        //无需验证的类
        $no_auths = ['passport'];
        //是否验证
        if (in_array($this->url_class, $no_auths)) {
            return;
        }

        $accessToken = $this->input->get_request_header('Access-Token');
        if (empty($accessToken)) {
            $this->json_do->set_error('004', 'no access_token');
        }

        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
        $s_user = $apiAccessTokenCache->get();
        if (!$s_user) {
            $this->json_do->set_error('004-1', '登录过期,请重新登录');
        }

        //获取最新的公众号配置
        $wmGzhConfigCache = new WmGzhConfigCache(['aid' => $this->aid]);
        $mGzhConfig = $wmGzhConfigCache->getDataASNX();

        // log_message('error', json_encode($s_user));
        if (!$s_user->openid) {
            $this->json_do->set_error('004', '获取OPENID失败');
        }

        if (get_class($s_user) == 's_meal_user_do' && $s_user->appid == $mGzhConfig->app_id) {
            $this->s_user = $s_user;
        } else {
            $this->json_do->set_error('004-1', 'access_token 无效');
        }

    }

}

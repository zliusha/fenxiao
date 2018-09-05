<?php
/**
 * @Author: binghe
 * @Date:   2018-04-17 10:40:24
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:38:36
 */
/**
 * h5
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\CompanyDomainCache;
use Service\Cache\WmGzhConfigCache;

class mshop_controller extends auth_controller
{

    const MODULE = 'ydb_mshop';
    public $aid;
    public $visit_id;
    public $s_user;
    public function __construct()
    {
        parent::__construct();
        //加载来源
        $this->_init_source();
        //加载已用户
        $this->_init_user();
        //权限验证
        $this->_check_service(module_enum::WM_MODULE,$this->aid);
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

        $domain = strtolower($this->input->get_request_header('Host'));
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
        $this->aid = $cData['aid'];
        $this->visit_id = $cData['visit_id'];

    }
    /**
     * 初始化用户
     */
    private function _init_user()
    {
        $accessToken = $this->input->get_request_header('Access-Token');
        if (empty($accessToken)) {
            return;
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

        if (get_class($s_user) == 's_wm_user_do') {
            $this->s_user = $s_user;
        } else {
            $this->json_do->set_error('005','access token 无效');
        }

    }
}

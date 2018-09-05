<?php

/**
 * @Author: binghe
 * @Date:   2018-07-12 13:41:10
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-26 09:23:02
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\Main\AidSaasAccountCache;
use Service\Cache\Main\UidSaasAccountCache;

class dj_controller extends auth_controller
{
    const MODULE = 'ydb_manage';
    /** @var  s_saas_user_do */
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
        if ($this->source != self::MODULE)
            $this->json_do->set_error('001', '非法来源请求');
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
        if (in_array($this->url_class, $no_auths))
            return;
        // $data=new s_saas_user_do();
        // $this->s_user=$data;
        // return;
        //同时支持AccessToken和cookie访问，AccessToken优先
        $accessToken = $this->input->get_request_header('Access-Token');
        $sUser = null;
        if ($accessToken)    //accessToken访问
        {
            $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
            $sUser = $apiAccessTokenCache->get();
        } else   //cookie统一访问
        {
            $userCookie = get_cookie('saas_user');
            if ($userCookie) {
                $encrypUser = $this->encryption->decrypt($userCookie);
                if ($encrypUser){
                    $sUser = @unserialize($encrypUser);
                }
                else
                    delete_cookie('saas_user');//不是正确的cookie删除
            }
        }
        if ($sUser && get_class($sUser) == 's_saas_user_do')
        {
            $this->s_user = $sUser;
        }
        else
            $this->json_do->set_error('004-1', '登录过期,请重新登录');

    }

    /**
     * 检没是否开通此Saas服务
     * @return [type] [description]
     */
    protected function validSaas($saasId)
    {
        if ($this->s_user->is_admin) {
            $aidSaasAccountCache = new AidSaasAccountCache(['aid' => $this->s_user->aid]);
            $saasArr = $aidSaasAccountCache->getDataASNX();
        } else {
            $uidSaasAccountCache = new UidSaasAccountCache(['aid' => $this->s_user->aid, 'uid' => $this->s_user->id]);
            $saasArr = $uidSaasAccountCache->getDataASNX();
        }
        if (!$saasArr)
            $this->json_do->set_error('004', '未开通服务或没有访问权限');

        $saas = array_find($saasArr, 'saas_id', $saasId);
        if (!$saas)
            $this->json_do->set_error('004', '未开通此服务');
        if (!empty($saas['expire_time']) && $saas['expire_time'] < date('Y-m-d'))
            $this->json_do->set_error('004', '服务已到期');
    }
}
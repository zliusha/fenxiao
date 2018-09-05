<?php

/**
 * @Author: binghe
 * @Date:   2018-07-18 18:49:43
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-16 17:35:04
 */
/**
 * 商圈后台管理
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\Hcity\HcityManageAccountCache;

class hcity_manage_controller extends auth_controller
{

    const MODULE = 'ydb_manage_hcity';
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
        //同时支持AccessToken和cookie访问，AccessToken优先
        $accessToken = $this->input->get_request_header('Access-Token');
        $sUser = null;
        if ($accessToken)    //accessToken访问
        {
            $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
            $sUser = $apiAccessTokenCache->get();
        } else   //cookie统一访问
        {
            $userCookie = get_cookie('hm_user');
            if ($userCookie) {
                $encrypUser = $this->encryption->decrypt($userCookie);
                if ($encrypUser)
                    $sUser = unserialize($encrypUser);
            }
        }
        if ($sUser && get_class($sUser) == 's_hmanage_user_do')
        {
            //限制城市合伙人权限
            $this->_manage_auth($sUser);
            $this->s_user = $sUser;
            if ($this->url_class == 'manager_balance_record')
            {
                return;
            }
            //验证城市合伙人是否过期
            $this->_expire_verify($sUser);
        }
        else
        {
            delete_cookie('hm_user');
            $this->json_do->set_error('004-1', '登录过期,请重新登录');
        }


    }

    //城市合伙人只拥有以下权限
    private function _manage_auth($sUser)
    {
        //如果是城市合伙人则没有以下权限
        if($sUser->type==0)
        {
            //有权限的控制器
            $no_auths = ['passport','shop_ext','shop_apply','goods_kz','goods_apply','order','manager_balance_record','home_page_shop_cate','activity_popular_goods','shop_category','index','activity_jz','activity_kj'];
            //是否验证
            if (!in_array($this->url_class, $no_auths))
                $this->json_do->set_error('005', '你没有此模块权限');
        }
    }


    //登陆过的合伙人再进行过期验证
    private function _expire_verify($sUser)
    {
        $hcityManageAccountCache = new HcityManageAccountCache(['id'=>$sUser->id]);
        $info = $hcityManageAccountCache->getDataASNX();
        if($info->status==1)
        {
            $this->json_do->set_error('005', '此账号已被拉黑，请联系管理员');
        }
        if($info->type==0)
        {
            if($info->expire_time<time())
            {
                $this->json_do->set_error('003', '您没有此模块权限');
            }
        }

    }
}
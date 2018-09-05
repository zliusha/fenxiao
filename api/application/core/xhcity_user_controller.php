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
use Service\Cache\Hcity\HcityUserCache;
use Service\Cache\Hcity\HcityQsCache;

class xhcity_user_controller extends xhcity_controller
{

    function __construct($filterMethods=[])
    {
        parent::__construct();
        $this->_valide_user($filterMethods);
    }
    /**
     * 验证用户
     * @param  array $filterMethods 过滤无需验证的方法,转为小写
     * @return [type]                [description]
     */
    private function _valide_user($filterMethods=[])
    {
        if(in_array($this->url_method,$filterMethods) || !empty($this->s_user->uid))
            return;
        else
            $this->json_do->set_error('004-2','请绑定手机号');

    }

    /**
     * 校验用户账户状态
     */
    protected function check_user_status()
    {
        $hcityUserCache = new HcityUserCache(['uid'=>$this->s_user->uid]);
        $user = $hcityUserCache->getDataASNX();
        if($user->status != 0)
        {
            $this->json_do->set_error('005','用户账户异常，请联系平台客服');
        }
    }

    /**
     * 校验嘿卡骑士身份
     * @param $uid
     */
    protected function valid_qs()
    {
        $hcityQsCache = new HcityQsCache(['uid'=>$this->s_user->uid]);
        $mQs = $hcityQsCache->getDataASNX();
        if($mQs->status==0)
        {
            $this->json_do->set_error('005','骑士申请未审核');
        }

        if($mQs->status==2)
        {
            $this->json_do->set_error('005','骑士账号已被禁用');
        }

        return $mQs;
    }
}
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
use Service\Cache\Main\MobileShopAccountCache;

class xhcity_dj_controller extends xhcity_controller
{
    protected $mobileShopAccount = null;

    function __construct($filterMethods=[])
    {
        parent::__construct();
        $this->_valide_user($filterMethods);
        //校验用户账户是否关联店铺或账户
        $this->_valide_mobile_to_shop();
    }
    /**
     * 验证用户
     * @param  array $filterMethods 过滤无需验证的方法,转为小写
     * @return [type]                [description]
     */
    private function _valide_user($filterMethods=[])
    {
        if(in_array($this->url_method,$filterMethods) || $this->s_user->uid!=0)
            return;
        else
            $this->json_do->set_error('004-2','请绑定手机号');

    }

    /**
     * 校验用户账户是否关联店铺或账户
     */
    private function _valide_mobile_to_shop()
    {
        $mobileShopAccountCache = new MobileShopAccountCache(['mobile'=>$this->s_user->mobile]);
        $mobileShopAccount = $mobileShopAccountCache->getDataASNX();
        if(!$mobileShopAccount)
        {
            $this->json_do->set_error('005','用户未关联店铺');
        }

        $this->mobileShopAccount = $mobileShopAccount;
    }

    /**
     * 检测参数是否匹配
     * @param $aid
     * @param int $shop_id
     */
    protected function check_dj_match($aid, $shop_id=0)
    {
        // 验证商户权限
        if($this->mobileShopAccount->aid != $aid)
        {
            throw new Exception('暂无权限');
        }
        //验证店铺权限
        if($shop_id>0 && $this->mobileShopAccount->shop_id>0 && $this->mobileShopAccount->shop_id != $shop_id)
        {
            throw new Exception('暂无权限');
        }
    }
}
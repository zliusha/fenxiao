<?php
/**
 * @Author: binghe
 * @Date:   2017-08-11 16:44:53
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:39:27
 */
use Service\Cache\WmGzhConfigCache;
use Service\DbFrame\DataBase\WmMainDbModels\WmSettingDao;
/**
*  微卖控制基类
*/
class wm_service_controller extends service_controller
{
    function __construct($module = null)
    {
       parent::__construct($module);

    }
    /**
     * 获取门店基础URL
     */
    public function get_shop_base_url($scheme = 'https',$exit = true)
    {
        $url = '';
        try{
            $wmGzhConfigCache = new WmGzhConfigCache(['aid' => $this->s_user->aid]);
            $config = $wmGzhConfigCache->getDataASNX();
        }catch(Exception $e){
            $config = null;
        }


        //开启第三方授权登录
        if (isset($config->login_type) && $config->login_type == 1) {
            $scrm_sdk = new scrm_sdk;
            $result = $scrm_sdk->getGzhInfo(['visit_id' => $this->s_user->visit_id]);
            if(!isset($result['data']['authorizer_appid']))
            {
            	if($exit)
            		$this->_error('004','开启第三方授权登录,未授权公众号');
            	else
            		$sub_domain = '{null}';

            }
            else
                $sub_domain = $result['data']['authorizer_appid'];
        } else {
            $sub_domain = $this->s_user->aid;
            $wmSettingDao = WmSettingDao::i();
            $wm_setting = $wmSettingDao->getOne(['aid' => $this->s_user->aid]);

            if ($wm_setting && !empty($wm_setting->domain)) {
                $sub_domain = $wm_setting->domain;
            }
        }

        if ($scheme === null) {
            $url = $sub_domain . '.' . M_SUB_URL;
        } else {
            $url = $scheme . '://' . $sub_domain . '.' . M_SUB_URL;
        }

        return $url;
    }
}
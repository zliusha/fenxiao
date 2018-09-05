<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 11:46:41
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-06 17:01:50
 */
use Service\Sdk\WxOAuthSdk;
use Service\Cache\WmGzhConfigCache;
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;
/**
 * 到店h5登录
 */
class Passport extends meal_controller
{
    
    /**
     * 正式获取登录地址
     * @return [type] [description]
     */
    public function wx_codeurl()
    {
        if(in_array(ENVIRONMENT, ['development']))
            $this->json_do->set_error('008');
        $rules = [
            ['field'=>'redirect_url','label'=>'微信回调地址','rules'=>'trim|required|preg_key[URL]'],
            ['field'=>'state','label'=>'state参数','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $wxOAuthSdk = new WxOAuthSdk($this->_getGzhConfig());

        $url = $wxOAuthSdk->getOauthUrlForCode(urlencode($fdata['redirect_url']),$fdata['state']);
        $data['url'] = $url;
        // log_message('error', $data['url']);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 正式code登录
     * @return [type] [description]
     */
    public function wx_codelogin()
    {
        if(in_array(ENVIRONMENT, ['development']))
            $this->json_do->set_error('008');

        $rules = [
            ['field'=>'code','label'=>'微信code','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        try {
            $wxConfig = $this->_getGzhConfig();
            $wxOAuthSdk = new WxOAuthSdk($wxConfig);
            $openid = $wxOAuthSdk->getOpenidFromMp($fdata['code']);
            $wxInfo = $wxOAuthSdk->GetUserInfo();
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }

        $s_meal_user_do = new s_meal_user_do;
        $s_meal_user_do->appid = $wxConfig['appid'];
        $nickname = strip_emoji($wxInfo['nickname']);
        $s_meal_user_do->nickname = empty($nickname)?'wx_'.create_order_number():$nickname;
        $s_meal_user_do->openid = $wxInfo['openid'];
        $s_meal_user_do->headimg = $wxInfo['headimgurl'];
        if(isset($wxInfo['unionid']))
            $s_meal_user_do->unionid = $wxInfo['unionid'];
        $accessToken = md5(create_guid());
        $time = time();
        $expiredTime = 7200;
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$accessToken]);
        $apiAccessTokenCache->save($s_meal_user_do,$expiredTime);
        
        //扫码的有效期为1天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token'=>$refreshToken]);
        $apiRefreshTokenCache->save($s_meal_user_do,3600 * 24);

        //用户信息
        $s_meal_user_do->appid = '';
        $data['user_info'] = $s_meal_user_do;

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime];
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 获取公众号配置
     * @return [type] [description]
     */
    private function _getGzhConfig()
    {
        $wmGzhConfigCache = new WmGzhConfigCache(['aid'=>$this->aid]);
        $config = $wmGzhConfigCache->getDataASNX();
        if(!$config)
            $this->json_do->set_error('005','公众号未配置');
        $wxConfig['login_type'] = $config->login_type;
        $wxConfig['appid'] = $config->app_id;       
        $wxConfig['app_secret'] = $config->app_secret;
        //开启第三方授权登录
        if($wxConfig['login_type'] ==1 )
        {
            $scrm_sdk = new scrm_sdk;
            $result = $scrm_sdk->getGzhInfo(['visit_id'=>$this->visit_id]);
            //appid 需要重新赋值
            $wxConfig['appid'] = $result['data']['authorizer_appid'];
            $wxConfig['component_appid'] = $result['data']['component_appid'];
            $wxConfig['component_access_token'] = $result['data']['component_access_token'];
            $wxConfig['authorizer_access_token'] = $result['data']['authorizer_access_token'];
        }
        return $wxConfig;
    }
    
    /**
     * 模拟登录
     */
    public function mode_wxlogin($num=1)
    {
        if(in_array(ENVIRONMENT, ['production'])) 
            $this->json->set_error('008');
        $s_meal_user_do = new s_meal_user_do;
        $s_meal_user_do->appid = 'wx8c7be78b29e147da';
        $s_meal_user_do->openid = 'oqLrov469e_2Y6nettB24x0YDttI'.$num;
        $s_meal_user_do->nickname = '大地'.$num;
        $s_meal_user_do->headimg = 'http://thirdwx.qlogo.cn/mmopen/vi_32/drJfvibQyCeGpcnjSUvu0NbsSTjfqQdbDBcDFav23LX1eCt1lp8aMSJSjDOmo6e14ZobvLYn4frJ2J6IPLObIWw/132';
        $s_meal_user_do->unionid = '';
        $s_meal_user_do->mobile = '18867792018';
        $accessToken = md5(create_guid());
        $expiredTime = 7200;
        $time = time();
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$accessToken]);
        $apiAccessTokenCache->save($s_meal_user_do,$expiredTime);
        //扫码的有效期为1天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token'=>$refreshToken]);
        $apiRefreshTokenCache->save($s_meal_user_do,3600 * 24);

        //用户信息
        $s_meal_user_do->appid = '';
        $data['user_info'] = $s_meal_user_do;

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime];
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 获取分享签名参数
     */
    public function jssdk()
    {
        $rules = [
            ['field' => 'url', 'label' => '页面URL', 'rules' => 'trim']
        ];
        $this->check->check_ajax_form($rules, true);
        $f_data = $this->form_data($rules);

        try{
            $wxConfig = $this->_getGzhConfig();
            $input = [
                'aid' => $this->aid,
                'app_id' => $wxConfig['appid'],
                'app_secret' => $wxConfig['app_secret'],
                'login_type' => isset($wxConfig['login_type']) ? $wxConfig['login_type'] : 0,
                'authorizer_access_token' => isset($wxConfig['authorizer_access_token']) ? $wxConfig['authorizer_access_token'] : ''
            ];

            $wxJsSdk = new ci_jssdk($input);
            $data['signPackage'] = $wxJsSdk->getSignPackage($f_data['url']);

            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }catch(Exception $e){
            $this->json_do->set_error('005', 'JSSDK获取失败');
        }

    }
}
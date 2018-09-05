<?php

/**
 * @Author: binghe
 * @Date:   2018-07-05 09:43:49
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 16:12:57
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;

/**
 * Passport
 */
class Passport extends xhcity_controller
{
    /**
     * 小程序登录
     * @return [type] [description]
     */
    public function xcx_login()
    {
        //模拟登录
        if (in_array(ENVIRONMENT, ['development'])) {
            $this->_modeLogin();
        }

        $rules = [
            ['field' => 'code', 'label' => 'code', 'rules' => 'trim|required'],
//            ['field' => 'encryptedData', 'label' => '密文串', 'rules' => 'trim|required'],
//            ['field' => 'iv', 'label' => '加密初始量', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $xcx_inc = &inc_config('xcx_hcity');
        //1.获取session_key
        try {
            $xcx_sdk = new xcx_sdk;
            $input['code'] = $fdata['code'];
            $input['app_id'] = $xcx_inc['app_id'];
            $input['secret'] = $xcx_inc['app_secret'];
            $response = $xcx_sdk->secretCodeLogin($input);
        } catch (Exception $e) {
            $this->json_do->set_error('005-1', $e->getMessage());
        }
        //2.获取OpenID和UID的绑定关系
        $hcityUserWxbindDao = HcityUserWxbindDao::i();
        $mHcityUserWxbind = $hcityUserWxbindDao->getOne(['app_id' => $xcx_inc['app_id'], 'open_id' => $response['openid'], 'type' => 0]);

        //3.用户登录
        $s_xhcity_user_do = new s_xhcity_user_do;
        $s_xhcity_user_do->appid = $xcx_inc['app_id'];
        $type = 0;//0游客,1正式用户
        //3.1 正式用户登录
        if ($mHcityUserWxbind) {

            $hcityUserDao = HcityUserDao::i();
            $mHcityUser = $hcityUserDao->getOne(['id' => $mHcityUserWxbind->uid]);
            if ($mHcityUser) {
                $s_xhcity_user_do->uid = $mHcityUser->id;
                $s_xhcity_user_do->mobile = $mHcityUser->mobile;
                $s_xhcity_user_do->openid = $mHcityUserWxbind->open_id;
                $s_xhcity_user_do->nickname = $mHcityUser->username;
                $s_xhcity_user_do->headimg = $mHcityUser->img;
                $s_xhcity_user_do->unionid = $mHcityUserWxbind->union_id;
            } else {
                $this->json_do->set_error('005', '关联账号出错');
            }
            $type = 1;
        } else//游客
        {
            //获取用户信息
//            $xd = new xcx_decrypt($xcx_inc['app_id'], $response['session_key']);
//            $errCode = $xd->decryptData($fdata['encryptedData'], $fdata['iv'], $userInfo);
//            if ($errCode != 0) {
//                log_message('error', __METHOD__ . ' => xcx_decrypt解密失败 ' . $errCode);
//                $this->json_do->set_error('005', '获取用户信息失败');
//            } else {
//                $userInfo = json_decode($userInfo, true);
//
//                $s_xhcity_user_do->openid = $response['openid'];
//                $s_xhcity_user_do->nickname = strip_emoji($userInfo['nickName']);
//                $s_xhcity_user_do->headimg = $userInfo['avatarUrl'];
//                isset($response['unionid']) && $s_xhcity_user_do->unionid = $response['unionid'];
//            }

            $s_xhcity_user_do->openid = $response['openid'];
            $s_xhcity_user_do->nickname = '';
            $s_xhcity_user_do->headimg = '';
            isset($response['unionid']) && $s_xhcity_user_do->unionid = $response['unionid'];

        }
        //4.登录成功
        $time = time();
        //4.1设置访问令牌access_token
        $accessToken = md5(create_guid());
        $expiredTime = 7200;
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
        $apiAccessTokenCache->save($s_xhcity_user_do, $expiredTime);


        //4.2设置刷新令牌refresh_token　有效期1天
        $refreshToken = md5(create_guid());
        $refreshExpiredTime = 3600 * 24;
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $apiRefreshTokenCache->save($s_xhcity_user_do, $refreshExpiredTime);

        $data['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime];
        $data['refresh_token'] = ['value' => $accessToken, 'expire_time' => $time + $refreshExpiredTime];
        $data['type'] = $type;    //用户标识-0游客,1正式

        $this->json_do->set_data($data);
        $this->json_do->set_msg('登录成功');
        $this->json_do->out_put();


    }

    /**
     * 模拟登录
     * @return [type] [description]
     */
    private function _modeLogin()
    {
        $s_xhcity_user_do = new s_xhcity_user_do();
        //正式用户
        $type = 1;
        $s_xhcity_user_do->uid = 6;
        $s_xhcity_user_do->mobile = '18530640571';

        // 临时用户
        $type = 0;
//        $s_xhcity_user_do->uid = 0;
        $s_xhcity_user_do->appid = 'wxappid123';
        $s_xhcity_user_do->nickname = '金凯';
        $s_xhcity_user_do->openid = 'ox5c65GFkb7OiR9QChuo14YfhCbc';
        $s_xhcity_user_do->headimg = 'http://thirdwx.qlogo.cn/mmopen/vi_32/drJfvibQyCeGpcnjSUvu0NbsSTjfqQdbDBcDFav23LX1eCt1lp8aMSJSjDOmo6e14ZobvLYn4frJ2J6IPLObIWw/132';
        $s_xhcity_user_do->session_key = 'HyVFkGl5F5OQWJZZaNzBBg==';
        $s_xhcity_user_do->unionid = '';
        $accessToken = md5(create_guid());
        $expiredTime = 7200;
        $time = time();
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
        $apiAccessTokenCache->save($s_xhcity_user_do, $expiredTime);


        //有效期为1天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $apiRefreshTokenCache->save($s_xhcity_user_do, 3600 * 24);
        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime, 'type' => $type];

        $this->json_do->set_data($data);
        $this->json_do->set_msg('登录成功');
        $this->json_do->out_put();
    }


    public function test_login()
    {
        $this->_modeLogin();
    }
}
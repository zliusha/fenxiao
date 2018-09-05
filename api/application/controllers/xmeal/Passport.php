<?php
/**
 * @Author: binghe
 * @Date:   2017-12-26 14:10:59
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 16:12:38
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;

use Service\DbFrame\DataBase\WmMainDbModels\XcxAppDao;
/**
 * passport
 */
class Passport extends xmeal_controller
{
    /**
     * 小程序登录
     * @return [type] [description]
     */
    public function xcx_login()
    {
        $rules = [
            ['field' => 'code', 'label' => 'code', 'rules' => 'trim|required'],
            ['field' => 'encryptedData', 'label' => '密文串', 'rules' => 'trim|required'],
            ['field' => 'iv', 'label' => '加密初始量', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        if (in_array(ENVIRONMENT, ['development'])) {
            // $this->_modeLogin();
            try {
                //微信第三方登录接口
                $xcx_sdk = new xcx_sdk;
                $input['code'] = $fdata['code'];
                $input['app_id'] = $this->app_id;

                $xcxAppDao = XcxAppDao::i();
                $m_xcx_app = $xcxAppDao->getOne(['aid' => $this->aid, 'app_id' => $this->app_id], 'app_secreat');
                if (!$m_xcx_app->app_secreat) {
                    // throw new Exception('CI_WXPAY:小程序secret配置错误');
                    $this->json_do->set_error('005', '小程序secret配置错误');
                }
                $input['secret'] = $m_xcx_app->app_secreat;
                $response = $xcx_sdk->secretCodeLogin($input);
            } catch (Exception $e) {
                $this->json_do->set_error('005-1', $e->getMessage());
            }
        } else {
            try {
                $scrm_sdk = new scrm_sdk;
                $params['visit_id'] = $this->visit_id;
                $params['app_id'] = $this->app_id;
                $json = $scrm_sdk->getXcxInfo($params);
                //微信第三方登录接口
                $xcx_sdk = new xcx_sdk;
                $input['code'] = $fdata['code'];
                $input['component_appid'] = $json['data']['component_appid'];
                $input['app_id'] = $this->app_id;
                $input['component_access_token'] = $json['data']['component_access_token'];
                $response = $xcx_sdk->codeLogin($input);
            } catch (Exception $e) {
                $this->json_do->set_error('005-1', $e->getMessage());
            }
        }

        // $appid = 'wx4f4bc4dec97d474b';
        // $sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';

        // $encryptedData = "CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
        //         QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
        //         9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
        //         3hVbJSRgv+4lGOETKUQz6OYStslQ142d
        //         NCuabNPGBzlooOmB231qMM85d2/fV6Ch
        //         evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
        //         /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
        //         u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
        //         /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
        //         8LOddcQhULW4ucetDf96JcR3g0gfRK4P
        //         C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
        //         6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
        //         /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
        //         lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
        //         oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
        //         20f0a04COwfneQAGGwd5oa+T8yO5hzuy
        //         Db/XcxxmK01EpqOyuxINew==";

        // $iv = 'r7BXXKkLb8qrSNn05n0qiA==';

        // {
        //     "openId": "oGZUI0egBJY1zhBYw2KhdUfwVJJE",
        //     "nickName": "Band",
        //     "gender": 1,
        //     "language": "zh_CN",
        //     "city": "Guangzhou",
        //     "province": "Guangdong",
        //     "country": "CN",
        //     "avatarUrl": "http://wx.qlogo.cn/mmopen/vi_32/aSKcBBPpibyKNicHNTMM0qJVh8Kjgiak2AHWr8MHM4WgMEm7GFhsf8OYrySdbvAMvTsw3mo8ibKicsnfN5pRjl1p8HQ/0",
        //     "unionId": "ocMvos6NjeKLIBqg5Mr9QjxrP1FA",
        //     "watermark": {
        //         "timestamp": 1477314187,
        //         "appid": "wx4f4bc4dec97d474b"
        //     }
        // }

        $xd = new xcx_decrypt($this->app_id, $response['session_key']);
        $errCode = $xd->decryptData($fdata['encryptedData'], $fdata['iv'], $userInfo);

        if ($errCode != 0) {
            log_message('error', __METHOD__ . ' => xcx_decrypt解密失败 ' . $errCode);
            $this->json_do->set_error('005', '获取用户信息失败');
        } else {
            $userInfo = json_decode($userInfo, true);
        }

        //用户登录
        $s_xmeal_user_do = new s_xmeal_user_do;
        $s_xmeal_user_do->openid = $response['openid'];
        $s_xmeal_user_do->nickname = strip_emoji($userInfo['nickName']);
        $s_xmeal_user_do->headimg = $userInfo['avatarUrl'];
        $s_xmeal_user_do->appid = $this->app_id;
        $s_xmeal_user_do->session_key = $response['session_key'];
        if (isset($response['unionid'])) {
            $s_xmeal_user_do->unionid = $response['unionid'];
        }

        $accessToken = md5(create_guid());

        $expiredTime = 7200;
        $time = time();
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
        $apiAccessTokenCache->save($s_xmeal_user_do, $expiredTime);
        //扫码的有效期为1天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $apiRefreshTokenCache->save($s_xmeal_user_do, 3600 * 24);

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime];
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
        //用户登录
        $s_xmeal_user_do = new s_xmeal_user_do;
        $s_xmeal_user_do->openid = 'oqLrov469e_2Y6nettB24x0YDttI';
        $s_xmeal_user_do->nickname = '模拟用户';
        $s_xmeal_user_do->headimg = 'http://thirdwx.qlogo.cn/mmopen/vi_32/drJfvibQyCeGpcnjSUvu0NbsSTjfqQdbDBcDFav23LX1eCt1lp8aMSJSjDOmo6e14ZobvLYn4frJ2J6IPLObIWw/132';
        $s_xmeal_user_do->appid = 'wx_aaaaaaa';
        $s_xmeal_user_do->session_key = 'HyVFkGl5F5OQWJZZaNzBBg==';
        $s_xmeal_user_do->unionid = '';

        $accessToken = md5(create_guid());
        $expiredTime = 7200;
        $time = time();
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
        $apiAccessTokenCache->save($s_xmeal_user_do, $expiredTime);
        //扫码的有效期为1天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $apiRefreshTokenCache->save($s_xmeal_user_do, 3600 * 24);

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime, 'type' => 1];
        $this->json_do->set_data($data);
        $this->json_do->set_msg('登录成功');
        $this->json_do->out_put();
    }
}

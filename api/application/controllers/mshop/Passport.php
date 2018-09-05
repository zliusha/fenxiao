<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 11:46:41
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:36:45
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;
use Service\Cache\WmGzhConfigCache;
use Service\Sdk\WxOAuthSdk;

use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserWxbindDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
/**
 * 到店h5登录
 */

class Passport extends mshop_controller
{
    /**
     * 正式获取登录地址
     */
    public function wx_codeurl()
    {
        $rules = [
            ['field' => 'redirect_url', 'label' => '微信回调地址', 'rules' => 'trim|required|preg_key[URL]'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $wxOAuthSdk = new WxOAuthSdk($this->_getGzhConfig());

        $url = $wxOAuthSdk->getOauthUrlForCode(urlencode($fdata['redirect_url']));
        $data['url'] = $url;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 正式code登录
     */
    public function wx_codelogin()
    {
        $rules = [
            ['field' => 'code', 'label' => '微信code', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $wxConfig = $this->_getGzhConfig();
            $wxOAuthSdk = new WxOAuthSdk($wxConfig);
            $openid = $wxOAuthSdk->getOpenidFromMp($fdata['code']);
            $wxInfo = $wxOAuthSdk->GetUserInfo();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

        $wmUserWxbindDao = WmUserWxbindDao::i($this->aid);
        $wxbind = $wmUserWxbindDao->getOne(['open_id' => $openid, 'type' => 'weixin', 'aid' => $this->aid]);
        $wm_user_bll = new wm_user_bll;
        $needBindMobile = false;
        if ($wxbind) {
            // 获取用户信息
            $wmUserDao = WmUserDao::i($this->aid);
            $user = $wmUserDao->getOne(['id' => $wxbind->uid, 'aid' => $this->aid]);
            if (!$user) {
                log_message('error', __METHOD__ . ' 用户登录失败 uid=>' . $wxbind->uid . ' openid=>' . $openid);
                $this->json_do->set_error('005', '登录失败,用户不存在');
            }
            $s_user = $wm_user_bll->get_s_user($user, $wxbind);
        } else //游客
        {
            $needBindMobile = true;
            $s_user = $wm_user_bll->get_visitor_s_user($wxInfo);
        }
        //临时
        $s_user->appid = $wxConfig['appid'];
        //保存登录信息
        $accessToken = md5(create_guid());
        $time = time();
        $expiredTime = 7200;
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
        $apiAccessTokenCache->save($s_user, $expiredTime);
        //扫码的有效期为7天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $apiRefreshTokenCache->save($s_user, 3600 * 24 * 7);

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime];
        $data['need_bind_mobile'] = $needBindMobile;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 获取公众号配置
     */
    private function _getGzhConfig()
    {
        $wmGzhConfigCache = new WmGzhConfigCache(['aid' => $this->aid]);
        $config = $wmGzhConfigCache->getDataASNX();
        $wxConfig['login_type'] = (int) $config->login_type;
        $wxConfig['appid'] = $config->app_id;
        $wxConfig['app_secret'] = $config->app_secret;
        //开启第三方授权登录
        if ($wxConfig['login_type'] == 1) {
            $scrm_sdk = new scrm_sdk;
            $result = $scrm_sdk->getGzhInfo(['visit_id' => $this->visit_id]);
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
    public function mode_wxlogin()
    {
        if (in_array(ENVIRONMENT, ['production'])) {
            $this->json_do->set_error('008');
        }

        $s_wm_user_do = new s_wm_user_do;
        $s_wm_user_do->uid = 1231;
        $s_wm_user_do->openid = 'oqLrov_LlipBffM7Fdg_s1ZUfYQc';
        $s_wm_user_do->mobile = '18258283897';
        $s_wm_user_do->nickname = '寒夜';
        $s_wm_user_do->img = 'http://oydp172vs.bkt.clouddn.com/afs_pic/1516590037230_1155.png';
        $s_wm_user_do->unionid = '';
        $s_wm_user_do->appid = 'wx8c7be78b29e147da';
        $accessToken = md5(create_guid());
        $expiredTime = 7200;
        $time = time();
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $accessToken]);
        $apiAccessTokenCache->save($s_wm_user_do, $expiredTime);
        //扫码的有效期为7天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $apiRefreshTokenCache->save($s_wm_user_do, 3600 * 24 * 7);

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime];
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 绑定手机号
     */
    public function bind_mobile()
    {
        $rules = [
            ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]'],
        ];
        $this->check->check_ajax_form($rules, true);
        $f_data = $this->form_data($rules);

        $oldAccessToken = $this->input->get_request_header('Access-Token');
        if (empty($oldAccessToken)) {
            $this->json_do->set_error('001', 'no access_token');
        }

        //1获取登录信息
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $oldAccessToken]);
        $s_user = $apiAccessTokenCache->get();
        if (empty($s_user)) {
            $this->json_do->set_error('004-1');
        }

        $this->s_user = $s_user;
        //验证手机验证码
        $mainMobileCodeDao = MainMobileCodeDao::i();
        $model = $mainMobileCodeDao->getNormalCode($f_data['mobile']);

        if (!$model || $model->code != $f_data['mobile_code']) {
            $this->json_do->set_error('005', '验证码错误');
        }
        $wmUserDao = WmUserDao::i($this->aid);
        $wmUserWxbindDao = WmUserWxbindDao::i($this->aid);

        if ($this->s_user->uid == 0) {
            $m_user = $wmUserDao->getOne(['mobile' => $f_data['mobile'], 'aid' => $this->aid]);
            if (!$m_user) {
                $user = [
                    'username' => $this->s_user->nickname,
                    'mobile' => $f_data['mobile'],
                    'password' => '',
                    'img' => $this->s_user->img,
                    'sex' => 0,
                    'birth_day' => '',
                    'now_address' => '',
                    'home_address' => '',
                    'status' => 0,
                    'reg_ip' => get_ip(),
                    'reg_address' => '',
                    'login_count' => 1,
                    'login_time' => time(),
                    'login_ip' => get_ip(),
                    'login_error_count' => 0,
                    'login_limit_time' => 0,
                    'aid' => $this->aid,
                ];
                //创建新用户
                $uid = $wmUserDao->create($user);
                if (empty($uid)) {
                    $this->json_do->set_error('004', '创建账号失败，请稍候重试');
                }

            } else {
                $uid = $m_user->id;
            }

            $wxbind = [
                'open_id' => $this->s_user->openid,
                'union_id' => $this->s_user->unionid,
                'type' => 'weixin',
                'uid' => $uid,
                'aid' => $this->aid,
            ];
            //openid关联用户
            $wmUserWxbindDao->create($wxbind);
        } else {
            // 兼容老数据，使用老方式绑定手机号
            $m_user = $wmUserDao->getOne(['mobile' => $f_data['mobile'], 'aid' => $this->aid]);

            if ($m_user && $m_user->id != $this->s_user->uid) {
                $this->json_do->set_error('005', '手机号码已绑定');
            }
            $wmUserDao->update(['mobile' => $f_data['mobile']], ['id' => $this->s_user->uid,'aid' => $this->aid]);
        }
        //使用openid重新登录
        $mNewWxbind = $wmUserWxbindDao->getOne(['aid' => $this->aid, 'open_id' => $this->s_user->openid]);
        if (!$mNewWxbind) {
            $this->json_do->set_error('004', '微信关联用户不存在');
        }

        $mNewUser = $wmUserDao->getOne(['aid' => $this->aid, 'id' => $mNewWxbind->uid]);
        if (!$mNewUser) {
            $this->json_do->set_error('004', '用户不存在');
        }

        $wm_user_bll = new wm_user_bll;
        $newSWmUser = $wm_user_bll->get_s_user($mNewUser, $mNewWxbind);
        $newAccessToken = md5(create_guid());
        $expiredTime = 7200;
        $time = time();
        //删除老token
        $apiAccessTokenCache->delete();
        //保存新token
        $newApiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $newAccessToken]);
        $newApiAccessTokenCache->save($newSWmUser, $expiredTime);
        //扫码的有效期为7天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $apiRefreshTokenCache->save($newSWmUser, 3600 * 24 * 7);

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $newAccessToken, 'expire_time' => $time + $expiredTime];
        $this->json_do->set_data($data);
        $this->json_do->set_msg('绑定手机号成功');
        $this->json_do->out_put();
    }

    /**
     * 获取分享签名参数
     */
    public function jssdk()
    {
        $rules = [
            ['field' => 'url', 'label' => '页面URL', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rules, true);
        $f_data = $this->form_data($rules);

        try
        {
            $wxConfig = $this->_getGzhConfig();

            $input = [
                'aid' => $this->aid,
                'app_id' => $wxConfig['appid'],
                'app_secret' => $wxConfig['app_secret'],
                'login_type' => $wxConfig['login_type'],
                'authorizer_access_token' => isset($wxConfig['authorizer_access_token']) ? $wxConfig['authorizer_access_token'] : '',
            ];

            $wxJsSdk = new ci_jssdk($input);
            $data['signPackage'] = $wxJsSdk->getSignPackage($f_data['url']);

            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', 'JSSDK获取失败');
        }

    }

}

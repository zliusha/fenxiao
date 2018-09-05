<?php
/**
 * @Author: binghe
 * @Date:   2017-12-26 14:10:59
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:45
 */
use Service\Cache\ApiAccessTokenCache;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserWxbindDao;

/**
 * passport
 */
class Passport extends xcx_controller
{
    /**
     * 小程序登录
     * @return [type] [description]
     */
    public function xcx_login()
    {

        if (in_array(ENVIRONMENT, ['development'])) {
            $this->_modeLogin();
        }

        $rules = [
            ['field' => 'code', 'label' => 'code', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $scrm_sdk = new scrm_sdk;
            $params['visit_id'] = $this->visit_id;
            $params['appid'] = $this->app_id;
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
        // 获取OpenID和UID的绑定关系
        $wmUserWxbindDao = WmUserWxbindDao::i($this->aid);
        $wxbind = $wmUserWxbindDao->getOne(['aid' => $this->aid, 'open_id' => $response['openid'], 'type' => 'xcx']);

        //游客配置
        $uid = 0;
        $mobile = '';
        $type = 0; //type = 0 临时用户,1正式用户
        //已经直接登录，未存在生成账号
        if ($wxbind) {
            $uid = $wxbind->uid;
            $type = 1;
            $wmUserDao = WmUserDao::i($this->aid);
            $m_wm_user = $wmUserDao->getOne(['aid' => $this->aid, 'id' => $uid]);
            if ($m_wm_user) {
                $mobile = $m_wm_user->mobile;
            } else {
                $this->json_do->set_error('005', '关联账号出错');
            }

        }

        //用户登录
        $s_xcx_user_do = new s_xcx_user_do;
        $s_xcx_user_do->aid = $this->aid;
        $s_xcx_user_do->uid = $uid;
        $s_xcx_user_do->mobile = $mobile;
        $s_xcx_user_do->ext['open_id'] = $response['openid'];
        $s_xcx_user_do->ext['app_id'] = $this->app_id;
        $s_xcx_user_do->ext['session_key'] = $response['session_key'];
        $accessToken = md5(create_guid());

        $expiredTime = 7200;
        $time = time();
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$accessToken]);
        $apiAccessTokenCache->save($s_xcx_user_do,$expiredTime);
        $data1['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime, 'type' => $type];
        $this->json_do->set_data($data1);
        $this->json_do->set_msg('登录成功');
        $this->json_do->out_put();

    }
    /**
     * 模拟登录
     * @return [type] [description]
     */
    private function _modeLogin()
    {
        $s_xcx_user_do = new s_xcx_user_do;
        $s_xcx_user_do->aid = 1226;
        //正式用户
        $type = 1;
        $s_xcx_user_do->uid = 1227;
        $s_xcx_user_do->mobile = '18358588359';
        // 临时用户
        // $type = 0;
        // $s_xcx_user_do->uid = 0;
        // $s_xcx_user_do->mobile='';

        $s_xcx_user_do->ext['open_id'] = 'oqLrov469e_2Y6nettB24x0YDttI';
        $s_xcx_user_do->ext['app_id'] = 'wx_aaaaaaa';
        $s_xcx_user_do->ext['session_key'] = 'HyVFkGl5F5OQWJZZaNzBBg==';
        $accessToken = md5(create_guid());
        $expiredTime = 7200;
        $time = time();
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$accessToken]);
        $apiAccessTokenCache->save($s_xcx_user_do,$expiredTime);
        $data1['access_token'] = ['value' => $accessToken, 'expire_time' => $time + $expiredTime, 'type' => $type];
        $this->json_do->set_data($data1);
        $this->json_do->set_msg('登录成功');
        $this->json_do->out_put();
    }
}

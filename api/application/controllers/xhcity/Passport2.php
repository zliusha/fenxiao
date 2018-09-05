<?php

/**
 * @Author: binghe
 * @Date:   2018-07-05 17:38:24
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 15:42:27
 */
use Service\Cache\Hcity\HcityUserCache;
use Service\Bll\Hcity\AccountBll;
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;

/**
 * 临时用户登录
 */
class Passport2 extends xhcity_controller
{

    /**
     * 游客绑定手机号
     * @return [type] [description]
     */
    public function bind_mobile()
    {
        if ($this->s_user->uid != 0)
            $this->json_do->set_error('004', '不是临时用户');
        $rules = [
            ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]'],
            ['field' => 'inviter_code', 'label' => '邀请码', 'rules' => 'trim'],
            ['field' => 'username', 'label' => '用户名', 'rules' => 'trim|required'],
            ['field' => 'headimg', 'label' => '头像', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        //1.验证码校验
        $mainMobileCodeDao = MainMobileCodeDao::i();
        $m_main_mobile_code = $mainMobileCodeDao->getNormalCode($fdata['mobile']);
        if (!$m_main_mobile_code || $m_main_mobile_code->code != $fdata['mobile_code'])
            $this->json_do->set_error('004', '验证码错误');
        //2.验证手机号是否存在
        $hcityUserDao = HcityUserDao::i();
        $hcityUserWxbindDao = HcityUserWxbindDao::i();
        $mHcityUser = $hcityUserDao->getOne(['mobile' => $fdata['mobile']]);
        //3.已有账号直接绑定,没有账号注册加绑定

        if ($mHcityUser) {
            $mHcityUserWxbind = $hcityUserWxbindDao->getOne(['uid' => $mHcityUser->id]);
            if ($mHcityUserWxbind) {
                $this->json_do->set_error('004', '手机号已绑定微信号');
            }
            //3.1绑定已有的账号
            $bindData['uid'] = $mHcityUser->id;
            $bindData['app_id'] = $this->s_user->appid;
            $bindData['open_id'] = $this->s_user->openid;
            $bindData['union_id'] = $this->s_user->unionid;
            $bindData['type'] = 0;//小程序
            $hcityUserWxbindDao->create($bindData);
            $uid = $mHcityUser->id;
            //更新会员信息
            $userUpdateData = [
                'last_login_time' => time(),
                'username' => strip_emoji($fdata['username']),
                'img' => $fdata['headimg'],
            ];
            HcityUserDao::i()->update($userUpdateData, ['id' => $uid]);
        } else {
            $idCode = '';
            $generateCount = 1;
            //个人邀请码，设置6位
            $idCodeLength = 6;
            while (true) {
                if ($generateCount >= 20) {
                    $idCodeLength++;
                    $generateCount = 1;
                }
                $idCode = generate_id_code($idCodeLength);
                $existUser = HcityUserDao::i()->getOne(['id_code' => $idCode], 'id');
                if (empty($existUser)) {
                    break;
                }
                $generateCount++;
            }
            //3.2.1 注册账号
            $userData['username'] = strip_emoji($fdata['username']);
            $userData['mobile'] = $fdata['mobile'];
            $userData['img'] = $fdata['headimg'];
            $userData['id_code'] = $idCode;//生成邀请码
            $userData['level'] = 1;//1普通会员,2网点合伙人,3城市合伙人
            $userData['last_login_time'] = time();
            $userData['shard_node'] = '0-0-0';
            $uid = $hcityUserDao->create($userData);
            //3.2.2 绑定账号
            $bindData['uid'] = $uid;
            $bindData['app_id'] = $this->s_user->appid;
            $bindData['open_id'] = $this->s_user->openid;
            $bindData['union_id'] = $this->s_user->unionid;
            $bindData['type'] = 0;
            $hcityUserWxbindDao->create($bindData);

            //绑定邀请码关系
            if (!empty($fdata['inviter_code'])) {
                try {
                    (new AccountBll())->setInviterByCode($uid, $fdata['inviter_code']);
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '绑定邀请码关系失败UID:' . $this->s_user->uid . '-inviter_code:' . $fdata['inviter_code']);
                }
            }
        }

        //4.重新登录
        $this->s_user->uid = $uid;
        $this->s_user->mobile = $fdata['mobile'];
        $this->s_user->nickname = strip_emoji($fdata['username']);
        $this->s_user->headimg = $fdata['headimg'];

        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();

        //4.1删除原登录信息
        $oldAccessToken = $this->input->get_request_header('Access-Token');
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $oldAccessToken]);
        $apiAccessTokenCache->delete();
        //4.2重新生成登录信息
        $newAccessToken = md5(create_guid());
        $newApiAccessTokenCache = new ApiAccessTokenCache(['access_token' => $newAccessToken]);
        $expiredTime = 7200;
        $newApiAccessTokenCache->save($this->s_user, $expiredTime);

        //有效期为1天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $refreshExpiredTime = 3600 * 24;
        $apiRefreshTokenCache->save($this->s_user, $refreshExpiredTime);
        $time = time();
        //type = 0 临时用户,1正式用户
        $outData['access_token'] = ['value' => $newAccessToken, 'expire_time' => $time + $expiredTime];
        $outData['refresh_token'] = ['value' => $refreshToken, 'expire_time' => $time + $refreshExpiredTime];
        $outData['type'] = 1;    //用户标识-0游客,1正式
        $this->json_do->set_data($outData);
        $this->json_do->out_put();
    }
}
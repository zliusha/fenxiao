<?php

/**
 * @Author: binghe
 * @Date:   2018-07-12 13:55:00
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-12 14:05:41
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;

class Home_api extends base_controller
{
    /**
     * 获取api token
     * @return [type] [description]
     */
    public function get_api_token()
    {
        $expiredTime = 3600 * 2;
        //cookie保存登录信息保存7天
        $input['access_token'] = md5(create_guid());
        $apiAccessTokenCache = new ApiAccessTokenCache($input);
        $apiAccessTokenCache->save($this->s_user, $expiredTime);

        //t1登录缓存为7
        $rExpiredTime = 3600 * 24 * 7;
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token' => $refreshToken]);
        $apiRefreshTokenCache->save($this->s_user, $rExpiredTime);

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $input['access_token'], 'expire_time' => time() + $rExpiredTime];
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
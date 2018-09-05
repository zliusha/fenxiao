<?php
/**
 * @Author: binghe
 * @Date:   2018-04-12 15:38:12
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-15 17:20:08
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\Bll\Main\MainCompanyAccountBll;
/**
* passport
*/
class Passport extends auth_controller
{
    /**
     * saas　令牌登录
     * @return [type] [description]
     */
    public function saas_token_login()
    {
        $rule = [
            ['field' => 'token', 'label' => '令牌', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $apiAccessTokenCache = new ApiAccessTokenCache($fdata['token']);
        $sUser = $apiAccessTokenCache->get();
        if(!$sUser || get_class($sUser) != 's_saas_user_do')
            $this->json_do->out_put('004','令牌失效');
        $mainCompanyAccountBll = new MainCompanyAccountBll;
        $data = $mainCompanyAccountBll->getSaasTokenByModel($sUser);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * saas 账号密码登录
     * @return [type] [description]
     */
    public function saas_login()
    {
        //验证
        $rule = [
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|min_length[6]|max_length[25]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $mainCompanyAccountBll = new MainCompanyAccountBll;
        $data = $mainCompanyAccountBll->getSaasToken($fdata['mobile'],$fdata['password']);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 用户退出
     */
    public function login_out()
    {
        $accessToken = $this->input->get_request_header('Access-Token');
        if(empty($accessToken))
            $this->json_do->set_error('001','no access_token');
        $input['access_token'] = $accessToken;
        $apiAccessTokenCache = new ApiAccessTokenCache($input);
        $apiAccessTokenCache->delete();
        $this->json_do->out_put();
    }
    /*
     * 刷新token 此接口需要url参数　?access_token={$access_token}
     */
    public function refresh_token()
    {
        
        //验证
        $rule = [
            ['field' => 'refresh_token', 'label' => '令牌', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        //1删除原登录信息
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token'=>$fdata['refresh_token']]);
        $s_user = $apiRefreshTokenCache->get();
        if(empty($s_user))
            $this->json_do->set_error('004-1');
        $time = time();
        //2重新生成登录信息
        $newAccessToken = md5(create_guid());
        $newApiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$newAccessToken]);
        $expiredTime = 7200;
        $newApiAccessTokenCache->save($s_user,$expiredTime);

        $data['access_token'] = ['value'=>$newAccessToken,'expire_time'=>$time+$expiredTime];
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
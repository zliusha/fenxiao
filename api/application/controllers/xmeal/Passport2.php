<?php
/**
 * @Author: binghe
 * @Date:   2018-01-19 15:13:18
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:47
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;

use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserWxbindDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
/**
* 临时用户登录
*/
class Passport2 extends xmeal_controller
{
    
    /**
     * 游客绑定手机号
     * @return [type] [description]
     */
    public function bind_mobile()
    {
        if($this->s_user->uid != 0)
            $this->json_do->set_error('004','不是临时用户');
        $rules = [
            ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        //1.验证码校验
        $mainMobileCodeDao = MainMobileCodeDao::i();
        $m_main_mobile_code=$mainMobileCodeDao->getNormalCode($fdata['mobile']);
        if(!$m_main_mobile_code || $m_main_mobile_code->code!=$fdata['mobile_code'])
            $this->json_do->set_error('004','验证码错误');
        //2.验证手机号是否存在
        $wmUserDao = WmUserDao::i($this->aid);
        $wmUserWxbindDao = WmUserWxbindDao::i($this->aid);
        $m_wm_user = $wmUserDao->getOne(['aid'=>$this->aid,'mobile'=>$fdata['mobile']]);
        //3.已有账号直接绑定,没有账号注册加绑定
        
        if($m_wm_user)
        {
            //3.1绑定已有的账号
            $bindData['uid']=$m_wm_user->id;
            $bindData['aid']=$m_wm_user->aid;
            $bindData['open_id']=$this->s_user->openid;
            $bindData['union_id']='';
            $bindData['type']='xcx';
            $wmUserWxbindDao->create($bindData);
            $uid = $m_wm_user->id;
        }
        else
        {
            //3.2.1 注册账号
            $userData['aid']=$this->aid;
            $userData['mobile']=$fdata['mobile'];
            $uid=$wmUserDao->create($userData);
            //3.2.2 绑定账号
            $bindData['uid']=$uid;
            $bindData['aid']=$this->aid;
            $bindData['open_id']=$this->s_user->openid;
            $bindData['union_id']='';
            $bindData['type']='xcx';
            $wmUserWxbindDao->create($bindData);

        }
        //3.注册新账号,绑定关联
        $userData['mobile']=$fdata['mobile'];
        $userData['aid']=$this->aid;

        //4.重新登录
        $this->s_user->uid = $uid;
        $this->s_user->mobile = $fdata['mobile'];

        //4.1删除原登录信息
        $oldAccessToken = $this->input->get('access_token');
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$oldAccessToken]);
        $apiAccessTokenCache->delete();
        //4.2重新生成登录信息
        $newAccessToken = md5(create_guid());
        $newApiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$newAccessToken]);
        $expiredTime = 7200;
        $newApiAccessTokenCache->save($this->s_user,$expiredTime);
        $time = time();
        //扫码的有效期为1天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token'=>$refreshToken]);
        $apiRefreshTokenCache->save($this->s_user,3600 * 24);

        $outData['refresh_token'] = $refreshToken;
        //type = 0 临时用户,1正式用户
        $outData['access_token'] = ['value'=>$newAccessToken,'expire_time'=>$time + $expiredTime,'type'=>1];
        $this->json_do->set_data($outData);
        $this->json_do->out_put();
    }
}
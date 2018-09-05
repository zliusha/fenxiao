<?php
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2017/8/22
 * Time: 18:57
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserWxbindDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
class User extends mshop_user_controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 用户信息
     */
    public function info()
    {
        $wmUserDao = WmUserDao::i($this->aid);
        $model = $wmUserDao->getOne(['id' => $this->s_user->uid, 'aid' => $this->aid]);
        if($model)
            $model->img = conver_picurl($model->img);
        if($model && $model->username==null)
            $model->username = '';

        $this->json_do->set_data($model);
        $this->json_do->out_put();
    }

    /**
     * 编辑用户信息
     */
    public function edit()
    {
        $rules = [
            ['field' => 'field', 'label' => '字段', 'rules' => 'trim|required'],
            ['field' => 'value', 'label' => '值', 'rules' => 'required'],
        ];

        $this->check->check_ajax_form($rules, true);

        $f_data = $this->form_data($rules);
        $fields_arr = ['img', 'username'];

        if (!in_array($f_data['field'], $fields_arr)) {
            $this->json_do->set_error('005', '无此字段，保存错误');
        }

        $data = [];
        $wmUserDao = WmUserDao::i($this->aid);

        $data[$f_data['field']] = $f_data['value'];
        if ($wmUserDao->update($data, ['id' => $this->s_user->uid, 'aid' => $this->aid]) !== false) {
            //更新用户信息
            $data = $this->_update_user_info();

            $this->json_do->set_data($data);
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        }

        $this->json_do->set_error('001', '保存失败');
    }

    /**
     * 修改手机号
     */
    public function update_mobile()
    {

        $rules = [
            ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]'],
        ];
        $this->check->check_ajax_form($rules, true);
        $f_data = $this->form_data($rules);

        $wmUserDao = WmUserDao::i($this->aid);
        $mainMobileCodeDao = MainMobileCodeDao::i();

        $model = $mainMobileCodeDao->getNormalCode($f_data['mobile']);
        if (!$model || $model->code != $f_data['mobile_code']) {
            $this->json_do->set_error('005', '验证码错误');
        }

        $m_user = $wmUserDao->getOne(['mobile' => $f_data['mobile'], 'aid' => $this->aid]);

        if ($m_user) {
            $this->json_do->set_error('005', '手机号码已绑定');
        }

        if ($wmUserDao->update(['mobile' => $f_data['mobile']], ['id' => $this->s_user->uid, 'aid' => $this->aid]) !== false) {

            //更新用户信息
            $data = $this->_update_user_info();

            $this->json_do->set_data($data);
            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '修改失败');
        }

    }


    /**
     * 更新用户信息
     */
    private function _update_user_info()
    {
        $oldAccessToken = $this->input->get_request_header('Access-Token');
        if(empty($oldAccessToken))
            $this->json_do->set_error('001','no access_token');
        //1获取登录信息
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$oldAccessToken]);

        $wmUserDao = WmUserDao::i($this->aid);
        $wmUserWxbindDao = WmUserWxbindDao::i($this->aid);

        //使用openid重新登录
        $mNewWxbind = $wmUserWxbindDao->getOne(['aid'=>$this->aid,'open_id'=>$this->s_user->openid]);
        if(!$mNewWxbind)
            $this->json_do->set_error('004','微信关联用户不存在');
        $mNewUser = $wmUserDao->getOne(['aid'=>$this->aid,'id'=>$mNewWxbind->uid]);
        if(!$mNewUser)
            $this->json_do->set_error('004','用户不存在');
        $wm_user_bll = new wm_user_bll;
        $newSWmUser = $wm_user_bll->get_s_user($mNewUser,$mNewWxbind);
        $newAccessToken = md5(create_guid());
        $expiredTime = 7200;
        $time = time();
        //删除老token
        $apiAccessTokenCache->delete();
        //保存新token
        $newApiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$newAccessToken]);
        $newApiAccessTokenCache->save($newSWmUser,$expiredTime);
        //扫码的有效期为7天
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token'=>$refreshToken]);
        $apiRefreshTokenCache->save($newSWmUser,3600 * 24 * 7);

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value' => $newAccessToken, 'expire_time' => $time + $expiredTime];

        return $data;
    }

}

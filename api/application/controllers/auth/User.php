<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2017/8/22
 * Time: 18:57
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
class User extends xcx_user_controller
{
    /**
     * 用户信息
     */
    public function info()
    {
        $wmUserDao = WmUserDao::i($this->aid);
        $model = $wmUserDao->getOne(['id' => $this->s_user->uid, 'aid' => $this->aid]);
        if($model && $model->username==null)
            $model->username = '';
        //$model->img = conver_picurl($model->img);
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

        if (!$this->check->check_form($rules)) {
            $this->json_do->set_error('005', validation_errors());
        }

        $f_data = $this->form_data($rules);
        $fields_arr = ['img', 'username'];

        if (!in_array($f_data['field'], $fields_arr)) {
            $this->json_do->set_error('005', '无此字段，保存错误');
        }

        $data = [];
        $wmUserDao = WmUserDao::i($this->aid);

        $data[$f_data['field']] = $f_data['value'];
        if ($wmUserDao->update($data, ['id' => $this->s_user->uid, 'aid' => $this->aid]) !== false) {
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
          ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]']
        ];
        $this->check->check_ajax_form($rules, true);
        $f_data = $this->form_data($rules);

        $wmUserDao = WmUserDao::i($this->aid);
        $mainMobileCodeDao = MainMobileCodeDao::i();

        $model = $mainMobileCodeDao->getNormalCode($f_data['mobile']);
        if (!$model || $model->code != $f_data['mobile_code']) {
          $this->json_do->set_error('005', '验证码错误');
        }
        $m_account = $wmUserDao->getOne(['mobile' => $f_data['mobile'], 'aid' => $this->aid]);

        if ($m_account) {
            $this->json_do->set_error('005', '手机号码已绑定');
        }

        if ($wmUserDao->update(['mobile' => $f_data['mobile']], ['id' => $this->s_user->uid, 'aid' => $this->aid]) !== false) {

            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '修改失败');
        }

    }

    /**
     * 绑定手机号
     */
    public function bind_mobile()
    {
        $rules = [
          ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|required|preg_key[MOBILE]'],
          ['field' => 'mobile_code', 'label' => '验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]']
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

        if ($m_user && $m_user->id != $this->s_user->uid) {
          $this->json_do->set_error('005', '手机号码已绑定');
        }

        $wmUserDao->update(['mobile' => $f_data['mobile']], ['id' => $this->s_user->uid, 'aid' => $this->aid]);


        $this->json_do->set_msg('修改成功');
        $this->json_do->out_put();
    }

  /**
   * 更新用户头像
   */
    public function check_avatar()
    {
        $rules = [
          ['field' => 'img', 'label' => '用户头像', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);


        $wmUserDao = WmUserDao::i($this->aid);
        $m_wm_user = $wmUserDao->getOne(['aid' => $this->aid, 'id' => $this->s_user->uid]);
        if($m_wm_user && empty($m_wm_user->img))
        {
            $wmUserDao->update(['img'=>$f_data['img']], ['aid' => $this->aid, 'id' => $this->s_user->uid]);
            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        }
    }

}

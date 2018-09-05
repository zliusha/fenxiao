<?php

/**
 * @Author: binghe
 * @Date:   2018-07-21 10:11:57
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-26 16:39:20
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\Bll\Main\MainCompanyAccountBll;
/**
 * account
 */
class User extends dj_controller
{
	
	/**
     * 账户信息
     */
    public function info()
    {
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $model = $mainCompanyAccountDao->getOne(['id'=>$this->s_user->id], 'id,username,img,sex,is_admin');

        try {
          $erp_sdk = new erp_sdk;
          $params[]=$this->s_user->visit_id;
          $params[]=$this->s_user->user_id;
          $res=$erp_sdk->getUserById($params);
        } catch (Exception $e) {
          $this->json_do->set_error('005',$e->getMessage());
        }
        $model->img = conver_picurl($model->img);
        $model->mobile=$res['phone'];
        $model->is_admin = $model->is_admin == 1;
        $this->json_do->set_data($model);
        $this->json_do->out_put();
    }

  /**
   * 单个字段修改
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
        $fields_arr = ['username', 'img', 'sex'];

        if (!in_array($f_data['field'], $fields_arr)) {
          $this->json_do->set_error('005', '无此字段，保存错误');
        }

        //判断username 字段长度
        if($f_data['field'] == 'username' && mb_strlen($f_data['value']) > 20)
        {
           $this->json_do->set_error('005', '字符长度过长');
        }
        $data = [];
        $mainCompanyAccountDao = MainCompanyAccountDao::i();

        $data[$f_data['field']] = $f_data['value'];
        if ($mainCompanyAccountDao->update($data, ['id' => $this->s_user->id]) !== false) {

            $m_main_company_account = $mainCompanyAccountDao->getOne(['id' => $this->s_user->id]);
            if ($m_main_company_account) {
              $mainCompanyAccountBll = new MainCompanyAccountBll();
              $sUser = $mainCompanyAccountBll->getSaasSUserByModel($m_main_company_account); 
              //更新cookie,保存7天
              $c_value=$this->encryption->encrypt(serialize($sUser));
              set_cookie('c_user',$c_value,3600*24*7);
            }

          $this->json_do->set_msg('保存成功');
          $this->json_do->out_put();
        }

        $this->json_do->set_error('001', '保存失败');
    }

    /**
     * 修改密码
     */
    public function update_password()
    {
        $rules = array(
          array('field' => 'old_password', 'label' => '旧密码', 'rules' => 'trim|required|min_length[6]|max_length[25]'),
          array('field' => 'new_password', 'label' => '新密码', 'rules' => 'trim|required|preg_key[PWD]'),
          array('field' => 're_password', 'label' => '重复密码', 'rules' => 'trim|required|preg_key[PWD]|matches[new_password]'),
        );
        if (!$this->check->check_form($rules)) {
          $this->json_do->set_error('005', validation_errors());
        }
        $f_data = $this->form_data($rules);

        try {
          
          $erp_sdk = new erp_sdk;
          $params[]=$this->s_user->visit_id;
          $params[]=$this->s_user->user_id;
          $params[]=['oldPwd'=>$f_data['old_password'],'newPwd'=>$f_data['new_password']];
          $res=$erp_sdk->updateUser($params);
          $this->json_do->set_msg('修改密码成功');
          $this->json_do->out_put();
        } catch (Exception $e) {
          $this->json_do->set_error('005',$e->getMessage());
        }
    }
}
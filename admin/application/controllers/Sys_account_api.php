<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/1/29
 * Time: 13:45
 */
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainSysAccountDao;
class Sys_account_api extends crud_controller
{
  /**
   * 账户信息
   */
    public function info()
    {
        $sysAccountDao = ManageMainSysAccountDao::i();
        $m_sys_account = $sysAccountDao->getOne(['id'=>$this->s_user->id], 'id,username,account,last_login_time,current_login_time,time,is_super_admin,status');

        $data['m_account'] = $m_sys_account;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
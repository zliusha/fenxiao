<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/1/25
 * Time: 14:09
 */
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainSysAccountDao;
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainSysRoleDao;
use Service\DbFrame\DataBase\ManageMainDbModels\ManageMainSysPoweritemDao;

class Passport_api extends crud_controller
{
    public  function login()
    {
        $rules = [
          ['field'=>'account','label'=>'用户名','rules'=>'trim|required'],
          ['field'=>'password','label'=>'密码','rules'=>'trim|required|preg_key[PWD]']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);

        $sysAccountDao = ManageMainSysAccountDao::i();
        $where = ['account'=>$f_data['account'],'password'=>md5($f_data['password'].SECRET_KEY),'status'=>0];
        $m_account = $sysAccountDao->getOne($where);
        if($m_account)
        {
            //更新用户信息
            $data['last_login_ip']=$m_account->current_login_ip;
            $data['last_login_time']=$m_account->current_login_time;
            $data['current_login_time']=time();
            $data['current_login_ip']=$this->input->ip_address();
            $data['login_count']=$m_account->login_count+1;
            $sysAccountDao->update($data,array('id'=>$m_account->id));

            //记录登录信息
            $m_user = new s_user_do();
            $m_user->id = $m_account->id;
            $m_user->account = $m_account->account;
            $m_user->username = $m_account->username;
            $m_user->rid=$m_account->rid;
            $m_user->is_super_admin=$m_account->is_super_admin;
            $power = array();
            $sysPoweritemDao = ManageMainSysPoweritemDao::i();
            $sysRoleDao = ManageMainSysRoleDao::i();
            if($m_account->is_super_admin)
            {
              $power=$sysPoweritemDao->getAll(false,'*','sort desc,id asc');
            }
            else if($m_account->rid)
            {
              $role_where['where_in']=array('id'=>explode(',',$m_account->rid));
              $m_roles=$sysRoleDao->getEntitysByAR($role_where);

              $power_ids=array();
              foreach ($m_roles as $m_role) {
                if($m_role->powercode && $m_role->status==0)
                {
                  $t_ids=explode(',',$m_role->powercode);
                  $power_ids=array_merge($power_ids,$t_ids);
                }
              }
              if($power_ids)
              {   $power_ids=array_unique($power_ids);
                $power_where['where_in']=array('id'=>$power_ids);
                $power_where['field']='*';
                $power_where['order_by']='sort desc,id asc';
                $power=$sysPoweritemDao->getEntitysByAR($power_where);
              }
          }
          $m_user->power = $power;
          //默认2个小时
          $this->session->set_userdata('s_user',$m_user);

          //日志
          $this->slog('用户登录',$m_user->id,$m_account->username,0);

          $this->json_do->set_msg('登录成功');
          $this->json_do->out_put();
        }
        else
        {

          $this->json_do->set_error('004', '用户不存在或密码错误!');

        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/1/25
 * Time: 14:13
 */
class Passport extends crud_controller
{

    function __construct()
    {
      parent::__construct();
    }
    //用户登录,防暴力破解
    public function login()
    {
      //已登录直接跳转
      if($this->s_user)
        redirect('/home/index');
      else
        $this->load->view('main/login');
    }
    //用户注册
    public function register()
    {
      $this->load->view('main/register');
    }

    //安全退出
    public function logout()
    {
        $this->session->unset_userdata('s_user');
        redirect('/passport/login');
    }
}
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Welcome extends CI_Controller
{

    //主题皮肤
    public function index()
    {

        $this->load->view('main/index');
    }

    //首页
    public function sys_default()
    {
        $this->load->view('sys_default', array('username' => $this->session->s_user->username));
    }


}

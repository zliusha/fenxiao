<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2017/8/16
 * Time: 9:58
 */
class User extends base_controller
{
    public function index()
    {
        $this->load->view('main/profile');
    }
}
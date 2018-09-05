<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2017/6/14
 * Time: 13:28
 */
class Home extends CI_Controller
{
    public function error404()
    {
        $this->load->view('404');
    }

    public function index()
    {
    	redirect(MOBILE_URL);
    }
}

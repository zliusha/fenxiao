<?php
/**
 * Created by PhpStorm.
 * User: shaoyu
 * Date: 2018/4/16
 * Time: 11:23
 */

class Elm extends crud_controller
{
    public function index()
    {

        $this->load->view('elm/index');
    }

}
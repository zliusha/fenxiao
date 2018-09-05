<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/31
 * Time: 11:00
 */
class Wm_main_version extends crud_controller
{
    public function index()
    {

        $this->load->view('ydb/main_version/index');
    }

    public function add()
    {

        $this->load->view('ydb/main_version//add');
    }

    public function edit($id=0)
    {

        $this->load->view('ydb/main_version//edit',['id'=>$id]);
    }

    public function detail($id=0)
    {

        $this->load->view('ydb/main_version//detail',['id'=>$id]);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/23
 * Time: 17:32
 */
class Wm_main_xcx_version extends crud_controller
{
    public function index()
    {

        $this->load->view('ydb/main_xcx_version/index');
    }

    public function add()
    {

        $this->load->view('ydb/main_xcx_version//add');
    }

    public function edit($id=0)
    {

        $this->load->view('ydb/main_xcx_version//edit',['id'=>$id]);
    }

    public function detail($id=0)
    {

        $this->load->view('ydb/main_xcx_version//detail',['id'=>$id]);
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 商品管理
 */
class Store_goods extends wm_service_controller
{

    /**
     *商品列表
     */
    public function index()
    {
        $this->load->view('mshop/store_goods/index');
    }

    public function add()
    {
        $this->load->view('mshop/store_goods/add');
    }

    public function edit($goods_id=0)
    {
        $this->load->view('mshop/store_goods/edit', ['goods_id'=>$goods_id]);
    }
  
}

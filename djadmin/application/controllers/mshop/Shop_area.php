<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/3/30
 * Time: 10:03
 */
class Shop_area extends wm_service_controller
{
    public function index($shop_id=0)
    {
        $this->load->view('mshop/shop_area/index', ['shop_id'=>$shop_id]);
    }

    public function table($shop_id=0)
    {
        $this->load->view('mshop/shop_area/table', ['shop_id'=>$shop_id]);
    }

    public function table_detail($shop_id=0)
    {
        $this->load->view('mshop/shop_area/table_detail', ['shop_id'=>$shop_id]);
    }
}
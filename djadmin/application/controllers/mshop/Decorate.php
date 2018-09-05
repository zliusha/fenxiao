<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 商品管理
 */
class Decorate extends wm_service_controller
{

    /**
     *装修
     */
    public function index($shop_id=0)
    {
        $this->load->view('mshop/decorate/index', ['shop_id'=>$shop_id]);
    }

    public function poster($shop_id=0)
    {
        $this->load->view('mshop/decorate/poster', ['shop_id'=>$shop_id]);
    }

    public function recommend($shop_id=0)
    {
        $this->load->view('mshop/decorate/recommend', ['shop_id'=>$shop_id]);
    }

    public function saoma($shop_id=0)
    {
        $this->load->view('mshop/decorate/saoma', ['shop_id'=>$shop_id]);
    }

    public function setting($shop_id=0)
    {
        $this->load->view('mshop/decorate/setting', ['shop_id'=>$shop_id]);
    }
  
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 商品管理
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Items extends wm_service_controller
{

    /**
     *商品列表
     */
    public function index()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if($this->is_zongbu) {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/good/index', $data);
    }

    /**
     * 分类列表
     */
    public function cate()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        if($this->is_zongbu) {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/good/cate', $data);
    }


    public function add($shop_id=0)
    {
        $this->load->view('mshop/good/add',['shop_id'=>$shop_id]);
    }

    public function edit($goods_id=0)
    {
        $this->load->view('mshop/good/edit', ['goods_id'=>$goods_id]);
    }
  
}

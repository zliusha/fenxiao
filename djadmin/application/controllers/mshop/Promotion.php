<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 优惠活动
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Promotion extends wm_service_controller
{
    public function discount_list()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);

        // 判断子账号权限
        if ($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/promotion/discount_list', $data);
    }

    public function activity_list()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);

        // 判断子账号权限
        if ($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/promotion/activity_list', $data);
    }

    //满减活动
    public function activity_add()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);

        // 判断子账号权限
        if ($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/promotion/activity_add', $data);
    }

    public function activity_edit($id = 0)
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);

        $data['active_id'] = $id;
        // 判断子账号权限
        if ($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/promotion/activity_edit', $data);
    }

    //限时折扣添加
    public function discount_add()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);

        // 判断子账号权限
        if ($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/promotion/discount_add', $data);
    }

    public function discount_edit($id = 0)
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);

        $data['active_id'] = $id;
        // 判断子账号权限
        if ($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }
        $this->load->view('mshop/promotion/discount_edit', $data);
    }

    //新用户优惠
    public function new_user()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if ($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/promotion/new_user', $data);
    }
}

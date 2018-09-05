<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
/**
* 数据统计
*/

class Statistics extends wm_service_controller
{
  /**
   * 营业统计（总店）
   */
  public function trade()
  {  
      $wmShopDao = WmShopDao::i($this->s_user->aid); 
      // 判断子账号权限
      if($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
      } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
      }

      $this->load->view('mshop/statistics/trade', $data);
  }


  /**
   * 流量分析（总店）
   */
  public function flow()
  {  
      $wmShopDao = WmShopDao::i($this->s_user->aid); 
      // 判断子账号权限
      if($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
          $this->load->view('mshop/statistics/flow', $data);
      } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
          $this->load->view('mshop/statistics/flow_shop', $data);
      }
  }


  /**
   * 商品分析（总店）
   */
  public function good()
  {  
      $wmShopDao = WmShopDao::i($this->s_user->aid);
      // 判断子账号权限
      if($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
          
      } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
      }

      $this->load->view('mshop/statistics/good', $data);
  }
}
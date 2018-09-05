<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 评价
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Comment extends wm_service_controller
{
    public function index()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if($this->is_zongbu)
        {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
          $this->load->view('mshop/comment/total_index', $data);
        }
        else
        {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
          $this->load->view('mshop/comment/index', $data);
        }

    }
}

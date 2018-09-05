<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 首页
 */
use Service\Cache\Wm\WmServiceCache;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
class Home extends wm_service_controller
{
    /**
     * 概况
     */
    function index()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $mainCompanyAccountDao = MainCompanyAccountDao::i();

        if($this->is_zongbu) {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
          $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }
        
        $model = $mainCompanyAccountDao->getOne(['id'=>$this->s_user->id], 'id,username,img,sex');
        $model->img = conver_picurl($model->img);
        $shop_model = null;
        if(!$this->is_zongbu)
        {
            log_message('error','111-'.$this->currentShopId);
            $shop_model = $wmShopDao->getOne(['id'=>$this->currentShopId]);
            $shop_model->shop_logo = conver_picurl($shop_model->shop_logo);
        }
        $wmServiceCache = new WmServiceCache(['aid'=>$this->s_user->aid]);
        $service = $wmServiceCache->getDataASNX();

        $data['model'] = $model;
        $data['shop_model'] = $shop_model;
        $data['service_model'] = $service;

        $this->load->view('mshop/home/index', $data);
    }
}

<?php
/**
 * @Author: binghe
 * @Date:   2017-10-25 10:02:58
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:27:22
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
/**
* 店铺
*/
class Shop extends wm_service_controller
{
    public function index()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $add_enabel = 1;
        //判断门店，除旗舰版，其它版本只能添加五个门店
        if($this->service->shop_limit > 0)
        {
          $shop_count = $wmShopDao->getCount(['aid'=>$this->s_user->aid,'is_delete'=>0]);
          if($shop_count >= $this->service->shop_limit)
            $add_enabel = 0;
        }
        $data['add_enabel']=$add_enabel;
        $data['shop_limit']=$this->service->shop_limit;
        $this->load->view('mshop/shop/index',$data);
    }
    /**
     * 门店编辑
     * @param int $shop_id
     */
    public function edit($shop_id=0)
    {
        $this->load->view('mshop/shop/edit', ['shop_id'=>$shop_id]);
    }

    public function info() {
        $this->load->view('mshop/shop/info', ['shop_id'=>$this->currentShopId]);
    }
}
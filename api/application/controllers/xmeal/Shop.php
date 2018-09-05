<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/17
 * Time: 10:29
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Shop extends xmeal_controller
{
    /**
     * 门店列表查询
     */
    public function get_meal_shop()
    {
        $wmShopDao = WmShopDao::i($this->aid);
        $fields = "id,aid,shop_name,contact,shop_logo,contact,shop_state,shop_city,shop_district,shop_address,longitude,latitude,type,meal_status";
        $list = $wmShopDao->getAllArray("aid={$this->aid} AND is_delete=0 AND meal_status=0 AND (type&2)>0", $fields);//2=1<<1

        $data['rows'] =  $list = convert_client_list($list, [['type'=>'img','field'=>'shop_logo']]);;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
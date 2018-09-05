<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2017/8/18
 * Time: 10:17
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
class Cart extends mshop_controller
{
    //自检购物车
    function valid_cart()
    {

        $rule=array(
            array('field'=>'shop_id','label'=>'IDS','rules'=>'trim|required|numeric'),
            array('field'=>'sku_ids','label'=>'IDS','rules'=>'trim|required|preg_key[IDS]')
        );
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $sku_ids = trim($f_data['sku_ids'], ',');

        $wmShardDb = WmShardDb::i($this->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->aid);
        $goods_config_arr = array(
          'field' => 'a.id,a.shop_id,a.goods_id,a.sale_price,a.use_stock_num,a.attr_names,b.title,b.pict_url,b.status,b.sku_type',
          'table' => "{$wmShardDb->tables['wm_goods_sku']} a",
          'join' => array(
            array("{$wmShardDb->tables['wm_goods']} b", 'a.goods_id=b.id', 'left'),
          ),
          'where' => " a.shop_id={$f_data['shop_id']} AND a.id in ($sku_ids) AND b.status=1",
        );
        $list = $wmGoodsSkuDao->getEntitysByAR($goods_config_arr, true);

        $list = convert_client_list($list, [['type'=>'img','field'=>'pict_url']]);

        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

}
<?php
/**
 * @Author: binghe
 * @Date:   2017-08-24 14:21:04
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:51:29
 */
/**
* Goods
*/
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
class Goods extends open_controller
{
    /**
     * 门店商品
     * @return [type] [description]
     */
    public function list()
    {
        $rules=[
            ['field'=>'aid','label'=>'aid','rules'=>'trim|required|numeric']
            ,['field'=>'shop_id','label'=>'店铺ID','rules'=>'trim|required|numeric']
            ,['field'=>'update_time','label'=>'更新时间','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmShardDb = WmShardDb::i($fdata['aid']);
        $page = new PageList($wmShardDb);
        $p_conf=$page->getBtConfig();

        $p_conf->table="{$wmShardDb->tables['wm_goods']}";
        $p_conf->fields='id,aid,shop_id,title,pict_url,sku_type,inner_price,description,status,is_delete,time,update_time';
        $p_conf->where .= " AND aid={$fdata['aid']} AND shop_id={$fdata['shop_id']} AND update_time>={$fdata['update_time']} ";
        $p_conf->order_by='update_time asc';
        $count = 0;
        $rows=$page->getBtList($p_conf,$count);
        
        if($rows)
        {
            $goods_ids = [];
            foreach ($rows as $item) {
                array_push($goods_ids,$item['id']);
            }
            $goods_ids_str = sql_where_in($goods_ids);
            $wmGoodsSkuDao = WmGoodsSkuDao::i($fdata['aid']);
            $goods_sku_list = $wmGoodsSkuDao->getAllArray("aid={$fdata['aid']} AND shop_id={$fdata['shop_id']} AND goods_id in ({$goods_ids_str})", 'id,goods_id,shop_id,attr_names,box_fee,price,sale_price,use_stock_num,time,update_time');

            foreach($rows as $key => $goods)
            {
                $rows[$key]['pict_url'] = conver_picurl($goods['pict_url']);
                $rows[$key]['sku_list'] = array_find_list($goods_sku_list,'goods_id',$goods['id']);
            }
        
        }

        $data['count']=$count;
        $data['rows']=$rows;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 需要商品主信息,sku信息
     * @return [type] [description]
     */
    public function simple_list()
    {
        //goods_id以逗号隔开
        $rules=[
            ['field'=>'aid','label'=>'AID','rules'=>'trim|required|numeric']
            ,['field'=>'shop_id','label'=>'店铺ID','rules'=>'trim|required|numeric']
            ,['field'=>'goods_ids','label'=>'商品IDS','rules'=>'trim|required|preg_key[IDS]']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmGoodsDao = WmGoodsDao::i($fdata['aid']);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($fdata['aid']);
        $goods_list = $wmGoodsDao->getAllArray("aid={$fdata['aid']} AND shop_id={$fdata['shop_id']} AND id in ({$fdata['goods_ids']})", 'id,aid,shop_id,title,pict_url,sku_type,inner_price,description,status,is_delete,time,update_time');
        $goods_sku_list = $wmGoodsSkuDao->getAllArray("aid={$fdata['aid']} AND shop_id={$fdata['shop_id']} AND goods_id in ({$fdata['goods_ids']})", 'id,goods_id,shop_id,attr_names,box_fee,price,sale_price,use_stock_num,time,update_time');

        foreach($goods_list as $key => $goods)
        {
            $goods_list[$key]['pict_url'] = conver_picurl($goods['pict_url']);
            $goods_list[$key]['sku_list'] = array_find_list($goods_sku_list,'goods_id',$goods['id']);
        }

        $data['rows'] = $goods_list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    
}
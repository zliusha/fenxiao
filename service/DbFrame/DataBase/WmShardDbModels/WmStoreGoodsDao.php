<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:41:56
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:05
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;

/**
 * 公司
 */
class WmStoreGoodsDao extends BaseDao
{
	/**
     * 更新价格区间波动
     * @param int $goodsId
     */
    public function updateInnerPrice($goodsId=0)
    {
    	$wmStoreGoodsSkuDao = WmStoreGoodsSkuDao::i($this->shardNode);
        $max_model = $wmStoreGoodsSkuDao->selectMax('sale_price', ['goods_id'=>$goodsId]);
        $min_model = $wmStoreGoodsSkuDao->selectMin('sale_price', ['goods_id'=>$goodsId]);
       
        if($max_model->sale_price == $min_model->sale_price) $inner_price = $max_model->sale_price;
        else $inner_price = $min_model->sale_price.'~'.$max_model->sale_price;
        $this->update(['inner_price'=>$inner_price], ['id'=>$goodsId]);
    }

  /**
   * 更新商品库分类名称
   * @param int $cateId
   * @param int $shopId
   * @param int $aid
   * @return bool
   */
    public function updateCateNames($cateId=0, $shopId=0, $aid=0)
    {
        $wmCateDao = WmCateDao::i($this->shardNode);
        $cate_list = $wmCateDao->getAllArray(['shop_id'=>$shopId, 'aid'=>$aid]);
        $_m_cate = array_find($cate_list, 'id', $cateId);
        if(!$cate_list || !$_m_cate) return false;

        $store_goods_list = $this->getAllArray("aid={$aid} AND CONCAT(',',cate_ids,',') LIKE CONCAT('%,',{$cateId},',%')", 'id,cate_ids,cate_names');

        $up_data = [];
        foreach($store_goods_list as $goods)
        {

            $_cate_arr = array_search_list($cate_list, 'id', explode(',',$goods['cate_ids']));
            $_cate_names = trim(implode(',', array_column($_cate_arr, 'cate_name')), ',');

            $tmp['id'] = $goods['id'];
            $tmp['aid'] =$aid;
            $tmp['cate_names'] = $_cate_names;

            $up_data[] = $tmp;

        }
        if(!empty($up_data))
            $this->updateBatch($up_data, 'id');
    }
}
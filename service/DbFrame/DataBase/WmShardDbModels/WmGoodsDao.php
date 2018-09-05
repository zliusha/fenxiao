<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:04:00
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:02
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmGoodsDao extends BaseDao
{
	/**
     * 更新价格区间波动
     * @param int $goodsId
     */
    public function updateInnerPrice($goodsId=0)
    {
    	$wmGoodsSkuDao = WmGoodsSkuDao::i($this->shardNode);
        $max_model = $wmGoodsSkuDao->selectMax('sale_price', ['goods_id'=>$goodsId]);
        $min_model = $wmGoodsSkuDao->selectMin('sale_price', ['goods_id'=>$goodsId]);
       
        if($max_model->sale_price == $min_model->sale_price) $inner_price = $max_model->sale_price;
        else $inner_price = $min_model->sale_price.'~'.$max_model->sale_price;
        $this->update(['inner_price'=>$inner_price], ['id'=>$goodsId]);
    }

    public function getCateName($cate_ids)
    {
        if(empty($cate_ids)) return false;
        $wmCateDao = WmCateDao::i($this->shardNode);
        $list = $wmCateDao->getAllArray(" id in ({$cate_ids})");
        return array_column($list, 'cate_name');
    }

  /**
   * 增加商品维度销量
   * @param $where
   * @param $num
   * @return bool
   */
    public function updateSaleNum($where, $num)
    {
        if($where) return false;
        $this->db->where($where);
        //增加销量
        $this->db->set('sale_num',"sale_num+{$num}",false);
        $this->db->set('update_time',time(),false);
        $this->db->update($this->tableName);
    }
}
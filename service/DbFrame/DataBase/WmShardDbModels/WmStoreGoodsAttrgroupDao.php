<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:40:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:05
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmStoreGoodsAttrgroupDao extends BaseDao
{
	/**
   * 添加多规格商品属性
   * @param $data
   * @param int $goodsId
   * @return bool
   */
    public function addAttr($data, $goodsId=0, $aid=0)
    {
        if(empty($goodsId) || !is_numeric($goodsId) || !is_numeric($aid)) return false;
        //处理逻辑sql
        $this->delete(['goods_id'=>$goodsId]);
        $wmStoreGoodsAttritemDao = WmStoreGoodsAttritemDao::i($this->shardNode);
        $wmStoreGoodsAttritemDao->delete(['goods_id'=>$goodsId]);

        foreach($data as $key => $attr)
        {
            $tmp = [];
            $item_tmp = [];
            $tmp['aid'] = $aid;
            $tmp['goods_id'] = $goodsId;
            $tmp['title'] = $attr['attr_name'];
            $attr_id = $this->create($tmp);
            foreach($attr['value'] as $attr_value)
            {
                $attr_tmp = [];
                $attr_tmp['aid'] = $aid;
                $attr_tmp['group_id'] = $attr_id;
                $attr_tmp['goods_id'] = $goodsId;
                $attr_tmp['attr_name'] = $attr_value;
                $attr_tmp['time'] = time();

                $item_tmp[] = $attr_tmp;
            }
            if(!empty($item_tmp)) $wmStoreGoodsAttritemDao->createBatch($item_tmp);
        }
        return true;
    }
}
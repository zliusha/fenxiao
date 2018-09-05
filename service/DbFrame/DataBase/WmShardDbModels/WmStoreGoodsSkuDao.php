<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:42:25
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:05
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmStoreGoodsSkuDao extends BaseDao
{
	/**
     * @param $data sku 数据结构
     * @param int $goodsId
     * @param int $aid
     * @param bool $edit 是否编辑
     * @return bool
     */
    public function addUpdateSku($data, $goodsId=0, $aid=0, $edit=false)
    { 
        if(empty($goodsId) || !is_numeric($goodsId) || !is_numeric($aid)) return false;
   
        $sku_add_data = [];
        $sku_update_data = [];
        $wmStoreGoodsAttrgroupDao = WmStoreGoodsAttrgroupDao::i($this->shardNode);
        $wmStoreGoodsAttritemDao = WmStoreGoodsAttritemDao::i($this->shardNode);
        $sku_ids = '';

        foreach($data as $key => $sku)
        {
            $tmp = [];
            $attr_ids = '';
            $attr_names = '';
            //获取属性值id
            foreach($sku['sku_attr'] as $sku_attr)
            {
                $model = $wmStoreGoodsAttrgroupDao->getOne(['goods_id'=>$goodsId, 'title'=>$sku_attr['attr']]);
                if(!$model) return false;
                $attr_model =  $wmStoreGoodsAttritemDao->getOne(['goods_id'=>$goodsId, 'group_id'=>$model->id, 'attr_name'=>$sku_attr['value']]);
                if(!$attr_model) return false;
                $attr_ids .= $attr_model->id.',';
                $attr_names .= $attr_model->attr_name.',';
            }

            $tmp['attr_ids'] = trim($attr_ids, ',');
            $tmp['attr_names'] = trim($attr_names, ',');
            $tmp['sale_price'] = floatval($sku['sale_price']);
            $tmp['box_fee'] = floatval($sku['box_fee']);
            $tmp['goods_id'] = $goodsId;
            $tmp['aid'] = $aid;
            $tmp['time'] = time();

            $tmp['goods_sku_sn'] = trim($sku['goods_sku_sn']);

            if(!empty($sku['sku_id']))
            {
                $sku_model = $this->getOne(['id'=>$sku['sku_id']]);
                if(!$sku_model)
                {
                   log_message('error', __METHOD__.':sku_id-'.$sku['id'].'无效');
                }
                else
                {
                    $tmp['id'] = $sku['sku_id'];
                    $sku_ids .= $sku['sku_id'].',';
                    $sku_update_data[] = $tmp;
                }
            }
            else
            {
                $sku_add_data[] = $tmp;
            }

        }
        if($edit)
        {
            //判断删除 已需要删除的sku
            if(!empty(trim($sku_ids, ',')))
            {
              $where = " goods_id={$goodsId} AND id not in (".trim($sku_ids, ',').')';
            }
            else
            {
              $where = " goods_id={$goodsId} ";
            }

            $this->delete($where);
        }

        if(!empty($sku_update_data))
        {
            $this->updateBatch($sku_update_data, 'id');
        }

        if(!empty($sku_add_data))
        {
            $this->createBatch($sku_add_data);
        }

        return true;
    }

    /**
     * 获取sku的属性名称
     * @param int $skuId
     * @param int $shopId
     * @return array|bool
     */
    public function getAttr($skuId=0, $shopId=0)
    {
        if(!is_numeric($skuId)) return false;
        $model = $this->getOne(['id'=>$skuId]);
        if(!$model) return false;
        $wmStoreGoodsAttritemDao = WmStoreGoodsAttritemDao::i($this->shardNode);
        $attr = $wmStoreGoodsAttritemDao->getAllArray("id in ({$model->attr_ids})");
        return array_column($attr, 'attr_name');
    }
}
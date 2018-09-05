<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:04:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:02
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmGoodsSkuDao extends BaseDao
{
	/**
     * @param $data sku 数据结构
     * @param int $goodsId
     * @param int $aid
     * @param bool $edit 是否编辑
     * @return bool
     */
    public function addUpdateSku($data, $goodsId=0, $aid=0, $shopId=0, $edit=false)
    { 
        if(empty($goodsId) || !is_numeric($goodsId) || !is_numeric($aid)) return false;
   
        $sku_add_data = [];
        $sku_update_data = [];
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->shardNode);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->shardNode);
        $sku_ids = '';

        foreach($data as $key => $sku)
        {
            $tmp = [];
            $attr_ids = '';
            $attr_names = '';
            //获取属性值id
            foreach($sku['sku_attr'] as $sku_attr)
            {
                $model = $wmGoodsAttrgroupDao->getOne(['goods_id'=>$goodsId, 'title'=>$sku_attr['attr']]);
                if(!$model) return false;
                $attr_model =  $wmGoodsAttritemDao->getOne(['goods_id'=>$goodsId, 'group_id'=>$model->id, 'attr_name'=>$sku_attr['value']]);
                if(!$attr_model) return false;
                $attr_ids .= $attr_model->id.',';
                $attr_names .= $attr_model->attr_name.',';
            }

            $tmp['attr_ids'] = trim($attr_ids, ',');
            $tmp['attr_names'] = trim($attr_names, ',');
            $tmp['sale_price'] = floatval($sku['sale_price']);
            $tmp['box_fee'] = floatval($sku['box_fee']);
            $tmp['use_stock_num'] = floatval($sku['use_stock_num']);
            $tmp['total_stock_num'] = floatval($sku['use_stock_num']);
            $tmp['goods_id'] = $goodsId;
            $tmp['shop_id'] = $shopId;
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
                    if($sku['use_stock_num'] >= 0 )
                    {
                        $tmp['total_stock_num'] = $sku['use_stock_num']+$sku_model->dj_stock_num;
                    }
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
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->shardNode);
        $attr = $wmGoodsAttritemDao->getAllArray("id in ({$model->attr_ids})");
        return array_column($attr, 'attr_name');
    }

    /**
     * 获取sku_ids
     * @param $data
     * @return string
     */
    public function getSkuIds($data)
    {
        $sku_ids = '';
        foreach($data as $key => $sku)
        {
            if(!empty($sku['sku_id']))
            {
              $sku_ids .= $sku['sku_id'].',';
            }
        }
        return $sku_ids;
    }

    /**
     * 确认减库存
     * @param $skuData
     * @param int $shopId
     */
    public function reduceStock($skuData=array(), $shopId=0)
    {
        if(empty($shopId) || !is_numeric($shopId) || empty($skuData)) return ['error'=>"001",'msg'=>"参数错误"];

        $wmGoodsDao = WmGoodsDao::i($this->shardNode);

        foreach($skuData as $sku)
        {
            $this->db->where(['shop_id'=>$shopId, 'id'=>$sku['sku_id']]);
            $this->db->set('total_stock_num',"total_stock_num-{$sku['quantity']}",false);
            $this->db->set('dj_stock_num',"dj_stock_num-{$sku['quantity']}",false);
            //增加销量
            $this->db->set('sale_num',"sale_num+{$sku['quantity']}",false);
            $this->db->set('update_time',time(),false);
            $this->db->update($this->tableName);

            //增加商品维度销量
            $wmGoodsDao->updateSaleNum(['id'=>$sku['goods_id'],'shop_id'=>$shopId], $sku['quantity']);
        }

        return true;
    }

    /**
     * 回原库存
     * @param $skuData
     * @param int $shopId
     */
    public function restoreStock($skuData=array(), $shopId=0)
    {
        if(empty($shopId) || !is_numeric($shopId) || empty($skuData)) return ['error'=>"001",'msg'=>"参数错误"];
        foreach($skuData as $sku)
        {
            $model = $this->getOne(['shop_id'=>$shopId, 'id'=>$sku['sku_id']]);
            if(!$model)
            {
                log_message('error', __METHOD__."确认减库存：sku_id-".$sku['sku_id'].'记录不存在');
            }

            $this->db->where(['shop_id'=>$shopId, 'id'=>$sku['sku_id']]);
            if( $model->use_stock_num >= 0 )//库存大于等于0 回原库存
                $this->db->set('use_stock_num',"use_stock_num+{$sku['quantity']}",false);
            $this->db->set('dj_stock_num',"dj_stock_num-{$sku['quantity']}",false);
            $this->db->set('update_time',time(),false);
            $this->db->update($this->tableName);
        }

        return true;
    }

    /**
     * 锁定库存
     * @param $skuData
     * @param int $shopId
     */
    public function lockStock($skuData=array(), $shopId=0)
    {
        if(empty($shopId) || !is_numeric($shopId) || empty($skuData)) return ['error'=>"001",'msg'=>"参数错误"];;
        foreach($skuData as $key =>  $sku)
        {
            $model = $this->getOne(['shop_id'=>$shopId, 'id'=>$sku['sku_id']]);
            if(!$model)
            {
              return ['error'=>'004', 'msg'=>"商品不存在"];
            }
            if( $model->use_stock_num == 0 || ($model->use_stock_num > 0 && $model->use_stock_num < $sku['quantity']) )
            {
              return ['error'=>'004', 'msg'=>"商品库存不足"];
            }
            $skuData[$key]['use_stock_num'] = $model->use_stock_num;
        }
        foreach($skuData as $sku)
        {
            $this->db->where(['shop_id'=>$shopId, 'id'=>$sku['sku_id']]);
            if($sku['use_stock_num'] > 0)
                $this->db->set('use_stock_num',"use_stock_num-{$sku['quantity']}",false);
            $this->db->set('dj_stock_num',"dj_stock_num+{$sku['quantity']}",false);
            $this->db->set('update_time',time(),false);
            $this->db->update($this->tableName);
        }
        return true;
    }
}
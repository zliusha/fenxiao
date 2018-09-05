<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 09:59:13
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:55:59
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmCateDao extends BaseDao
{
	/**
   * 添加同步分店分类
   * @param array $cateArr 分类名称数组
   * @param int $shopId  分店ID
   * @param int $aid
   */
    public function addByNames($cateArr=[], $shopId=0, $aid=0)
    {
        $cate_list = $this->getAllArray(['aid' => $aid, 'shop_id' => $shopId]);
        $cate_names_arr = array_column($cate_list, 'cate_name');

        $data = [];
        foreach($cateArr as $cate_name)
        {
            if(empty($cate_name) || in_array($cate_name ,$cate_names_arr))
                continue;
            $tmp['aid'] = $aid;
            $tmp['shop_id'] = $shopId;
            $tmp['cate_name'] = $cate_name;
            $tmp['sort'] = 0;
            $tmp['time'] = time();

            $data[] = $tmp;
        }

        if(!empty($data))
            $this->createBatch($data);
    }

  /**
   * 根据分类名称获取分店分类IDS
   * @param string $cateName  分类名称，英文逗号隔开的字符串
   * @param int $shopId  分店ID
   * @param int $aid
   * @return string
   */
    public function getIdsByNames($cateName='', $shopId=0, $aid=0)
    {
        $cate_list = $this->getAllArray("aid={$aid} AND shop_id={$shopId} AND FIND_IN_SET(cate_name, '{$cateName}')");
        $cate_ids_arr = array_column($cate_list, 'id');
        return implode(',', $cate_ids_arr);
    }

  /**
   * 总店分类添加商品
   * @param int $cateId  分类ID
   * @param string $goodsIds
   * @param int $aid
   */
    public function cateStoreGoodsAdd($cateId=0, $goodsIds='', $aid=0)
    {
        $cate_list = $this->getAllArray(['aid' => $aid, 'shop_id' => 0]);
        $wmStoreGoodsDao =  WmStoreGoodsDao::i($this->shardNode);
        $goods_list = $wmStoreGoodsDao->getAllArray("aid={$aid}  AND id in ({$goodsIds})", 'id,cate_ids');

        $update_data = [];
        if(!empty($goods_list))
        {
            foreach($goods_list as $goods)
            {
                $cate_ids = ','.$goods['cate_ids'].',';
                if(stripos($cate_ids, ','.$cateId.',') === false)
                {
                    $tmp['id'] = $goods['id'];
                    $tmp['cate_ids'] = $goods['cate_ids'].','.$cateId;

                    $cate_ids_arr = explode(",", trim($tmp['cate_ids'], ','));
                    $cate_names_arr = array_search_list($cate_list, 'id', $cate_ids_arr);
                    $cate_names = implode(',', array_column($cate_names_arr, 'cate_name'));

                    $tmp['cate_names'] = $cate_names;
                    $update_data[] = $tmp;
                }
            }
        }

        if(!empty($update_data))
            $wmStoreGoodsDao->updateBatch($update_data, 'id');
    }
}
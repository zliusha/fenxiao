<?php
/**
 * Created by Sublime.
 * author: feiying<@iyenei.com>
 * Date: 2018/7/10
 * Time: 下午13:42
 */

namespace Service\DbFrame\DataBase\HcityShardDbModels;


class ShcityGoodsDao extends BaseDao
{
    /******************商圈商品状态**********************/
    const HCITY_STATYS_AUDIT = 2; //审核中


    /**
     * 商品详情数据
     * @param int $aid
     * @param int $goods_id
     * @return object
     * @throws Exception
     */
    public function detail(int $aid, int $goods_id)
    {
        $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid' => $aid]);
        $shcityGoodsAttrgroupDao = ShcityGoodsAttrgroupDao::i(['aid' => $aid]);
        $shcityGoodsAttritemDao = ShcityGoodsAttritemDao::i(['aid' => $aid]);

        $m_shcity_goods = $shcityGoodsDao->getOne(['aid' => $aid, 'id' => $goods_id]);
        if (!$m_shcity_goods) {
            return null;
        }
        $sku_list = $shcityGoodsSkuDao->getAllArray(['aid' => $aid, 'goods_id' => $goods_id]);
        if (empty($sku_list)) {
            return null;
        }
        //目前一维组合
        $attr_group = $shcityGoodsAttrgroupDao->getOneArray(['aid' => $aid, 'id' => $goods_id]);
        $attr_item = $shcityGoodsAttritemDao->getAllArray(['aid' => $aid, 'id' => $goods_id]);

        //该商品所有属性
        if ($attr_group) {
            $attr_group['attr_value'] = array_find_list($attr_item, 'group_id', $attr_group['id']);
        }
        $m_shcity_goods->goods_attr = $attr_group;
        $m_shcity_goods->sku_list = $sku_list;

        $m_shcity_goods->pic_url = conver_picurl($m_shcity_goods->pic_url);
        $m_shcity_goods->poster_url = conver_picurl($m_shcity_goods->poster_url);
        $goodsImgs = explode(',', $m_shcity_goods->pic_url_list);
        if (!empty($goodsImgs)) {
            $goodsImgTmp = [];
            foreach ($goodsImgs as $img) {
                $goodsImgTmp[] = conver_picurl($img);
            }
            $m_shcity_goods->pic_url_list = $goodsImgTmp;
        }
        return $m_shcity_goods;
    }
}
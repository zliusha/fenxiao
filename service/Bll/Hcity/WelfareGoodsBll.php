<?php
namespace Service\Bll\Hcity;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/21
 * Time: 10:44
 */
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsSkuDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsAttritemDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsAttrgroupDao;


class WelfareGoodsBll
{
    /**
     * 福利池列表
     * @param $input
     * @return mixed
     */
    public function goodsList($input)
    {
        $mainShopDao = MainShopDao::i();
        $hcityShopExtDao = HcityShopExtDao::i();

        // 筛选区域店铺
        $shop_where = '1=1';
        if (!empty($input['city_code']))
            $shop_where .= "  AND region like '%{$input['city_code']}%'";
        $shop_list = $mainShopDao->getAllArray(" {$shop_where} ");
        if (empty($shop_list))
            throw new Exception('无店铺服务通');
        $shop_ids = implode(',', array_column($shop_list, 'id'));

        // 获取有效店铺列表
        $shop_ext_list = $hcityShopExtDao->getAllArray("hcity_show_status=1 AND shop_id in ({$shop_ids})");
        if (empty($shop_ext_list))
            throw new Exception('无店铺服务通');

        // 计算店铺与当前位置的距离
        $valid_shop_list = [];
        foreach ($shop_list as $sk => $shop) {
            //有效的店铺
            if (array_find($shop_ext_list, 'shop_id', $shop['id'])) {
                $shop['distance'] = get_distance($input['long'], $input['lat'], $shop['longitude'], $shop['latitude']);
                $valid_shop_list[] = $shop;
            }
        }
        // 获得店铺排序的id数组
        $shop_sort_ids = implode(',', array_column(array_sort($valid_shop_list, 'distance', 'asc'), 'id'));

        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_welfare_goods']}";
        $p_conf->fields = 'id,aid,shop_id,title,pic_url,price,group_price,time,update_time,cost_price,hcard_price,use_end_time,sales_num,welfare_status,region,free_num,original_goods_id';
        $p_conf->where .= " AND is_delete=0";
        $p_conf->order = " FIELD(a.shop_id,{$shop_sort_ids}) ASC, sales_num DESC, id DESC";
        // 标题
        if (!empty($input['title'])) {
            $p_conf->where .= "and title like '%{$page->filterLike($input['title'])}%'";
        }
        // 店铺筛选
        if ($input['shop_id']) {
            $p_conf->where .= ' and shop_id=' . $input['shop_id'];
        }
        // 区域码
        if (!empty($input['region'])) {
            $p_conf->where .= " and region like '%{$page->filterLike($input['region'])}%'";
        }
        // 状态
        if (isset($input['welfare_status']) && is_numeric($input['welfare_status'])) {
            $p_conf->where .= ' and welfare_status=' . $input['welfare_status'];
        }
        // 自定义where
        if (!empty($input['where'])) {
            $p_conf->where .= " and {$input['where']}";
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $goods_list = $page->getList($p_conf, $count);

        // 店铺与当前位置的距离
        foreach ($goods_list as $gk => $goods) {
            $shop = array_find($valid_shop_list, 'id', $goods['shop_id']);
            $goods_list[$gk]['distance'] = isset($shop['distance']) ? $shop['distance'] : 0;
            $goods_list[$gk]['shop_name'] = isset($shop['shop_name']) ? $shop['shop_name'] : '';
            //格式图片
            $goods_list[$gk]['pic_url'] = conver_picurl($goods['pic_url']);
        }

        $data['total'] = $count;
        $data['rows'] = $goods_list;
        return $data;
    }

    /**
     * 福利池商品详情
     * @param int $aid
     * @param int $goods_id
     * @return null|object
     */
    public function detail(int $aid, int $goods_id)
    {
        $welfareGoodsDao = HcityWelfareGoodsDao::i();
        $welfareGoodsSkuDao = HcityWelfareGoodsSkuDao::i();
        $welfareGoodsAttrgroupDao = HcityWelfareGoodsAttrgroupDao::i(['aid' => $aid]);
        $welfareGoodsAttritemDao = HcityWelfareGoodsAttritemDao::i(['aid' => $aid]);

        $m_welfare_goods = $welfareGoodsDao->getOne(['aid' => $aid, 'id' => $goods_id]);
        if (!$m_welfare_goods) {
            return null;
        }
        $sku_list = $welfareGoodsSkuDao->getAllArray(['aid' => $aid, 'goods_id' => $goods_id]);
        if (empty($sku_list)) {
            return null;
        }
        //目前一维组合
        $attr_group = $welfareGoodsAttrgroupDao->getOneArray(['aid' => $aid, 'id' => $goods_id]);
        $attr_item = $welfareGoodsAttritemDao->getAllArray(['aid' => $aid, 'id' => $goods_id]);

        //该商品所有属性
        if ($attr_group) {
            $attr_group['attr_value'] = array_find_list($attr_item, 'group_id', $attr_group['id']);
        }
        $m_welfare_goods->goods_attr = $attr_group;
        $m_welfare_goods->sku_list = $sku_list;

        $m_welfare_goods->pic_url = conver_picurl($m_welfare_goods->pic_url);
        $goodsImgs = explode(',', $m_welfare_goods->pic_url_list);
        if (!empty($goodsImgs)) {
            $goodsImgTmp = [];
            foreach ($goodsImgs as $img) {
                $goodsImgTmp[] = conver_picurl($img);
            }
            $m_welfare_goods->pic_url_list = $goodsImgTmp;
        }
        return $m_welfare_goods;
    }
}
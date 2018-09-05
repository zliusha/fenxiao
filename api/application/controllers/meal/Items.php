<?php
/**
 * 扫码点餐商品
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\WmCateDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttrgroupDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttritemDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
class Items extends meal_controller
{
    // 所有分类商品列表
    public function cate_goods_list()
    {
        $rules = [
            ['field'=>'shop_id','label'=>'店铺ID','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $f_data=$this->form_data($rules);
        $shop_id = $f_data['shop_id'];

        $wmCateDao = WmCateDao::i($this->aid);
        $wmGoodsDao = WmGoodsDao::i($this->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->aid);
        $wmShardDb = WmShardDb::i($this->aid);

        //获取所有分类
        $cate_list = $wmCateDao->getAllArray(['aid' => $this->aid, 'shop_id' => $shop_id], '*', "sort desc,id desc");

        $fields = 'a.id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id promo_id,a.base_sale_num';
        //获取店铺所有商品
        $goods_config_arr = array(
            'field' => $fields,
            'table' => "{$wmShardDb->tables['wm_goods']} a",
            'where' => " a.aid={$this->aid} AND a.shop_id={$shop_id} AND a.is_delete=0 AND a.status=1 AND a.measure_type=1",
        );

        //获取所有商品列表
        $goods_all_list = $wmGoodsDao->getEntitysByAR($goods_config_arr, true);

        $goods_ids = implode(',', array_column($goods_all_list, 'id'));
        if (empty($goods_ids)) {
            $goods_ids = '0';
        }

        //获取所有sku列表
        $sku_all_list = $wmGoodsSkuDao->getAllArray("aid={$this->aid} AND goods_id in ({$goods_ids})");
        $attr_all_group = $wmGoodsAttrgroupDao->getAllArray("aid={$this->aid} AND goods_id in ({$goods_ids})");
        $attr_all_item = $wmGoodsAttritemDao->getAllArray("aid={$this->aid} AND goods_id in ({$goods_ids})");

        foreach ($cate_list as $key => $cate) {
            //获取当前分类的商品
            $goods_list = $this->_get_cate_goods($goods_all_list, $cate['id']);
            foreach ($goods_list as $g_k => $goods) {
                $goods_list[$g_k]['pict_url'] = conver_picurl($goods['pict_url']);

                //该商品所有属性
                $attr_group = array_find($attr_all_group, 'goods_id', $goods['id']);
                if ($attr_group) {
                    $attr_group['attr_value'] = array_find_list($attr_all_item, 'group_id', $attr_group['id']);
                }
                $goods_list[$g_k]['attr'] = $attr_group;

                //获取当前商品的sku
                $sku_list = array_find_list($sku_all_list, 'goods_id', $goods['id']);
                //整体sku属性数据结构
                foreach ($sku_list as $sk => $sku) {
                    $sku_attr = [];
                    $attr_ids_arr = explode(',', $sku['attr_ids']);
                    if (!empty($attr_ids_arr)) {
                        foreach ($attr_ids_arr as $k => $attr_id) {
                            if (empty($attr_id)) {
                                continue;
                            }

                            $_attr_value = array_find($attr_all_item, 'id', $attr_id);
                            $_attr_group = isset($_attr_value['group_id']) ? array_find($attr_all_group, 'id', $_attr_value['group_id']) : null;
                            $tmp['attr_group_title'] = isset($_attr_group['title']) ? $_attr_group['title'] : '';
                            $tmp['attr_group_id'] = isset($_attr_group['id']) ? $_attr_group['id'] : 0;
                            $tmp['attr_value'] = isset($_attr_value['attr_name']) ? $_attr_value['attr_name'] : '';
                            $tmp['attr_value_id'] = isset($_attr_value['id']) ? $_attr_value['id'] : 0;
                            $sku_attr[] = $tmp;
                        }
                    }
                    $sku_list[$sk]['sku_attr'] = $sku_attr;
                }
                $goods_list[$g_k]['sku_list'] = $sku_list;

            }
            $cate_list[$key]['goods_list'] = $goods_list;
        }

        $this->json_do->set_data($cate_list);
        $this->json_do->out_put();
    }

    /**
     * 从商品列表中刷选同一分类商品
     * @param $goods_list 商品列表
     * @param $cate_id 分类id
     * @return array
     */
    private function _get_cate_goods($goods_list, $cate_id)
    {
        $list_arr = [];
        if (!empty($goods_list)) {
            foreach ($goods_list as $arr) {
                if (isset($arr['cate_ids']) && strpos(',' . $arr['cate_ids'] . ',', ',' . $cate_id . ',') !== false) {
                    array_push($list_arr, $arr);
                }
            }
        }
        return $list_arr;
    }

    // 推荐商品列表
    public function recommend_goods_list()
    {
        $rules = [
            ['field'=>'shop_id','label'=>'店铺ID','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $f_data=$this->form_data($rules);
        $shop_id = $f_data['shop_id'];

        $wmGoodsDao = WmGoodsDao::i($this->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->aid);
        $wmShardDb = WmShardDb::i($this->aid);
        $fields = 'a.id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id promo_id,a.base_sale_num';
        //获取店铺所有商品
        $goods_config_arr = array(
            'field' => $fields,
            'table' => "{$wmShardDb->tables['wm_goods']} a",
            'where' => " a.aid={$this->aid} AND a.shop_id={$shop_id} AND a.is_delete=0 AND a.status=1 AND a.tag=1 AND a.measure_type=1",
            'limit' => 20
        );

        //获取所有商品列表
        $_goods_list = $wmGoodsDao->getEntitysByAR($goods_config_arr, true);
        $count = count($_goods_list);

        //######从数组总随机去除3条
        $goods_list = $this->_array_rand($_goods_list, $count>3 ? 3 : $count);
        //######

        $goods_ids = implode(',', array_column($goods_list, 'id'));
        if (empty($goods_ids))
        {
            $goods_ids = '0';
        }
        //获取所有sku列表
        $sku_all_list = $wmGoodsSkuDao->getAllArray("aid={$this->aid} AND goods_id in ({$goods_ids})");
        $attr_all_group = $wmGoodsAttrgroupDao->getAllArray("aid={$this->aid} AND goods_id in ({$goods_ids})");
        $attr_all_item = $wmGoodsAttritemDao->getAllArray("aid={$this->aid} AND goods_id in ({$goods_ids})");

        foreach ($goods_list as $g_k => $goods)
        {
            $goods_list[$g_k]['pict_url'] = conver_picurl($goods['pict_url']);

            //该商品所有属性
            $attr_group = array_find($attr_all_group, 'goods_id', $goods['id']);
            if ($attr_group)
            {
                $attr_group['attr_value'] = array_find_list($attr_all_item, 'group_id', $attr_group['id']);
            }
            $goods_list[$g_k]['attr'] = $attr_group;

            //获取当前商品的sku
            $sku_list = array_find_list($sku_all_list, 'goods_id', $goods['id']);
            //整体sku属性数据结构
            foreach ($sku_list as $sk => $sku)
            {
                $sku_attr = [];
                $attr_ids_arr = explode(',', $sku['attr_ids']);
                if (!empty($attr_ids_arr)) {
                    foreach ($attr_ids_arr as $k => $attr_id)
                    {
                        if (empty($attr_id)) {
                            continue;
                        }
                        $_attr_value = array_find($attr_all_item, 'id', $attr_id);
                        $_attr_group = isset($_attr_value['group_id']) ? array_find($attr_all_group, 'id', $_attr_value['group_id']) : null;
                        $tmp['attr_group_title'] = isset($_attr_group['title']) ? $_attr_group['title'] : '';
                        $tmp['attr_group_id'] = isset($_attr_group['id']) ? $_attr_group['id'] : 0;
                        $tmp['attr_value'] = isset($_attr_value['attr_name']) ? $_attr_value['attr_name'] : '';
                        $tmp['attr_value_id'] = isset($_attr_value['id']) ? $_attr_value['id'] : 0;
                        $sku_attr[] = $tmp;
                    }
                }
                $sku_list[$sk]['sku_attr'] = $sku_attr;
            }
            $goods_list[$g_k]['sku_list'] = $sku_list;

        }

        $this->json_do->set_data($goods_list);
        $this->json_do->out_put();
    }

    /**
     * 获取随机数组
     * @param $arr 索引数组
     * @param int $limit
     */
    private function _array_rand($arr, $limit=0)
    {
        $_arr = [];
        $temp = [];
        if($limit==0) return $_arr;
        //######从数组总随机去除3条
        $_temp = array_rand($arr, $limit);
        //limit=1 array_rand返回的是值
        if($limit==1) $temp[] = $_temp;
        else $temp = $_temp;
        //重组数组
        foreach($temp as $val){
            $_arr[] = $arr[$val];
        }
        //######
        return $_arr;
    }
}

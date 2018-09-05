<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/4
 * Time: 10:33
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmCateDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttrgroupDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttritemDao;
class Items extends sy_controller
{
    // 所有分类商品列表
    public function cate_goods_list()
    {
        $rules = [
            ['field' => 'measure_type', 'label' => '商品类型', 'rules' => 'trim|in_list[0,1,2]'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $outWhere = '';
        if($fdata['measure_type'])
            $outWhere = " AND a.measure_type={$fdata['measure_type']} ";

        $shop_id = $this->s_user->shop_id;
        $wmCateDao = WmCateDao::i($this->s_user->aid);
        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->s_user->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->s_user->aid);

        //获取所有分类
        $cate_list = $wmCateDao->getAllArray(['aid' => $this->s_user->aid, 'shop_id' => $shop_id], '*', "sort desc,id desc");

        $fields = 'a.id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id promo_id,a.base_sale_num,a.pro_attrs,a.measure_type,a.unit_type';

        //获取店铺所有商品
        $goods_config_arr = array(
          'field' => $fields,
          'table' => "{$wmGoodsDao->db->tables['wm_goods']} a",
          'where' => " a.aid={$this->s_user->aid} AND a.shop_id={$shop_id} AND a.is_delete=0 AND a.status=1".$outWhere,
        );

        //获取所有商品列表
        $goods_all_list = $wmGoodsDao->getEntitysByAR($goods_config_arr, true);

        $goods_ids = implode(',', array_column($goods_all_list, 'id'));
        if (empty($goods_ids))
        {
          $goods_ids = '0';
        }

        //获取所有sku列表
        $sku_all_list = $wmGoodsSkuDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");
        $attr_all_group = $wmGoodsAttrgroupDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");
        $attr_all_item = $wmGoodsAttritemDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");


        foreach ($cate_list as $key => $cate)
        {
            //获取当前分类的商品
            $goods_list = $this->_get_cate_goods($goods_all_list, $cate['id']);
            foreach ($goods_list as $g_k => $goods)
            {
                $goods_list[$g_k]['pict_url'] = conver_picurl($goods['pict_url']);
                $goods_list[$g_k]['pro_attrs'] = @json_decode($goods['pro_attrs'], true);

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
            $cate_list[$key]['goods_list'] = empty($goods_list) ? [] : $goods_list;
        }

        $this->json_do->set_data($cate_list);
        $this->json_do->out_put();
    }

    /**
     * 商品搜索
     */
    public function search()
    {
        $rule = [
          ['field' => 'title', 'label' => '关键词', 'rules' => 'trim|required']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $shop_id = $this->s_user->shop_id;

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->s_user->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->s_user->aid);

        $fields = 'a.id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id promo_id,a.base_sale_num,a.pro_attrs,a.measure_type,a.unit_type';
        //获取店铺所有商品
        $goods_config_arr = array(
            'field' => $fields,
            'table' => "{$wmGoodsDao->db->tables['wm_goods']} a",
            'where' => " a.aid={$this->s_user->aid} AND a.shop_id={$shop_id} AND a.is_delete=0 AND a.status=1 AND a.title like '%{$f_data['title']}%'",
        );

        //获取所有商品列表
        $goods_list = $wmGoodsDao->getEntitysByAR($goods_config_arr, true);

        $goods_ids = implode(',', array_column($goods_list, 'id'));
        if (empty($goods_ids))
        {
            $goods_ids = '0';
        }
        //获取所有sku列表
        $sku_all_list = $wmGoodsSkuDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");
        $attr_all_group = $wmGoodsAttrgroupDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");
        $attr_all_item = $wmGoodsAttritemDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");

        foreach ($goods_list as $g_k => $goods)
        {
            $goods_list[$g_k]['pict_url'] = conver_picurl($goods['pict_url']);
            $goods_list[$g_k]['pro_attrs'] = @json_decode($goods['pro_attrs'], true);

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
}
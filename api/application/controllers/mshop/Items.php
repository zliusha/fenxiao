<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 商品数据接口
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\WmCateDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttrgroupDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttritemDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
class Items extends mshop_controller
{

    /**
     * 商品详情
     */
    public function goods_info()
    {

        $rule = [
            ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $goods_id = $f_data['goods_id'];

        $wmGoodsDao = WmGoodsDao::i($this->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->aid);
        $wmShardDb = WmShardDb::i($this->aid);
        $model = $wmGoodsDao->getOne(['id'=>$goods_id, 'aid' => $this->aid]);
        if(!$model)
        {
            $this->json_do->set_error('001', '参数错误');
        }
        $sku_list = $wmGoodsSkuDao->getAllArray(['goods_id'=>$goods_id, 'aid' => $this->aid]);
        $attr_groups = $wmGoodsAttrgroupDao->getAllArray(['goods_id'=>$goods_id, 'aid' => $this->aid]);
        $attr_values = $wmGoodsAttritemDao->getAllArray(['goods_id'=>$goods_id, 'aid' => $this->aid]);

        if(!empty($attr_groups))
        {
            foreach($attr_groups as $key => $group)
            {
              $attr_groups[$key]['value'] = array_find_list($attr_values,'group_id',$group['id']);
            }
        }
        $model->attr = $attr_groups;

        //属性
        if(!empty($model->pro_attrs))
        {
            $model->pro_attrs = @json_decode($model->pro_attrs);
        }

        //整体sku数据结构
        foreach($sku_list as $key => $sku)
        {
            $sku_attr = [];
            $attr_ids_arr = explode(',',$sku['attr_ids']);
            if(!empty($attr_ids_arr))
            {
                foreach($attr_ids_arr as $k => $attr_id)
                {
                    if(empty($attr_id)) continue;
                    $attr_value = array_find($attr_values, 'id', $attr_id);
                    $attr_group = array_find($attr_groups, 'id', $attr_value['group_id']);
                    $tmp['attr'] = $attr_group['title'];
                    $tmp['attr_id'] = $attr_group['id'];
                    $tmp['value'] = $attr_value['attr_name'];
                    $tmp['value_id'] = $attr_value['id'];
                    $sku_attr[] = $tmp;
                }
            }
            $sku_list[$key]['sku_attr'] = $sku_attr;
        }
        $model->sku = $sku_list;

        $this->json_do->set_data($model);
        $this->json_do->out_put();
    }

    /**
     * 所有分类商品列表
     */
    public function cate_goods_list()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $shop_id = $f_data['shop_id'];

        $wmCateDao = WmCateDao::i($this->aid);
        $wmGoodsDao = WmGoodsDao::i($this->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->aid);
        $wmOrderExtDao = WmOrderExtDao::i($this->aid);
        $wmShardDb = WmShardDb::i($this->aid);

        //获取所有分类
        $cate_list = $wmCateDao->getAllArray(['aid' => $this->aid,'shop_id'=>$shop_id], '*', "sort desc,id desc");

        $fields = 'a.id,a.store_goods_id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id,a.base_sale_num,a.pro_attrs,a.measure_type,a.unit_type';
        $fields .= ',b.start_time promo_start_time,b.end_time promo_end_time,b.type promo_type,b.discount_type promo_discount_type,b.limit_buy promo_limit_buy,b.setting promo_setting,b.status promo_status,b.shop_id promo_shop_id';
        //获取店铺所有商品
        $goods_config_arr = array(
            'field' => $fields,
            'table' => "{$wmShardDb->tables['wm_goods']} a",
            'join' => array(
                array("{$wmShardDb->tables['wm_promotion']} b", "a.promo_id=b.id", 'left')
            ),
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
                $goods_list[$g_k]['pro_attrs'] = @json_decode($goods['pro_attrs'], true);

                //当前用户已下单活动商品个数
                $goods_list[$g_k]['order_goods_number'] = 0;
                //所有用户已下单活动商品个数
                $goods_list[$g_k]['promo_total_goods_number'] = 0;
                if($goods['promo_id'] > 0 && $goods['promo_status'] == 2 && $goods['promo_start_time'] < time() && $goods['promo_end_time'] >= time())
                {
                      $time = strtotime(date('Y-m-d'));
                      if($time < $goods['promo_start_time']) $time = $goods['promo_start_time'];

                      $goods_list[$g_k]['order_goods_number'] = isset($this->s_user->uid) ? $wmOrderExtDao->orderUserGoodsNumber($goods['id'],$this->s_user->uid,$time,0,$this->aid) : 0;
                      $promo_setting_arr = json_decode($goods['promo_setting']);
                      if($promo_setting_arr && !empty($promo_setting_arr))
                      {
                          if($goods['promo_shop_id'] > 0)//子门店设置的活动
                          {
                              //商品的活动设置信息
                              $goods_list[$g_k]['promo_setting'] = object_find($promo_setting_arr, 'id', $goods['id']);
                          }
                          else//设置的全部门店活动
                          {
                              //商品的活动设置信息
                              $goods_list[$g_k]['promo_setting'] = object_find($promo_setting_arr, 'id', $goods['store_goods_id']);
                          }
                      }
                      else
                      {
                        $goods_list[$g_k]['promo_setting'] = null;
                      }
                }
                else
                {
                    $goods_list[$g_k]['promo_setting'] = null;
                }
                //该商品所有属性
                $attr_group = array_find($attr_all_group, 'goods_id', $goods['id']);
                if($attr_group)
                {
                    $attr_group['attr_value'] = array_find_list($attr_all_item,'group_id',$attr_group['id']);
                }
                $goods_list[$g_k]['attr'] = $attr_group;

                //获取当前商品的sku
                $sku_list = array_find_list($sku_all_list, 'goods_id', $goods['id']);
                //整体sku属性数据结构
                foreach($sku_list as $sk => $sku)
                {
                    $sku_attr = [];
                    $attr_ids_arr = explode(',',$sku['attr_ids']);
                    if(!empty($attr_ids_arr))
                    {
                        foreach($attr_ids_arr as $k => $attr_id)
                        {
                            if(empty($attr_id)) continue;
                            $_attr_value = array_find($attr_all_item, 'id', $attr_id);
                            $_attr_group = isset($_attr_value['group_id']) ? array_find($attr_all_group, 'id', $_attr_value['group_id']) : null;
                            $tmp['attr_group_title'] = isset($_attr_group['title']) ? $_attr_group['title'] : '';
                            $tmp['attr_group_id'] = isset($_attr_group['id']) ? $_attr_group['id'] : 0;
                            $tmp['attr_value'] = isset($_attr_value['attr_name']) ? $_attr_value['attr_name'] :''; ;
                            $tmp['attr_value_id'] = isset($_attr_value['id']) ? $_attr_value['id'] : 0;;
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


    /**
     * 商品搜索
     */
    public function search()
    {
        $rule = [
            ['field' => 'title', 'label' => '关键词', 'rules' => 'trim|required'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $shop_id = $f_data['shop_id'];

        $wmGoodsDao = WmGoodsDao::i($this->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->aid);
        $wmOrderExtDao = WmOrderExtDao::i($this->aid);
        $wmShardDb = WmShardDb::i($this->aid);

        $fields = 'a.id,a.store_goods_id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id,a.base_sale_num,a.pro_attrs,a.measure_type,a.unit_type';
        $fields .= ',b.start_time promo_start_time,b.end_time promo_end_time,b.type promo_type,b.discount_type promo_discount_type,b.limit_buy promo_limit_buy,b.setting promo_setting,b.status promo_status,b.shop_id promo_shop_id';
        //获取店铺所有商品
        $goods_config_arr = array(
            'field' => $fields,
            'table' => "{$wmShardDb->tables['wm_goods']} a",
            'join' => array(
                array("{$wmShardDb->tables['wm_promotion']} b", "a.promo_id=b.id", 'left')
            ),
            'where' => " a.aid={$this->aid} AND a.shop_id={$shop_id} AND a.is_delete=0 AND a.status=1 AND a.measure_type=1 AND a.title like '%{$f_data['title']}%'",
        );

        //获取所有商品列表
        $goods_list = $wmGoodsDao->getEntitysByAR($goods_config_arr, true);

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
            $goods_list[$g_k]['pro_attrs'] = @json_decode($goods['pro_attrs'], true);

            //当前用户已下单活动商品个数
            $goods_list[$g_k]['order_goods_number'] = 0;
            //所有用户已下单活动商品个数
            $goods_list[$g_k]['promo_total_goods_number'] = 0;
            if($goods['promo_id'] > 0 && $goods['promo_status'] == 2 && $goods['promo_start_time'] < time() && $goods['promo_end_time'] >= time())
            {
                $time = strtotime(date('Y-m-d'));
                if($time < $goods['promo_start_time']) $time = $goods['promo_start_time'];

                $goods_list[$g_k]['order_goods_number'] = $wmOrderExtDao->orderUserGoodsNumber($goods['id'],$this->s_user->uid,$time,0,$this->aid);
                $promo_setting_arr = json_decode($goods['promo_setting']);
                if($promo_setting_arr && !empty($promo_setting_arr))
                {
                    if($goods['promo_shop_id'] > 0)//子门店设置的活动
                    {
                        //商品的活动设置信息
                        $goods_list[$g_k]['promo_setting'] = object_find($promo_setting_arr, 'id', $goods['id']);
                    }
                    else//设置的全部门店活动
                    {
                        //商品的活动设置信息
                        $goods_list[$g_k]['promo_setting'] = object_find($promo_setting_arr, 'id', $goods['store_goods_id']);
                    }
                }
                else
                {
                    $goods_list[$g_k]['promo_setting'] = null;
                }
            }
            else
            {
                $goods_list[$g_k]['promo_setting'] = null;
            }

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
}

<?php
/**
 * @Author: binghe
 * @Date:   2017-08-15 14:04:22
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:50
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmZxSelectModuleDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttritemDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttrgroupDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
/**
* 微商城装修bll
*/
class wm_zx_bll extends base_bll
{

    /**
     * 得到页面模块数据
     * @param  int  $shop_id 店铺id
     * @param  varchar $module_is_save 过滤模块是否保存:all,yes,no
     * @return [type]                  [description]
     */
    /**
     * @param int $shop_id
     * @param int $module_id
     * @param bool $type
     * @param string $module_is_save
     * @return mixed
     */
    function get_modules($shop_id=0, $module_id=0, $aid=0, $uid=0, $type=false, $module_is_save='all')
    {
        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($aid);
        $where['aid']=$aid;
        $where['shop_id']=$shop_id;
        if($module_id > 0) $where['module_id']=$module_id;
        switch ($module_is_save) {
            case 'yes':
                $where['is_save']=1;
                break;
            case 'no':
                $where['is_save']=0;
                break;
            default:break;

        }
        $m_wsc_select_modules = $wmZxSelectModuleDao->getAll($where,'*','sort asc,id asc');
        foreach ($m_wsc_select_modules as $module) {
            if($type == true)//预览数据
            {
                if($module->ext)
                {
                    $module->module_data = $module->ext;
                    //模块数据
                    $module->module_data=$this->get_module_do($module->module_id,unserialize($module->module_data));
                }


                //模块系统数据
                $module->sys_data=$this->get_sys_data($module, $uid);
            }
            else   //正式数据
            {
                if($module->module_data)
                  //模块数据
                  $module->module_data=$this->get_module_do($module->module_id,unserialize($module->module_data));
                  //模块系统数据
                  $module->sys_data=$this->get_sys_data($module, $uid);
            }

        }
        return $m_wsc_select_modules;
    }
    /**
     * 获取模块的实体
     * @param  int $module_id 模块id
     * @param  obj $module_do json,初始化数据
     * @return obj            返回module_do
     */
    function get_module_do($module_id,$module_do=null)
    {
        $wsc_zx_do=null;
        switch ($module_id) {
            case 1://门店海报
               $wsc_zx_do = new wm_zx_poster_do();
                break;
            case 2://推荐商品
               $wsc_zx_do = new wm_zx_tj_goods_do();
                break;
            case 3://小程序横幅
              $wsc_zx_do = new wm_zx_xcx_banner_do();
              break;
            case 4://扫码小程序横幅
                $wsc_zx_do = new wm_zx_meal_banner_do();
                break;
        }
        if($wsc_zx_do)
        {
            //初始化数据
            if($module_do)
            {
                //将module_do数据映射到$wsc_zx_do
                foreach ($wsc_zx_do as $k1 => $v1) {
                    foreach ($module_do as $k2 => $v2) {
                       if($k1 == $k2 )
                       {
                         $wsc_zx_do->$k1=$v2;
                         continue;
                       }
                    }
                }

            }
        }
        return $wsc_zx_do;
    }
    /**
     * 得到系统模块
     * @param  obj $m_wm_zx_select_module 模块
     * @return obj                     系统数据
     */
    public function get_sys_data($m_wm_zx_select_module, $uid=0)
    {
        if(!$m_wm_zx_select_module)
            return null;
        $sys_data=null;
        switch ($m_wm_zx_select_module->module_id) {
            case 1: //门店海报
                $sys_data=$this->_poster_sys_data($m_wm_zx_select_module, $uid);
                break;
            case 2: //推荐商品
                $sys_data=$this->_tj_goods_sys_data($m_wm_zx_select_module, $uid);
                break;
        }
        return $sys_data;
    }
   
  /**
   * 获取推荐商品信息
   * @param $m_wm_zx_select_module
   * @return null
   */
    private function _tj_goods_sys_data($m_wm_zx_select_module, $uid=0)
    {
        if(!$m_wm_zx_select_module->module_data)
          return null;

        $goods_ids = $m_wm_zx_select_module->module_data->good_ids;
        return $this->_goods_data($goods_ids, $uid, $m_wm_zx_select_module->aid);
    }

    /**
     * 获取海报商品信息
     * @param $m_wm_zx_select_module
     * @return null
     */
    private function _poster_sys_data($m_wm_zx_select_module, $uid=0)
    {
        if(!$m_wm_zx_select_module->module_data)
            return null;

        $goods_ids = $m_wm_zx_select_module->module_data->good_ids;
        return $this->_goods_data($goods_ids, $uid,$m_wm_zx_select_module->aid);
    }

    /**
     * 根据goods_ids获取商品
     * @param $goods_ids 多个逗号隔开
     * @param int $uid
     * @return array|null
     */
    private function _goods_data($goods_ids, $uid=0,$aid)
    {
        if(empty($goods_ids)) return null;
        $wmGoodsDao = WmGoodsDao::i($aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($aid);
        $wmOrderExtDao = WmOrderExtDao::i($aid);

        $fields = 'a.id,a.store_goods_id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id,a.base_sale_num,a.pro_attrs,a.measure_type,a.unit_type';
        $fields .= ',b.start_time promo_start_time,b.end_time promo_end_time,b.type promo_type,b.discount_type promo_discount_type,b.limit_buy promo_limit_buy,b.setting promo_setting,b.status promo_status,b.shop_id promo_shop_id';
        //获取店铺所有商品
        $goods_config_arr = array(
            'field' => $fields,
            'table' =>"{$wmGoodsDao->db->tables['wm_goods']} a",
            'join' => array(
                array("{$wmGoodsDao->db->tables['wm_promotion']} b", "a.promo_id=b.id", 'left'),
            ),
            'where' => " a.is_delete=0 AND a.status=1  AND a.measure_type=1 AND a.id in ({$goods_ids})",
        );

        //获取所有商品列表
        $goods_list = $wmGoodsDao->getEntitysByAR($goods_config_arr, true);

        $sku_all_list = $wmGoodsSkuDao->getAllArray("goods_id in ({$goods_ids})");
        $attr_all_group = $wmGoodsAttrgroupDao->getAllArray("goods_id in ({$goods_ids})");
        $attr_all_item = $wmGoodsAttritemDao->getAllArray("goods_id in ({$goods_ids})");
        if(!empty($goods_list))
        {
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

                    if($uid > 0)
                        $goods_list[$g_k]['order_goods_number'] = $uid>0 ? $wmOrderExtDao->orderUserGoodsNumber($goods['id'], $uid, $time, 0,$aid) : 0;
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
                            $_attr_group = array_find($attr_all_group, 'id', $_attr_value['group_id']);
                            $tmp['attr_group_title'] = $_attr_group['title'];
                            $tmp['attr_group_id'] = $_attr_group['id'];
                            $tmp['attr_value'] = $_attr_value['attr_name'];
                            $tmp['attr_value_id'] = $_attr_value['id'];
                            $sku_attr[] = $tmp;
                        }
                    }
                    $sku_list[$sk]['sku_attr'] = $sku_attr;
                }
                $goods_list[$g_k]['sku_list'] = $sku_list;

            }
        }
        $goods_ids_arr = explode(',', trim($goods_ids, ','));
        $data = [];
        foreach($goods_ids_arr as $goods_id)
        {
            if(empty($goods_id) || !is_numeric($goods_id)) continue;
            $goods = array_find($goods_list, 'id', $goods_id);

            if($goods)
                $data[] = $goods;
        }

        return $data;
    }
}
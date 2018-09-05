<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/2/23
 * Time: 9:48
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmZxSelectModuleDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCateDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttrgroupDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttritemDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPromotionDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
class Decorate_api extends wm_service_controller
{

  /**
   * 装修门店招牌图片，背景图片
   */
    public function store()
    {
        $rules = [
            ['field' => 'bg_img', 'label' => '背景图片', 'rules' => 'trim'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $f_data=$this->form_data($rules);

        $shop_id = $f_data['shop_id'];
        if(!$this->is_zongbu)
        {
            $shop_id = $this->currentShopId;
        }

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $wmShopDao->update(['bg_img'=>$f_data['bg_img']], ['id'=>$shop_id, 'aid'=>$this->s_user->aid]);

        $this->json_do->set_msg('保存成功');
        $this->json_do->out_put();
    }

    /**
     * 得到单一具体模块数据
     * @return [type] [description]
     */
    public function get_module_data()
    {
        $id = $this->input->get('id');
        $type = $this->input->get('type');
        $shop_id = intval($this->input->get('shop_id'));

        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }
        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->s_user->aid);
        $m_wm_select_module = $wmZxSelectModuleDao->getOne(['id' => $id, 'aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
        if (!$m_wm_select_module)
        {
          $this->json_do->set_error('004', '记录不存在');
        }

        if(is_numeric($type) && $type == 1)//获取预览数据
        {
            if ($m_wm_select_module->ext)
            {
                $wm_zx_bll = new wm_zx_bll();
                $m_wm_select_module->module_data = $m_wm_select_module->ext;
                //模块数据
                $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, unserialize($m_wm_select_module->module_data));

                //模块系统数据
                $m_wm_select_module->sys_data = $wm_zx_bll->get_sys_data($m_wm_select_module);
            }

        }
        else
        {
            if ($m_wm_select_module->module_data) {
              $wm_zx_bll = new wm_zx_bll();
              //模块数据
              $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, unserialize($m_wm_select_module->module_data));

              //模块系统数据
              $m_wm_select_module->sys_data = $wm_zx_bll->get_sys_data($m_wm_select_module);
            }
        }


        $this->json_do->set_data($m_wm_select_module);
        $this->json_do->out_put();
    }
    /**
     * 得到模块数据
     * @return [type] [description]
     */
    public function get_modules_data()
    {
        $type = $this->input->get('type');
        $shop_id = intval($this->input->get('shop_id'));
        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }

        $wm_zx_bll = new wm_zx_bll();
        $m_poster_modules = $wm_zx_bll->get_modules($shop_id, 1, $this->s_user->aid, 0, $type);
        $m_tj_goods_modules = $wm_zx_bll->get_modules($shop_id, 2, $this->s_user->aid, 0, $type);

        $data['poster_modules'] = $m_poster_modules;
        $data['tj_goods_modules'] = $m_tj_goods_modules;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 更改模块数据
     * @return [type] [description]
     */
    public function save_module_data()
    {
        $rules = [
            ['field' => 'id', 'label' => '记录ID', 'rules' => 'trim|numeric'],
            ['field' => 'module_id', 'label' => '模块ID', 'rules' => 'trim|numeric'],
            ['field' => 'json', 'label' => '模块数据', 'rules' => 'trim'],
            ['field' => 'is_save', 'label' => '添加类型', 'rules' => 'trim|numeric'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $f_data = $this->form_data($rules);
        $req = @json_decode($f_data['json']);

        if (empty($f_data['module_id'])  || empty($req))
        {
            $this->json_do->set_error('001');
        }

        $shop_id = $f_data['shop_id'];
        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }

        //id=0,则新增记录
        if(empty($f_data['id']))
          $this->_add_module_data(['module_id' => $f_data['module_id'], 'json' => $req, 'is_save' => $f_data['is_save'], 'shop_id' => $shop_id]);


        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->s_user->aid);
        $m_wm_zx_select_module = $wmZxSelectModuleDao->getOne(['id' => $f_data['id'], 'aid'=>$this->s_user->aid, 'shop_id'=>$shop_id]);

        if (!$m_wm_zx_select_module) {
          $this->json_do->set_error('004', '记录不存在');
        }

        //type=1,则更新扩展数据
        if(!isset($f_data['is_save']) || $f_data['is_save'] == 0)
        {
            $this->_save_ext_data(['id'=> $f_data['id'], 'module_id' => $f_data['module_id'], 'json' => $req, 'shop_id' => $shop_id]);
        }

        $wm_zx_bll = new wm_zx_bll();
        $module_do = null;
        if ($m_wm_zx_select_module->module_data) {
            $module_do = unserialize($m_wm_zx_select_module->module_data);
        }

        $wm_zx_do = $wm_zx_bll->get_module_do($m_wm_zx_select_module->module_id, $module_do);
        //模块数据重新赋值

        foreach ($wm_zx_do as $k1 => $v1) {
          foreach ($req as $k2 => $v2) {
            if ($k1 == $k2) {
              $wm_zx_do->$k1 = $v2;
            }
          }
        }

        //更新模块数据
        if ($wmZxSelectModuleDao->update(['module_data' => serialize($wm_zx_do), 'ext' => serialize($wm_zx_do), 'is_save' => 1], ['id' => $m_wm_zx_select_module->id]) !== false) {
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005','保存失败');
        }

    }

    /**
     * 更改模块数据
     * @return [type] [description]
     */
    private function _save_ext_data($input=[])
    {

        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->s_user->aid);
        $m_wm_zx_select_module = $wmZxSelectModuleDao->getOne(['id' => $input['id'], 'aid'=>$this->s_user->aid, 'shop_id'=>$input['shop_id']]);

        if (!$m_wm_zx_select_module) {
          $this->json_do->set_error('004', '记录不存在');
        }


        $wm_zx_bll = new wm_zx_bll();
        $module_do = null;
        if ($m_wm_zx_select_module->ext) {
          $module_do = unserialize($m_wm_zx_select_module->ext);
        }

        $wm_zx_do = $wm_zx_bll->get_module_do($m_wm_zx_select_module->module_id, $module_do);
        //模块数据重新赋值

        foreach ($wm_zx_do as $k1 => $v1) {
          foreach ($input['json'] as $k2 => $v2) {
            if ($k1 == $k2) {
              $wm_zx_do->$k1 = $v2;
            }

          }
        }

        //更新模块数据
        if ($wmZxSelectModuleDao->update(['ext' => serialize($wm_zx_do)], ['id' => $m_wm_zx_select_module->id]) !== false) {
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005','保存失败');
        }

    }

    /**
     * @param int $id
     */
    private function _add_module_data($input=[])
    {
        $module_do = $input['json'];

        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->s_user->aid);

        if($input['module_id'] == 2)
        {
            $m_wm_select_module = $wmZxSelectModuleDao->getOne(['module_id'=>2, 'aid'=> $this->s_user->aid, 'shop_id' => $input['shop_id']]);
            if($m_wm_select_module)
            {
                $this->json_do->set_error('005', '该模块无法重复添加');
            }
        }
        $wm_zx_bll = new wm_zx_bll();
        $wm_zx_do = $wm_zx_bll->get_module_do($input['module_id'], $module_do);
        if(!isset($input['is_save']) || $input['is_save'] == 0)
        {
            $data = [
              'sort' => 0,
              'is_save' => 0,
              'aid' => $this->s_user->aid,
              'shop_id' => $input['shop_id'],
              'module_id' => $input['module_id'],
              'ext' => serialize($wm_zx_do),
              'time' => time()
            ];
        }
        else
        {
            $data = [
            'sort' => 0,
            'is_save' => 1,
            'aid' => $this->s_user->aid,
            'shop_id' => $input['shop_id'],
            'module_id' => $input['module_id'],
            'module_data' => serialize($wm_zx_do),
            'ext' => serialize($wm_zx_do),
            'time' => time()
        ];

        }


        $id = $wmZxSelectModuleDao->create($data);
        if ($id) {
            $this->json_do->set_data(['id' => $id]);
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '添加模块失败');
        }
    }

    /**
     * 删除页面
     * @return [type] [description]
     */
    public function del_module()
    {
        $id = $this->input->post('id');
        if(!is_numeric($id))
            $this->json_do->set_error('005', '参数错误');

        $shop_id = intval($this->input->post('shop_id'));
        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }
        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->s_user->aid);
        $m_wm_zx_select_module = $wmZxSelectModuleDao->getOne(['id' => $id,'aid'=>$this->s_user->aid, 'shop_id' => $shop_id]);

        //检测橱窗
        if(!$m_wm_zx_select_module)
            $this->json_do->set_error('004','记录不存在');

        if ($wmZxSelectModuleDao->delete(['id' => $m_wm_zx_select_module->id,'aid'=>$this->s_user->aid, 'shop_id' => $shop_id]))
        {
            $this->json_do->set_msg('删除成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '删除失败');
        }
    }

    /**
     * 预览店铺详情
     */
    public function preview_shop_info()
    {
        $shop_id = intval($this->input->get('shop_id'));
        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }
        $WmShopDao = WmShopDao::i($this->s_user->aid);
        $shop_model = $WmShopDao->getOne(['id'=>$shop_id]);
        if(!$shop_model)
        {
          $this->json_do->set_error('001', '未找到相关店铺');
        }

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $wmPromotionDao = WmPromotionDao::i($this->s_user->aid);

        $_before_time = strtotime(date('Y-m-d')) - 30*24*3600;
        $shop_model->order_count = $wmOrderDao->getCount("aid={$this->s_user->aid} AND shop_id={$shop_id} AND time >= {$_before_time}");
        $shop_model->shop_logo = conver_picurl($shop_model->shop_logo);

        $time = time();
        $promotion_model = $wmPromotionDao->getOne("aid={$this->s_user->aid} AND shop_id={$shop_id} AND type=2 AND status=2 AND start_time < {$time} AND end_time > {$time}");

        if($promotion_model)
        {
          $promotion_model->setting = array_sort(json_decode($promotion_model->setting), 'price');
        }
        $this->json_do->set_data(['shop'=>$shop_model, 'promotion'=>$promotion_model]);
        $this->json_do->out_put();
    }
    /**
     * 所有分类商品列表
     */
    public function preview_cate_goods_list()
    {
        $shop_id = intval($this->input->get('shop_id'));
        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }

        $wmCateDao = WmCateDao::i($this->s_user->aid);
        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->s_user->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->s_user->aid);
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        //获取所有分类
        $cate_list = $wmCateDao->getAllArray(['aid' => $this->s_user->aid,'shop_id'=>$shop_id], '*', "sort desc,id desc");

        $fields = 'a.id,a.store_goods_id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id,a.base_sale_num,a.pro_attrs,a.measure_type,a.unit_type';
        $fields .= ',b.start_time promo_start_time,b.end_time promo_end_time,b.type promo_type,b.discount_type promo_discount_type,b.limit_buy promo_limit_buy,b.setting promo_setting,b.status promo_status,b.shop_id promo_shop_id';
        //获取店铺所有商品
        $goods_config_arr = array(
          'field' => $fields,
          'table' => "{$wmShardDb->tables['wm_goods']} a",
          'join' => array(
            array("{$wmShardDb->tables['wm_promotion']} b", "a.promo_id=b.id", 'left'),
          ),
          'where' => " a.aid={$this->s_user->aid} AND a.shop_id={$shop_id} AND a.is_delete=0 AND a.status=1 AND a.measure_type=1",
        );

        //获取所有商品列表
        $goods_all_list = $wmGoodsDao->getEntitysByAR($goods_config_arr, true);

        $goods_ids = implode(',', array_column($goods_all_list, 'id'));
        if (empty($goods_ids)) {
          $goods_ids = '0';
        }

        //获取所有sku列表
        $sku_all_list = $wmGoodsSkuDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");
        $attr_all_group = $wmGoodsAttrgroupDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");
        $attr_all_item = $wmGoodsAttritemDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");

        foreach ($cate_list as $key => $cate) {
          //获取当前分类的商品
          $goods_list = $this->_get_cate_goods($goods_all_list, $cate['id']);
          foreach ($goods_list as $g_k => $goods) {
            $goods_list[$g_k]['pict_url'] = conver_picurl($goods['pict_url']);

            //当前用户已下单活动商品个数
            $goods_list[$g_k]['order_goods_number'] = 0;
            //所有用户已下单活动商品个数
            $goods_list[$g_k]['promo_total_goods_number'] = 0;
            if($goods['promo_id'] > 0 && $goods['promo_status'] == 2 && $goods['promo_start_time'] < time() && $goods['promo_end_time'] >= time())
            {
              $time = strtotime(date('Y-m-d'));
              if($time < $goods['promo_start_time']) $time = $goods['promo_start_time'];

              $goods_list[$g_k]['order_goods_number'] = 0;
              $promo_setting_arr = json_decode($goods['promo_setting']);
              if($promo_setting_arr && !empty($promo_setting_arr))
              {
                //商品的活动设置信息
                $goods_list[$g_k]['promo_setting'] = object_find($promo_setting_arr, 'id', $goods['id']);
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
     * 所有分类商品列表
     */
    public function goods_list()
    {
        $shop_id = intval($this->input->get('shop_id'));
        if (empty($shop_id) && !is_numeric($shop_id)) {
          $this->json_do->set_error('001', '参数错误');
        }

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->s_user->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->s_user->aid);
        $wmShardDb = WmShardDb::i($this->s_user->aid);

        $fields = 'a.id,a.store_goods_id,a.shop_id,a.title,a.pict_url,a.cate_ids,a.sku_type,a.tag,a.description,a.promo_id,a.base_sale_num,a.pro_attrs,a.measure_type,a.unit_type';
        $fields .= ',b.start_time promo_start_time,b.end_time promo_end_time,b.type promo_type,b.discount_type promo_discount_type,b.limit_buy promo_limit_buy,b.setting promo_setting,b.status promo_status,b.shop_id promo_shop_id';
        //获取店铺所有商品
        $goods_config_arr = array(
          'field' => $fields,
            'table' => "{$wmShardDb->tables['wm_goods']} a",
            'join' => array(
                array("{$wmShardDb->tables['wm_promotion']} b", "a.promo_id=b.id", 'left'),
          ),
          'where' => " a.aid={$this->s_user->aid} AND a.shop_id={$shop_id} AND a.is_delete=0 AND a.status=1 AND a.measure_type=1",
        );

        //获取所有商品列表
        $goods_list = $wmGoodsDao->getEntitysByAR($goods_config_arr, true);

        $goods_ids = implode(',', array_column($goods_list, 'id'));
        if (empty($goods_ids)) {
          $goods_ids = '0';
        }

        //获取所有sku列表
        $sku_all_list = $wmGoodsSkuDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");
        $attr_all_group = $wmGoodsAttrgroupDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");
        $attr_all_item = $wmGoodsAttritemDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");


        foreach ($goods_list as $g_k => $goods) {
            $goods_list[$g_k]['pict_url'] = conver_picurl($goods['pict_url']);

            //当前用户已下单活动商品个数
            $goods_list[$g_k]['order_goods_number'] = 0;
            //所有用户已下单活动商品个数
            $goods_list[$g_k]['promo_total_goods_number'] = 0;
            if($goods['promo_id'] > 0 && $goods['promo_status'] == 2 && $goods['promo_start_time'] < time() && $goods['promo_end_time'] >= time())
            {
                $time = strtotime(date('Y-m-d'));
                if($time < $goods['promo_start_time']) $time = $goods['promo_start_time'];

                $promo_setting_arr = json_decode($goods['promo_setting']);
                if($promo_setting_arr && !empty($promo_setting_arr))
                {
                  //商品的活动设置信息
                  $goods_list[$g_k]['promo_setting'] = object_find($promo_setting_arr, 'id', $goods['id']);
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


        $this->json_do->set_data($goods_list);
        $this->json_do->out_put();
    }

  /**
   * 小程序启动页数据获取接口
   */
    public function get_xcx_index_data()
    {
        $id = intval($this->input->get('module_id'));

        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->s_user->aid);
        $m_wm_select_module = $wmZxSelectModuleDao->getOne(['module_id' => $id, 'aid' => $this->s_user->aid, 'shop_id' => 0, 'is_save' => 1]);
        if (!$m_wm_select_module)
        {
            $wm_zx_bll = new wm_zx_bll();
            $wm_zx_do = $wm_zx_bll->get_module_do(3);

              $data = [
                'sort' => 0,
                'is_save' => 1,
                'aid' => $this->s_user->aid,
                'shop_id' => 0,
                'module_id' => 3,
                'module_data' => serialize($wm_zx_do),
                'time' => time()
              ];

            $_id = $wmZxSelectModuleDao->create($data);
            if(!$_id)
            {
                $this->json_do->set_error('005', '数据错误');
            }
            $m_wm_select_module = $wmZxSelectModuleDao->getOne(['id' => $_id, 'aid' => $this->s_user->aid, 'shop_id' => 0, 'is_save' => 1]);
        }


        if ($m_wm_select_module->module_data) {
            $wm_zx_bll = new wm_zx_bll();
            //模块数据
            $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, unserialize($m_wm_select_module->module_data));

            //模块系统数据
            $m_wm_select_module->sys_data = $wm_zx_bll->get_sys_data($m_wm_select_module);
        }

        $this->json_do->set_data($m_wm_select_module);
        $this->json_do->out_put();
    }

  /**
   * 是否开启显示
   */
    public function xcx_index_on()
    {
        $is_on = intval($this->input->post('is_on'));

        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->s_user->aid);
        $m_wm_select_module = $wmZxSelectModuleDao->getOne(['module_id' => 3, 'aid' => $this->s_user->aid, 'shop_id' => 0, 'is_save' => 1]);
        if(!$m_wm_select_module || !$m_wm_select_module->module_data)
        {
            $this->json_do->set_error('005', '数据错误');
        }

        //模块数据
        $module_data = @unserialize($m_wm_select_module->module_data);
        $module_data->is_on = $is_on;
        $module_data = serialize($module_data);

        $res = $wmZxSelectModuleDao->update(['module_data' => $module_data], ['id' => $m_wm_select_module->id, 'aid' => $this->s_user->aid]);
        if($res === false)
        {
            $this->json_do->set_error('005', '失败');
        }

        $this->json_do->set_msg('成功');
        $this->json_do->out_put();
    }

    /**
     * 得到单一具体模块数据
     * @return [type] [description]
     */
    public function get_select_module_data()
    {

        $rules = [
            ['field' => 'module_id', 'label' => '模块ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'type', 'label' => '保存类型', 'rules' => 'trim|numeric'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $f_data=$this->form_data($rules);

        $id = $f_data['module_id'];
        $type = $f_data['type'];
        $shop_id = $f_data['shop_id'];

        if(!$this->is_zongbu)
        {
            $shop_id = $this->currentShopId;
        }
        $wmZxSelectModuleDao = WmZxSelectModuleDao::i($this->s_user->aid);
        $m_wm_select_module = $wmZxSelectModuleDao->getOne(['module_id' => $id, 'aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
        if (!$m_wm_select_module)
        {
            $this->json_do->set_error('004', '记录不存在');
        }

        if(is_numeric($type) && $type == 1)//获取预览数据
        {
            if ($m_wm_select_module->ext)
            {
                $wm_zx_bll = new wm_zx_bll();
                $m_wm_select_module->module_data = $m_wm_select_module->ext;
                //模块数据
                $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, unserialize($m_wm_select_module->module_data));

                //模块系统数据
                $m_wm_select_module->sys_data = $wm_zx_bll->get_sys_data($m_wm_select_module);
            }

        }
        else
        {
            if ($m_wm_select_module->module_data) {
                $wm_zx_bll = new wm_zx_bll();
                //模块数据
                $m_wm_select_module->module_data = $wm_zx_bll->get_module_do($m_wm_select_module->module_id, unserialize($m_wm_select_module->module_data));

                //模块系统数据
                $m_wm_select_module->sys_data = $wm_zx_bll->get_sys_data($m_wm_select_module);
            }
        }


        $this->json_do->set_data($m_wm_select_module);
        $this->json_do->out_put();
    }
}
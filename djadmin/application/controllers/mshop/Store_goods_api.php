<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmStoreGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmStoreGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmStoreGoodsAttrgroupDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmStoreGoodsAttritemDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCateDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttrgroupDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmSyncRecordDao;
/**
 * 商品管理
 */
class Store_goods_api extends wm_service_controller
{

    public function index()
    {
        $attr = [
            ['name'=>'商品规格', 'value'=>['红色','黑色','蓝色']],
        ];
        $sku = [
            [
              'sku_attr'=>[
                ['attr'=>'商品规格','value'=>'红色'],
              ],
              'box_fee'=>'餐盒费',
              'sale_price'=>'售价',
              'sku_id'=>''
            ]
        ];
    }

  /**
   * 仓库商品列表
   */
    public function goods_list()
    {
        $title = $this->input->get('title');

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$wmShardDb->tables['wm_store_goods']} a ";
        $p_conf->order = 'a.id desc';
        $p_conf->fields = 'a.id goods_id,a.*';
        $p_conf->where .= "  AND a.aid={$this->s_user->aid} AND a.is_delete=0 ";

        if(!empty($title))
        {
          $p_conf->where .= " AND a.title LIKE '%".$page->filterLike($title)."%'";
        }

        $count = 0;
        $list = $page->getList($p_conf, $count);
        $wmStoreGoodsSkuDao = WmStoreGoodsSkuDao::i($this->s_user->aid);

        $goods_ids = trim(implode(',',array_column($list, 'id')), ',');
        if(empty($goods_ids)) $goods_ids = '0';
        //获取所有sku
        $sku_all_list = $wmStoreGoodsSkuDao->getAllArray("goods_id in ({$goods_ids})");

        //整理数据结构
        foreach($list as $key=>$goods)
        {
            $sku_list = array_find_list($sku_all_list, 'goods_id', $goods['id']);
            $list[$key]['sku_list'] = $sku_list;
        }

        $data['total'] = $count;
        $data['rows'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 商品添加
     */
    public function goods_add()
    {
        $rule = [
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim|required'],
            // ['field' => 'pict_url', 'label' => '商品主图', 'rules' => 'trim|required'],
            ['field' => 'sku_type', 'label' => '规格类型', 'rules' => 'required|in_list[0,1]'],
            ['field' => 'description', 'label' => '商品详情', 'rules' => 'trim'],
            ['field' => 'tag', 'label' => '标签', 'rules' => 'trim|required'],
            ['field' => 'attr', 'label' => '商品属性', 'rules' => 'trim|is_json'],
            ['field' => 'sku', 'label' => '商品SKU', 'rules' => 'trim|required|is_json'],
            ['field' => 'cate_ids', 'label' => '分类IDS', 'rules' => 'trim|preg_key[IDS]'],
            ['field' => 'cate_names', 'label' => '分类名称', 'rules' => 'trim'],
            ['field' => 'picarr', 'label' => '商品图集', 'rules' => 'trim|required'],
            // ['field' => 'is_open_mprice', 'label' => '开启会员价', 'rules' => 'trim|required|in_list[0,1]'],
            ['field' => 'goods_sn', 'label' => '商品编码', 'rules' => 'trim'],
            ['field' => 'measure_type', 'label' => '计量方式', 'rules' => 'trim|required|in_list[1,2]'],
            ['field' => 'unit_type', 'label' => '单位', 'rules' => 'trim|in_list[0,1,2,3,4,5,6]'],
            ['field' => 'pro_attrs', 'label' => '属性', 'rules' => 'trim|is_json'],
//            ['field' => 'base_sale_num', 'label' => '基础销量', 'rules' => 'trim|numeric']

        ];

        if($this->check->check_ajax_form($rule, false) === false)
        {
          $this->json_do->set_error('002', validation_errors());
        }
        $f_data = $this->form_data($rule);
        $f_data['sku'] = @json_decode($f_data['sku'], true);
        $f_data['attr'] = @json_decode($f_data['attr'], true);
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
        $wmStoreGoodsSkuDao = WmStoreGoodsSkuDao::i($this->s_user->aid);
        
        //检测属性重复
        if(!$this->_attr_repeat(@json_decode($f_data['pro_attrs'], true)))
            $this->json_do->set_error('004','属性重复');

        $picarr = explode(',', $f_data['picarr']);
        //检测商品编号
        if($f_data['goods_sn'])
        {
            $m_wm_store_goods = $wmStoreGoodsDao->getOne(['aid'=>$this->s_user->aid,'goods_sn'=>$f_data['goods_sn']],'id');
            if($m_wm_store_goods)
                $this->json_do->set_error('004','商品条码已存在');

        }
        //检测sku编码
        $goods_sku_sns = array_column($f_data['sku'], 'goods_sku_sn');
        $goods_sku_sns = array_filter($goods_sku_sns,function($val){
            return !empty($val);
        });
        if($goods_sku_sns)
        {
            //当前有重复
            if(count($goods_sku_sns) != count(array_unique($goods_sku_sns)))
            {
                $this->json_do->set_error('sku条码不能重复');
            }
            //检测数据库唯一
            $tmpWhereIn=sql_where_in($goods_sku_sns,false);
            $skuExistWhere=" aid={$this->s_user->aid} and goods_sku_sn in({$tmpWhereIn})";
            $existSkuSns = $wmStoreGoodsSkuDao->getAllArray($skuExistWhere,'goods_sku_sn');
            if($existSkuSns)
                $this->json_do->set_error('004','sku条码已存在 ('.implode(',', array_column($existSkuSns, 'goods_sku_sn')).')');
        }
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $wmShardDb->trans_start();
        $goods['aid'] = $this->s_user->aid;
        $goods['title'] = $f_data['title'];
        $goods['pict_url'] = $picarr['0'];
        $goods['tag'] = $f_data['tag'];
        $goods['sku_type'] = $f_data['sku_type'];
        $goods['description'] = $f_data['description'];
        $goods['inner_price'] = '';
        $goods['update_time'] = time();
        $goods['cate_ids'] = trim($f_data['cate_ids'], ',');
        $goods['cate_names'] = trim($f_data['cate_names'], ',');
        $goods['base_sale_num'] = 0;

        $goods['picarr'] = $f_data['picarr'];
        $goods['goods_sn'] = $f_data['goods_sn'];
        // $goods['is_open_mprice'] = (int)$f_data['is_open_mprice'];
        $goods['measure_type'] = $f_data['measure_type'];
        $goods['unit_type'] = $f_data['unit_type'];
        $goods['pro_attrs'] = $f_data['pro_attrs'];

        $goods_id = $wmStoreGoodsDao->create($goods);
        $attr_data = $f_data['attr'];
        $wmStoreGoodsAttrgroupDao = WmStoreGoodsAttrgroupDao::i($this->s_user->aid);
        $wmStoreGoodsAttrgroupDao->addAttr($attr_data, $goods_id, $this->s_user->aid);

        $sku = $f_data['sku'];
        //添加sku
        $wmStoreGoodsSkuDao->addUpdateSku($sku, $goods_id, $this->s_user->aid);
        //更新价格区间
        $wmStoreGoodsDao->updateInnerPrice($goods_id);

        $wmShardDb->trans_complete();

        if($wmShardDb->trans_status() === false)
        {
          $this->json_do->set_error('005', '添加失败');
        }

        $this->json_do->set_msg('添加成功');
        $this->json_do->out_put();
    }

  /**
   * 商品编辑
   */
    public function goods_edit()
    {
        $rule = [
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim|required'],
            // ['field' => 'pict_url', 'label' => '商品主图', 'rules' => 'trim|required'],
            ['field' => 'sku_type', 'label' => '规格类型', 'rules' => 'required|in_list[0,1]'],
            ['field' => 'description', 'label' => '商品详情', 'rules' => 'trim'],
            ['field' => 'tag', 'label' => '标签', 'rules' => 'trim|required'],
            ['field' => 'attr', 'label' => '商品属性', 'rules' => 'trim|is_json'],
            ['field' => 'sku', 'label' => '商品SKU', 'rules' => 'trim|required|is_json'],
            ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim|numeric'],
            ['field' => 'cate_ids', 'label' => '分类IDS', 'rules' => 'trim|preg_key[IDS]'],
            ['field' => 'cate_names', 'label' => '分类名称', 'rules' => 'trim'],
//            ['field' => 'base_sale_num', 'label' => '基础销量', 'rules' => 'trim|numeric']
            ['field' => 'picarr', 'label' => '商品图集', 'rules' => 'trim|required'],
            // ['field' => 'is_open_mprice', 'label' => '开启会员价', 'rules' => 'trim|required|in_list[0,1]'],
            ['field' => 'goods_sn', 'label' => '商品编码', 'rules' => 'trim'],
            // ['field' => 'measure_type', 'label' => '计量方式', 'rules' => 'trim|required|in_list[1,2]'],
            ['field' => 'unit_type', 'label' => '单位', 'rules' => 'trim|in_list[0,1,2,3,4,5,6]'],
            ['field' => 'pro_attrs', 'label' => '属性', 'rules' => 'trim|is_json'],
        ];

        if($this->check->check_ajax_form($rule, false) === false)
        {
          $this->json_do->set_error('002', validation_errors());
        }
        $f_data = $this->form_data($rule);
        $f_data['sku'] = @json_decode($f_data['sku'], true);
        $f_data['attr'] = @json_decode($f_data['attr'], true);
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
        $wmStoreGoodsSkuDao = WmStoreGoodsSkuDao::i($this->s_user->aid);

        //检测属性重复
        if(!$this->_attr_repeat(@json_decode($f_data['pro_attrs'], true)))
            $this->json_do->set_error('004','属性重复');

        $picarr = explode(',', $f_data['picarr']);
        //检测商品编号
        if($f_data['goods_sn'])
        {
            $m_wm_store_goods = $wmStoreGoodsDao->getOne(['aid'=>$this->s_user->aid,'id <>'=>$f_data['goods_id'],'goods_sn'=>$f_data['goods_sn']],'id');
            if($m_wm_store_goods)
                $this->json_do->set_error('004','商品条码已存在');

        }
        //检测sku编码
        $goods_sku_sns = array_column($f_data['sku'], 'goods_sku_sn');
        $goods_sku_sns = array_filter($goods_sku_sns,function($val){
            return !empty($val);
        });
        if($goods_sku_sns)
        {
            //当前有重复
            if(count($goods_sku_sns) != count(array_unique($goods_sku_sns)))
            {
                $this->json_do->set_error('sku条码不能重复');
            }
            //检测数据库唯一
            $tmpWhereIn=sql_where_in($goods_sku_sns,false);
            $skuExistWhere=" aid={$this->s_user->aid} AND goods_id<>{$f_data['goods_id']} AND goods_sku_sn in({$tmpWhereIn})";
            $existSkuSns = $wmStoreGoodsSkuDao->getAllArray($skuExistWhere,'goods_sku_sn');
            if($existSkuSns)
                $this->json_do->set_error('004','sku条码已存在 ('.implode(',', array_column($existSkuSns, 'goods_sku_sn')).')');
        }
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $wmShardDb->trans_start();

        $goods['aid'] = $this->s_user->aid;
        $goods['title'] = $f_data['title'];
        $goods['pict_url'] = $picarr[0];
        $goods['sku_type'] = $f_data['sku_type'];
        $goods['description'] = $f_data['description'];
        $goods['update_time'] = time();
        $goods['tag'] = $f_data['tag'];
        $goods['cate_ids'] = trim($f_data['cate_ids'], ',');
        $goods['cate_names'] = trim($f_data['cate_names'], ',');
//        $goods['base_sale_num'] = $f_data['base_sale_num'];
        $goods['picarr'] = $f_data['picarr'];
        $goods['goods_sn'] = $f_data['goods_sn'];
        // $goods['is_open_mprice'] = (int)$f_data['is_open_mprice'];
//        $goods['measure_type'] = $f_data['measure_type'];
        $goods['unit_type'] = $f_data['unit_type'];
        $goods['pro_attrs'] = $f_data['pro_attrs'];

        $wmStoreGoodsDao->update($goods, ['id'=>$f_data['goods_id'], 'aid'=>$this->s_user->aid]);
        $goods_id = $f_data['goods_id'];
        $attr_data = $f_data['attr'];
        $wmStoreGoodsAttrgroupDao = WmStoreGoodsAttrgroupDao::i($this->s_user->aid);
        $wmStoreGoodsAttrgroupDao->addAttr($attr_data, $goods_id, $this->s_user->aid);

        $sku = $f_data['sku'];
        
        //更细sku数据
        $wmStoreGoodsSkuDao->addUpdateSku($sku, $goods_id, $this->s_user->aid, true);

        //更新价格区间
        $wmStoreGoodsDao->updateInnerPrice($goods_id);
        $wmShardDb->trans_complete();

        if($wmShardDb->trans_status() === false)
        {
          $this->json_do->set_error('005', '修改失败');
        }

        $this->json_do->set_msg('修改成功');
        $this->json_do->out_put();
    }

  /**
   * 商品虚拟删除
   */
    public function goods_del()
    {
        $goods_id = $this->input->post('goods_id');
        $type = $this->input->post('type');
        if(!is_numeric($goods_id))
          $this->json_do->set_error('001', '参数错误');
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
        $model = $wmStoreGoodsDao->getOne(['id'=>$goods_id, 'aid'=>$this->s_user->aid]);
        if(!$model)
          $this->json_do->set_error('004', '参数错误');

        //删除商品仓库
        $wmStoreGoodsDao->update(['is_delete'=>1], ['id'=>$goods_id,'aid'=>$this->s_user->aid]);
        //删除分店商品
        if($type == 1)
        {
            $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
            $goods_list = $wmGoodsDao->getAllArray(['store_goods_id'=>$goods_id,'aid'=>$this->s_user->aid, 'status'=>0], 'id,shop_id');
            $wmGoodsDao->update(['is_delete'=>1], ['store_goods_id'=>$goods_id,'aid'=>$this->s_user->aid, 'status'=>0]);

            //获取店铺ID
            $shop_id_arr = array_unique(array_column($goods_list, 'shop_id'));
            $items = [];
            foreach($shop_id_arr as $shop_id)
            {
                $_goods_list = array_find_list($goods_list,'shop_id',$shop_id);
                $_goods_ids = trim(implode(',',array_column($_goods_list, 'id')), ',');
                if(!empty($_goods_ids)) $items[] = ['shop_id' => $shop_id, 'goods_ids' => "{$_goods_ids}"];
            }

            //通知
            if (!empty($items))
            {
                $input['aid']=$this->s_user->aid;
                $input['visit_id']=$this->s_user->visit_id;
                $input['operation']='delete';  //添加参数
                $input['items'] = $items;
                $this->_mnsGoods($input);
            }
        }


        $this->json_do->set_msg('删除成功');
        $this->json_do->out_put();
    }
    /**
     * 商品详情
     */
    public function goods_info()
    {
        $goods_id = intval($this->input->get('goods_id'));
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
        $wmStoreGoodsSkuDao = WmStoreGoodsSkuDao::i($this->s_user->aid);
        $model = $wmStoreGoodsDao->getOne(['id'=>$goods_id]);
        if(!$model)
        {
          $this->json_do->set_error('001', '参数错误');
        }
        $sku_list = $wmStoreGoodsSkuDao->getAllArray(['goods_id'=>$goods_id]);
        $wmStoreGoodsAttrgroupDao = WmStoreGoodsAttrgroupDao::i($this->s_user->aid);
        $wmStoreGoodsAttritemDao = wmStoreGoodsAttritemDao::i($this->s_user->aid);
        $attr_groups = $wmStoreGoodsAttrgroupDao->getAllArray(['goods_id'=>$goods_id]);
        $attr_values = $wmStoreGoodsAttritemDao->getAllArray(['goods_id'=>$goods_id]);
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
   * 同步商品
   */
    public function sync_goods()
    {
        set_time_limit(0); //让程序一直执行下去
        ignore_user_abort(true); // 当脚本终端结束，脚本不会被立即中止
        $rule = [
          ['field' => 'goods_ids', 'label' => '商品IDS', 'rules' => 'trim|required|preg_key[IDS]'],
          ['field' => 'shop_ids', 'label' => '店铺IDS', 'rules' => 'trim|required|preg_key[IDS]']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $goods_ids = $f_data['goods_ids'];
        $shop_ids = $f_data['shop_ids'];
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
        $wm_goods_list = $wmStoreGoodsDao->getAllArray("aid={$this->s_user->aid} AND id in ($goods_ids)");
        if (empty($wm_goods_list)) {
          $this->json_do->set_error('001', '商品列表为空');
        }
        //获取guid

        $store_goods_id_arr = array_column($wm_goods_list, 'id');
        $store_goods_ids = trim(implode(',', $store_goods_id_arr), ',');
        if (empty($store_goods_ids)) $store_goods_ids = '0';

        //获取所有同步商品的分类名
        $all_store_goods_cate_names_arr = array_column($wm_goods_list, 'cate_names');
        $store_goods_cate_names_str = trim(implode(',', $all_store_goods_cate_names_arr), ',');

        $store_goods_cate_names_arr = explode(',', $store_goods_cate_names_str);

        $wmStoreGoodsSkuDao = WmStoreGoodsSkuDao::i($this->s_user->aid);
        $wmStoreGoodsAttrgroupDao = WmStoreGoodsAttrgroupDao::i($this->s_user->aid);
        $wmStoreGoodsAttritemDao = WmStoreGoodsAttritemDao::i($this->s_user->aid);

        //获取所有sku
        $sku_all_list = $wmStoreGoodsSkuDao->getAllArray(" aid={$this->s_user->aid} and goods_id in ({$store_goods_ids})");
        $attr_groups = $wmStoreGoodsAttrgroupDao->getAllArray(" aid={$this->s_user->aid} and goods_id in ({$store_goods_ids})");
        $attr_values = $wmStoreGoodsAttritemDao->getAllArray(" aid={$this->s_user->aid} and goods_id in ({$store_goods_ids})");

        $message = '';//记录同步信息

        //验证shop_id 是在同一总账号下面
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $wm_shop_list = $wmShopDao->getAllArray("aid={$this->s_user->aid} AND is_delete=0 AND id in($shop_ids)", 'id,shop_name');

        if (empty($wm_shop_list)) {
          $this->json_do->set_error('001', '有效店铺列表为空');
        }
        //$shop_ids_arr = array_column($wm_shop_list, 'id');

        $items = [];
        $wmCateDao = WmCateDao::i($this->s_user->aid);
        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->s_user->aid);

        foreach ($wm_shop_list as $shop) {

          $shop_id = $shop['id'];
          //同步分类
          $wmCateDao->addByNames(array_unique($store_goods_cate_names_arr), $shop_id, $this->s_user->aid);

          //根据guid排除相同商品
          $shop_goods_list = $wmGoodsDao->getAllArray("shop_id={$shop_id} AND is_delete=0 AND store_goods_id in ($store_goods_ids)", 'id,store_goods_id');
          $shop_goods_store_goods_id_arr = array_column($shop_goods_list, 'store_goods_id');
          $shop_goods_sku_list = $wmGoodsSkuDao->getAllArray(" aid={$this->s_user->aid} AND shop_id={$shop_id} AND goods_sku_sn<>'' ");
          $shop_goods_sku_sn_list = array_column($shop_goods_sku_list, 'goods_sku_sn');
          $_suc_goods_count = 0;//成功商品个数
          $_repet_goods_count = 0;//重复商品个数
          $_fail_goods_count = 0;//失败商品个数
          $_fail_goods_str = '';//失败商品id字符串

          $goods_ids = '';

          //组合同步店铺的数据
          foreach ($wm_goods_list as $goods) {
            //去重
            if (in_array($goods['id'], $shop_goods_store_goods_id_arr)) {//踢出重复商品
              $_repet_goods_count++;
              continue;
            }
            //检测sku编码是否有重复
            $_sku_list = array_find_list($sku_all_list, 'goods_id', $goods['id']);
            $_sku_goods_sku_sn_list = array_column($_sku_list, 'goods_sku_sn');
            $_intersect_goods_sku_sn_list = array_intersect($shop_goods_sku_sn_list, $_sku_goods_sku_sn_list);
            //检测goods_sku_sn
            if($_intersect_goods_sku_sn_list)
            {
              $_fail_goods_count++;
              continue;
            }

            $tmp_goods['aid'] = $this->s_user->aid;
            $tmp_goods['title'] = $goods['title'];
            $tmp_goods['pict_url'] = $goods['pict_url'];
            $tmp_goods['cate_ids'] = $wmCateDao->getIdsByNames($goods['cate_names'], $shop_id,  $this->s_user->aid) ;
            $tmp_goods['status'] = 1;
            $tmp_goods['shop_id'] = $shop_id;
            $tmp_goods['sku_type'] = $goods['sku_type'];
            $tmp_goods['description'] = $goods['description'];
            $tmp_goods['store_goods_id'] = $goods['id'];
            $tmp_goods['base_sale_num'] = $goods['base_sale_num'];
            $tmp_goods['inner_price'] = '';
            $tmp_goods['tag'] = $goods['tag'];
            $tmp_goods['time'] = time();
            $tmp_goods['promo_id'] = $goods['promo_id'];
            //>>>>> 2018.6.26 新增字段
            $tmp_goods['picarr'] = $goods['picarr'];
            $tmp_goods['goods_sn'] = $goods['goods_sn'];
            // $tmp_goods['is_open_mprice'] = (int)$goods['is_open_mprice'];
            $tmp_goods['measure_type'] = $goods['measure_type'];
            $tmp_goods['unit_type'] = $goods['unit_type'];
            $tmp_goods['pro_attrs'] = $goods['pro_attrs'];
            // 2018.6.26 新增字段
            //组装属性数据结构
            $attr_group = array_find($attr_groups, 'goods_id', $goods['id']);
            $_attr_values = array_find_list($attr_values, 'group_id', $attr_group['id']);
            $_attr_value = array_column($_attr_values, 'attr_name');
            $_attr = [
              ['attr_name' => $attr_group['title'], 'value' => $_attr_value],
            ];

            

            $sku = [];
            foreach ($_sku_list as $_sku) {
              $_sku_attr_value = array_find($_attr_values, 'id', $_sku['attr_ids']);
              $tmp_sku['box_fee'] = $_sku['box_fee'];
              $tmp_sku['sale_price'] = $_sku['sale_price'];
              $tmp_sku['use_stock_num'] = 0;
              $tmp_sku['sku_attr'] = [
                ['attr' => $attr_group['title'], 'value' => $_sku_attr_value['attr_name']],
              ];
              $tmp_sku['goods_sku_sn'] = $_sku['goods_sku_sn'];
              // $tmp_sku['member_price'] = $_sku['member_price'];
              $sku[] = $tmp_sku;
            }

            if (empty($sku)) {
              log_message('error', __METHOD__ . ':商品[' . $goods['title'] . ']同步到店铺[' . $shop['shop_name'] . ']数据错误');
              $_fail_goods_count++;
              $_fail_goods_str .= $goods['title'] . ',';
              continue;
            }
            $_goods_id = $wmGoodsDao->create($tmp_goods);
            if (empty($_goods_id)) {
              log_message('error', __METHOD__ . ':商品[' . $goods['title'] . ']同步到店铺[' . $shop['shop_name'] . ']失败');
              $_fail_goods_count++;
              $_fail_goods_str .= $goods['title'] . ',';
              $goods_ids .= $_goods_id . ",";
              continue;
            }
            //添加属性
            $wmGoodsAttrgroupDao->addAttr($_attr, $_goods_id, $this->s_user->aid);
            //添加sku
            $wmGoodsSkuDao->addUpdateSku($sku, $_goods_id, $this->s_user->aid, $shop_id);
            //更新价格区间
            $wmGoodsDao->updateInnerPrice($_goods_id);

            $_suc_goods_count++;
          }

          $message .= "同步商品到店铺[{$shop['shop_name']}]:成功{$_suc_goods_count}个";
          if ($_repet_goods_count > 0) {
            $message .= ",重复{$_repet_goods_count}个";
          }
          if ($_fail_goods_count > 0) {
            $_fail_goods_str = trim($_fail_goods_str, ',');
            $message .= ",失败{$_fail_goods_count}个，[{$_fail_goods_str}]";
          }
          $message .= ';';

          //通知参数
          if (!empty($goods_ids))
          {
              $goods_ids = trim($goods_ids, ',');
              $items[] = ['shop_id' => $shop_id, 'goods_ids' => "{$goods_ids}"];
          }
        }

        //通知
        if (!empty($items))
        {
            $input['aid']=$this->s_user->aid;
            $input['visit_id']=$this->s_user->visit_id;
            $input['operation']='online';  //添加参数
            $input['items'] = $items;
            $this->_mnsGoods($input);
        }


        //记录同步信息
        WmSyncRecordDao::i($this->s_user->aid)->create(['aid'=>$this->s_user->aid, 'title'=>'同步商品','desc'=>$message, 'time'=>time()]);

        $this->json_do->set_msg('同步成功');
        $this->json_do->out_put();

    }

    /**
     * 添加分类商品
     */
    public function cate_goods_add()
    {
        $rule = [
          ['field' => 'cate_id', 'label' => '分类ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'goods_ids', 'label' => '商品ID', 'rules' => 'trim|required|preg_key[IDS]']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $wmCateDao = WmCateDao::i($this->s_user->aid);
        $wmCateDao->cateStoreGoodsAdd($f_data['cate_id'], $f_data['goods_ids'], $this->s_user->aid);

        $this->json_do->set_msg('添加成功');
        $this->json_do->out_put();
    }

    /**
     * 移除分类商品
     */
    public function cate_goods_remove()
    {
        $rule = [
          ['field' => 'cate_id', 'label' => '分类ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
        $m_goods = $wmStoreGoodsDao->getOne(['id'=>$f_data['goods_id'],'aid'=>$this->s_user->aid], 'id,cate_ids');
        if(!$m_goods)
        {
          $this->json_do->set_error('001', '参数错误');
        }

        $cate_ids = trim(str_replace(','.$f_data['cate_id'].',', ',', ','.$m_goods->cate_ids.','), ',');
        if(empty($cate_ids)) $cate_ids = 0;

        $catelist = WmCateDao::i($this->s_user->aid)->getAllArray("aid={$this->s_user->aid} AND shop_id=0 AND id in({$cate_ids})");
        $cate_names = implode(',', array_column($catelist, 'cate_name'));

        $wmStoreGoodsDao->update(['cate_ids'=>$cate_ids, 'cate_names'=>$cate_names], ['id'=>$f_data['goods_id']]);

        $this->json_do->set_msg('移除成功');
        $this->json_do->out_put();
    }
  /**
   * 同步记录
   */
    public function get_sync_record_list()
    {
        $type = (int)$this->input->post_get('type');
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->table = "{$wmShardDb->tables['wm_sync_record']} ";
        $p_conf->where .= "  AND aid={$this->s_user->aid} AND type={$type}";
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        $data['total'] = $count;
        $data['rows'] = convert_client_list($list);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 固定ids商品列表
     */
    public function goods_ids_list()
    {
        $goods_ids = $this->input->get('goods_ids');
        if(empty($goods_ids))
        {
          $this->json_do->set_error('001', '参数错误');
        }
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
        $wmStoreGoodsSkuDao = WmStoreGoodsSkuDao::i($this->s_user->aid);

        $list = $wmStoreGoodsDao->getAllArray("id in ({$goods_ids})");
        $goods_ids = trim(implode(',',array_column($list, 'id')), ',');
        if(empty($goods_ids)) $goods_ids = '0';
        //获取所有sku
        $sku_all_list = $wmStoreGoodsSkuDao->getAllArray("goods_id in ({$goods_ids})");

        //整理数据结构
        foreach($list as $key=>$goods)
        {
          $sku_list = array_find_list($sku_all_list, 'goods_id', $goods['id']);
          $list[$key]['sku_list'] = $sku_list;
        }

        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }


    /**
     * 商品通知-添加(add)，删除(delete)，修改(update)，上下架(online,offline),同步商品(sync)
     * @param array $input 必填:aid,items:[shop_id,goods_ids:'111,222,333'],operation 可选visit_id
     * @return [type] [description]
     */    
    private function _mnsGoods($input)
    {
        try {
            $event_bll = new event_bll;
            return $event_bll->mnsGoods($input);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 检测属性是否重复
     * @param $arr
     * @return bool
     */
    private function _attr_repeat($arr)
    {
        if(empty($arr)) return true;
        $count = count($arr);
        $_name_attr_arr = array_column($arr, 'name');
        $name_count = count(array_unique($_name_attr_arr));
        //检测名称是否重复
        if ($count != $name_count)
            return false;
        $_value_attr_arr = array_column($arr, 'value');
        if (is_array($_value_attr_arr))
        {
            foreach($_value_attr_arr as $attr)
            {
                if(count($attr) != count(array_unique($attr)))
                    return false;
            }
        }
        return true;
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 商品管理
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmCateDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttrgroupDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsAttritemDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmStoreGoodsDao;
class Items_api extends wm_service_controller
{

    public function index()
    {
        $attr = [
            ['attr_name'=>'商品规格', 'value'=>['红色','黑色','蓝色']],
        ];
        $sku = [
            [
              'sku_attr'=>[
                ['attr'=>'商品规格','value'=>'红色'],
              ],
              'box_fee'=>'餐盒费',
              'sale_price'=>'售价',
              'use_stock_num'=>'可用库存',
              'sku_id'=>''
            ]
        ];
    }

    public function goods_list()
    {

        $title = $this->input->get('title');
        $shop_id = intval($this->input->get('shop_id'));
        $cate_id = intval($this->input->get('cate_id'));
        $status = $this->input->get('status');

        //子账号权限
        if(!$this->is_zongbu)
        {
            $shop_id = $this->currentShopId;
        }

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $tmp_sql = "SELECT `shop_id`, `goods_id`, SUM(`total_stock_num`) AS `total_stock_num`,SUM(`use_stock_num`) AS `use_stock_num`,SUM(`dj_stock_num`) AS `dj_stock_num`,SUM(`safe_stock_num`) AS `safe_stock_num`,SUM(`sale_num`) AS `sale_num` FROM {$wmShardDb->tables['wm_goods_sku']} WHERE `shop_id`={$shop_id} GROUP BY `aid`,`goods_id`,`shop_id`";
        $p_conf->table = "{$wmShardDb->tables['wm_goods']} a left join ($tmp_sql) b on a.id=b.goods_id left join {$wmShardDb->tables['wm_shop']} c on a.shop_id=c.id";
        $p_conf->order = 'a.id desc';
        $p_conf->fields = 'a.*,b.*';
        $p_conf->where .= " AND a.shop_id={$shop_id} AND a.aid={$this->s_user->aid} AND a.is_delete=0 AND c.is_delete=0";
        if($cate_id > 0)
        {
          $p_conf->where .= " AND CONCAT(',',a. cate_ids, ',') LIKE CONCAT('%,', $cate_id, ',%')";
        }
        if(!empty($title))
        {
          $p_conf->where .= " AND a.title LIKE '%".$page->filterLike($title)."%'";
        }

        if(is_numeric($status))
        {
            $p_conf->where .= " AND a.status={$status}";
        }
        $count = 0;
        $list = $page->getList($p_conf, $count);

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);

        $goods_ids = trim(implode(',',array_column($list, 'id')), ',');
        if(empty($goods_ids)) $goods_ids = '0';
        //获取所有sku
        $sku_all_list = $wmGoodsSkuDao->getAllArray("goods_id in ({$goods_ids})");

        //整理数据结构
        foreach($list as $key=>$goods)
        {
            $list[$key]['cate_name'] = $wmGoodsDao->getCateName(trim($goods['cate_ids'], ','));
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
            ['field' => 'cate_ids', 'label' => '分类ID', 'rules' => 'trim|required'],
            ['field' => 'sku_type', 'label' => '规格类型', 'rules' => 'numeric'],
            ['field' => 'description', 'label' => '商品详情', 'rules' => 'trim'],
            ['field' => 'tag', 'label' => '标签', 'rules' => 'trim|required'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
//            ['field' => 'base_sale_num', 'label' => '基础销量', 'rules' => 'trim|numeric'],
            ['field' => 'attr', 'label' => '商品属性', 'rules' => 'trim|is_json'],
            ['field' => 'sku', 'label' => '商品SKU', 'rules' => 'trim'],
            ['field' => 'picarr', 'label' => '商品图集', 'rules' => 'trim|required'],
            // ['field' => 'is_open_mprice', 'label' => '开启会员价', 'rules' => 'trim|required|in_list[0,1]'],
            ['field' => 'goods_sn', 'label' => '商品编码', 'rules' => 'trim'],
            ['field' => 'measure_type', 'label' => '计量方式', 'rules' => 'trim|required|in_list[1,2]'],
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

        $shop_id = $f_data['shop_id'];
        //子账号权限
        if(!$this->is_zongbu)
        {
            $shop_id = $this->currentShopId;
        }

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);

        //检测属性重复
        if(!$this->_attr_repeat(@json_decode($f_data['pro_attrs'], true)))
            $this->json_do->set_error('004','属性重复');

        $picarr = explode(',', $f_data['picarr']);
        //检测商品编号
        if($f_data['goods_sn'])
        {
            $m_wm_goods = $wmGoodsDao->getOne(['aid'=>$this->s_user->aid,'goods_sn'=>$f_data['goods_sn']],'id');
            if($m_wm_goods)
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
                $this->json_do->set_error('004', 'sku条码不能重复');
            }
            //检测数据库唯一
            $tmpWhereIn=sql_where_in($goods_sku_sns,false);
            $skuExistWhere=" aid={$this->s_user->aid}  and goods_sku_sn in({$tmpWhereIn})";
            $existSkuSns = $wmGoodsSkuDao->getAllArray($skuExistWhere,'goods_sku_sn');
            if($existSkuSns)
            {
                $this->json_do->set_error('004','sku条码已存在 ('.implode(',', array_column($existSkuSns, 'goods_sku_sn')).')');
            }
        }
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $wmShardDb->trans_start();

        $goods['aid'] = $this->s_user->aid;
        $goods['title'] = $f_data['title'];
        $goods['pict_url'] = $picarr[0];
        $goods['cate_ids'] = trim($f_data['cate_ids'], ',');
        $goods['tag'] = $f_data['tag'];
        $goods['shop_id'] = $shop_id;
        $goods['sku_type'] = $f_data['sku_type'];
        $goods['description'] = $f_data['description'];
        $goods['inner_price'] = '';
        $goods['base_sale_num'] = 0;
        $goods['status'] = 1;
        $goods['update_time'] = time();

        $goods['picarr'] = $f_data['picarr'];
        $goods['goods_sn'] = $f_data['goods_sn'];
        // $goods['is_open_mprice'] = (int)$f_data['is_open_mprice'];
        $goods['measure_type'] = $f_data['measure_type'];
        $goods['unit_type'] = $f_data['unit_type'];
        $goods['pro_attrs'] = $f_data['pro_attrs'];

        $goods_id = $wmGoodsDao->create($goods);
        $attr_data = $f_data['attr'];

        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->s_user->aid);
        $wmGoodsAttrgroupDao->addAttr($attr_data, $goods_id, $this->s_user->aid);

        $sku = $f_data['sku'];

        //添加sku
        $wmGoodsSkuDao->addUpdateSku($sku, $goods_id, $this->s_user->aid,$shop_id);
        //更新价格区间
        $wmGoodsDao->updateInnerPrice($goods_id);

        $wmShardDb->trans_complete();

        if($wmShardDb->trans_status() === false)
        {
          $this->json_do->set_error('005', '添加失败');
        }
        //商品通知
        $input['aid']=$this->s_user->aid;
        $input['visit_id']=$this->s_user->visit_id;
        $input['operation']='add';  //添加参数
        $items[]=['shop_id'=>$shop_id,'goods_ids'=>"{$goods_id}"];
        $input['items'] = $items;
        $this->_mnsGoods($input);

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
            ['field' => 'cate_ids', 'label' => '分类ID', 'rules' => 'trim|required'],
            ['field' => 'sku_type', 'label' => '规格类型', 'rules' => 'numeric'],
            ['field' => 'description', 'label' => '商品详情', 'rules' => 'trim'],
            ['field' => 'tag', 'label' => '标签', 'rules' => 'trim|required'],
            ['field' => 'attr', 'label' => '商品属性', 'rules' => 'trim|is_json'],
            ['field' => 'sku', 'label' => '商品SKU', 'rules' => 'trim|is_json'],
            ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim|numeric'],
//            ['field' => 'base_sale_num', 'label' => '基础销量', 'rules' => 'trim|numeric']
            //['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric']
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

        //子账号权限
        $where = ['id'=>$f_data['goods_id'],'aid'=>$this->s_user->aid];
        if(!$this->is_zongbu)
        {
          $where['shop_id'] = $this->currentShopId;
        }
        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);

        $m_model = $wmGoodsDao->getOne($where);
        if(!$m_model)
        {
            $this->json_do->set_error('005', '参数错误');
        }

        //检测属性重复
        if(!$this->_attr_repeat(@json_decode($f_data['pro_attrs'], true)))
            $this->json_do->set_error('004','属性重复');

        $picarr = explode(',', $f_data['picarr']);
        //检测商品编号
        if($f_data['goods_sn'])
        {
            $m_wm_goods = $wmGoodsDao->getOne(['aid'=>$this->s_user->aid,'id <>'=>$f_data['goods_id'],'goods_sn'=>$f_data['goods_sn'], 'is_delete'=>0],'id');
            if($m_wm_goods)
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
                $this->json_do->set_error('004','sku条码不能重复');
            }
            //检测数据库唯一
            $tmpWhereIn=sql_where_in($goods_sku_sns,false);
            $skuExistWhere=" aid={$this->s_user->aid}  AND goods_id<>{$f_data['goods_id']} AND goods_sku_sn in({$tmpWhereIn})";
            $existSkuSns = $wmGoodsSkuDao->getAllArray($skuExistWhere,'goods_sku_sn');

            if($existSkuSns)
            {
                $this->json_do->set_error('004','sku条码已存在 ('.implode(',', array_column($existSkuSns, 'goods_sku_sn')).')');
            }

        }

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $wmShardDb->trans_start();

        $goods['aid'] = $this->s_user->aid;
        $goods['title'] = $f_data['title'];
        $goods['pict_url'] = $picarr[0];
        $goods['cate_ids'] = trim($f_data['cate_ids'], ',');
        $goods['sku_type'] = $f_data['sku_type'];
        $goods['description'] = $f_data['description'];
        $goods['update_time'] = time();
        $goods['tag'] = $f_data['tag'];

        $goods['picarr'] = $f_data['picarr'];
        $goods['goods_sn'] = $f_data['goods_sn'];
        // $goods['is_open_mprice'] = (int)$f_data['is_open_mprice'];
//        $goods['measure_type'] = $f_data['measure_type'];
        $goods['unit_type'] = $f_data['unit_type'];
        $goods['pro_attrs'] = $f_data['pro_attrs'];

        $wmGoodsDao->update($goods, $where);

        $goods_id = $f_data['goods_id'];
        $attr_data = $f_data['attr'];
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->s_user->aid);
        $wmGoodsAttrgroupDao->addAttr($attr_data, $goods_id, $this->s_user->aid);

        $sku = $f_data['sku'];
        
        //更细sku数据
        $wmGoodsSkuDao->addUpdateSku($sku, $goods_id, $this->s_user->aid, $m_model->shop_id, true);

        //更新价格区间
        $wmGoodsDao->updateInnerPrice($goods_id);
        $wmShardDb->trans_complete();

        if($wmShardDb->trans_status() === false)
        {
          $this->json_do->set_error('005', '修改失败');
        }
        //商品通知
        $input['aid']=$this->s_user->aid;
        $input['visit_id']=$this->s_user->visit_id;
        $input['operation']='update';  //添加参数
        $items[]=['shop_id'=>$m_model->shop_id,'goods_ids'=>"{$goods_id}"];
        $input['items'] = $items;
        $this->_mnsGoods($input);

        $this->json_do->set_msg('修改成功');
        $this->json_do->out_put();
    }

  /**
   * 商品虚拟删除
   */
    public function goods_del()
    {
        $goods_id = intval($this->input->post('goods_id'));
        if(!is_numeric($goods_id))
          $this->json_do->set_error('001', '参数错误');

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);

        $where = ['id'=>$goods_id, 'aid'=>$this->s_user->aid];
        //子账号权限
        if(!$this->is_zongbu)
        {
            $where['shop_id'] = $this->currentShopId;
        }

        $model = $wmGoodsDao->getOne($where);
        if(!$model)
        {
            $this->json_do->set_error('001', '参数错误');
        }

        if($model->status == 1)
          $this->json_do->set_error('004', '此商品未全部在下架状态，请全部下架后删除');

        $wmGoodsDao->update(['is_delete'=>1], ['id'=>$goods_id,'aid'=>$this->s_user->aid]);
        $wmGoodsSkuDao->delete(['goods_id'=>$goods_id,'aid'=>$this->s_user->aid]);

        //商品通知
        $input['aid']=$this->s_user->aid;
        $input['visit_id']=$this->s_user->visit_id;
        $input['operation']='delete';  //添加参数
        $items[]=['shop_id'=>$model->shop_id,'goods_ids'=>"{$model->id}"];
        $input['items'] = $items;
        $this->_mnsGoods($input);

        $this->json_do->set_msg('删除成功');
        $this->json_do->out_put();
    }
    /**
     * 商品详情
     */
    public function goods_info()
    {
        $goods_id = intval($this->input->get('goods_id'));

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
        $wmGoodsAttrgroupDao = WmGoodsAttrgroupDao::i($this->s_user->aid);
        $wmGoodsAttritemDao = WmGoodsAttritemDao::i($this->s_user->aid);

        $model = $wmGoodsDao->getOne(['id'=>$goods_id]);
        if(!$model)
        {
          $this->json_do->set_error('001', '参数错误');
        }
        $sku_list = $wmGoodsSkuDao->getAllArray(['goods_id'=>$goods_id]);

        $attr_groups = $wmGoodsAttrgroupDao->getAllArray(['goods_id'=>$goods_id]);
        $attr_values = $wmGoodsAttritemDao->getAllArray(['goods_id'=>$goods_id]);
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
     * 分类添加
     */
    public function cate_add()
    {
        $rule = [
            ['field' => 'cate_name', 'label' => '分类名称', 'rules' => 'trim|required|max_length[8]'],
            ['field' => 'sort', 'label' => '排序', 'rules' => 'trim|numeric'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $f_data['aid'] = $this->s_user->aid;

        //子账号权限
        if(!$this->is_zongbu)
        {
          $f_data['shop_id'] = $this->currentShopId;
        }

        $wmCateDao = WmCateDao::i($this->s_user->aid);
        //判断是否重复
        $m_wm_cate = $wmCateDao->getOne(['cate_name'=>$f_data['cate_name'], 'aid'=>$this->s_user->aid, 'shop_id'=>$f_data['shop_id']]);
        if($m_wm_cate)
        {
          $this->json_do->set_error('005-2','分类名称重复');
        }

        $id = $wmCateDao->create($f_data);
        if($id)
        {
            $this->json_do->set_msg('添加成功');
            $this->json_do->out_put();
        }
        else
        {
            $this->json_do->set_error('005-2','添加失败');
        }

    }

    /**
     * 分类编辑
     */
    public function cate_edit()
    {
        $rule = [
            ['field' => 'id', 'label' => '分类ID', 'rules' => 'required|numeric'],
            ['field' => 'cate_name', 'label' => '分类名称', 'rules' => 'trim|required|max_length[8]'],
            ['field' => 'sort', 'label' => '排序', 'rules' => 'trim|numeric'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $f_data['aid'] = $this->s_user->aid;


        $where = ['id'=>$f_data['id'], 'aid'=>$this->s_user->aid];
        //子账号权限
        if(!$this->is_zongbu)
        {
            $f_data['shop_id'] = $this->currentShopId;
            $where['shop_id'] = $f_data['shop_id'];
        }

        $wmCateDao = WmCateDao::i($this->s_user->aid);
        //判断是否重复
        $m_wm_cate = $wmCateDao->getOne("id != {$f_data['id']} AND cate_name='{$f_data['cate_name']}' AND shop_id={$f_data['shop_id']} AND aid={$this->s_user->aid}");
        if($m_wm_cate)
        {
            $this->json_do->set_error('005-2','分类名称重复');
        }
        if($wmCateDao->update($f_data, $where) !== false)
        {
            //处理商品库分类名称修改的问题
            if($f_data['shop_id'] == 0)
            {
                $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
                $wmStoreGoodsDao->updateCateNames($f_data['id'], $f_data['shop_id'], $this->s_user->aid);
            }

            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        }
        else
        {
            $this->json_do->set_error('005-2','修改失败');
        }
    }

  /**
   * 修改排序
   */
    public function cate_sort_edit()
    {
        $rule = [
          ['field' => 'id', 'label' => '分类ID', 'rules' => 'required|numeric'],
          ['field' => 'sort', 'label' => '排序', 'rules' => 'trim|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $where = ['id'=>$f_data['id'], 'aid'=>$this->s_user->aid];
        //子账号权限
        if(!$this->is_zongbu)
        {
          $where['shop_id'] = $this->currentShopId;
        }

        $wmCateDao = WmCateDao::i($this->s_user->aid);
        if($wmCateDao->update($f_data, $where) !== false)
        {
          $this->json_do->set_msg('修改成功');
          $this->json_do->out_put();
        }
        else
        {
          $this->json_do->set_error('005-2','修改失败');
        }
    }

    /**
     *  分类删除
     */
    public function cate_del()
    {

        $id = intval($this->input->post('id'));

        $where = ['id'=>$id, 'aid'=>$this->s_user->aid];
        //子账号权限
        if(!$this->is_zongbu)
        {
          $where['shop_id'] = $this->currentShopId;
        }
        $wmCateDao = WmCateDao::i($this->s_user->aid);;
        $m_cate = $wmCateDao->getOne(['id' => $id, 'aid' => $this->s_user->aid]);
        log_message('error', '--m_cate--'.json_encode($m_cate));

        //处理分类相关商品库商品/
        if($m_cate && $m_cate->shop_id == 0)
        {

            $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
            $store_goods_list = $wmStoreGoodsDao->getAllArray("aid={$this->s_user->aid} AND CONCAT(',',cate_ids,',') LIKE CONCAT('%,',{$id},',%')", 'id,cate_ids,cate_names');
            $up_data = [];
            foreach($store_goods_list as $k => $goods)
            {

                $cate_ids = trim(str_replace(','.$id.',', ',', ','.$goods['cate_ids'].','), ',');
                $cate_names = trim(str_replace(','.$m_cate->cate_name.',', ',', ','.$goods['cate_names'].','), ',');

                $tmp['id'] = $goods['id'];
                $tmp['cate_ids'] = $cate_ids;
                $tmp['cate_names'] = $cate_names;

                $up_data[] = $tmp;
            }
            if(!empty($up_data))
                $wmStoreGoodsDao->updateBatch($up_data, 'id');
        }


        if($wmCateDao->delete($where) !== false)
        {
          $this->json_do->set_msg('删除成功');
          $this->json_do->out_put();
        }

        $this->json_do->set_error('005-2','删除失败');

    }

    /**
     * 商品分类分页列表
     */
    public function cate_list()
    {
        $shop_id = intval($this->input->get('shop_id'));
        //子账号权限
        if(!$this->is_zongbu)
        {
           $shop_id = $this->currentShopId;
        }


        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->table = "{$wmShardDb->tables['wm_cate']}";
        $p_conf->order = 'sort desc,id desc';
        $p_conf->where = " aid={$this->s_user->aid} AND shop_id={$shop_id}";
        $count = 0;
        $list = $page->getList($p_conf, $count);

        $v_rules = array(
          array('type' => 'time', 'field' => 'time', 'format'=>'Y-m-d H:i:s')
        );

        $data['total'] = $count;
        $data['rows'] = convert_client_list($list, $v_rules);
        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }

    /**
     * 获取所有分类
     */
    public function get_all_cate()
    {
        $shop_id = intval($this->input->get('shop_id'));
        //子账号权限
        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }
        $wmCateDao = WmCateDao::i($this->s_user->aid);;
        $data = $wmCateDao->getAllArray(['aid'=>$this->s_user->aid,'shop_id'=>$shop_id]);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 批量设置上架
     */
    public function shelves_up()
    {

        $shop_id = $this->input->post('shop_id');
        $goods_id = $this->input->post('goods_id');
        if(!is_numeric($shop_id) || empty($goods_id))
        {
          $this->json_do->set_error('001', '参数错误');
        }
        //子账号权限
        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }
        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $return = $wmGoodsDao->update(['status'=>1], " aid={$this->s_user->aid} AND shop_id={$shop_id} AND id in ({$goods_id}) ");
        if($return !== false)
        {

          //商品通知
          $input['aid']=$this->s_user->aid;
          $input['visit_id']=$this->s_user->visit_id;
          $input['operation']='online';  //添加参数
          $items[]=['shop_id'=>$shop_id,'goods_ids'=>"{$goods_id}"];
          $input['items'] = $items;
          $this->_mnsGoods($input);

          $this->json_do->set_data('上架成功');
          $this->json_do->out_put();
        }

        $this->json_do->set_error('001', '上架失败');

    }

    /**
     * 批量设置下架
     */
    public function shelves_down()
    {

        $shop_id = intval($this->input->post('shop_id'));
        $goods_id = $this->input->post('goods_id');

        if(!is_numeric($shop_id) || empty($goods_id))
        {
          $this->json_do->set_error('001', '参数错误');
        }
        //子账号权限
        if(!$this->is_zongbu)
        {
          $shop_id = $this->currentShopId;
        }

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $return = $wmGoodsDao->update(['status'=>0], " aid={$this->s_user->aid} AND shop_id={$shop_id} AND id in ({$goods_id}) ");
        if($return !== false)
        {
          //商品通知
          $input['aid']=$this->s_user->aid;
          $input['visit_id']=$this->s_user->visit_id;
          $input['operation']='offline';  //添加参数
          $items[]=['shop_id'=>$shop_id,'goods_ids'=>"{$goods_id}"];
          $input['items'] = $items;
          $this->_mnsGoods($input);

          $this->json_do->set_data('下架成功');
          $this->json_do->out_put();
        }

        $this->json_do->set_error('001', '下架失败');

    }

    /**
     * 添加分类商品
     */
    public function cate_goods_add()
    {
        $rule = [
          ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'cate_id', 'label' => '分类ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'goods_ids', 'label' => '商品ID', 'rules' => 'trim|required|preg_key[IDS]']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        //子账号权限
        if(!$this->is_zongbu)
        {
            $f_data['shop_id'] = $this->currentShopId;
        }

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $goods_list = $wmGoodsDao->getAllArray("aid={$this->s_user->aid} AND shop_id={$f_data['shop_id']} AND id in ({$f_data['goods_ids']})", 'id,cate_ids');

        $update_data = [];
        if(!empty($goods_list))
        {
          foreach($goods_list as $goods)
          {
            $cate_ids = ','.$goods['cate_ids'].',';
            if(stripos($cate_ids, ','.$f_data['cate_id'].',') === false)
            {
              $tmp['id'] = $goods['id'];
              $tmp['cate_ids'] = $goods['cate_ids'].','.$f_data['cate_id'];
              $update_data[] = $tmp;
            }
          }
        }

        if(!empty($update_data))
            $wmGoodsDao->updateBatch($update_data, 'id');

        $this->json_do->set_msg('添加成功');
        $this->json_do->out_put();
    }


    /**
     * 移除分类商品
     */
    public function cate_goods_remove()
    {
        $rule = [
          ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'cate_id', 'label' => '分类ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim|required|preg_key[IDS]']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        //子账号权限
        if(!$this->is_zongbu)
        {
            $f_data['shop_id'] = $this->currentShopId;
        }

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $m_goods = $wmGoodsDao->getOne(['id'=>$f_data['goods_id'],'aid'=>$this->s_user->aid,'shop_id'=>$f_data['shop_id']], 'id,cate_ids');
        if(!$m_goods)
        {
          $this->json_do->set_error('001', '参数错误');
        }

        $cate_ids = trim(str_replace(','.$f_data['cate_id'].',', ',', ','.$m_goods->cate_ids.','), ',');
        $wmGoodsDao->update(['cate_ids'=>$cate_ids], ['id'=>$f_data['goods_id']]);

        $this->json_do->set_msg('移除成功');
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

        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);

        $list = $wmGoodsDao->getAllArray("aid={$this->s_user->aid} AND id in ({$goods_ids})");
        $goods_ids = trim(implode(',',array_column($list, 'id')), ',');
        if(empty($goods_ids)) $goods_ids = '0';
        //获取所有sku
        $sku_all_list = $wmGoodsSkuDao->getAllArray("aid={$this->s_user->aid} AND goods_id in ({$goods_ids})");

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
     * 编辑sku库存
     */
    public function edit_sku_stock()
    {
        $shop_id = $this->input->post('shop_id');
        $sku_arr = @json_decode($this->input->post('sku'),true);

        if(!is_numeric($shop_id) || empty($sku_arr))
          $this->json_do->set_error('001', '参数错误');

        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
        foreach($sku_arr as $sku)
        {
            $m_model = $wmGoodsSkuDao->getOne(['shop_id'=>$shop_id,'id'=>$sku['sku_id'], 'aid'=>$this->s_user->aid]);
            if(!$m_model)
              continue;
            $tmp = [];

            $tmp['id'] = $sku['sku_id'];
            $tmp['shop_id'] = $shop_id;
            $tmp['use_stock_num'] = $sku['use_stock_num'];

          $sku_data[] = $tmp;
        }
        if(!empty($sku_data))
        {
            $wmGoodsSkuDao->updateBatch($sku_data, 'id');
            $this->json_do->set_data('修改成功');
            $this->json_do->out_put();
        }

        $this->json_do->set_error('001', '参数错误');

    }

    /**
     * 商品sku库存列表
     */
    public function stock_sku_list()
    {
        $goods_id = $this->input->get('goods_id');
        if(!is_numeric($goods_id))
          $this->json_do->set_error('001', '参数错误');

        $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
        $sku_list = $wmGoodsSkuDao->getAllArray(['goods_id'=>$goods_id, 'aid'=>$this->s_user->aid],'id,goods_id,shop_id,use_stock_num,attr_names');
        $this->json_do->set_data($sku_list);
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

<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/3
 * Time: 18:55
 */
use Service\Cache\MealShopAreaTableCache;

use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmMainDbModels\WmSettingDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealCartDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaTableDao;
class Area_table extends sy_controller
{

    public function area_list()
    {
//        $rule = [
//          ['field' => 'shop_id', 'label' => '就餐人数', 'rules' => 'trim|required|numeric']
//        ];
//        $this->check->check_ajax_form($rule);
//        $f_data = $this->form_data($rule);
        $shop_id = $this->s_user->shop_id;

        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $shop_area_list = $mealShopAreaDao->getAllArray(['aid'=>$this->s_user->aid, 'shop_id'=>$shop_id]);

        $this->json_do->set_data($shop_area_list);
        $this->json_do->out_put();
    }
  /**
   * 区域桌位列表
   */
    public function table_list()
    {
        $rule = [
//          ['field' => 'shop_id', 'label' => '就餐人数', 'rules' => 'trim|required|numeric'],
          ['field' => 'shop_area_id', 'label' => '区域ID', 'rules' => 'trim|required|numeric'],
          ['field' => 'name', 'label' => '桌位名称', 'rules' => 'trim']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $shop_id = $this->s_user->shop_id;

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $area_where = "aid={$this->s_user->aid} AND shop_id={$shop_id} ";
        $area_table_where = "a.aid={$this->s_user->aid} AND a.shop_id={$shop_id}";
        if($f_data['shop_area_id'] > 0)
        {
          $area_where .= " AND id={$f_data['shop_area_id']}";
          $area_table_where .= " AND a.shop_area_id={$f_data['shop_area_id']}";
        }
        if(!empty($f_data['name']))
        {
          $area_table_where .= " AND a.name like '%{$f_data['name']}%'";
        }

        $shop_area_list = $mealShopAreaDao->getAllArray($area_where);

        //获取店铺所有商品
        $goods_config_arr = array(
            'field' => "a.*,b.number real_number, b.order_count, b.id order_table_id",
            'table' => "{$wmShardDb->tables['meal_shop_area_table']} a",
            'join' => array(
                array("{$wmShardDb->tables['meal_order_table']} b", "a.cur_order_table_id=b.id", 'left'),
            ),
            'where' => $area_table_where,
        );

        //获取所有商品列表
        $shop_area_table_list = $mealShopAreaTableDao->getEntitysByAR($goods_config_arr, true);


        $v_rules = [
          ['type' => 'time', 'field' => 'time', 'format'=>'Y-m-d H:i:s'],
          ['type' => 'img', 'field' => 'qr_img']
        ];
        $shop_area_table_list  = convert_client_list($shop_area_table_list, $v_rules);

        foreach($shop_area_list as $key => $shop_area)
        {
            $shop_area_list[$key]['area_table_list'] = array_search_list($shop_area_table_list, 'shop_area_id', $shop_area['id']);
            $shop_area_list[$key]['time'] = date('Y-m-d H:i:s', $shop_area['time']);
        }

        $this->json_do->set_data($shop_area_list);
        $this->json_do->out_put();

    }

  /**
   * 桌位添加
   */
    public function table_add()
    {
        $rule = [
          ['field' => 'name', 'label' => '桌位名称', 'rules' => 'trim|required'],
          ['field' => 'number', 'label' => '建议人数', 'rules' => 'trim|required|numeric'],
          ['field' => 'shop_area_id', 'label' => '区域ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $m_shop_area = $mealShopAreaDao->getOne(['id'=>$f_data['shop_area_id'], 'aid'=>$this->s_user->aid]);
        if(!$m_shop_area)
        {
          $this->json_do->set_error('002','区域记录不存在');
        }

        //判断name是否重复
        $m_area_table = $mealShopAreaTableDao->getOne(['aid'=>$this->s_user->aid, 'shop_area_id'=>$f_data['shop_area_id'], 'name'=>$f_data['name']]);
        if($m_area_table)
            $this->json_do->set_error('002','桌位名称重复');

        $f_data['aid'] = $this->s_user->aid;
        $f_data['shop_id'] = $m_shop_area->shop_id;
        $f_data['time'] = time();

        $id = $mealShopAreaTableDao->create($f_data);
        if($id > 0)
        {
            try{
                //生成二维码
                $m_shop_area_table = $mealShopAreaTableDao->getOne(['id'=>$id, 'aid'=>$this->s_user->aid]);
                $this->_gen_qrcode($m_shop_area_table);
            }catch(Exception $e){
                log_message('error', __METHOD__.'--'.'生成桌位二维码失败');
            }
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005-2','保存失败');
    }

    /**
     * 设置桌位信息
     */
    public function setting()
    {
        $rule = [
            ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
            ['field' => 'number', 'label' => '就餐人数', 'rules' => 'trim|required|numeric'],
            ['field' => 'remark', 'label' => '备注', 'rules' => 'trim']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $m_shop_area_table = $mealShopAreaTableDao->getOne(['id'=>$f_data['table_id'], 'aid'=>$this->s_user->aid]);
        if(!$m_shop_area_table)
        {
            $this->json_do->set_error('002','桌位记录不存在');
        }

        if (empty($m_shop_area_table->cur_order_table_id)) {

            // 加载门店信息
            $WmShopDao = WmShopDao::i($this->s_user->aid);
            $shop = $WmShopDao->getOne(['id' => $m_shop_area_table->shop_id, 'aid'=>$this->s_user->aid]);
            if (!$shop) {
                $this->json_do->set_error('004', '门店不存在');
            }
            //加载区域信息
            $m_area = $mealShopAreaDao->getOne(['id' => $m_shop_area_table->shop_area_id, 'aid'=>$this->s_user->aid]);

            $record_id = $mealOrderTableDao->create([
                'aid' => $this->s_user->aid,
                'shop_id' => $shop->id,
                'shop_name' => $shop->shop_name,
                'shop_contact' => $shop->contact,
                'shop_logo' => $shop->shop_logo,
                'table_id' => $m_shop_area_table->id,
                'table_name' => $m_shop_area_table->name,
                'number' => $f_data['number'],
                'remark' => $f_data['remark'],
                'status' => 2,
                'area_name' => $m_area ? $m_area->name : ''
            ]);


            $result = $mealShopAreaTableDao->update(['cur_order_table_id' => $record_id, 'status'=>2], ['id' => $m_shop_area_table->id, 'aid'=>$this->s_user->aid]);
        } else {
            $result = $mealOrderTableDao->update(['number' => $f_data['number'], 'remark' => $f_data['remark']], ['id' => $m_shop_area_table->cur_order_table_id, 'aid'=>$this->s_user->aid]);
        }

        //清楚桌位信息缓存
        $mealShopAreaTableCache = new MealShopAreaTableCache(['aid'=>$this->s_user->aid,'table_id'=>$m_shop_area_table->id,'code'=>$m_shop_area_table->code]);
        $mealShopAreaTableCache->delete();

        if ($result) {
            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '操作失败');
        }
    }
    /**
     * 清桌
     */
    public function clear_table()
    {
        $rule = [
            ['field' => 'table_id', 'label' => '桌位ID', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $shop_id = $this->s_user->shop_id;

        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $f_data['table_id'], 'aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
        if(!$m_shop_area_table)
            $this->json_do->set_error('002','桌位不存在');

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        //判断是否存在待审核订单
        $count = $mealOrderDao->getCount(['order_table_id' => $m_shop_area_table->cur_order_table_id, 'api_status' => 1, 'aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
        if($count > 0)
            $this->json_do->set_error('002','存在待审核订单');

        //判断是否存在待结算订单
        $count = $mealOrderDao->getCount(['order_table_id' => $m_shop_area_table->cur_order_table_id, 'api_status' => 2, 'aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
        if($count > 0)
            $this->json_do->set_error('002','存在待结算订单');

        //购物车数据处理
        $mealCartDao = MealCartDao::i($this->s_user->aid);
        $mealCartDao->resetCart($f_data['table_id']);

        //更新cur_table_order_id=0
        $mealShopAreaTableDao->update(['cur_order_table_id' => 0, 'status' => 0], ['id' => $f_data['table_id'], 'aid' => $this->s_user->aid, 'shop_id' => $shop_id]);

        //清楚桌位信息缓存
        $mealShopAreaTableCache = new MealShopAreaTableCache(['aid'=>$this->s_user->aid,'table_id'=>$m_shop_area_table->id,'code'=>$m_shop_area_table->code]);
        $mealShopAreaTableCache->delete();

        //socket 通知 [aid,shop_id,table_id]
        $worker_bll = new worker_bll();
        $worker_bll->mealTableClear(['aid'=>$this->s_user->aid, 'shop_id'=>$this->s_user->shop_id, 'table_id'=>$f_data['table_id']]);


        $this->json_do->set_msg('成功');
        $this->json_do->out_put();
    }

    /**
     * 桌位信息
     */
    public function table_info()
    {
        $rule = [
            ['field' => 'table_id', 'label' => '桌位ID', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $shop_id = $this->s_user->shop_id;

        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_shop_area_table = $mealShopAreaTableDao->getOne(['id'=>$f_data['table_id'], 'aid'=>$this->s_user->aid]);

        if(!$m_shop_area_table)
            $this->json_do->set_error('002','暂无记录');
        $m_shop_area = $mealShopAreaDao->getOne(['id'=>$m_shop_area_table->shop_area_id, 'aid'=>$this->s_user->aid]);
        //区域名称
        $m_shop_area_table->shop_area_name = $m_shop_area->name;

        //没有图片路径则生成二维码图片
        if(!empty($m_shop_area_table->qr_img))
        {
            $m_shop_area_table->qr_img = conver_picurl($m_shop_area_table->qr_img);
        }
        $order = [];
        //当前桌位批次详细信息
        if($m_shop_area_table->cur_order_table_id > 0)
        {

            $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
            $order['order_table'] = $mealOrderTableDao->getOneArray(['id' => $m_shop_area_table->cur_order_table_id, 'aid' => $this->s_user->aid]);

            $meal_order_query_bll = new meal_order_query_bll();
            $list = $meal_order_query_bll->query_all(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_table_id' => $m_shop_area_table->cur_order_table_id]);

            //待审核订单
            $order['audit_order']['list'] = array_find_list($list, 'api_status', 1);
            $order['audit_order']['total_money'] = array_sum(array_map(function($val){return $val['pay_money'];}, $order['audit_order']['list']));
            $order['audit_order']['total_num'] = array_sum(array_map(function($val){return $val['total_num'];}, $order['audit_order']['list']));
        }

        $data['table_info'] = $m_shop_area_table;
        $data['order_info'] = $order;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 生成二维码，合成图片
     * @param null $meal_shop_area_table_model  桌位model
     * @return bool|null
     */
    private function _gen_qrcode($meal_shop_area_table_model=null)
    {

        if(!$meal_shop_area_table_model)
            return null;

        $wmSettingDao = WmSettingDao::i();
        $m_setting = $wmSettingDao->getOne(['aid'=>$this->s_user->aid]);
        //自定义域名
        $domain = $this->s_user->aid;
        if($m_setting && !empty($m_setting->domain))
        {
            $domain = $m_setting->domain;
        }
        //跳转路径

        $code = strtoupper(substr(md5(uniqid(mt_rand(), true)),8,6));
        $redirect_url = 'https://'.$domain.'.'.M_SUB_URL."/meal/?table_id={$meal_shop_area_table_model->id}&code={$code}&shop_id={$meal_shop_area_table_model->shop_id}";

        $abs_dir = UPLOAD_PATH . 'qrcode';
        if (!is_dir($abs_dir)) {
            @RecursiveMkdir($abs_dir, '0755');
        }
        $qr_path = 'qrcode/'.md5($redirect_url) . '.png';

        $params = [
            'd'=> $redirect_url,
            'l' => 'M',
            's' => 10,
            'sn' => UPLOAD_PATH.$qr_path
        ];

        $qr_url_params = http_build_query($params);
        $qr_url = $url = DJADMIN_URL . 'qr_api/gen_qrcode?' .$qr_url_params;
        //生成·二维码
        @file_get_contents($qr_url);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $m_shop_area = $mealShopAreaTableDao->getOne(['id'=>$meal_shop_area_table_model->shop_area_id, 'aid'=>$this->s_user->aid]);
        $title = '';
        if($m_shop_area)
        {
            $title = "桌位：{$m_shop_area->name} {$meal_shop_area_table_model->name}";
        }
        //合成图片
        $url = $url = DJADMIN_URL . 'qr_api/table_qr?qr_path=' . urlencode($qr_path)
            . "&title=". urlencode($title);


        $abs_qr_path = trim(@file_get_contents($url), ' ');

        if($abs_qr_path)
        {
            $ci_qiniu = new ci_qiniu();
            $target = 'meal/qrcode/'.md5($qr_url).'.png';

            $qr_img = $ci_qiniu->moveQiniu($abs_qr_path, $target);
            $mealShopAreaTableDao->update(['qr_img'=>$qr_img, 'code'=>$code], ['id'=>$meal_shop_area_table_model->id, 'aid'=>$this->s_user->aid]);
            //删除本地服务器图片
            @unlink($abs_qr_path);

            //清楚桌位信息缓存
            $mealShopAreaTableCache = new MealShopAreaTableCache(['aid'=>$this->s_user->aid,'table_id'=>$meal_shop_area_table_model->id,'code'=>$code]);
            $mealShopAreaTableCache->delete();

            return $qr_img;
        }
        return null;
    }
}
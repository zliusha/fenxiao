<?php
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopAccessDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2017/11/24
 * Time: 9:41
 */
class Statistics_api extends wm_service_controller
{
    /**
     *
     */
    public function money_shop_info()
    {
        $service = $this->input->get('service');
        $shop_id = intval($this->input->get('shop_id'));
        $time = trim($this->input->get('time'));

        //子账号权限
        if(!$this->is_zongbu)
            $shop_id = $this->currentShopId;

        $c_time = strtotime(date('y-m-d', time()));
        $s_time = $c_time;
        $e_time = $s_time + 86400;
        // 按创建时间范围查询
        if (!empty($time))
        {
            $range = explode(' - ', $time);
            $range = array_map('strtotime', $range);
            if (count($range) == 2)
            {
                $range[1] += 86400;
                $s_time = $range[0];
                $e_time = $range[1];
            }
        }
        $where = "aid={$this->s_user->aid} ";
        if($shop_id > 0 )
            $where .= " AND shop_id={$shop_id}";
        $where .= " AND time>{$s_time} AND time<={$e_time}";

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);

        $data['total_count'] = 0;
        $data['total_discount_money'] = 0.00;
        $data['member_order_money'] = 0.00;
        $data['pay_order_money'] = 0.00;
        $data['pay_order_count'] = 0;
        $data['valid_order_money'] = 0.00;
        $data['valid_order_count'] = 0;
        $data['done_order_money'] = 0.00;
        $data['done_order_count'] = 0;
        $data['invalid_order_money'] = 0.00;
        $data['invalid_order_count'] = 0;
        $data['refuse_order_money'] = 0.00;
        $data['refuse_order_count'] = 0;
        $data['no_shipping_order_money'] = 0.00;
        $data['no_shipping_order_count'] = 0;
        $data['cancel_order_money'] = 0.00;
        $data['cancel_order_count'] = 0;
        $data['tk_order_money'] = 0.00;
        $data['tk_order_count'] = 0;
        $data['afsno_order_money'] = 0.00;
        $data['afsno_order_count'] = 0;
        //外卖
        if($this->_valid_service($service, module_enum::WM_MODULE))
        {
            $wm_data = $wmOrderDao->orderStatistics($where);
            $data['total_count'] += $wm_data[0]['total_count'];
            $data['total_discount_money'] += $wm_data[0]['total_discount_money'];
            $data['member_order_money'] += $wm_data[0]['member_order_money'];
            $data['pay_order_money'] += $wm_data[0]['pay_order_money'];
            $data['pay_order_count'] += $wm_data[0]['pay_order_count'];
            $data['valid_order_money'] += $wm_data[0]['valid_order_money'];
            $data['valid_order_count'] += $wm_data[0]['valid_order_count'];
            $data['done_order_money'] += $wm_data[0]['done_order_money'];
            $data['done_order_count'] += $wm_data[0]['done_order_count'];
            $data['invalid_order_money'] += $wm_data[0]['invalid_order_money'];
            $data['invalid_order_count'] += $wm_data[0]['invalid_order_count'];
            $data['refuse_order_money'] += $wm_data[0]['refuse_order_money'];
            $data['refuse_order_count'] += $wm_data[0]['refuse_order_count'];
            $data['no_shipping_order_money'] += $wm_data[0]['no_shipping_order_money'];
            $data['no_shipping_order_count'] += $wm_data[0]['no_shipping_order_count'];
            $data['cancel_order_money'] += $wm_data[0]['cancel_order_money'];
            $data['cancel_order_count'] += $wm_data[0]['cancel_order_count'];
            $data['tk_order_money'] += $wm_data[0]['afsno_order_money'];
            $data['tk_order_count'] += $wm_data[0]['afsno_order_count'];
        }
        //零售
        if($this->_valid_service($service, module_enum::LS_MODULE))
        {
            $ls_data = $mealStatementsDao->statistics($where." AND `source_type`=3");
            $data['total_count'] += $ls_data[0]['total_count'];
            $data['total_discount_money'] += $ls_data[0]['total_discount_money'];
            $data['member_order_money'] += $ls_data[0]['member_order_money'];
            $data['pay_order_money'] += $ls_data[0]['pay_order_money'];
            $data['pay_order_count'] += $ls_data[0]['total_count'];
            $data['tk_order_money'] += $ls_data[0]['tk_order_money'];
            $data['tk_order_count'] += $ls_data[0]['tk_order_count'];
        }
        //堂食
        if($this->_valid_service($service, module_enum::MEAL_MODULE))
        {
            $meal_data = $mealStatementsDao->statistics($where." AND `source_type`=1");
            $data['total_count'] += $meal_data[0]['total_count'];
            $data['total_discount_money'] += $meal_data[0]['total_discount_money'];
            $data['member_order_money'] += $meal_data[0]['member_order_money'];
            $data['pay_order_money'] += $meal_data[0]['pay_order_money'];
            $data['pay_order_count'] += $meal_data[0]['total_count'];
            $data['tk_order_money'] += $meal_data[0]['tk_order_money'];
            $data['tk_order_count'] += $meal_data[0]['tk_order_count'];
        }

        $out_data['total_count'] = $data['total_count'];//订单数
        $out_data['pay_order_money'] = $data['pay_order_money'];//营业额
        $out_data['pay_order_count'] = $data['pay_order_count'];//付款订单数
        $out_data['total_discount_money'] = $data['total_discount_money'];//优惠抵扣
        $out_data['member_order_money'] = $data['member_order_money'];//储值卡消费额
        $out_data['tk_order_money'] = $data['tk_order_money'];//退款金额
        $out_data['tk_order_count'] = $data['tk_order_count'];//退款订单个数
        $out_data['invalid_order_count'] = $data['invalid_order_count'];//无效订单个数

        $this->json_do->set_data($out_data);
        $this->json_do->out_put();
    }



  //流量概况-昨天
  public function flow_info()
  {
      //条件-店铺id
      $shop_id=intval($this->input->get('shop_id'));
      $time = trim($this->input->get('time'));
      if(!is_numeric($shop_id))
          $this->json_do->set_error('002', '参数错误');

      $c_time = strtotime(date('y-m-d', time()));
      $s_time = $c_time;
      $e_time = $s_time + 86400;
      // 按创建时间范围查询
      if (!empty($time))
      {
          $range = explode(' - ', $time);
          $range = array_map('strtotime', $range);
          if (count($range) == 2)
          {
            $range[1] += 86400;
            $s_time = $range[0];
            $e_time = $range[1];
          }
      }

      //子账号权限
      if(!$this->is_zongbu)
      {
          $shop_id = $this->currentShopId;
      }
      $wmOrderDao = WmOrderDao::i($this->s_user->aid);
      $wmShopAccessDao = WmShopAccessDao::i($this->s_user->aid);
      $wmShopDao = WmShopDao::i($this->s_user->aid);
      $shop_ids = $wmShopDao->getShopIds(['aid'=>$this->s_user->aid, 'is_delete'=>0]);
      $query_where = "aid={$this->s_user->aid} AND `time` >= {$s_time} AND `time` < {$e_time} ";
      if($shop_id > 0)
      {
         $query_where .= " AND shop_id={$shop_id}";
      }
      else
      {
         $query_where .= " AND shop_id in ($shop_ids)";
      }
      //访客数
      $c_uv = $wmShopAccessDao->getSum("uv", $query_where);
      //下单人数
      $c_order_user = $wmOrderDao->getOrderUser($query_where);
      //下单金额
      $c_order_money = $wmOrderDao->getSum("pay_money", $query_where);
      //下单个数
      $c_order_count = $wmOrderDao->getCount($query_where);

      $pay_order_status = "2020,2035,2040,2050,2060,4020,4035,4040,4050,4060,5010,5011,5012,5020,5034,5040,5060,6060,6061";
      //付款人数
      $c_pay_order_user = $wmOrderDao->getOrderUser($query_where."  AND status in({$pay_order_status})");
      //付款金额
      $c_pay_order_money = $wmOrderDao->getSum("pay_money", $query_where." AND status in({$pay_order_status}) ");
      //付款订单个数
      $c_pay_order_count = $wmOrderDao->getCount($query_where."  AND status in({$pay_order_status}) ");
      //客单价
      $c_guest_unit_price = $c_pay_order_count > 0 ? $c_pay_order_money/$c_pay_order_count : 0;
      //下单转化率
      $c_turn_order_rate = format_rate($c_order_user,$c_uv,2);
      //付款转化率
      $c_turn_pay_order_rate = format_rate($c_pay_order_user,$c_order_user,2);
      //全店转化率
      $c_turn_shop_rate = format_rate($c_pay_order_user,$c_uv,2);


      $data['c_uv'] = $c_uv;
      $data['c_order_user'] = $c_order_user;
      $data['c_order_money'] = number_format($c_order_money,2,'.','');
      $data['c_order_count'] = $c_order_count;
      $data['c_pay_order_user'] = $c_pay_order_user;
      $data['c_pay_order_money'] = number_format($c_pay_order_money,2,'.','');
      $data['c_pay_order_count'] = $c_pay_order_count;
      $data['c_guest_unit_price'] = number_format($c_guest_unit_price,2,'.','');
      $data['c_turn_order_rate'] = $c_turn_order_rate;
      $data['c_turn_pay_order_rate'] = $c_turn_pay_order_rate;
      $data['c_turn_shop_rate'] = $c_turn_shop_rate;

      //昨日数据
      $data['y_uv'] = 0;
      $data['y_order_user'] = 0;
      $data['y_order_money'] = 0.00;
      $data['y_order_count'] = 0;
      $data['y_pay_order_user'] = 0;
      $data['y_pay_order_money'] = 0.00;
      $data['y_pay_order_count'] = 0;
      $data['y_guest_unit_price'] = 0.00;
      $data['y_turn_order_rate'] = 0.00;
      $data['y_turn_pay_order_rate'] = 0.00;
      $data['y_turn_shop_rate'] = 0.00;

      //判断是否需要昨日数据
      if(($e_time-3600*24) == $s_time && $s_time==$c_time)
      {
          $y_time = $s_time-3600*24;
          $y_query_where = "aid={$this->s_user->aid} AND `time` >= {$y_time} AND `time` < {$s_time} ";
          if($shop_id > 0)
          {
            $y_query_where .= " AND shop_id={$shop_id}";
          }
          //昨日访客数
          $y_uv = $wmShopAccessDao->getSum("uv", $y_query_where);
          //昨日下单人数
          $y_order_user = $wmOrderDao->getOrderUser($y_query_where);
          //昨日下单金额
          $y_order_money = $wmOrderDao->getSum("pay_money", $y_query_where);
          //昨日下单个数
          $y_order_count = $wmOrderDao->getCount($y_query_where);
          //包含付款的订单状态
          $pay_order_status = "2020,2035,2040,2050,2060,4020,4035,4040,4050,4060,5010,5011,5012,5020,5034,5040,5060,6060,6061";
          //昨日付款人数
          $y_pay_order_user = $wmOrderDao->getOrderUser($y_query_where."  AND status in({$pay_order_status})");
          //昨日付款金额
          $y_pay_order_money = $wmOrderDao->getSum("pay_money", $y_query_where." AND status in({$pay_order_status}) ");
          //昨日付款订单个数
          $y_pay_order_count = $wmOrderDao->getCount($y_query_where."  AND status in({$pay_order_status}) ");
          //昨日客单价
          $y_guest_unit_price = $c_pay_order_count > 0 ? $c_pay_order_money/$c_pay_order_count : 0;
          //昨日下单转化率
          $y_turn_order_rate = format_rate($c_order_user,$c_uv,2);
          //昨日付款转化率
          $y_turn_pay_order_rate = format_rate($c_pay_order_user,$c_order_user,2);
          //昨日全店转化率
          $y_turn_shop_rate = format_rate($c_pay_order_user,$c_uv,2);

          $data['y_uv'] = $y_uv;
          $data['y_order_user'] = $y_order_user;
          $data['y_order_money'] = number_format($y_order_money,2,'.','');
          $data['y_order_count'] = $y_order_count;
          $data['y_pay_order_user'] = $y_pay_order_user;
          $data['y_pay_order_money'] = number_format($y_pay_order_money,2,'.','');
          $data['y_pay_order_count'] = $y_pay_order_count;
          $data['y_guest_unit_price'] = number_format($y_guest_unit_price,2,'.','');
          $data['y_turn_order_rate'] = $y_turn_order_rate;
          $data['y_turn_pay_order_rate'] = $y_turn_pay_order_rate;
          $data['y_turn_shop_rate'] = $y_turn_shop_rate;
      }

      $this->json_do->set_data($data);
      $this->json_do->out_put();
  }

  /**
   * 商品统计
   */
  public function goods_info()
  {
      //条件-店铺id
      $service = $this->input->get('service');
      $shop_id =intval($this->input->get('shop_id'));
      $time    = trim($this->input->get('time'));
      $measure_type    = trim($this->input->get('measure_type'));
      $sort_number_type = trim($this->input->get('sort_number_type'));
      $sort_money_type  = trim($this->input->get('sort_money_type'));

      //子账号权限
      if(!$this->is_zongbu)
      {
          $shop_id = $this->currentShopId;
      }

      $c_time = strtotime(date('y-m-d', time()));
      $s_time = $c_time;
      $e_time = $s_time + 86400;
      // 按创建时间范围查询
      if (!empty($time))
      {
          $range = explode(' - ', $time);
          $range = array_map('strtotime', $range);
          if (count($range) == 2)
          {
            $range[1] += 86400;
            $s_time = $range[0];
            $e_time = $range[1];
          }
      }

      $wmShopDao = WmShopDao::i($this->s_user->aid);
      $wmGoodsSkuDao = WmGoodsSkuDao::i($this->s_user->aid);
      $shop_list = $wmShopDao->getAllArray(['aid'=>$this->s_user->aid, 'is_delete'=>0], 'id,aid,shop_name');
      $shop_ids = trim(implode(',',array_column($shop_list, 'id')), ',');
      if(empty($shop_ids)) $shop_ids = '0';

      $wmShardDb = WmShardDb::i($this->s_user->aid);

      $order_ext_sql = "";
      if($shop_id > 0)
          $order_where = " AND shop_id={$shop_id}";
      else
          $order_where = " AND shop_id in ({$shop_ids})";

      //商品纬度统计数据
      //外卖
      if($this->_valid_service($service, module_enum::WM_MODULE))
      {

          $order_ext_sql  = " SELECT aid,shop_id,goods_id,sku_id,num,final_price,pay_money FROM {$wmShardDb->tables['wm_order_ext']} WHERE status>=2020 AND `time`>={$s_time} AND `time`<{$e_time} {$order_where}";
      }

      //零售
      if($this->_valid_service($service, module_enum::LS_MODULE))
      {
          if($order_ext_sql)
              $order_ext_sql .= " UNION ALL ";
          $order_ext_sql  .= " SELECT aid,shop_id,goods_id,sku_id,num,final_price,pay_money FROM {$wmShardDb->tables['retail_order_ext']} WHERE status>=2020 AND `time`>={$s_time} AND `time`<{$e_time} {$order_where}";
      }
      //堂食
      if($this->_valid_service($service, module_enum::MEAL_MODULE))
      {
          if($order_ext_sql)
              $order_ext_sql .= " UNION ALL ";
          $order_ext_sql  .= " SELECT aid,shop_id,goods_id,sku_id,num,final_price,pay_money FROM {$wmShardDb->tables['meal_order_ext']} WHERE status>=2020 AND `time`>={$s_time} AND `time`<{$e_time} {$order_where}";
      }
      $tmp_sql = "SELECT goods_id,IFNULL(SUM(num),0) sale_num, IFNULL(SUM(final_price), 0) sale_money FROM ({$order_ext_sql}) a GROUP BY goods_id";

      $page = new PageList($wmShardDb);
      $p_conf = $page->getConfig();
      $p_conf->table = "{$wmShardDb->tables['wm_goods']} c left join ($tmp_sql) d ON c.id=d.goods_id";
      $p_conf->fields = 'c.id,c.aid,c.shop_id,c.pict_url,c.title,c.sku_type,c.inner_price,c.time,c.measure_type,c.unit_type,IFNULL(d.sale_num,0) as sale_number,IFNULL(d.sale_money,0) as sale_money';
      $p_conf->where = " c.aid={$this->s_user->aid}  AND c.is_delete=0 ";

      //计量方式1计件,2计重
      if(is_numeric($measure_type) && $measure_type > 0)
      {
          $p_conf->where .= " AND c.measure_type={$measure_type}";
      }
      //是否子门店
      if($shop_id > 0)
      {
        $p_conf->where .= " AND c.shop_id={$shop_id}";
      }
      else
      {
        $p_conf->where .= " AND c.shop_id in ($shop_ids)";
      }

      //排序
      $sort_number = "";
      $sort_money = "";
      if(in_array($sort_number_type, ['asc', 'desc']))
        $sort_number = $sort_number_type;
      if(in_array($sort_money_type, ['asc', 'desc']))
        $sort_money = $sort_money_type;

      if(!empty($sort_number))
      {
          $p_conf->order = "sale_number {$sort_number}";
      }
      if(!empty($sort_money))
      {
          $p_conf->order = "sale_money {$sort_money}";
      }
      $count = 0;
      $list = $page->getList($p_conf, $count);
      //获取goods_ids
      $goods_ids = implode(',', array_column($list, 'id'));
      if (empty($goods_ids)) {
          $goods_ids = '0';
      }
      //获取商品的sku信息
      $goods_sku_where = " aid={$this->s_user->aid} AND goods_id in ($goods_ids)";
      if($shop_id > 0)
          $goods_sku_where .= " AND shop_id={$shop_id}";
      $sku_list = $wmGoodsSkuDao->getAllArray($goods_sku_where);
      //sku纬度统计信息
      $order_sku_ext_sql = "";
      //外卖
      if($this->_valid_service($service, module_enum::WM_MODULE))
      {

          $order_sku_ext_sql  = " SELECT aid,shop_id,goods_id,sku_id,sku_str,num,final_price,pay_money FROM {$wmShardDb->tables['wm_order_ext']} WHERE goods_id in ({$goods_ids}) AND status>=2020 AND `time`>={$s_time} AND `time`<{$e_time} {$order_where}";
      }
      //零售
      if($this->_valid_service($service, module_enum::LS_MODULE))
      {
          if($order_sku_ext_sql)
              $order_sku_ext_sql .= " UNION ALL ";
          $order_sku_ext_sql  .= " SELECT aid,shop_id,goods_id,sku_id,sku_str,num,final_price,pay_money FROM {$wmShardDb->tables['retail_order_ext']} WHERE goods_id in ({$goods_ids}) AND status>=2020 AND `time`>={$s_time} AND `time`<{$e_time} {$order_where}";
      }
      //堂食
      if($this->_valid_service($service, module_enum::MEAL_MODULE))
      {
          if($order_sku_ext_sql)
              $order_sku_ext_sql .= " UNION ALL ";
          $order_sku_ext_sql  .= " SELECT aid,shop_id,goods_id,sku_id,sku_str,num,final_price,pay_money FROM {$wmShardDb->tables['meal_order_ext']} WHERE goods_id in ({$goods_ids}) AND status>=2020 AND `time`>={$s_time} AND `time`<{$e_time} {$order_where}";
      }
      $tmp_sku_sql = "SELECT goods_id,sku_id,sku_str,IFNULL(SUM(num),0) sale_num, IFNULL(SUM(final_price), 0) sale_money FROM ({$order_sku_ext_sql}) a GROUP BY sku_id";
      $order_sku_list = $wmShardDb->query($tmp_sku_sql)->result_array();

      $inc = &inc_config('waimai');
      foreach($list as $k => $goods)
      {
          $list[$k]['pict_url'] = conver_picurl($goods['pict_url']);
          $list[$k]['sale_money'] = sprintf("%.2f",$goods['sale_money']);

          //商品所在店铺名称
          $list[$k]['shop_name'] = '';
          $shop = array_find($shop_list, 'id', $goods['shop_id']);
          if($shop)
              $list[$k]['shop_name'] = $shop['shop_name'];

          //计量单位
          $list[$k]['unit_name'] = '';
          if($goods['unit_type']>1)
              $list[$k]['unit_name'] = $inc['goods_unit_type'][$goods['unit_type']];
          else
              $list[$k]['sale_number'] = intval($goods['sale_number']);

          //增加sku数据
          $_sku_list = array_find_list($sku_list, 'goods_id', $goods['id']);
          if($_sku_list)
          {
              foreach($_sku_list as $sk => $sku)
              {
                  $_order_sku = array_find($order_sku_list, 'sku_id', $sku['id']);
                  $_sku_list[$sk]['sale_num'] = isset($_order_sku['sale_num']) ? $_order_sku['sale_num'] : 0;
                  if($goods['unit_type']<=1)
                      $_sku_list[$sk]['sale_num'] = intval($_sku_list[$sk]['sale_num']);
                  $_sku_list[$sk]['sale_money'] = isset($_order_sku['sale_money']) ? sprintf("%.2f",$_order_sku['sale_money']) : 0.00;
              }
          }
          $list[$k]['sku_list'] = $_sku_list;
      }

      $data['total'] = $count;
      $data['rows'] = $list;
      $this->json_do->set_data($data);
      $this->json_do->out_put();
  }
  //营收统计
  public function money_info()
  {
      //条件-店铺id
      $shop_id=intval($this->input->get('shop_id'));
      $time = trim($this->input->get('time'));
      if(!is_numeric($shop_id))
        $this->json_do->set_error('002', '参数错误');

      $c_time = strtotime(date('y-m-d', time()));
      $s_time = $c_time;
      $e_time = $s_time + 86400;
      // 按创建时间范围查询
      if (!empty($time))
      {
        $range = explode(' - ', $time);
        $range = array_map('strtotime', $range);
        if (count($range) == 2)
        {
          $range[1] += 86400;
          $s_time = $range[0];
          $e_time = $range[1];
        }
      }

      //子账号权限
      if(!$this->is_zongbu)
      {
        $shop_id = $this->currentShopId;
      }
      $wmOrderDao = WmOrderDao::i($this->s_user->aid);
      $wmOrderFreightDao = WmOrderFreightDao::i($this->s_user->aid);

      $pay_order_status = "2020,2030,2035,2040,2050,2060,4020,4030,4035,4040,4050,4060,5010,5011,5012,5020,5034,5040,5060,6060,6061";
      $valid_order_status = "2020,2035,2040,2050,2060,,4030,4020,4035,4040,4060,6060,6061";

      $query_where = "aid={$this->s_user->aid} AND `time` >= {$s_time} AND `time` < {$e_time} ";
      $_freight_where = "a.aid={$this->s_user->aid} AND b.`time` >= {$s_time} AND b.`time` < {$e_time}";

      $wmShopDao = WmShopDao::i($this->s_user->aid);
      $shop_ids = $wmShopDao->getShopIds(['aid'=>$this->s_user->aid, 'is_delete'=>0]);
      if($shop_id > 0)
      {
        $query_where .= " AND shop_id={$shop_id}";
        $_freight_where .= " AND a.shop_id={$shop_id}";
      }
      else
      {
        $query_where .= " AND shop_id in ($shop_ids)";
        $_freight_where .= " AND a.shop_id in ($shop_ids)";
      }
      //付款金额
      $c_pay_order_money = $wmOrderDao->getSum("pay_money", $query_where." AND status in({$pay_order_status}) ");
      //支出（配送费）
      $c_shipping_order_money = $wmOrderFreightDao->getFreight($_freight_where, true);
      //预计收入
      $c_pre_money = $c_pay_order_money-$c_shipping_order_money;
      //有效订单
      $c_valid_order_count = $wmOrderDao->getCount($query_where." AND status in({$valid_order_status}) ");
      $c_valid_order_money = $wmOrderDao->getSum('pay_money',$query_where." AND status in({$valid_order_status}) ");

      //客单价
      $c_guest_unit_price = $c_valid_order_count > 0 ? $c_valid_order_money/$c_valid_order_count : 0;
      //无效订单
      $c_refuse_order_count = $wmOrderDao->getCount($query_where." AND status in (5010,5011,5020)");
      $c_refuse_order_money = $wmOrderDao->getSum('pay_money',$query_where." AND status in (5010,5011,5020)");
      $c_no_shipping_order_count = $wmOrderDao->getCount($query_where." AND status in (5012,5040)");
      $c_no_shipping_order_money = $wmOrderDao->getSum('pay_money',$query_where." AND status in (5012,5040)");
      $c_cancel_order_count =  $wmOrderDao->getCount($query_where." AND status in (5001,5000)");
      $c_cancel_order_money = $wmOrderDao->getSum('pay_money',$query_where." AND status in (5001,5000)");
      $c_tk_order_count = $wmOrderDao->getCount($query_where." AND status=5060");
      $c_tk_order_money = $wmOrderDao->getSum('pay_money',$query_where." AND status=5060");

      //售后订单
      $c_part_tk_order_count = $wmOrderDao->getCount($query_where." AND status=6061");
      $c_part_tk_order_money = $wmOrderDao->getSum('tk_money',$query_where." AND status=6061");

      $data['c_pay_order_money'] = number_format($c_pay_order_money,2,'.','');
      $data['c_shipping_order_money'] = number_format($c_shipping_order_money,2,'.','');
      $data['c_pre_money'] = number_format($c_pre_money,2,'.','');
      $data['c_valid_order_count'] = $c_valid_order_count;
      $data['c_valid_order_count'] = $c_valid_order_count;
      $data['c_valid_order_money'] = number_format($c_valid_order_money,2,'.','');
      $data['c_guest_unit_price'] = number_format($c_guest_unit_price, 2, '.', "");

      $data['c_refuse_order_count'] = $c_refuse_order_count;
      $data['c_refuse_order_money'] = number_format($c_refuse_order_money,2,'.','');
      $data['c_no_shipping_order_count'] = $c_no_shipping_order_count;
      $data['c_no_shipping_order_money'] = number_format($c_no_shipping_order_money,2,'.','');
      $data['c_cancel_order_count'] = $c_cancel_order_count;
      $data['c_cancel_order_money'] = number_format($c_cancel_order_money,2,'.','');
      $data['c_tk_order_count'] = $c_tk_order_count;
      $data['c_tk_order_money'] = number_format($c_tk_order_money,2,'.','');
      $data['c_invalid_order_count'] = $c_refuse_order_count+$c_no_shipping_order_count+$c_cancel_order_count+$c_tk_order_count;
      $data['c_invalid_order_money'] = $c_refuse_order_money+$c_no_shipping_order_money+$c_cancel_order_money+$c_tk_order_money;



      $data['c_part_tk_order_count'] = $c_part_tk_order_count;
      $data['c_part_tk_order_money'] = number_format($c_part_tk_order_money,2,'.','');


      //对比昨日数据
      $data['y_pay_order_money'] = 0.00;
      $data['y_shipping_order_money'] = 0.00;
      $data['y_pre_money'] = 0.00;
      $data['y_valid_order_count'] = 0;
      $data['y_valid_order_money'] = 0.00;
      $data['y_guest_unit_price'] = 0.00;

      $data['y_refuse_order_count'] = 0;
      $data['y_refuse_order_money'] = 0.00;
      $data['y_no_shipping_order_count'] = 0;
      $data['y_no_shipping_order_money'] = 0.00;
      $data['y_cancel_order_count'] = 0;
      $data['y_cancel_order_money'] = 0.00;
      $data['y_tk_order_count'] = 0;
      $data['y_tk_order_money'] = 0.00;

      $data['y_invalid_order_count'] = 0.00;
      $data['y_invalid_order_money'] = 0.00;

      $data['y_part_tk_order_count'] = 0;
      $data['y_part_tk_order_money'] = 0.00;

      //判断是否需要昨日数据
      if(($e_time-3600*24) == $s_time && $s_time==$c_time)
      {
          $y_time = $s_time-3600*24;
          $y_query_where = "aid={$this->s_user->aid} AND `time` >= {$y_time} AND `time` < {$s_time} ";
          $_y_freight_where = "a.aid={$this->s_user->aid} AND b.`time` >= {$y_time} AND b.`time` < {$s_time} ";
          if($shop_id > 0)
          {
            $y_query_where .= " AND shop_id={$shop_id}";
            $_y_freight_where .= " AND a.shop_id={$shop_id}";
          }
          else
          {
            $y_query_where .= " AND shop_id in ($shop_ids)";
            $_y_freight_where .= " AND a.shop_id in ($shop_ids)";
          }

          //昨日付款金额
          $y_pay_order_money = $wmOrderDao->getSum("pay_money", $y_query_where." AND status in({$pay_order_status}) ");
          //昨日支出（配送费）
          $y_shipping_order_money = $wmOrderFreightDao->getFreight($_y_freight_where, true);
          //昨日预计收入
          $y_pre_money = $y_pay_order_money-$y_shipping_order_money;
          //昨日有效订单
          $y_valid_order_count = $wmOrderDao->getCount($y_query_where." AND status in({$valid_order_status}) ");
          $y_valid_order_money = $wmOrderDao->getSum('pay_money',$y_query_where." AND status in({$valid_order_status}) ");
          //客单价
          $y_guest_unit_price =$y_valid_order_count>0 ? $y_valid_order_money/$y_valid_order_count : 0;
          //昨日无效订单
          $y_refuse_order_count = $wmOrderDao->getCount($y_query_where." AND status in (5010,5011,5020)");
          $y_refuse_order_money = $wmOrderDao->getSum('pay_money',$y_query_where." AND status in (5010,5011,5020)");
          $y_no_shipping_order_count = $wmOrderDao->getCount($y_query_where." AND status in (5012,5040)");
          $y_no_shipping_order_money = $wmOrderDao->getSum('pay_money',$y_query_where." AND status in (5012,5040)");
          $y_cancel_order_count =  $wmOrderDao->getCount($y_query_where." AND status in (5001,5000)");
          $y_cancel_order_money = $wmOrderDao->getSum('pay_money',$y_query_where." AND status in (5001,5000)");
          $y_tk_order_count = $wmOrderDao->getCount($y_query_where." AND status=5060");
          $y_tk_order_money = $wmOrderDao->getSum('pay_money',$y_query_where." AND status=5060");

          //昨日售后订单
          $y_part_tk_order_count = $wmOrderDao->getCount($y_query_where." AND status=6061");
          $y_part_tk_order_money = $wmOrderDao->getSum('tk_money',$y_query_where." AND status=6061");

          $data['y_pay_order_money'] = $y_pay_order_money;
          $data['y_shipping_order_money'] = number_format($y_shipping_order_money,2,'.','');
          $data['y_pre_money'] = number_format($y_pre_money,2,'.','');
          $data['y_valid_order_count'] = $y_valid_order_count;
          $data['y_valid_order_money'] = number_format($y_valid_order_money,2,'.','');
          $data['y_guest_unit_price'] = number_format($y_guest_unit_price,2,'.','');

          //无效订单
          $data['y_refuse_order_count'] = $y_refuse_order_count;
          $data['y_refuse_order_money'] = number_format($y_refuse_order_money,2,'.','');
          $data['y_no_shipping_order_count'] = $y_no_shipping_order_count;
          $data['y_no_shipping_order_money'] = number_format($y_no_shipping_order_money,2,'.','');
          $data['y_cancel_order_count'] = $y_cancel_order_count;
          $data['y_cancel_order_money'] = number_format($y_cancel_order_money,2,'.','');
          $data['y_tk_order_count'] = $y_tk_order_count;
          $data['y_tk_order_money'] = number_format($y_tk_order_money,2,'.','');
          $data['y_invalid_order_count'] = $y_refuse_order_count+$y_no_shipping_order_count+$y_cancel_order_count+$y_tk_order_count;
          $data['y_invalid_order_money'] = $y_refuse_order_money+$y_no_shipping_order_money+$y_cancel_order_money+$y_tk_order_money;

          $data['y_part_tk_order_count'] = $y_part_tk_order_count;
          $data['y_part_tk_order_money'] = number_format($y_part_tk_order_money,2,'.','');
      }


      //时间段数据
      $zc_order_count = $wmOrderDao->getCount($query_where." AND status in ({$valid_order_status}) AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')>='06' AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')<='09'");
      $wc_order_count = $wmOrderDao->getCount($query_where." AND status in ({$valid_order_status}) AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')>='11' AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')<='13'");
      $xwc_order_count = $wmOrderDao->getCount($query_where." AND status in ({$valid_order_status}) AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')>='14' AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')<='16'");
      $dc_order_count = $wmOrderDao->getCount($query_where." AND status in ({$valid_order_status}) AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')>='17' AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')<='19'");
      $yx_order_count = $wmOrderDao->getCount($query_where." AND status in ({$valid_order_status}) AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')>='21' AND DATE_FORMAT(FROM_UNIXTIME(`time`),'%H')<='23'");

      $data['zc_order_rate'] = format_rate($zc_order_count,$c_valid_order_count, 2);
      $data['wc_order_rate'] = format_rate($wc_order_count,$c_valid_order_count, 2);
      $data['xwc_order_rate'] = format_rate($xwc_order_count,$c_valid_order_count, 2);
      $data['dc_order_rate'] = format_rate($dc_order_count,$c_valid_order_count, 2);
      $data['yx_order_rate'] =  format_rate($yx_order_count,$c_valid_order_count, 2);


      $this->json_do->set_data($data);
      $this->json_do->out_put();
  }
  /**
   * 获取门店排行数据
   * @param int $rank_type 数据类型
   * @param int $s_time
   * @param int $e_time
   * @return array|bool
   */
  public function get_flow_shop_statistic_data()
  {
      $rank_type = $this->input->get('rank_type');
      $c_time = strtotime(date('y-m-d', time()));
      $s_time = $c_time;
      $e_time = $s_time + 86400;
      // 按创建时间范围查询
      if (!empty($time))
      {
        $range = explode(' - ', $time);
        $range = array_map('strtotime', $range);
        if (count($range) == 2)
        {
          $range[1] += 86400;
          $s_time = $range[0];
          $e_time = $range[1];
        }
      }

      //门店排行
      $_rank_type = [1,2,3,4,5,6,7];
      //默认uv排行
      if(!in_array($rank_type,$_rank_type)) $rank_type = 1;
      $wmShopDao = WmShopDao::i($this->s_user->aid);
      $wmOrderDao = WmOrderDao::i($this->s_user->aid);
      $wm_shops = $wmShopDao->getAllArray(['aid'=>$this->s_user->aid, 'is_delete'=>0]);
      if(empty($wm_shops)) return false;

      $c_where = "aid={$this->s_user->aid} AND `time` > {$s_time} AND `time` < {$e_time} ";
      $y_time = $s_time-3600*24;
      $y_where = "aid={$this->s_user->aid} AND `time` > {$y_time} AND `time` < {$s_time}  ";


      $data = $wmOrderDao->getFlowStatisticData($rank_type, $c_where, $y_where);
      //包含已付款的订单状态
      $pay_order_status = "2020,2035,2040,2050,2060,4020,4035,4040,4050,4060,5010,5011,5012,5020,5034,5040,5060,6060,6061";

      $shop_data = [];
      foreach($wm_shops as $shop)
      {

          if($rank_type == 1) //访客数
          {

              $c_shop_data = array_find($data['c_data'],'shop_id',$shop['id']);
              $y_shop_data = array_find($data['y_data'],'shop_id',$shop['id']);

              $c_data = isset($c_shop_data['uv']) ? $c_shop_data['uv'] : 0 ;
              $y_data = isset($y_shop_data['uv']) ? $y_shop_data['uv'] : 0 ;
          }
          elseif($rank_type==2)//下单人数
          {

              $c_shop_data = array_find($data['c_data'],'shop_id',$shop['id']);
              $y_shop_data = array_find($data['y_data'],'shop_id',$shop['id']);

              $c_data = isset($c_shop_data['num']) ? $c_shop_data['num'] : 0 ;
              $y_data = isset($y_shop_data['num']) ? $y_shop_data['num'] : 0 ;
          }
          elseif($rank_type==3)//付款人数
          {
              $c_shop_data = array_find($data['c_data'],'shop_id',$shop['id']);
              $y_shop_data = array_find($data['y_data'],'shop_id',$shop['id']);

              $c_data = isset($c_shop_data['num']) ? $c_shop_data['num'] : 0 ;
              $y_data = isset($y_shop_data['num']) ? $y_shop_data['num'] : 0 ;
          }
          elseif($rank_type==4)//客单价
          {
              $c_shop_pay_order_money_data = array_find($data['c_data']['pay_order_money'],'shop_id', $shop['id']);
              $c_shop_pay_order_count_data = array_find($data['c_data']['pay_order_count'],'shop_id', $shop['id']);

              $c_shop_pay_order_money = isset($c_shop_pay_order_money_data['pay_money']) ? $c_shop_pay_order_money_data['pay_money'] : 0 ;
              $c_shop_pay_order_count = isset($c_shop_pay_order_count_data['num']) ? $c_shop_pay_order_count_data['num'] : 0 ;
              $c_data = format_rate($c_shop_pay_order_money, $c_shop_pay_order_count, 2);

              $y_shop_pay_order_money_data = array_find($data['y_data']['pay_order_money'],'shop_id', $shop['id']);
              $y_shop_pay_order_count_data = array_find($data['y_data']['pay_order_count'],'shop_id', $shop['id']);

              $y_shop_pay_order_money = isset($y_shop_pay_order_money_data['pay_money']) ? $y_shop_pay_order_money_data['pay_money'] : 0 ;
              $y_shop_pay_order_count = isset($y_shop_pay_order_count_data['num']) ? $y_shop_pay_order_count_data['num'] : 0 ;
              $y_data = format_rate($y_shop_pay_order_money, $y_shop_pay_order_count, 2);
          }
          elseif($rank_type==5)//下单转化率
          {
              $c_shop_uv_data = array_find($data['c_data']['uv'],'shop_id',$shop['id']);
              $c_shop_order_user_data = array_find($data['c_data']['order_user'],'shop_id',$shop['id']);
              $c_shop_uv = isset($c_shop_uv_data['uv']) ? $c_shop_uv_data['uv'] : 0 ;
              $c_shop_order_user =isset($c_shop_order_user_data['num']) ? $c_shop_order_user_data['num'] : 0 ;
              $c_data = format_rate($c_shop_order_user,$c_shop_uv,2);

              $y_shop_uv_data = array_find($data['y_data']['uv'],'shop_id',$shop['id']);
              $y_shop_order_user_data = array_find($data['y_data']['order_user'],'shop_id',$shop['id']);
              $y_shop_uv = isset($y_shop_uv_data['uv']) ? $y_shop_uv_data['uv'] : 0 ;
              $y_shop_order_user =isset($y_shop_order_user_data['num']) ? $y_shop_order_user_data['num'] : 0 ;
              $y_data = format_rate($y_shop_order_user,$y_shop_uv,2);
          }
          elseif($rank_type==6)//付款转化率
          {

              $c_shop_pay_order_user_data = array_find($data['c_data']['pay_order_user'],'shop_id',$shop['id']);
              $c_shop_order_user_data = array_find($data['c_data']['order_user'],'shop_id',$shop['id']);
              $c_shop_pay_order_user = isset($c_shop_pay_order_user_data['num']) ? $c_shop_pay_order_user_data['num'] : 0 ;
              $c_shop_order_user =isset($c_shop_order_user_data['num']) ? $c_shop_order_user_data['num'] : 0 ;
              $c_data = format_rate($c_shop_pay_order_user,$c_shop_order_user,2);

              $y_shop_pay_order_user_data = array_find($data['y_data']['pay_order_user'],'shop_id',$shop['id']);
              $y_shop_order_user_data = array_find($data['y_data']['order_user'],'shop_id',$shop['id']);
              $y_shop_pay_order_user = isset($y_shop_pay_order_user_data['num']) ? $y_shop_pay_order_user_data['num'] : 0 ;
              $y_shop_order_user =isset($y_shop_order_user_data['num']) ? $y_shop_order_user_data['num'] : 0 ;
              $y_data = format_rate($y_shop_pay_order_user,$y_shop_order_user,2);

          }
          elseif($rank_type==7)//全店转化率
          {
              $c_shop_uv_data = array_find($data['c_data']['uv'],'shop_id',$shop['id']);
              $c_shop_pay_order_user_data = array_find($data['c_data']['pay_order_user'],'shop_id',$shop['id']);
              $c_shop_uv = isset($c_shop_uv_data['uv']) ? $c_shop_uv_data['uv'] : 0 ;
              $c_shop_pay_order_user = isset($c_shop_pay_order_user_data['num']) ? $c_shop_pay_order_user_data['num'] : 0 ;
              $c_data = format_rate($c_shop_pay_order_user,$c_shop_uv,2);


              $y_shop_uv_data = array_find($data['y_data']['uv'],'shop_id',$shop['id']);
              $y_shop_pay_order_user_data = array_find($data['y_data']['pay_order_user'],'shop_id',$shop['id']);
              $y_shop_uv = isset($y_shop_uv_data['uv']) ? $y_shop_uv_data['uv'] : 0 ;
              $y_shop_pay_order_user = isset($y_shop_pay_order_user_data['num']) ? $y_shop_pay_order_user_data['num'] : 0 ;
              $y_data = format_rate($y_shop_pay_order_user,$y_shop_uv,2);
          }
          else
          {
              //
              $c_data = 0;
              $y_data = 0;
          }

          $shop_data[] = ['shop_name'=>$shop['shop_name'], 'c_data'=>$c_data,'y_data'=>$y_data];
      }

      $data =  array_sort($shop_data, 'c_data', 'desc');

      $this->json_do->set_data($data);
      $this->json_do->out_put();
  }

  /**
   * 子账号流量统计图标数据
   * @param int $shop_id  店铺id
   * @param int $e_time  结束时间
   * @param int $days  天数
   * @return array
   */
  public function flow_chart_data()
  {
      $shop_id = $this->currentShopId;

      //当天夜晚24点时间
      $_e_time = strtotime(date('Y-m-d', time()))+3600*24;
      //7天前时间
      $s_days = date('Y-m-d', strtotime('-7 days'));
      $_s_time = strtotime($s_days);
      $wmShardDb = WmShardDb::i($this->s_user->aid);
      $wmShopAccessDao = WmShopAccessDao::i($this->s_user->aid);

      //包含已付款的订单状态
      $pay_order_status = "2020,2035,2040,2050,2060,4020,4035,4040,4050,4060,5010,5011,5012,5020,5034,5040,5060,6060,6061";

      //访客数
      $uv_data = $wmShopAccessDao->getAllArray("aid={$this->s_user->aid} AND shop_id={$shop_id} AND `time` > {$_s_time} AND `time` < {$_e_time}", 'id,day,uv');

      //付款人数
      $pay_order_user_sql = "SELECT count(DISTINCT uid) AS num,FROM_UNIXTIME(`time`,'%Y-%m-%d') AS date_time FROM {$wmShardDb->tables['wm_order']} WHERE aid={$this->s_user->aid} AND shop_id={$shop_id} AND `time` > {$_s_time} AND `time` < {$_e_time}  AND status in({$pay_order_status}) GROUP BY date_time";
      $pay_order_user_data = $wmShardDb->query($pay_order_user_sql)->result_array();
      //付款金额
      $pay_order_money_sql = "SELECT SUM(pay_money) AS pay_money,FROM_UNIXTIME(`time`,'%Y-%m-%d') AS date_time FROM {$wmShardDb->tables['wm_order']} WHERE aid={$this->s_user->aid} AND shop_id={$shop_id} AND `time` > {$_s_time} AND `time` < {$_e_time}  AND status in({$pay_order_status}) GROUP BY date_time";
      $pay_order_money_data = $wmShardDb->query($pay_order_money_sql)->result_array();
      //下单人数
      $order_user_sql = "SELECT count(DISTINCT uid) AS num,FROM_UNIXTIME(`time`,'%Y-%m-%d') AS date_time FROM {$wmShardDb->tables['wm_order']} WHERE aid={$this->s_user->aid} AND shop_id={$shop_id} AND `time` > {$_s_time} AND `time` < {$_e_time}  GROUP BY date_time";
      $order_user_data = $wmShardDb->query($order_user_sql)->result_array();

      $chart_data = [];
      $days = 7;
      //获取七天数据
      for($i=1; $i<=$days; $i++)
      {
          $e_time = $_e_time-3600*24*($i-1);
          $s_time = $e_time-3600*24;
          $day = date('Y-m-d', $s_time);

          $_uv_data = array_find($uv_data, 'day', $day);
          $_order_user_data = array_find($order_user_data, 'date_time', $day);
          $_pay_order_user_data = array_find($pay_order_user_data, 'date_time', $day);
          $_pay_order_money_data = array_find($pay_order_money_data, 'date_time', $day);

          //访客数
          $uv = isset($_uv_data['uv']) ? $_uv_data['uv'] : 0;
          //下单人数
          $order_user = isset($_order_user_data['num']) ? $_order_user_data['num'] : 0;

          //付款人数
          $pay_order_user = isset($_pay_order_user_data['num']) ? $_pay_order_user_data['num'] : 0;
          //付款金额
          $pay_order_money = isset($_pay_order_money_data['num']) ? $_pay_order_money_data['num'] : 0;
          //下单转化率
          $turn_order_rate = format_rate($order_user,$uv,2);
          //付款转化率
          $turn_pay_order_rate = format_rate($pay_order_user,$order_user,2);
          //全店转化率
          $turn_shop_rate = format_rate($pay_order_user,$uv,2);

          $tmp['sort'] = $i;
          $tmp['date'] = $day;
          $tmp['pay_order_money'] = $pay_order_money;
          $tmp['pay_order_user'] = $pay_order_user;
          $tmp['turn_order_rate'] = $turn_order_rate;
          $tmp['turn_pay_order_rate'] = $turn_pay_order_rate;
          $tmp['turn_shop_rate'] = $turn_shop_rate;

          $chart_data[] = $tmp;
      }

      $chart_data = array_sort($chart_data, 'sort', 'desc');
      $this->json_do->set_data($chart_data);
      $this->json_do->out_put();
  }

  /**
   * 营收统计获取店铺相关数据列表(当天有效)
   * @param int $rank_type 数据类型
   * @param int $s_time
   * @param int $e_time
   * @return array|bool
   */
  public function get_money_shop_statistic_data()
  {
      $service = $this->input->get('service');
      $rank_type = $this->input->get('rank_type');
      $time = trim($this->input->get('rank_time'));
      //当前时间
      $c_time = strtotime(date('y-m-d', time()));
      $s_time = $c_time;
      $e_time = $s_time + 86400;
      // 按创建时间范围查询
      if (!empty($time))
      {
        $range = explode(' - ', $time);
        $range = array_map('strtotime', $range);
        if (count($range) == 2)
        {
          $range[1] += 86400;
          $s_time = $range[0];
          $e_time = $range[1];
        }
      }


      //门店排行
      $_rank_type = [1=>'pay_order_money',2=>'pay_order_count'];
      //默认uv排行
      $_rank = isset($_rank_type[$rank_type]) ? $_rank_type[$rank_type] : $_rank_type[1];
      $wmShopDao = WmShopDao::i($this->s_user->aid);
      $wmOrderDao = WmOrderDao::i($this->s_user->aid);
      $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);

      $wm_shops = $wmShopDao->getAllArray(['aid'=>$this->s_user->aid, 'is_delete'=>0]);
      $where = "aid={$this->s_user->aid} AND `time` > {$s_time} AND `time` < {$e_time}";

      $wm_data = [];
      $ls_data = [];
      $meal_data = [];
      $data['pay_order_money'] = 0.00;
      $data['pay_order_count'] = 0;
      //外卖
      if($this->_valid_service($service, module_enum::WM_MODULE))
      {
          $wm_data = $wmOrderDao->orderStatistics($where, 'shop_id');
      }
      //零售
      if($this->_valid_service($service, module_enum::LS_MODULE))
      {
          $ls_data = $mealStatementsDao->statistics($where." AND `source_type`=3", 'shop_id');
      }
      //堂食
      if($this->_valid_service($service, module_enum::MEAL_MODULE))
      {
          $meal_data = $mealStatementsDao->statistics($where." AND `source_type`=1", 'shop_id');
      }

      $shop_data = [];
      foreach($wm_shops as $shop)
      {
          $_tmp_data['pay_order_money'] = 0.00;
          $_tmp_data['pay_order_count'] = 0;
          $_tmp_data['shop_name'] = $shop['shop_name'];
          $_tmp_data['shop_id'] = $shop['id'];
          //外卖
          $_wm_data = array_find($wm_data,'shop_id',$shop['id']);
          if($_wm_data)
          {
              $_tmp_data['pay_order_money'] += $_wm_data['pay_order_money'];
              $_tmp_data['pay_order_count'] += $_wm_data['pay_order_count'];
          }
          //零售
          $_ls_data = array_find($ls_data,'shop_id',$shop['id']);
          if($_ls_data)
          {
              $_tmp_data['pay_order_money'] += $_ls_data['pay_order_money'];
              $_tmp_data['pay_order_count'] += $_ls_data['total_count'];
          }
          //堂食
          $_meal_data = array_find($meal_data,'shop_id',$shop['id']);
          if($_meal_data)
          {
              $_tmp_data['pay_order_money'] += $_meal_data['pay_order_money'];
              $_tmp_data['pay_order_count'] += $_meal_data['total_count'];
          }
          $shop_data[] = $_tmp_data;
      }
      $shop_data = array_sort($shop_data, $_rank, 'desc');

      $this->json_do->set_data($shop_data);
      $this->json_do->out_put();
  }

    /**
    * 营收图表
    * @return array
    */
    public function money_chart_data()
    {
        $shop_id = intval($this->input->get('shop_id'));
        $service = $this->input->get('service');
        //子账号权限
        if(!$this->is_zongbu)
            $shop_id = $this->currentShopId;

        //当天夜晚24点时间
        $_e_time = strtotime(date('Y-m-d', time()))+3600*24;
        //7天前时间
        $s_days = date('Y-m-d', strtotime('-7 days'));
        $_s_time = strtotime($s_days);

        $where = "aid={$this->s_user->aid} ";
        if($shop_id > 0 )
          $where .= " AND shop_id={$shop_id}";
        $where .= " AND time>{$_s_time} AND time<={$_e_time}";

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);

        $chart_data = [];
        $wm_data = [];
        $ls_data = [];
        $meal_data = [];

        //外卖
        if($this->_valid_service($service, module_enum::WM_MODULE))
        {
          $wm_data = $wmOrderDao->orderStatistics($where, "date_time");
        }
        //零售
        if($this->_valid_service($service, module_enum::LS_MODULE))
        {
          $ls_data = $mealStatementsDao->statistics($where." AND `source_type`=3", "date_time");
        }
        //堂食
        if($this->_valid_service($service, module_enum::MEAL_MODULE))
        {
          $meal_data = $mealStatementsDao->statistics($where." AND `source_type`=1", "date_time");
        }
        $days = 7;
        //获取七天数据
        for($i=1; $i<=$days; $i++) {
          $e_time = $_e_time - 3600 * 24 * ($i - 1);
          $s_time = $e_time - 3600 * 24;
          $day = date('Y-m-d', $s_time);
          //外卖数据
          $_wm_data = array_find($wm_data, 'date_time', $day);
          if(!$_wm_data)
          {
              $_wm_data['total_count'] = 0;
              $_wm_data['total_discount_money'] = 0;
              $_wm_data['member_order_money'] = 0;
              $_wm_data['pay_order_money'] = 0;
              $_wm_data['pay_order_count'] = 0;
              $_wm_data['afsno_order_money'] = 0;
              $_wm_data['afsno_order_count'] = 0;
              $_wm_data['invalid_order_count'] = 0;
          }
          //零售数据
          $_ls_data = array_find($ls_data, 'date_time', $day);
          if(!$_ls_data)
          {
              $_ls_data['total_count'] = 0;
              $_ls_data['total_discount_money'] = 0;
              $_ls_data['member_order_money'] = 0;
              $_ls_data['pay_order_money'] = 0;
              $_ls_data['total_count'] = 0;
              $_ls_data['tk_order_money'] = 0;
              $_ls_data['tk_order_count'] = 0;
          }
          //堂食数据
          $_meal_data = array_find($meal_data, 'date_time', $day);
          if(!$_ls_data)
          {
              $_meal_data['total_count'] = 0;
              $_meal_data['total_discount_money'] = 0;
              $_meal_data['member_order_money'] = 0;
              $_meal_data['pay_order_money'] = 0;
              $_meal_data['total_count'] = 0;
              $_meal_data['tk_order_money'] = 0;
              $_meal_data['tk_order_count'] = 0;
          }

          $tmp['sort'] = $i;
          $tmp['date'] = $day;
          $tmp['total_count'] = $_wm_data['total_count'] + $_ls_data['total_count'] + $_meal_data['total_count'];//订单数
          $tmp['pay_order_money'] = $_wm_data['pay_order_money'] + $_ls_data['pay_order_money'] + $_meal_data['pay_order_money'];//营业额
          $tmp['pay_order_count'] =  $_wm_data['pay_order_count'] + $_ls_data['total_count'] + $_meal_data['total_count'];//付款订单数
          $tmp['total_discount_money'] = $_wm_data['total_discount_money'] + $_ls_data['total_discount_money'] + $_meal_data['total_discount_money'];//优惠抵扣
          $tmp['member_order_money'] = $_wm_data['member_order_money'] + $_ls_data['member_order_money'] + $_meal_data['member_order_money'];//储值卡消费额
          $tmp['tk_order_money'] = $_wm_data['afsno_order_money'] + $_ls_data['tk_order_money'] + $_meal_data['tk_order_money'];//退款金额
          $tmp['tk_order_count'] =$_wm_data['afsno_order_count'] + $_ls_data['tk_order_count'] + $_meal_data['tk_order_count'];//退款订单个数
          $tmp['invalid_order_count'] = $_wm_data['invalid_order_count'] ;//退款订单个数

          $chart_data[] = $tmp;
        }
        $chart_data = array_sort($chart_data, 'sort', 'desc');
        $this->json_do->set_data($chart_data);
        $this->json_do->out_put();
    }

    /**
     * 验证获取权限
     * @param string $service
     * @param string $module_enum
     */
    private function _valid_service($service='', $module_enum='')
    {
        //如果有具体服务名称
        if($service)
        {
            if($service == $module_enum && power_exists($module_enum,$this->service->power_keys)) return true;
            return false;
        }
        else
        {
            return power_exists($module_enum,$this->service->power_keys);
        }
    }
}
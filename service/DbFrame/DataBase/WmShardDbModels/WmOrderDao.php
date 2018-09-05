<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:06:30
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:03
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmOrderDao extends BaseDao
{
	/**
   * 获取下单人数
   */
    public function getOrderUser($where)
    {
        $this->db->select('count(DISTINCT uid) as num');
        if ($where) {
          $this->db->where($where);
        }

        $row = $this->db->from($this->tableName)->get()->row();
        if(isset($row->num)) return $row->num;

        return 0;
    }

  /**
   * 获取店铺相关统计信息
   */
    public function getMoneyStatisticData($rankType=0, $cWhere=[],$yWhere=[])
    {
        //包含已付款的订单状态
        $pay_order_status = "2020,2035,2040,2050,2060,4020,4035,4040,4050,4060,5010,5011,5012,5020,5035,5040,5060,6060,6061";
        $valid_order_status = "2020,2035,2040,2050,2060,4020,4035,4040,4060,6060,6061";
        if($rankType == 1) //营业额
        {
            //付款金额
            //获取以天数为间隔的金额求和数据
            $c_sql = "SELECT shop_id,SUM(pay_money) AS pay_money FROM {$this->tableName} WHERE {$cWhere['where']}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $c_data = $this->db->query($c_sql)->result_array();
            $y_sql = "SELECT shop_id,SUM(pay_money) AS pay_money FROM {$this->tableName} WHERE {$yWhere['where']}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $y_data = $this->db->query($y_sql)->result_array();
        }
        elseif($rankType==2)//有效订单
        {

            $c_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere['where']}  AND status in({$valid_order_status}) GROUP BY shop_id";
            $c_data = $this->db->query($c_sql)->result_array();
            $y_sql = "SELECT count(1) AS num,shop_id AS pay_money FROM {$this->tableName} WHERE {$yWhere['where']}  AND status in({$valid_order_status}) GROUP BY shop_id";
            $y_data = $this->db->query($y_sql)->result_array();

        }
        elseif($rankType==3)//支出
        {
        	$wmOrderFreightDao = WmOrderFreightDao::i($this->shardNode);
            $c_freight_where = $cWhere['freight_where'] ." AND b.status in({$pay_order_status})";
            $c_data = $wmOrderFreightDao->getFreight($c_freight_where);
            $y_freight_where = $yWhere['freight_where'] ." AND b.status in({$pay_order_status})";
            $y_data = $wmOrderFreightDao->getFreight($y_freight_where);;
        }
        elseif($rankType==4)//净收入预估
        {
            $wmOrderFreightDao = WmOrderFreightDao::i($this->shardNode);

            //付款金额
            $c_pay_order_money_sql = "SELECT shop_id,SUM(pay_money) AS pay_money FROM {$this->tableName} WHERE {$cWhere['where']}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $c_pay_order_money_data = $this->db->query($c_pay_order_money_sql)->result_array();
            //支出（配送费）
            $c_shipping_order_money_data = $wmOrderFreightDao->getFreight($cWhere['freight_where'] ." AND b.status in({$pay_order_status})");

            $c_data = ['pay_order_money'=>$c_pay_order_money_data, 'shipping_order_money'=>$c_shipping_order_money_data];
            //昨日付款金额
            $y_pay_order_money_sql = "SELECT shop_id,SUM(pay_money) AS pay_money FROM {$this->tableName} WHERE {$yWhere['where']}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $y_pay_order_money_data = $this->db->query($y_pay_order_money_sql)->result_array();
            //昨日支出（配送费）
            $y_shipping_order_money_data = $wmOrderFreightDao->getFreight($yWhere['freight_where'] ." AND b.status in({$pay_order_status})");

            $y_data = ['pay_order_money'=>$y_pay_order_money_data, 'shipping_order_money'=>$y_shipping_order_money_data];

        }
        elseif($rankType==5)//客单价
        {

            $c_shop_pay_order_money_sql = "SELECT shop_id,SUM(pay_money) AS pay_money FROM {$this->tableName} WHERE {$cWhere['where']}  AND status in({$valid_order_status}) GROUP BY shop_id";
            $c_pay_order_money_data = $this->db->query($c_shop_pay_order_money_sql)->result_array();
            $c_shop_pay_order_count_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere['where']}  AND status in({$valid_order_status}) GROUP BY shop_id";
            $c_shipping_order_count_data = $this->db->query($c_shop_pay_order_count_sql)->result_array();

            $c_data = ['pay_order_money'=>$c_pay_order_money_data, 'shop_pay_order_count'=>$c_shipping_order_count_data];

            $y_shop_pay_order_money_sql = "SELECT shop_id,SUM(pay_money) AS pay_money FROM {$this->tableName} WHERE {$yWhere['where']}  AND status in({$valid_order_status}) GROUP BY shop_id";
            $y_pay_order_money_data = $this->db->query($y_shop_pay_order_money_sql)->result_array();
            $y_shop_pay_order_count_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere['where']}  AND status in({$valid_order_status}) GROUP BY shop_id";
            $y_shipping_order_count_data = $this->db->query($y_shop_pay_order_count_sql)->result_array();

            $y_data = ['pay_order_money'=>$y_pay_order_money_data, 'shop_pay_order_count'=>$y_shipping_order_count_data];
        }
        elseif($rankType==6)//无效订单
        {
            $c_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere['where']}  AND status in(5010,5011,5020,5012,5040,5060) GROUP BY shop_id";
            $c_data = $this->db->query($c_sql)->result_array();
            $y_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere['where']}  AND status in(5010,5011,5020,5012,5040,5060) GROUP BY shop_id";
            $y_data = $this->db->query($y_sql)->result_array();
        }
        elseif($rankType==7)//售后
        {
            $c_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere['where']}  AND status=6061 GROUP BY shop_id";
            $c_data = $this->db->query($c_sql)->result_array();
            $y_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere['where']}  AND  status=6061 GROUP BY shop_id";
            $y_data = $this->db->query($y_sql)->result_array();
        }
        else
        {
            //
            $c_data = [];
            $y_data = [];
        }

        return ['c_data'=>$c_data, 'y_data'=>$y_data];
    }


    public function getFlowStatisticData($rankType=0, $cWhere='',$yWhere='')
    {
    	$wmShopAccessDao = WmShopAccessDao::i($this->shardNode);
        //包含已付款的订单状态
        $pay_order_status = "2020,2035,2040,2050,2060,4020,4035,4040,4050,4060,5010,5011,5012,5020,5035,5040,5060,6060,6061";
        if($rankType == 1) //访客数
        {
            $c_data = $wmShopAccessDao->getAllArray($cWhere, 'uv,shop_id');
            $y_data = $wmShopAccessDao->getAllArray($yWhere, 'uv,shop_id');
        }
        elseif($rankType==2)//下单人数
        {
            $c_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere}  GROUP BY shop_id";
            $c_data = $this->db->query($c_sql)->result_array();
            $y_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere}  GROUP BY shop_id";
            $y_data = $this->db->query($y_sql)->result_array();

        }
        elseif($rankType==3)//付款人数
        {
            $c_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $c_data = $this->db->query($c_sql)->result_array();
            $y_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $y_data = $this->db->query($y_sql)->result_array();
        }
        elseif($rankType==4)//客单价
        {
            $c_shop_pay_order_money_sql = "SELECT shop_id,SUM(pay_money) AS pay_money FROM {$this->tableName} WHERE {$cWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $c_shop_pay_order_money_data = $this->db->query($c_shop_pay_order_money_sql)->result_array();
            $c_shop_pay_order_count_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $c_shop_pay_order_count_data = $this->db->query($c_shop_pay_order_count_sql)->result_array();
            $c_data = ['pay_order_money'=>$c_shop_pay_order_money_data,'pay_order_count'=>$c_shop_pay_order_count_data];

            $y_shop_pay_order_money_sql = "SELECT shop_id,SUM(pay_money) AS pay_money FROM {$this->tableName} WHERE {$yWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $y_shop_pay_order_money_data = $this->db->query($y_shop_pay_order_money_sql)->result_array();
            $y_shop_pay_order_count_sql = "SELECT count(1) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $y_shop_pay_order_count_data = $this->db->query($y_shop_pay_order_count_sql)->result_array();
            $y_data = ['pay_order_money'=>$y_shop_pay_order_money_data,'pay_order_count'=>$y_shop_pay_order_count_data];
        }
        elseif($rankType==5)//下单转化率
        {
            $c_shop_uv_data = $wmShopAccessDao->getAllArray($cWhere, 'uv,shop_id');
            $c_order_user_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere}  GROUP BY shop_id";
            $c_order_user_data = $this->db->query($c_order_user_sql)->result_array();
            $c_data = ['uv'=>$c_shop_uv_data,'order_user'=>$c_order_user_data];

            $y_shop_uv_data = $wmShopAccessDao->getAllArray($yWhere, 'uv,shop_id');
            $y_order_user_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere}  GROUP BY shop_id";
            $y_order_user_data = $this->db->query($y_order_user_sql)->result_array();
            $y_data = ['uv'=>$y_shop_uv_data,'order_user'=>$y_order_user_data];
        }
        elseif($rankType==6)//付款转化率
        {

            $c_shop_order_user_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere}  GROUP BY shop_id";
            $c_shop_order_user_data = $this->db->query($c_shop_order_user_sql)->result_array();
            $c_shop_pay_order_user_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $c_shop_pay_order_user_data = $this->db->query($c_shop_pay_order_user_sql)->result_array();
            $c_data = ['pay_order_user'=>$c_shop_pay_order_user_data,'order_user'=>$c_shop_order_user_data];

            $y_shop_order_user_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere}  GROUP BY shop_id";
            $y_shop_order_user_data = $this->db->query($y_shop_order_user_sql)->result_array();
            $y_shop_pay_order_user_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $y_shop_pay_order_user_data = $this->db->query($y_shop_pay_order_user_sql)->result_array();
            $y_data = ['pay_order_user'=>$y_shop_pay_order_user_data,'order_user'=>$y_shop_order_user_data];

        }
        elseif($rankType==7)//全店转化率
        {
            $c_shop_uv_data = $wmShopAccessDao->getAllArray($cWhere, 'uv,shop_id');
            $c_shop_pay_order_user_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$cWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $c_shop_pay_order_user_data = $this->db->query($c_shop_pay_order_user_sql)->result_array();
            $c_data = ['uv'=>$c_shop_uv_data,'pay_order_user'=>$c_shop_pay_order_user_data];

            $y_shop_uv_data = $wmShopAccessDao->getAllArray($yWhere, 'uv,shop_id');
            $y_shop_pay_order_user_sql = "SELECT count(DISTINCT uid) AS num,shop_id FROM {$this->tableName} WHERE {$yWhere}  AND status in({$pay_order_status}) GROUP BY shop_id";
            $y_shop_pay_order_user_data = $this->db->query($y_shop_pay_order_user_sql)->result_array();
            $y_data = ['uv'=>$y_shop_uv_data,'pay_order_user'=>$y_shop_pay_order_user_data];
        }
        else
        {
            //
            $c_data = [];
            $y_data = [];
        }

        return ['c_data'=>$c_data, 'y_data'=>$y_data];
    }

    /**
     * 得到时间段的统计 
     * @param tArr ['t1'=>['stime'=>11,'etime'=>111],...]
     * @return [type] [description]
     * 
     */
    public function statistics($where,$tArr=[])
    {

        $sql = 'SELECT 
                IFNULL(SUM(CASE WHEN `pay_time`>0 THEN 1 ELSE 0 END),0) AS `total` 
                ,IFNULL(SUM(CASE WHEN `status`=2020 THEN 1 ELSE 0 END),0) AS `wait_receiver` 
                ,IFNULL(SUM(CASE WHEN `STATUS`>=2035 AND `STATUS`<=2060 THEN 1 ELSE 0 END),0) AS `receiver` 
                ,IFNULL(SUM(CASE WHEN `STATUS`=5011 THEN 1 ELSE 0 END),0) AS `refuse` 
                ,IFNULL(SUM(CASE WHEN `api_status`=9 THEN 1 ELSE 0 END),0) AS `success` ';
        foreach ($tArr as $k => $v) {
            $sql .= " ,IFNULL(SUM(CASE WHEN `pay_time`>={$v['stime']} AND `pay_time`<{$v['etime']} THEN 1 ELSE 0 END),0) AS `{$k}` ";
        }
        $sql .= " FROM {$this->tableName} ";
        if($where)
            $sql .= ' WHERE '.$where;
        return $this->db->query($sql)->result();
    }

    /**
     * 得到时间段的统计 
     * @param $where 查询条件 必须aid $group 分组条件
     * @return [type] [description]
     *  total_count 总订单数 total_discount_money 总优惠金额 pay_order_money 付款金额 pay_order_count 付款订单个数
     *  valid_order_money 有效订单金额 valid_order_count 有效订单个数 done_order_money 完成订单金额 done_order_count 完成订单个数
     *  refuse_order_money 拒绝订单金额 refuse_order_count 拒绝订单个数 no_shipping_order_money 配送异常订单金额 no_shipping_order_count 配送异常订单个数
     *  cancel_order_money 取消订单金额 cancel_order_count 取消订单个数 tk_order_money 退款订单金额 tk_order_count 退款订单个数
     *  afsno_order_count 售后订单数量 afsno_order_money 售后订单金额
     */
    public function orderStatistics($where, $group_by='')
    {

        $sql = "SELECT 
                count(1) AS total_count,FROM_UNIXTIME(`time`,'%Y-%m-%d') AS date_time,shop_id
                ,IFNULL(SUM(CASE WHEN `pay_time`>0 THEN `discount_money` ELSE 0 END),0) AS `total_discount_money`
                ,IFNULL(SUM(CASE WHEN `pay_time`>0 THEN `pay_money` ELSE 0 END),0) AS `pay_order_money`
                ,IFNULL(SUM(CASE WHEN `pay_time`>0 THEN 1 ELSE 0 END),0) AS `pay_order_count`
                ,IFNULL(SUM(CASE WHEN `pay_time`>0 AND `pay_type`=1 THEN `pay_money` ELSE 0 END),0) AS `member_order_money`
                ,IFNULL(SUM(CASE WHEN `status`=2020  THEN 1 ELSE 0 END),0) AS `new_order_count`
                ,IFNULL(SUM(CASE WHEN `status`=2035  THEN 1 ELSE 0 END),0) AS `selfpick_order_count`
                ,IFNULL(SUM(CASE WHEN (`status`=2030 OR (`status`>=2040 AND `status`<=2060))  THEN 1 ELSE 0 END),0) AS `delivery_order_count`
                ,IFNULL(SUM(CASE WHEN `status`>=2035 AND `status`<=4060 THEN `pay_money` ELSE 0 END),0) AS `valid_order_money`
                ,IFNULL(SUM(CASE WHEN `status`>=2035 AND `status`<=4060 THEN 1 ELSE 0 END),0) AS `valid_order_count` 
                ,IFNULL(SUM(CASE WHEN `status`>=6060 AND `status`<=6061 THEN `pay_money` ELSE 0 END),0) AS `done_order_money` 
                ,IFNULL(SUM(CASE WHEN `status`>=6060 AND `status`<=6061 THEN 1 ELSE 0 END),0) AS `done_order_count`
                ,IFNULL(SUM(CASE WHEN `status`>=5000 AND `status`<=5060 THEN `pay_money` ELSE 0 END),0) AS `invalid_order_money`
                ,IFNULL(SUM(CASE WHEN `status`>=5000 AND `status`<=5060 THEN 1 ELSE 0 END),0) AS `invalid_order_count` 

                ,IFNULL(SUM(CASE WHEN (`status`>=4020 AND `status`<=4060) THEN 1 ELSE 0 END),0) AS `abnormal_order_count`
                ,IFNULL(SUM(CASE WHEN (`status`=5010 OR `status`=5011 OR `status`=5020) THEN `pay_money` ELSE 0 END),0) AS `refuse_order_money`
                ,IFNULL(SUM(CASE WHEN (`status`=5010 OR `status`=5011 OR `status`=5020) THEN 1 ELSE 0 END),0) AS `refuse_order_count` 
                ,IFNULL(SUM(CASE WHEN (`status`=5012 OR `status`=5040) THEN `pay_money` ELSE 0 END),0) AS `no_shipping_order_money` 
                ,IFNULL(SUM(CASE WHEN (`status`=5012 OR `status`=5040) THEN 1 ELSE 0 END),0) AS `no_shipping_order_count` 
                ,IFNULL(SUM(CASE WHEN (`status`=5000 OR `status`=5001) THEN `pay_money` ELSE 0 END),0) AS `cancel_order_money` 
                ,IFNULL(SUM(CASE WHEN (`status`=5000 OR `status`=5001) THEN 1 ELSE 0 END),0) AS `cancel_order_count` 
                ,IFNULL(SUM(CASE WHEN `status`=5060  THEN `pay_money` ELSE 0 END),0) AS `tk_order_money` 
                ,IFNULL(SUM(CASE WHEN `status`=5060  THEN 1 ELSE 0 END),0) AS `tk_order_count`
                ,IFNULL(SUM(CASE WHEN `afsno`>0  AND `is_afs_finished`=1 THEN `tk_money` ELSE 0 END),0) AS `afsno_order_money`
                ,IFNULL(SUM(CASE WHEN `afsno`>0  AND `is_afs_finished`=1 THEN 1 ELSE 0 END),0) AS `afsno_order_count`";
    
        $sql .= " FROM {$this->tableName} ";
        if($where)
            $sql .= ' WHERE '.$where;
        if($group_by)
             $sql .= ' GROUP BY  '.$group_by;
        return $this->db->query($sql)->result_array();
    }
}
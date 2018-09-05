<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 09:50:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:55:58
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class MealOrderDao extends BaseDao
{
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
                ,IFNULL(SUM(CASE WHEN `pay_time`>0 THEN `pay_money` ELSE 0 END),0) AS `pay_order_money`
                ,IFNULL(SUM(CASE WHEN `pay_time`>0 THEN 1 ELSE 0 END),0) AS `pay_order_count`
                ,IFNULL(SUM(CASE WHEN `status`=1010  THEN 1 ELSE 0 END),0) AS `audit_order_count`
                ,IFNULL(SUM(CASE WHEN `status`=2020  THEN 1 ELSE 0 END),0) AS `cooked_order_money`
                ,IFNULL(SUM(CASE WHEN `status`=6060 THEN 1 ELSE 0 END),0) AS `done_order_count`
                ";

        $sql .= " FROM {$this->tableName} ";
        if ($where)
            $sql .= ' WHERE ' . $where;
        if ($group_by)
            $sql .= ' GROUP BY  ' . $group_by;
        return $this->db->query($sql)->result_array();
    }
}
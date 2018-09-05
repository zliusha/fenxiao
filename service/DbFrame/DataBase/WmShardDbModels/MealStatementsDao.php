<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 09:56:50
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:55:59
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class MealStatementsDao extends BaseDao
{
    /**
     * 统计流水信息
     * @param $where 查询条件 $group 分组条件
     * @return mixed
     * total_count 收款流水总个数 pay_order_money 收款流水实收总金额 total_money 收款流水总金额 total_discount_money 收款流水优惠信息
     * tk_order_count 退款总个数 tk_order_money 退款总金额
     */
	public function statistics($where, $group_by='')
    {
        $sql = 'SELECT
                FROM_UNIXTIME(`time`,\'%Y-%m-%d\') AS date_time,shop_id
                ,IFNULL(SUM(CASE WHEN `type`=1 THEN 1 ELSE 0 END),0) AS `total_count`
                ,IFNULL(SUM(CASE WHEN `type`=1 THEN `amount` ELSE 0 END),0) AS `pay_order_money`
                ,IFNULL(SUM(CASE WHEN `type`=1 AND (`gateway`=4 OR `gateway`=7) THEN `amount` ELSE 0 END),0) AS `member_order_money`
                ,IFNULL(SUM(CASE WHEN `type`=1 THEN `total_money` ELSE 0 END),0) AS `total_money`
                ,IFNULL(SUM(CASE WHEN `type`=1 THEN `discount_money` ELSE 0 END),0) AS `total_discount_money`
                ,IFNULL(SUM(CASE WHEN `type`=2 THEN 1 ELSE 0 END),0) AS `tk_order_count`
                ,IFNULL(SUM(CASE WHEN `type`=2 THEN `amount` ELSE 0 END),0) AS `tk_order_money`';

        $sql .= " FROM {$this->tableName} ";
        if($where)
            $sql .= ' WHERE '.$where;
        if($group_by)
            $sql .= ' GROUP BY  '.$group_by;
        return $this->db->query($sql)->result_array();
    }
}
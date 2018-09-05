<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/5
 * Time: 19:30
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopAccessDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;

class wm_statistic_bll extends base_bll
{

    public function moneyQuery($where,$tArr=[])
    {
        $sql = 'SELECT
                IFNULL(SUM(CASE WHEN `pay_time`>0 THEN `` ELSE 0 END),0) AS `total`
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
}
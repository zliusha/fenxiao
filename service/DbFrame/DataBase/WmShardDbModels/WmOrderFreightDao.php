<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:18:55
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:03
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmOrderFreightDao extends BaseDao
{
	/**
	 * 记录运费详情
	 * @param  [type]  $order          [description]
	 * @param  integer $money          [description]
	 * @param  integer $logisticsType [description]
	 * @return [type]                  [description]
	 */
    public function add($order = null, $money = 0, $logisticsType = 0)
    {
        if ($this->getCount(['tid' => $order->tid]) > 0) {
            return false;
        }

        return $this->create([
            'tid' => $order->tid,
            'aid' => $order->aid,
            'shop_id' => $order->shop_id,
            'logistics_type' => $logisticsType,
            'money' => $money,
            'final_money' => 0,
            'is_finish' => 0,
        ]);
    }

    /**
     * 记录最终运费
     * @param  string  $tid   [description]
     * @param  integer $money [description]
     * @return [type]         [description]
     */
    public function finish($tid = '', $money = 0) 
    {
        return $this->update(['final_money' => $money, 'is_finish' => 1], ['tid' => $tid, 'is_finish' => 0]);
    }

    /**
     * 获取运费支出
     * @param bool $where
     * @param int $shop_id
     * @return mixed
     */
    public function getFreight($where=false, $type=false, $group_by='a.shop_id')
    {

        $_where = "a.is_finish=1";

        if($where)
        {
            $_where .= " AND ".$where;
        }

        $sql = "SELECT a.shop_id,SUM(a.final_money) AS freight_money FROM {$this->tableName} a LEFT JOIN {$this->db->tables['wm_order']} b on a.tid=b.tid WHERE {$_where} ";
        if($group_by)
            $sql .=  " GROUP BY {$group_by}";

        $data = $this->db->query($sql)->result_array();
        if($type)
        {
            return isset($data[0]['freight_money']) ? $data[0]['freight_money'] : 0;
        }

        return $data;
    }
}
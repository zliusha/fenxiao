<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:43:47
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:06
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmWithdrawDao extends BaseDao
{
	/**
     * 得到提现金额
     * @param  boolean $where [description]
     * @return [type]         [description]
     */
    public function getWithdrawMoeny($where=false, $input=[])
    {
        $this->db->select('IFNULL(SUM(money),0.00) AS money');
        if ($where) {
            $this->db->where($where);
        }
        if(isset($input['where_in']))
        {
            foreach ($input['where_in'] as $where_in_key => $where_in_value) {
                $this->db->where_in($where_in_key, $where_in_value);
            }
        }
        $row = $this->db->from($this->tableName)->get()->row();
        return $row->money;
    }
}
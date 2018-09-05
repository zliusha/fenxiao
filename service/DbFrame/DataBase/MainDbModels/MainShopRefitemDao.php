<?php

/**
 * @Author: binghe
 * @Date:   2018-07-18 15:16:43
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-25 14:14:47
 */
namespace Service\DbFrame\DataBase\MainDbModels;
/**
 * saas门店关联
 */
class MainShopRefitemDao extends BaseDao
{
	
	/**
	 * 得到门店关联saas
	 * @param  int     $aid         [description]
	 * @param  array   $shopIds     [description]
	 * @param  boolean $returnArray [description]
	 * @return [type]               [description]
	 */
	public function getListWithName(int $aid,array $shopIds,$returnArray = true)
	{
		if(!$shopIds)
			return false;
		$whereInStr = \sql_where_in($shopIds);
		$sql = "SELECT a.id,a.saas_id,a.shop_id,a.ext_shop_id,b.saas_name FROM {$this->tableName} a LEFT JOIN {$this->db->tables['main_saas']} b ON a.saas_id = b.saas_id WHERE a.aid={$aid} AND a.shop_id IN ($whereInStr)";
		$result = $this->db->query($sql);
		if ($returnArray) {
            return $result->result_array();
        } else {
            return $result->result();
        }
	}
}
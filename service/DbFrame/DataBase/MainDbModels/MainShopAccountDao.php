<?php

/**
 * @Author: binghe
 * @Date:   2018-07-12 11:12:26
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-25 20:06:41
 */
namespace Service\DbFrame\DataBase\MainDbModels;
/**
 * 公司
 */
class MainShopAccountDao extends BaseDao
{
	
	/**
	 * 得到门店关联账号，
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
		$sql = "SELECT a.id,a.account_id,a.main_shop_id,b.username FROM {$this->tableName} a LEFT JOIN {$this->db->tables['main_company_account']} b ON a.account_id = b.id WHERE a.aid={$aid} AND a.main_shop_id IN ($whereInStr)";
		$result = $this->db->query($sql);
		if ($returnArray) {
            return $result->result_array();
        } else {
            return $result->result();
        }
	}
}
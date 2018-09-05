<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 09:48:32
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:55:58
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class MealCartDao extends BaseDao
{
	// 清空购物车列表
	public function resetCart($tableId)
	{
		return $this->delete(['table_id' => $tableId]);
	}
}
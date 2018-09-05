<?php

/**
 * @Author: yize
 * @Date:   2018-07-27 11:31:06
 */
namespace Service\DbFrame\DataBase\HcityMainDbModels;
class HcityManagerBalanceRecordDao extends BaseDao
{
	public $managerBalanceStatus = [
					'1' => '提现',
					'3' => '商品交易收入',
					'4' => '邀请办卡收入',
					'5' => '邀请商家收入',
				];


}
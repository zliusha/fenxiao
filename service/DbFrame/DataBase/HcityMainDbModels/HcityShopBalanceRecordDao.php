<?php

/**
 * @Author: feiying
 * @Date:   2018-07-25 17:31:06
 * @Last Modified by:  feiying
 */
namespace Service\DbFrame\DataBase\HcityMainDbModels;
class HcityShopBalanceRecordDao extends BaseDao
{
	public $shopBalanceStatus = [
					'1' => '提现',
					'2' => '商品销售收入',
					'3' => '商品分享收入',
                    '4' => '邀请办卡收入',
                    '5' => '邀请商家收入',
				];
}
<?php

/**
 * @Author: binghe
 * @Date:   2018-07-20 16:21:03
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-20 16:35:30
 */
namespace Service\Bll\Wm;
use Service\DbFrame\DataBase\WmMainDbModels\WmServiceAccountDao;
/**
 * 云店账号服务
 */
class WmServiceAccountBll extends \Service\Bll\BaseBll
{
	/**
	 * 修改门店数量
	 * @param  int    $aid       [description]
	 * @param  int    $shopLimit [description]
	 * @return [type]            [description]
	 */
	public function updateShopLimit(int $aid,int $shopLimit)
	{	
		$wmServiceAccountDao = WmServiceAccountDao::i();
		$mWmServiceAccount = $wmServiceAccountDao->getOne(['aid'=>$aid]);
		if($mWmServiceAccount)
		{
			return $wmServiceAccountDao->update(['shop_limit'=>$shopLimit],['id'=>$mWmServiceAccount->id]);
		}
		else
		{
			$data['aid'] = $aid;
			$data['shop_limit'] = $shopLimit;
			$id = $wmServiceAccountDao->create($data);
			return $id>0;
		}
	}
	
}
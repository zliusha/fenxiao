<?php

/**
 * @Author: binghe
 * @Date:   2018-08-03 11:00:20
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 11:16:25
 */
namespace Service\Bll\Main;
use Service\Cache\Main\UidMainShopIdsCache;
/**
 * 公司账号
 */
class MainShopAccountBll extends \Service\Bll\BaseBll
{
	/**
	 * 获取账号关联的main_shop_ids　总账号返回空数组
	 * @param  array  $params 必填:aid,uid,is_admin
	 * @return [type]         [description]
	 */
	public function getShopIds(array $params)
	{
		if($params['is_admin'])
			return [];
		$uidMainShopIdsCache = new UidMainShopIdsCache(['aid'=>$params['aid'],'uid'=>$params['uid']]);
		$data = $uidMainShopIdsCache->getDataASNX();
		return $data;
	}
}
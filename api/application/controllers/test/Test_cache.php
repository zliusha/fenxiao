<?php

/**
 * @Author: binghe
 * @Date:   2018-08-03 09:27:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 09:30:18
 */
use Service\Cache\Hcity\HcityManageAccountCache;
/**
 * 缓存测试
 */
class Test_cache extends base_controller
{
	
	public function index()
	{
		$hcityManageAccountCache = new HcityManageAccountCache(['id'=>2]);
		$data = $hcityManageAccountCache->getDataASNX();
		var_dump($data);
	}
}
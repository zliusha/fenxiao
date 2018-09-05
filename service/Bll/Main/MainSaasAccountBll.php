<?php

/**
 * @Author: binghe
 * @Date:   2018-08-07 11:38:06
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-07 11:53:22
 */
namespace Service\Bll\Main;
use Service\Cache\Main\AidSaasAccountCache;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
/**
 * saas
 */
class MainSaasAccountBll extends \Service\Bll\BaseBll
{
	/**
	 * 注册saas服务
	 * @return [type] [description]
	 */
	public function register(array $data)
	{
		if($id = MainSaasAccountDao::i()->create($data))
		{
			(new AidSaasAccountCache(['aid'=>$data['aid']]))->delete();
			return $id;
		}
		else
			throw new Exception('注册服务失败');
	}
}
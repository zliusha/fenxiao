<?php

/**
 * @Author: binghe
 * @Date:   2018-07-24 10:21:36
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-07 12:07:11
 */
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\Enum\ServiceEnum;
use Service\Bll\Main\MainSaasAccountBll;
/**
 * saas跳转
 */
class Saas extends base_controller
{
	/**
	 * Saas cookie同步登录
	 * @return [type] [description]
	 */
	public function login()
	{
		//判断是否开通了服务，未开通则开通试用版
		$mainSaasAccountDao = MainSaasAccountDao::i();
		$mMianSaasAccount = $mainSaasAccountDao->getOne(['aid'=>$this->s_user->aid,'saas_id'=>self::SAAS_ID]);
		//未开通服务器，则开通试用
		if(!$mMianSaasAccount)
		{
			$data['aid'] = $this->s_user->aid;
			$data['saas_id'] = self::SAAS_ID;
			$data['expire_time'] = date('Y-m-d',strtotime("+3 day"));//试用期三天
			$data['saas_item_id'] = ServiceEnum::TRIAL;
			$data['saas_item_name'] = '试用版';
			$time = time();
			$data['time'] = $time;
			$data['update_time'] = $time;
			if(!(new MainSaasAccountBll())->register($data))
			{
				$this->_error(500,'自动开通试用版失败');
			}
		}
		//登录默认门店
		redirect(DJADMIN_URL.'shop/toggle');
	}
}
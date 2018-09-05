<?php

/**
 * @Author: binghe
 * @Date:   2018-06-13 14:12:06
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-20 14:58:50
 */
use Service\Cache\Wm\WmServiceCache;
/**
 * 公司
 */
class Company extends sy_controller
{
	/**
	 * 公司
	 * @return [type] [description]
	 */
	public function info()
	{
		$wmServiceCache = new WmServiceCache(['aid'=>$this->s_user->aid]);
		$service = $wmServiceCache->getDataASNX();
		if(!$service)
			$this->json_do->set_error('error','未开通服务');
		$wmOpen = $mealOpen = $lsOpen = false;
		power_exists(module_enum::WM_MODULE,$service->power_keys) && $wmOpen = true;
		power_exists(module_enum::MEAL_MODULE,$service->power_keys) && $mealOpen = true;
		power_exists(module_enum::LS_MODULE,$service->power_keys) && $lsOpen = true;

		$power['wm_open'] = $wmOpen;
		$power['meal_open'] = $mealOpen;
		$power['ls_open'] = $lsOpen;
		$data['power'] = $power;
		$this->json_do->set_data($data);
		$this->json_do->out_put();
	}
}
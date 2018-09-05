<?php

/**
 * @Author: binghe
 * @Date:   2018-07-18 10:36:47
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 15:15:34
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\Bll\Main\MainCompanyAccountBll;
use Service\DbFrame\DataBase\MainDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\DbFrame\DataBase\MainDbModels\MainSaasDao;
use Service\Cache\Main\AidSaasAccountCache;
use Service\Cache\Main\UidSaasAccountCache;
/**
 * 公司相关
 */
class Company extends dj_controller
{
	
	/**
	 * saas列表
	 * @return [type] [description]
	 */
	public function saas_all()
	{
		if($this->s_user->is_admin)
		{
			$aidSaasAccountCache = new AidSaasAccountCache(['aid'=>$this->s_user->aid]);
			$saasList = $aidSaasAccountCache->getDataASNX();
		}
		else
		{
			$uidSaasAccountCache = new UidSaasAccountCache(['aid'=>$this->s_user->aid,'uid'=>$this->s_user->id]);
			$saasList = $uidSaasAccountCache->getDataASNX();
		}
		$mainSaasDao = MainSaasDao::i();
		$list = $mainSaasDao->getAllArray(['status'=>0],'saas_id,saas_name,is_free,is_has_trial');
		$lastList = [];
		foreach ($list as $k => $row) {
			$accountInfo = array_find($saasList,'saas_id',$row['saas_id']);
			$list[$k]['company_saas_info'] = null;
			if($accountInfo)
			{
				$companySaasInfo = [];
				$companySaasInfo['id'] = $accountInfo['id'];
				$companySaasInfo['expire_time'] = $accountInfo['expire_time'];
				$companySaasInfo['saas_item_id'] = $accountInfo['saas_item_id'];
				$companySaasInfo['saas_item_name'] = $accountInfo['saas_item_name'];
				$row['company_saas_info'] = $accountInfo;
			}
			if($this->s_user->is_admin || $accountInfo)
				array_push($lastList,$row);
		}
		
		$data['list'] = $lastList;
		$this->json_do->set_data($data);
		$this->json_do->out_put();
	}
	/**
	 * 组件列表
	 * @return [type] [description]
	 */
	public function component_all()
	{
		$list = [];
		//前期组件先硬编码
		if($this->s_user->is_admin)
		{
			$list =[
				['component_id'=>1,'component_name'=>'门店分配'],
				['component_id'=>2,'component_name'=>'员工管理']
			];
		}
		$data['list'] = $list;
		$this->json_do->set_data($data);
		$this->json_do->out_put();
	}
	
}
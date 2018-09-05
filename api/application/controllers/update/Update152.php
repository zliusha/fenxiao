<?php

/**
 * @Author: binghe
 * @Date:   2018-07-18 14:33:59
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 16:25:43
 */
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\Enum\SaasEnum;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyServiceDao;
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmServiceAccountDao;
/**
 * 更新 前请确保以下执行
 * 1同步结构　ydb_main,ydb_wm_main,ydb_wm_shard
 * 2同步数据　
 * 		2.1 ydb_main wd_main_saas,wd_main_saas_item
 * 		2.2 ydb_wm_main wd_wm_service
 */
class Update152 extends base_controller
{
	public $limit = 2000;
	//长级
	public function index()
	{
		//门店升级sql 第一步
		$this->upgradeShop();
		//升级门店账号权限 依赖 第一步
		$this->upgradeShopAccount();
		//升级账号版本
		$this->upgradeSevice();
	}
	//门店升级sql 第一步
	public function upgradeShop()
	{	
		//此处分库只有一个库,多分库不可以直接使用
		$wmShopDao = WmShopDao::i();
		$shopList = $wmShopDao->getAllArray(['main_shop_id'=>0,'is_delete'=>0],'*','id asc', $this->limit);
		if(empty($shopList))
		{
			echo '没有可升级的门店';exit;
		}
		$mainShopDao = MainShopDao::i(); 
		$mainShopRefitemDao = MainShopRefitemDao::i();
		foreach ($shopList as $row) {
			//创建主门店　
			$data=[];
			$data['aid'] = $row['aid'];
			$data['shop_name'] = $row['shop_name'];
	        $data['shop_logo'] = $row['shop_logo'];
	        $data['contact'] = $row['contact'];
	        $data['shop_state'] = $row['shop_state'];
	        $data['shop_city'] = $row['shop_city'];
	        $data['shop_district'] = $row['shop_district'];
	        $data['region'] = $row['region'];
	        $data['shop_address'] = $row['shop_address'];
	        $data['longitude'] = $row['longitude'];
	        $data['latitude'] = $row['latitude'];
	        $data['time'] = $row['time'];
	        $mainShopId = $mainShopDao->create($data);
	        //创建关联
	        $uData=[];
	        $uData['saas_id'] = SaasEnum::YD;
	        $uData['shop_id'] = $mainShopId;
	        $uData['ext_shop_id'] = $row['id'];
	        $uData['aid'] = $row['aid'];
	        $uData['time'] = $row['time'];
		    $mainShopRefitemDao->create($uData);
		    //更新现有
		    $wmShopDao->update(['main_shop_id'=>$mainShopId],['id'=>$row['id']]);
		}
		$count = count($shopList);
		if($count == $this->limit)
		{
			echo '升级了 '.$count.' 个门店，未完成,请继续';
		}
		else
			echo '升级了 '.$count.' 个门店，已完成';
	}
	//升级门店账号权限 依赖 第一步
	public function upgradeShopAccount()
	{
		$mainCompanyAccountDao = MainCompanyAccountDao::i();
		$mainShopAccountDao = MainShopAccountDao::i();
		$wmServiceAccountDao = WmServiceAccountDao::i();
		$accountList = $mainCompanyAccountDao->getAllArray(['is_admin'=>2,'shop_id >'=>0],'*','id asc', $this->limit);
		$failCount = 0;
		foreach ($accountList as $row) {
			$wmShopDao = WmShopDao::i($row['aid']);
			$mWmShop = $wmShopDao->getOne(['id'=>$row['shop_id'],'aid'=>$row['aid']]);
			if(!$mWmShop)
			{
				$failCount ++;
				echo 'shop_id-'.$row['shop_id'].'-不存在';
				echo '<br/>';
				continue;
			}
			$data=[];
			$data['aid']=$row['aid'];
			$data['account_id']=$row['id'];
			$data['main_shop_id']=$mWmShop->main_shop_id;
			$data['time'] = $row['time'];
			$mainShopAccountDao->create($data);

		}
		$count = count($accountList);
		echo '升级失败'.$failCount.'个账号';
		echo '<br/>';
		if($count == $this->limit)
		{
			echo '升级了 '.$count.' 个账号，未完成,请继续';
		}
		else
			echo '升级了 '.$count.' 个账号，已完成';
	}
	//升级账号版本
	public function upgradeSevice()
	{
		$time = time();
		$mainSaasAccountDao = MainSaasAccountDao::i();
		$mainCompanyServiceDao = MainCompanyServiceDao::i();
		$wmServiceAccountDao = WmServiceAccountDao::i();
		$serviceList = $mainCompanyServiceDao->getAllArray(['time <='=>$time],'*','id asc', $this->limit);
		foreach ($serviceList as $row) {
			$data=[];
			$data['aid'] = $row['aid'];
			$data['saas_id'] = SaasEnum::YD;
			$data['expire_time'] = $row['service_day_limit'];
			$data['saas_item_id'] = $row['service_id'];
			$data['saas_item_name'] = $row['service_type_name'];
			$data['time'] = $row['time'];
			$data['update_time'] = empty($row['update_time'])?$row['time']:$row['update_time'];
			$mainSaasAccountDao->create($data);
			$data1=[];
			$data1['aid'] = $row['aid'];
			$data1['shop_limit'] = $row['shop_limit'];
			$wmServiceAccountDao->create($data1);
		}
		$count = count($serviceList);
		if($count == $this->limit)
		{
			echo '升级了 '.$count.' 个账号版本，未完成,请继续';
		}
		else
			echo '升级了 '.$count.' 个账号版本，已完成';
	}
}
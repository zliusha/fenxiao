<?php

/**
 * @Author: binghe
 * @Date:   2018-07-25 10:29:51
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 13:45:33
 */
namespace Service\Bll\Main;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\DbFrame\DataBase\MainDb;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\Cache\Main\UidMainShopIdsCache;
use Service\Cache\Main\UidSaasAccountCache;
/**
 * shop bll
 */
class MainShopBll extends \Service\Bll\BaseBll
{
	/**
	 * 获取所有门店
	 * @param  int      $aid          
	 * @param  int|null $filterSaasId 过滤已开通的saas
	 * @return array                 
	 */
	public function all(int $aid,int $filterSaasId = null, bool $samplefields = true)
	{
		$fields = '*';
		if($samplefields)
			$fields='id,shop_name';
		$list = MainShopDao::i()->getAllArray(['aid'=>$aid],$fields);
		$lastList =[];
		if($list && $filterSaasId)
		{	
			//此saas已存在的门店
			$existShops = MainShopRefitemDao::i()->getAllArray(['aid'=>$aid,'saas_id'=>$filterSaasId]);
			foreach ($list as $key => $item) {
					$one = array_find($existShops,'shop_id',$item['id']);
					if($one)
						continue;
					else
					{
						//如果设置图片，则转换图片
						if(isset($item['shop_logo']))
							$item['shop_logo'] = \conver_picurl($item['shop_logo']);
						array_push($lastList, $item);
					}
			}
		}
		return $lastList;

	}
	/**
	 * 店铺info
	 * @param  int    $aid    
	 * @param  int    $shopId 店铺id
	 * @return object         
	 */
	public function info(int $aid,int $shopId,$autoConver = false)
	{
		$mainShopDao = MainShopDao::i();
        $mMainShop = $mainShopDao->getOne(['id'=>$shopId,'aid'=>$aid]);
        if(!$mMainShop)
        	throw new Exception("门店不存在");
        if(!empty($mMainShop->shop_logo) && $autoConver)
        	$mMainShop->shop_logo = \conver_picurl($mMainShop->shop_logo);
        return $mMainShop;
    }
    /**
     * 添加店铺
     * @param int   $aid  [description]
     * @param array $data [description]
     * @return  int id
     */
	public function add(int $aid,array $data)
	{
		!isset($data['aid']) && $data['aid'] = $aid;
		$mainShopDao = MainShopDao::i();
		return $mainShopDao->create($data);
	}
	/**
	 * 修改门店信息
	 * @param  int    $aid    
	 * @param  int    $shopId 门店id
	 * @param  array  $data   
	 * @return int         修改的记录数据
	 */
	public function edit(int $aid, int $shopId,array $data)
	{
		$mainShopDao = MainShopDao::i();
		//联动会同步店铺信息
		return $mainShopDao->syncUpdateOne($data,['id'=>$shopId,'aid'=>$aid]);
	}
	/**
	 * 删除店铺
	 * @param  int    $aid    [description]
	 * @param  int    $shopId [description]
	 * @return int 删除的记录数
	 */
	public function delete(int $aid,int $shopId)
	{
		$mainShopRefitemDao = MainShopRefitemDao::i();
		$count = $mainShopRefitemDao->getCount(['shop_id'=>$shopId,'aid'=>$aid]);
		if($count)
			throw new Exception("删除失败，该门店已经开通了其它应用");
		$mainShopDao = MainShopDao::i();
		//删除门店
		$affectedRows = $mainShopDao->delete(['id'=>$shopId,'aid'=>$aid]);
		//删除该门店的员工记录
		MainShopAccountDao::i()->delete(['main_shop_id'=>$shopId,'aid'=>$aid]);
		return $affectedRows;
	}
	/**
	 * 获取门店列表,带员工，服务　属性
	 */
	public function pageList(int $aid)
	{
		$mainDb = MainDb::i();
        $page = new PageList($mainDb);
        $pConf = $page->getConfig();
        $pConf->table = "{$mainDb->tables['main_shop']}";
        $pConf->where = ' aid=' . $aid;
        $count = 0;
        $list = $page->getList($pConf,$count);
        if($list)
        {
        	$shopIds = array_column($list, 'id');
        	$mainShopAccountDao = MainShopAccountDao::i();
        	$accountList = $mainShopAccountDao->getListWithName($aid,$shopIds);
        	$mainShopRefitemDao = MainShopRefitemDao::i();
        	$itemList = $mainShopRefitemDao->getListWithName($aid,$shopIds);
        	foreach($list as $k => $v) {
        		$list[$k]['shop_logo'] = \conver_picurl($v['shop_logo']);
        		$list[$k]['account_items'] = array_find_list($accountList,'main_shop_id',$v['id']);
        		$list[$k]['saas_items'] = array_find_list($itemList,'shop_id',$v['id']);
        	}
        }
        $data['total'] = $count;
        $data['rows'] = $list;
        return $data;
	}
	/**
	 * 门店添加员工
	 * @param int $aid      
	 * @param int $shopId    
	 * @param int $accountId 
	 */
	public function addEmployee(int $aid,int $shopId,int $accountId)
	{
		$mMainShop = MainShopDao::i()->getOne(['aid'=>$aid,'id'=>$shopId],'id');
		if(!$mMainShop)
			throw new Exception('添加失败,门店不存在');
		$mMainCompanyAccount = MainCompanyAccountDao::i()->getOne(['aid'=>$aid,'id'=>$accountId],'id');
		if(!$mMainCompanyAccount)
			throw new Exception('添加失败,员工账号不存在');
		$mainShopAccountDao = MainShopAccountDao::i();
		$mMainShopAccount = $mainShopAccountDao->getOne(['main_shop_id'=>$shopId,'account_id'=>$accountId]);
		if($mMainShopAccount)
			throw new Exception('添加失败,当前门店已关联此账号');
		$data['aid'] = $aid;
		$data['account_id'] = $mMainCompanyAccount->id;
		$data['main_shop_id'] = $mMainShop->id;
		//清除缓存
		$params = [
			'aid'=>$aid,
			'uid'=>$mMainCompanyAccount->id
		];
		(new UidMainShopIdsCache($params))->delete();
		(new UidSaasAccountCache($params))->delete();
		return $mainShopAccountDao->create($data);
			
	}
	/**
	 * 删除门店员工账号
	 * @param  int    $aid          [description]
	 * @param  int    $shopAccounId [description]
	 * @return [type]               [description]
	 */
	public function deleteEmployee(int $aid,int $shopAccounId)
	{
		$mainShopAccountDao = MainShopAccountDao::i();
		$mMainShopAccount = $mainShopAccountDao->getOne(['aid'=>$aid,'id'=>$shopAccounId]);
		if(!$mMainShopAccount)
			throw new Exception('删除失败,记录不存在');
		$affectedRows = $mainShopAccountDao->delete(['aid'=>$aid,'id'=>$shopAccounId]);
		if($affectedRows)
		{
			//清除缓存
			$params = ['aid'=>$aid,'uid'=>$mMainShopAccount->account_id];
			(new UidMainShopIdsCache($params))->delete();
			(new UidSaasAccountCache($params))->delete();
			return $affectedRows;
		}
		else
			throw new Exception('删除失败,关联记录不存在');
			
	}
}
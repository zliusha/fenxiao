<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 16:34:41
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:51:29
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\MainDb;
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
/**
 * test db
 */
class Test_db extends base_controller
{
	/**
	 * 单个表
	 * @return [type] [description]
	 */
	public function index()
	{
		$mainCompanyDao =  MainCompanyDao::i(1226);
		$one = $mainCompanyDao->getOne(['id'=>1226]);
		echo $mainCompanyDao->tableName;
		echo '----------------------<br/>';
		var_dump($one);
		var_dump($mainCompanyDao->db->last_query());
		var_dump($mainCompanyDao->db->tables['main_company']);
	}
	/**
	 * 单个库
	 * @return [type] [description]
	 */
	public function db()
	{
		$mainDb = MainDb::i();
		var_dump($mainDb->tables['main_company']);
		$wmShardDb = WmShardDb::i(1226);
		var_dump($wmShardDb->tables['wm_shop']);
	}
	/**
	 * 单表分页
	 * @return [type] [description]
	 */
	public function page()
	{
		$mainDb = MainDb::i();
		$pageList = new PageList($mainDb);
		$pConf = $pageList->getConfig();
		$pConf->table = $mainDb->tables['main_company'];
        $pConf->order = 'id desc';
        $count = 0;
        $list = $pageList->getList($pConf,$count);
        $data['count'] = $count;
        $data['list'] = $list;
        var_dump($data);
	}
	/**
	 * 多表分页，只能同库表进行连表
	 * @return [type] [description]
	 */
	public function page2()
	{
		//需指定 参数(mixed)
		// string=>节点值 如 '0-0-0'
		// int=>aid
		// array=>['visit_id']
		// object=>ShardNode
		$wmShardDb = WmShardDb::i(1226);
		$pageList = new PageList($wmShardDb);
		$pConf = $pageList->getConfig();
		$pConf->table = " {$wmShardDb->tables['wm_shop']} a left join {$wmShardDb->tables['wm_goods']} b on a.id = b.shop_id";
		$pConf->where .= ' AND a.aid = 1226 ';
		$pConf->fields = 'a.id as shop_id,a.shop_name,b.id as goods_id,b.title';
        $pConf->order = 'b.id desc';
        $count = 0;
        $list = $pageList->getList($pConf,$count);
        $data['count'] = $count;
        $data['list'] = $list;
        var_dump($data);
	}
	public function shard()
	{
		$wmUserDao = WmUserDao::i(1226);
		$mWmuser = $wmUserDao->getOne(['id'=>1]);
		var_dump($mWmuser);
	}
}
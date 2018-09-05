<?php

/**
 * @Author: binghe
 * @Date:   2018-08-14 14:04:23
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-09-04 16:22:09
 */
use Service\DbFrame\DataBase\MainDb;
use Service\DbFrame\DataBase\WmMainDb;
use Service\DbFrame\DataBase\MainDbModels\MainSalelogDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmNoticeDao;
/**
 * 事务测试
 */
class Test_trans extends base_controller
{
	
	public function index()
	{
		$mainDb = MainDb::i();
		$wmMainDb = WmMainDb::i();

		$mainDb->trans_start();
		$wmMainDb->trans_start();
		$mainSalelogDao = MainSalelogDao::i();
		$wmNoticeDao = WmNoticeDao::i();
		$data = [
			'sale_name'=>'测试'.rand(1000,9999),
			'aid'=>1226,
			'money'=>1.00,
			'type'=>1,
			'des'=>'测试',
			'time' => time()
		];
		$mainSalelogDao->create($data);

	
		$data2 = [
			'sale_name'=>'测试'.rand(1000,9999),
			'aid'=>1226,
			'money'=>2.00,
			'type'=>1,
			'des'=>'测试',
			'time' => time()
		];
		$mainSalelogDao->create($data2);

		$data3 = [
			'title'=>'测试'.rand(1000,9999),
			'content'=>'内容',
			'time' => time()
		];
		$wmNoticeDao->create($data3);

		throw new Exception('error');
		

		$mainDb->trans_complete();
		$wmMainDb->trans_complete();
		echo 'success';
	}

	public function index2()
	{
		$mainDb = MainDb::i();


		$mainDb->trans_begin();

		$mainSalelogDao = MainSalelogDao::i();
		$data = [
			'sale_name'=>'测试'.rand(1000,9999),
			'aid'=>1226,
			'money'=>1.00,
			'type'=>1,
			'des'=>'测试',
			'time' => time()
		];
		$mainSalelogDao->create($data);

		throw new Exception('error');
	
		$data2 = [
			'sale_name'=>'测试'.rand(1000,9999),
			'aid'=>1226,
			'money'=>2.00,
			'type'=>1,
			'des'=>'测试',
			'time' => time()
		];
		$mainSalelogDao->create($data2);

		
		
		if ($mainDb->trans_status() === FALSE)
		{
			$mainDb->trans_rollback();
		}
		else
		{
			$mainDb->trans_commit();
		}
		echo 'success';
	}
	public function index3()
	{
		$mainDb = MainDb::i();


		$mainDb->trans_begin();

		$mainSalelogDao = MainSalelogDao::i();
		$data = [
			'sale_name'=>'测试'.rand(1000,9999),
			'aid'=>1226,
			'money'=>1.00,
			'type'=>1,
			'des'=>'测试',
			'time' => time()
		];
		$mainSalelogDao->create($data);

	
		$data2 = [
			'sale_name'=>'测试'.rand(1000,9999),
			'aidm'=>1226,
			'money'=>2.00,
			'type'=>1,
			'des'=>'测试',
			'time' => time()
		];
		$mainSalelogDao->create($data2);

		
		
		$mainDb->trans_complete();
	}
}
<?php

/**
 * @Author: binghe
 * @Date:   2018-08-14 15:04:05
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-14 15:40:37
 */
use Service\Sdk\Pinyin;
use Service\DbFrame\DataBase\MainDbModels\MainAreaDao;
class Test_pinyin extends base_controller
{
	public function index()
	{
		$pinyin = new Pinyin();
		$mainAreaDao = MainAreaDao::i();
		$rows = $mainAreaDao->getAllArray('first_char is null','*','id desc');
		var_dump($rows);exit;
		$count = 0;
		foreach ($rows as $row) {
			$firstChar = $pinyin->getFirstChar($row['name']);
			$mainAreaDao->update(['first_char'=>$firstChar],['id'=>$row['id']]);
			$count ++ ;
		}
  		echo $count;
	}
  	public function index2()
  	{
  		$pinyin = new Pinyin();
  		echo $pinyin->getFirstChar('鄯善县');
  	}
}
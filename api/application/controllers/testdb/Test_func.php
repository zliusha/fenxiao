<?php

/**
 * @Author: binghe
 * @Date:   2018-07-04 11:05:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:47
 */
use Service\DbFrame\Config;
/**
 * test
 */
class Test_func extends base_controller
{
	
	public function index()
	{
		$className = 'Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaDao';
		echo $className;
		echo '<br/>';
        $tmp = explode('\\', $className);
        echo end($tmp);
        echo '<br/>';
        $baseClassName = rtrim(end($tmp),Config::TABLE_CLASS_SUFFIX);
        echo $baseClassName;
        echo '<br/>';
        $tableName = Config::TABLE_PREFIX.ltrim(strtolower(preg_replace("/([A-Z])/", "_\\1", $baseClassName)), "_");
        echo $tableName;
	}
	public function t()
	{
		$className = 'MealDaoAreaDao';
		preg_match('/^([a-zA-Z0-9]+)'.Config::TABLE_CLASS_SUFFIX.'$/',$className,$matchs);
		var_dump($matchs);
	}
}
<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 17:10:29
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-15 09:51:23
 */
namespace Service\DbFrame;
/**
 * 配置辅助类
 */
class Config 
{
	const DEFAULT_SHARDNODE = '0-0-0';
	/**
	 * 表类名后缀
	 */
	const TABLE_CLASS_SUFFIX = 'Dao';
	/**
	 * 表前缀
	 */
	const TABLE_PREFIX = 'wd_';
	/**
	 * 获取主机
	 * @return [type] [description]
	 */
	static function &getHost()
	{
		return \inc_config('shard');
	}
	/**
	 * 获取数据库信息
	 * @param  [type] $host [description]
	 * @return [type]       [description]
	 */
	static function getShardConfig($host)
	{
		return \get_shard_config($host);
	}
	/**
	 * 是否是cli模式
	 * @return boolean [description]
	 * @author binghe 2018-08-15
	 */
	static function isCli()
	{
		return IS_CLI;
	}
}
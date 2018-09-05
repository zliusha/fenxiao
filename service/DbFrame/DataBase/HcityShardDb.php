<?php

/**
 * @Author: binghe
 * @Date:   2018-07-09 17:13:49
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-21 16:10:41
 */
namespace Service\DbFrame\DataBase;
use Service\DbFrame\Db;
use Service\DbFrame\Protocol\IShardNodeHandler;
/**
* database
*/
class HcityShardDb extends Db implements IShardNodeHandler
{
	protected $groupName = 'hcity_shard';
	/**
	 * 分片规则扩展
	 * @param  mixed $shardNode 
	 * @return [type]            [description]
	 */
	public static function shardNodeHandler($shardNode)
	{
		if(is_array($shardNode))
		{
			if(isset($shardNode['uid']))
			{

			}
			elseif(isset($shardNode['aid']))
			{

			}
		}	
		return $shardNode;
			
	}
}

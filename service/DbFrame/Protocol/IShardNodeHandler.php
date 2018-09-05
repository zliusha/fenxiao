<?php

/**
 * @Author: binghe
 * @Date:   2018-07-08 18:07:12
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-08 18:23:14
 */
namespace Service\DbFrame\Protocol;
/**
 * 分实例实现
 */
interface IShardNodeHandler
{
	public static function shardNodeHandler($shardNode);
}
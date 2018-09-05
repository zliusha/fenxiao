<?php

/**
 * @Author: binghe
 * @Date:   2018-07-09 17:15:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-21 16:14:48
 */
namespace Service\DbFrame\DataBase\HcityShardDbModels;

use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\Protocol\IShardNodeHandler;
use Service\DbFrame\Table;

/**
 * 测试表
 */
abstract class BaseDao extends Table implements IShardNodeHandler
{

    /**
     * 实现tb接口
     */
    public function createDb($shardNode)
    {
        return HcityShardDb::i($shardNode);
    }
    /**
     * 分片规则扩展
     * @param  mixed $shardNode
     * @return [type]            [description]
     */
    public static function shardNodeHandler($shardNode)
    {
        if (is_array($shardNode)) {
            if (isset($shardNode['uid'])) {

            } elseif (isset($shardNode['aid'])) {

            }
        }
        return $shardNode;

    }
}

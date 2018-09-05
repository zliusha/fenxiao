<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 20:02:18
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:27:33
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;

use Service\Cache\AidToShardNodeCache;
use Service\Cache\VisitIdToAidCache;
use Service\DbFrame\Config;
use Service\DbFrame\DataBase\WmShardDb;
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
        return WmShardDb::i($shardNode);
    }
    /**
     * 实现接口
     * @param  [type] $shardNode [description]
     * @return [type]            [description]
     */
    public static function shardNodeHandler($shardNode)
    {
        if (is_numeric($shardNode)) {
            return self::aidToShardNode($shardNode);
        } elseif (is_array($shardNode) && isset($shardNode['visit_id'])) {
            $visitIdToAidCache = new VisitIdToAidCache(['visit_id' => $shardNode['visit_id']]);
            $aid               = $visitIdToAidCache->getDataASNX();
            return self::aidToShardNode($aid);
        }
        return $shardNode;
    }
    /**
     * aid to shardNode
     * @param  [type] $aid [description]
     * @return [type]      [description]
     */
    public static function aidToShardNode($aid)
    {
        $aidToShardNodeCache = new AidToShardNodeCache(['aid' => $aid]);
        $shardNode           = $aidToShardNodeCache->getDataASNX();
        //未设置的节点,取默认
        if (empty($shardNode)) {
            $shardNode = Config::DEFAULT_SHARDNODE;
        }

        return $shardNode;
    }
}

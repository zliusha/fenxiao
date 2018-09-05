<?php

/**
 * @Author: binghe
 * @Date:   2018-07-14 10:52:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:27:16
 */
namespace Service\DbFrame\DataBase;

use Service\Cache\AidToShardNodeCache;
use Service\Cache\VisitIdToAidCache;
use Service\DbFrame\Config;
use Service\DbFrame\Db;
use Service\DbFrame\Protocol\IShardNodeHandler;

/**
 * database
 */
class WmShardDb extends Db implements IShardNodeHandler
{
    protected $groupName = 'wm_shard';
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
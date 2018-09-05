<?php
/**
 * @Author: binghe
 * @Date:   2018-05-18 15:39:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-05-18 17:12:22
 */
namespace Service\DbFrame;
use Service\Traits\SingletonTrait;
/**
* 分片规则管理
*/
class ShardNode
{
   public $shardHostNode = 0 ;
   public $shardDbNode = 0 ;
   public $shardTbNode = 0 ;
   public static function getInstance($shardRule)
   {
        static $cache = [];
        if(!isset($cache[$shardRule]))
        {
            $pregRule = '/^([0-9]+\-){2}[0-9]+$/';
            if(!preg_match($pregRule,$shardRule))
                throw new \Exception("shard error");
            $instance = new static();
            list($instance->shardHostNode,$instance->shardDbNode,$instance->shardTbNode) = explode('-',$shardRule);
            $cache[$shardRule] = $instance;
        }
        return $cache[$shardRule];
   }

}

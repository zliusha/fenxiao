<?php
/**
 * @Author: binghe
 * @Date:   2018-05-18 13:32:52
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-04 09:44:32
 */
namespace Service\DbFrame;
/**
* tables
*/
class Tables implements \ArrayAccess
{
   /**
    * 输入分片节点对象 object
    */
   private $shardNode;
   /**
    * 表类名前缀
    */
   private $preModelName;
   /**
    * 表宣器
    */
   private $tables = [];
   public function __construct($preModelName,$shardNode)
   {
      $this->shardNode = $shardNode;
      $this->preModelName = $preModelName;
   }
   /**
    * 得到分片表名
    * @param $fullTableName 表类全名
    * @return 得到分片表名
    */
   private function getShardTableName($fullTableName)
   {
      return call_user_func([$fullTableName, 'getShardTableName'],$this->shardNode);
   }
   /**
    * 将逻辑表名转化为类名
    */
   private function converToClassName($offset)
   {
     return ucfirst(preg_replace_callback("/_(.)/", function($matches){
         return strtoupper($matches[1]);
     }, strtolower($offset)));
   }
   public function offsetExists ($offset)
   {
      if(isset($this->tables[$offset]))
         return true;
      $fullTableName = $this->preModelName."\\".$this->converToClassName($offset).Config::TABLE_CLASS_SUFFIX;
      if(class_exists($fullTableName))
      {
        $this->tables[$offset] = $this->getShardTableName($fullTableName);
        return true;
      }
      else return false;

   }
   public function offsetGet ($offset)
   {
     if($this->offsetExists($offset))
      return $this->tables[$offset];
      else
      throw new \Exception(" table not exist");
   }
   public function offsetSet ($offset, $value )
   {
      $this->tables[$offset] = $value;
   }
   public function offsetUnset ($offset)
   {
      unset($this->tables[$offset]);
   }
}

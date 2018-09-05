<?php
/**
 * @Author: binghe
 * @Date:   2018-05-18 14:40:35
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-08 19:04:49
 */
namespace Service\DbFrame;
use Service\DbFrame\Protocol\IShardHost;
use Service\DbFrame\Protocol\IShardDb;
use Service\DbFrame\ShardNode;
/**
* db
*/
abstract class Db
{
    private function __construct($shardNode)
    {
      $this->shardNode = $shardNode;
    }
    /**
     * 输入分片节点对象 object
     */
    protected $shardNode;
    /**
     * ci db
     */
    protected $db;
    /**
     * 懒加载同片区表名
     * @var [type]
     */
    public $tables;
    /**
     * 集群分组名称
     * @var string
     */
    protected $groupName = 'default';
    /**
     * host 节点
     * @var integer
     */
    protected $shardHostNode = 0;
    /**
     * db 节点
     * @var integer
     */
    protected $shardDbNode = 0;

    /**
     * 判断当前库是否实例
     * @return bool
     */
    protected function isShardHost()
    {
        return $this instanceof IShardHost;
    }
    /**
     * 判断当前库是否分库
     * @return bool
     */
    protected function isShardDb()
    {
        return $this instanceof IShardDb;
    }
    /**
     * 初始化
     * @return [type] [description]
     */
    protected function initialize()
    {
        $this->setDb();
        $this->tables = new Tables($this->getPreModelName(),$this->shardNode);
    }
    /**
     * 分片规则 0-0-0 实例-库-表
     * @var string
     */
    public static function instance($shardNode = '0-0-0')
    {
        is_string($shardNode) && $shardNode = ShardNode::getInstance($shardNode);
        $instance = new static($shardNode);
        $instance->isShardHost() && $instance->shardHostNode = $shardNode->shardHostNode;
        $instance->isShardDb() && $instance->shardDbNode = $shardNode->shardDbNode;
        $instance->initialize();
        return $instance;

    }
    /**
     * 分片规则 方法简写 0-0-0 实例-库-表 可扩展
     * @param  mixed $mixed [description]
     * @return static
     */
    public static function i($shardNode = '0-0-0')
    {
        is_callable([static::class, 'shardNodeHandler']) && $shardNode = static::shardNodeHandler($shardNode);
        return self::instance($shardNode);
    }
    /**
     * 获取Model的前缀
     */
    protected function getPreModelName()
    {
      return static::class.'Models';
    }
    /**
     * 设置数据库连接
     */
    protected function setDb()
    {
        $this->db = ConnectionManager::getInstance()->getConnection($this->groupName,$this->shardHostNode,$this->shardDbNode);
    }
    /**
     * 调用db方法
     * @param  [type] $method [description]
     * @param  [type] $args   [description]
     */
    public function __call($method,$args)
    {
      return call_user_func_array([$this->db,$method],$args);
    }
}

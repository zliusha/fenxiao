<?php
/**
 * @Author: binghe
 * @Date:   2018-05-16 16:03:09
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-15 09:52:31
 */
namespace Service\DbFrame;

use Service\Traits\SingletonTrait;
use Service\Traits\ControllerTrait;

/**
 * 数据库连接
 */
class ConnectionManager
{
    use SingletonTrait;
    use ControllerTrait;
    public $cache = [];

    public function getConnection($groupName, $shardHostNode, $shardDbNode)
    {
        $hosts = &Config::getHost('shard');
        $group = $hosts[$groupName];
        $host = count($group) == count($group, 1) ? $group : $group[$shardHostNode];
        if ($shardDbNode != 0)
            $host['database'] .= '_' . $shardDbNode;

        $groupKeyName = $groupName . '_' . $host['database'];
        $config = Config::getShardConfig($host);
        if (!isset($this->cache[$groupKeyName])) {
            $this->cache[$groupKeyName] = $this->load->database($config, true);
        } elseif (Config::isCli()) {
            //cli模式下调用 reconnect() 方法向数据库发送 ping 命令, 这样可以优雅的保持连接有效或者重新建立起连接。
            $this->cache[$groupKeyName]->reconnect();
            if (!$this->cache[$groupKeyName]->conn_id) {
                //连接仍旧失败时，重新初始化
                $this->cache[$groupKeyName]->initialize();
            }
        }
        return $this->cache[$groupKeyName];
    }
}
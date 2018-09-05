<?php

/**
 * @Author: binghe
 * @Date:   2018-07-10 15:31:28
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-10 15:31:35
 */
namespace Service\DbFrame\DataBase\HcityMainDbModels;
class HcityUserDao extends BaseDao
{
    public function create($data)
    {
        $shardNode = !empty($data['shard_node']) ? $data['shard_node'] : '0-0-0';

        if (isset($data['shard_node'])) {
            unset($data['shard_node']);
        }
        $uid = parent::create($data);
        $userData['shard_node'] = '0-0-0';
        if ($uid > 0) {
            //保存分片信息
            $createData = [
                'uid' => $uid,
                'shard_node' => $shardNode
            ];
            HcityUserExtDao::i()->create($createData);
        }
        return $uid;
    }
}
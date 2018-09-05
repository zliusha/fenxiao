<?php
/**
 * @Author: binghe
 * @Date:   2018-04-04 10:33:57
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-08 20:01:15
 */
namespace Service\Support;

use Service\Traits\SingletonTrait;

/**
 * redis
 */
class Redis
{
    private $redis = null;
    use SingletonTrait;

    public $key_prefix = 'ydb:';

    /**
     * @return \Redis
     * @author ahe<ahe@iyenei.com>
     */
    public function getDefaultRedis()
    {
        try {
            if ($this->redis === null)
                $this->redis = new \ci_redis();
            $conStatus = $this->redis->ping();
            if ($conStatus !== '+PONG')
                $this->redis = new \ci_redis();
        } catch (\RedisException $e) {
            $this->redis = null;
            throw $e;
        }
        return $this->redis;
    }

    //生成平台区分
    public function generate_key($init_key = '')
    {
        return $this->key_prefix . $init_key;
    }
}
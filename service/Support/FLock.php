<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/27
 * Time: 上午11:42
 */

namespace Service\Support;


use Service\Traits\SingletonTrait;

class FLock
{
    use SingletonTrait;

    private $redisInstance;

    private $lockNameArr = [];

    private function __construct()
    {
        $this->redisInstance = Redis::getInstance();
    }

    /**
     * 取锁
     * @param string $className
     * @return bool
     * @author ahe<ahe@iyenei.com>
     */
    public function lock(string $className, int $ttl = 30)
    {
        $className = str_replace(["\\", "/"], "_", $className);
        $lockName = $this->redisInstance->generate_key("lock:{$className}");
        $this->lockNameArr[] = $lockName;
        $result = $this->redisInstance->getDefaultRedis()->setnx($lockName, getmypid());
        $this->redisInstance->getDefaultRedis()->setTimeout($lockName, $ttl);

        if ($result === true) {
            Shutdown::getInstance()->register([$this, 'unlock']);
            return true;
        }

        return false;

    }

    /**
     * 解锁
     * @author ahe<ahe@iyenei.com>
     */
    public function unlock()
    {
        foreach ($this->lockNameArr as $lockName) {
//            log_message('error', __METHOD__ . '释放锁:' . $lockName);
            $this->redisInstance->getDefaultRedis()->del($lockName);
        }
    }
}
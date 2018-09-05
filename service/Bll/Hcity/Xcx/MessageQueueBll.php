<?php

/**
 * @Author: binghe
 * @Date:   2018-08-08 11:08:50
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-08 19:22:45
 */
namespace Service\Bll\Hcity\Xcx;
use Service\Traits\SingletonTrait;
use Service\Exceptions\InterruptException;
use Service\Support\Shutdown;
use Service\Support\Redis;
/**
 * 服务消息队列
 */
class MessageQueueBll
{
	use SingletonTrait;
    //队列名
    private $queueName = 'queue:xcx_message';

    private $interrupt = false;
	/**
	 * 是否中断任务
	 * @author binghe 2018-08-08
	 */
	public function interrupt()
    {
        $this->interrupt = true;
    }
    /**
     * 重投redis队列,使用rPush，保证重投后优先消费
     * @param array $task
     * @author ahe<ahe@iyenei.com>
     */
    public function rePushQueue(array $task)
    {
        $queueName = Redis::getInstance()->generate_key($this->queueName);
        Redis::getInstance()->getDefaultRedis()->rPush($queueName, serialize(new MessageQueueItemBll($task)));
    }
    /**
     * 投递任务
     * @return [type] [description]
     * @author binghe 2018-08-08
     */
    public function push(array $task)
    {
    	Shutdown::getInstance()->register([$this, 'handlePush'], $task);
    }
    /**
     * 程序执行完成后，任务入队
     * @param  array  $task 
     * @author binghe 2018-08-08
     */
    public function handlePush(array $task)
    {
        $queueName = Redis::getInstance()->generate_key($this->queueName);
        Redis::getInstance()->getDefaultRedis()->lPush($queueName, serialize(new MessageQueueItemBll($task)));
       
    }
    /**
     * 弹出任务
     * @author binghe 2018-08-08
     */
    public function pop()
    {
        $queueName = Redis::getInstance()->generate_key($this->queueName);
        while (!$this->interrupt) {
            pcntl_signal_dispatch();
            $item = Redis::getInstance()->getDefaultRedis()->brPop($queueName, 10);
            if (empty($item)) {
                continue;
            }
            return unserialize($item[1]);
        }
        throw new InterruptException("队列已被中止");
    }
}
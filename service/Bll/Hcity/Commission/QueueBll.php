<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/27
 * Time: 上午11:23
 */

namespace Service\Bll\Hcity\Commission;

use Service\Bll\BaseBll;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionQueueDao;
use Service\Exceptions\InterruptException;
use Service\Support\Shutdown;
use Service\Support\Redis;
use Service\Traits\SingletonTrait;

class QueueBll
{
    use SingletonTrait;
    //队列名
    private $queueName = 'queue:commission';

    private $interrupt = false;

    /**
     * 投递佣金计算任务
     * @param int $taskId
     * @author ahe<ahe@iyenei.com>
     */
    public function post(int $taskId)
    {
        Shutdown::getInstance()->register([$this, 'handlePost'], $taskId);
    }

    /**
     * 程序执行完成后，任务入队
     * @param int $taskId
     * @author ahe<ahe@iyenei.com>
     */
    public function handlePost(int $taskId)
    {
        $queue = HcityCommissionQueueDao::i()->getOne(['id' => $taskId, 'status !=' => 1]);
        if (!empty($queue)) {
            $queueName = Redis::getInstance()->generate_key($this->queueName);
            $task = [
                'task_id' => $taskId,
                'fid' => $queue->fid,
                'money' => $queue->money,
                'remark' => $queue->remark,
                'type' => $queue->type,
                'ext' => $queue->ext
            ];
            Redis::getInstance()->getDefaultRedis()->lPush($queueName, serialize(new QueueItemBll($task)));
        }
    }

    /**
     * 重投redis队列,使用rPush，保证重投后优先消费
     * @param array $task
     * @author ahe<ahe@iyenei.com>
     */
    public function rePostQueue(array $task)
    {
        $queueName = Redis::getInstance()->generate_key($this->queueName);
        Redis::getInstance()->getDefaultRedis()->rPush($queueName, serialize(new QueueItemBll($task)));
    }

    /**
     * 是否中断任务
     * @author ahe<ahe@iyenei.com>
     */
    public function interrupt()
    {
        $this->interrupt = true;
    }


    /**
     * 弹出任务
     * @return mixed
     * @throws InterruptException
     * @author ahe<ahe@iyenei.com>
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
<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/27
 * Time: 上午9:20
 */
use Service\Bll\Hcity\Commission\QueueBll;
use Service\Exceptions\InterruptException;
use Service\Bll\Hcity\Commission\QueueItemBll;
use Service\Support\Shutdown;

class Hcity_commission extends task_controller
{
    /**
     * 分佣任务消费入口
     * @author ahe<ahe@iyenei.com>
     */
    public function run_cal()
    {
        error_reporting(error_reporting() & ~E_WARNING);
        $this->log('commission', '进程已启动, 正在等待任务');
        pcntl_signal(SIGTERM, [$this, 'onSignal']);
        pcntl_signal(SIGUSR2, [$this, 'onSignal']);
        try {
            while (true) {
                /** @var QueueItemBll $task */
                $task = QueueBll::getInstance()->pop();
                try {
                    //@todo 执行任务
                    $task->run();
                } catch (\Service\Exceptions\Exception $e) {
                    $this->log('commission', '佣金计算失败：message=' . $e->getMessage());
                    //更新任务进度
                    $task->updateQueueStatus(2);
                } catch (Throwable $e) {
                    $class = get_class($e);
                    $this->log('commission', "出现未知异常: {$class} Message={$e->getMessage()} Code={$e->getCode()}" . PHP_EOL . "Trace: " . PHP_EOL . $e->getTraceAsString());

                    $taskData = $task->getTaskData();
                    //重投任务，重试三次
                    if (!isset($taskData['retry_count'])) {
                        $taskData['retry_count'] = 0;
                    }

                    if ($taskData['retry_count'] < 3) {
                        $taskData['retry_count']++;
                        sleep(3);//三秒后重投任务
                        QueueBll::getInstance()->rePostQueue($taskData);
                    } else {
                        $this->log('commission', "已达到最大重试次数, 放弃重试. Message={$e->getMessage()}");
                        //更新任务进度
                        $task->updateQueueStatus(2);
                    }
                } finally {
                    Shutdown::getInstance()->trigger();
                }
            }
        } catch (InterruptException $e) {
            $this->log('commission', "收到中止异常, 已退出循环");
        }
    }

    /**
     * 中断信号
     * @param $signo
     * @author ahe<ahe@iyenei.com>
     */
    public function onSignal($signo)
    {
        $this->log('commission', "收到退出指令, SIGNAL=" . $signo);
        QueueBll::getInstance()->interrupt();
    }

    /**
     * 手动重投任务
     * @author ahe<ahe@iyenei.com>
     */
    public function rePost()
    {
        $ids = $this->input->get_post('ids');
        if (empty($ids)) {
            $this->json_do->set_error('005', '参数缺失');
        }
        $idArr = explode(',', $ids);
        foreach ($idArr as $id) {
            QueueBll::getInstance()->post($id);
        }
        echo '手动重投任务成功';
    }
}
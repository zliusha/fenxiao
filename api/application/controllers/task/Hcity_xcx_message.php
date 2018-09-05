<?php

/**
 * @Author: binghe
 * @Date:   2018-08-08 10:42:34
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-08 19:50:31
 */
use Service\Bll\Hcity\Xcx\MessageQueueBll;
use Service\Bll\Hcity\Xcx\MessageQueueItemBll;
use Service\Exceptions\InterruptException;
use Service\Support\Shutdown;
use Service\Exceptions\Exception;
/**
 * 小程序服务通知 task
 */
class Hcity_xcx_message extends task_controller
{
	
	/**
	 * 服务消息任务
	 * @author binghe 2018-08-08
	 */
	public function run()
	{
		//设置应该报告何种 PHP 错误 去除警告
		error_reporting(error_reporting() & ~E_WARNING);
		$this->log('xcx_message', '进程已启动, 正在等待任务');
		//程序结束信号
        pcntl_signal(SIGTERM, [$this, 'onSignal']);
        //用户定义信号，程序员可以在程序中定义并使用该信号
        pcntl_signal(SIGUSR2, [$this, 'onSignal']);
        try {
        	while(true)
			{
				/** @var MessageQueueItemBll $task */
                $task = MessageQueueBll::getInstance()->pop();
				try {
					$task->run();
				} catch (Throwable $e) {
					$class = get_class($e);
                    $this->log('xcx_message', "出现未知异常: {$class} Message={$e->getMessage()} Code={$e->getCode()}" . PHP_EOL . "Trace: " . PHP_EOL . $e->getTraceAsString());
					$taskData = $task->getTaskData();
					//重投任务，重试三次
                    if (!isset($taskData['retry_count'])) {
                        $taskData['retry_count'] = 0;
                    }
                    if ($taskData['retry_count'] < 3) {
                        $taskData['retry_count']++;
                        MessageQueueBll::getInstance()->rePushQueue($taskData);
                    } else {
                        $this->log('xcx_message', "已达到最大重试次数, 放弃重试. Message={$e->getMessage()}");
                    }
				}
				finally
				{
					Shutdown::getInstance()->trigger();
				}
			}
        } catch (InterruptException $e) {
            $this->log('xcx_message', "收到中止异常, 已退出循环");
        }
		
	}
	/**
	 * 中断信号
	 * @author binghe 2018-08-08
	 */
	public function onSignal($signo)
	{
		$this->log('xcx_message', "收到退出指令, SIGNAL=" . $signo);
		MessageQueueBll::getInstance()->interrupt();
	}
}
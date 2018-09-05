<?php

/**
 * @Author: binghe
 * @Date:   2018-08-08 14:51:23
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-08 19:50:53
 */
namespace Service\Bll\Hcity\Xcx;
use Service\Exceptions\Exception;
/**
 * 消息item
 */
class MessageQueueItemBll 
{
	private $task;
	public function __construct(array $task)
    {
        $this->task = $task;
    }
    /**
     * 执行条条任务
     * @return [type] [description]
     * @author binghe 2018-08-08
     */
    public function run()
    {
    	$fromId = (new XcxFormIdBll())->get($this->task['touser']);
    	if(!$fromId)
    		throw new Exception('发放条件不符合,formId不足');
    	$data = array_merge($this->task,['form_id'=>$fromId]);
    	//发送服务通知
    	(new XcxBll())->sendTemplateMessage($data);
    }
    /**
     * 获取任务数据
     * @return [type] [description]
     * @author binghe 2018-08-08
     */
    public function getTaskData()
    {
    	return $this->task;
    }
}
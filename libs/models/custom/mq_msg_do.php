<?php
/**
 * @Author: binghe
 * @Date:   2017-12-13 17:46:50
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-12-14 14:40:38
 */
/**
* mq_msg_do
*/
class mq_msg_do
{
    
    //消息类型
    public $type='';
    //自定义数组
    public $data=[];
    //消息时间
    public $time=0;
    public function __construct()
    {
        $this->time = time();
    }
}
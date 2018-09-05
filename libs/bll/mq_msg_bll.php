<?php
/**
 * @Author: binghe
 * @Date:   2017-12-14 14:18:15
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-12-14 15:22:31
 */
/**
* mq_msg 消息队列
*/
class mq_msg_bll extends base_bll
{
    public $c_key;
    public $redis ;
    function __construct()
    {
        parent::__construct();
        $this->redis = new ci_redis();
        $this->c_key = $this->redis->generate_key('mq_list');
    }
    /**
     * 入列消息
     * @param  [type] $mq_msg_do [description]
     * @return [type]            [description]
     */
    public function rPush($mq_msg_do)
    {
        $this->redis->rPush($this->c_key,serialize($mq_msg_do));
    }
    /**
     * 入队订单消息
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function rPushOrderMsg($data=[])
    {
        if(empty($data))
            return;
        $mq_msg_do = new mq_msg_do;
        $mq_msg_do->type = mq_msg_enum::ORDER_MSG;
        $mq_msg_do->data = $data;
        $this->rPush($mq_msg_do);
    }
}
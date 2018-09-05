<?php
/**
 * @Author: binghe
 * @Date:   2018-03-13 14:13:45
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-05-17 18:56:17
 */
/**
* worker mq
*/
class worker_sdk
{
    public $inc;
    public function __construct()
    {
        $this->inc = inc_config('workerman');
    }
    /**
     * 发送消息
     * @param array $data 发送体
     * @return [type] [description]
     */
    public function send($data)
    {
        $client = stream_socket_client($this->inc['host'], $errno, $errmsg, 1);
        if(!$client)
        {
            log_message('error',"worker连接失败.{$errno}-{$errmsg}");
            return false;
        }
        //消息体
        $body['environment'] = $this->inc['environment'];
        $body['time'] = time();
        $body['data'] = json_encode($data);
        $body['sign'] = simple_auth::getSign($body,$this->inc['key']);
        // 发送数据，Text协议需要在数据末尾加上换行符
        fwrite($client, json_encode($body)."\n");
        // 读取推送结果
        $res = fread($client, 1024);
        fclose($client);
        
        // log_message('error',__METHOD__.'-'.$res);
        return true;
        
    }
}
<?php
/**
 * @Author: binghe
 * @Date:   2018-03-06 16:40:21
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-20 17:06:47
 */
define('ROOT', __DIR__);
// 心跳间隔25秒
define('HEARTBEAT_TIME', 30);
// workerman_key
define('WORKERMAN_KEY','f2a25eed70f9dd51e7d48e3aef9d5096');
//未登录
define('SYS_LOGIN_OUT','sys_login_out');
//room 未登录
define('SYS_ROOM_LOGIN_OUT','sys_room_login_out');
//验证出错
define('SYS_VALID_ERROR','sys_valid_error');
//默认type
define('SYS_RESULT', 'sys_result');
// composer 的 autoload 文件
require_once ROOT . '/../vendor/autoload.php';
use Workerman\Worker;
use Workerman\Lib\Timer;
//文件目录,结尾不带'/'



/********* function **********/
//日志文件
function log_msg($logMsg,$prex = 'log')
{
    echo $prex . ' - '.date('Y-m-d H:i:s') .'-->'.$logMsg."\n";
}
//检测array keys是否存在
function valid_keys_exists($keys,$params)
{
    if(empty($keys) || empty($params))
        return false;
    foreach ($keys as $key) {
        if(array_key_exists($key, $params))
            continue;
        else return false;
    }
    return true;
}
//{'msgType'}
//验证消息
function valid_msg($msg)
{
    //$msg是否格式正确
    if(empty($msg))
        return false;
    //检验msg键
    $checkKeys = ['environment','data','sign','time'];
    if(!valid_keys_exists($checkKeys,$msg))
        return false;
    //验证消息比过期时间多500秒,默认每条消息２小时过期
    if($msg['time'] + 7700 < time())
        return false;
    //验证sign 
    return valid_sign($msg);

}
//验证sign
function valid_sign($msg)
{
    
    $orgSign = $msg['sign'];
    unset($msg['sign']);

    ksort($msg, SORT_STRING);
    $kvs = [];
    foreach ($msg as $key => $value) {
        $kvs[] = $key . '=' . $value;
    }
    $data = implode('', $kvs);
    $sign = md5($data . WORKERMAN_KEY);
    $ret = $sign == $orgSign;
    if(!$ret)
        log_msg('sign:'.$sign.'-orgSign:'.$orgSign);
    return $ret;
}


//消息处理中心
function msg_process($connection,$msg)
{
    $data = @json_decode($msg['data'],true);
    if(!$data)
        return;
    if(!isset($data['method']))
        return;
    switch ($data['method']) {
        case 'login':
            msg_text_login($connection,$msg,$data);
            break;
        case 'join_room':
            msg_text_join_room($connection,$msg,$data);
            break;
    }

}
//业务消息-room
function msg_text_join_room($connection,$msg,$data)
{
    if(isset($connection->ext))
    {
        unset($data['method']);
        $room = new stdClass();
        $time = time();
        $room->roomId=$data['room_id'];
        $room->connectionTime = $time;
        $room->lastMessageTime = $time;
        $room->ext = $data;
        if(!isset($connection->ext['rooms']))
            $connection->ext['rooms']=[];
        $connection->ext['rooms'][$room->roomId] = $room;
        log_msg('room login success-'.json_encode($connection->ext));
    }
    else
        send_error($connection,SYS_LOGIN_OUT);
}
//业务消息-登录
function msg_text_login($connection,$msg,$data)
{
    $ext['environment'] = $msg['environment'];
    $ext['source'] = $msg['source'];
    unset($data['method']);
    //合并登录的自定义数据
    $ext = array_merge($ext,$data);
    $connection->ext = $ext;
    log_msg('login success-'.json_encode($ext));
}
/**
 * 规则过滤链接 规则中有错误|规则key不存在 =>false
 * @return bool
 */
function rules_filter($ext,$rules)
{
    //未设计规则
    if(empty($rules))
        return false;
    $rule_keys = ['rule','field','value'];
    $result = true;
    foreach ($rules as $rule) {
        if(!$result)
            return false;
        //验证rule格式
        if(!valid_keys_exists($rule_keys,$rule))
            return false;
        if(!isset($ext[$rule['field']]))
            return false;
        switch ($rule['rule']) {
            case 'equal':
                    $result = $rule['value'] == $ext[$rule['field']];
                break;
            case 'in':
                    $result = in_array($ext[$rule['field']],$rule['value']);
                break;
            case 'notequal': $result = $rule['value'] != $ext[$rule['field']];
                break;
            case 'key_in': $result = isset($ext[$rule['field']]) ? array_key_exists($rule['value'], $ext[$rule['field']]): false;
                break;
            default: $result=false; break;
        }
    }
    //所有通过
    return $result;
}
/**
 * 来源条件验证
 * @return bool
 */
function source_rules_filter($ext,$rules)
{
    $result = true;
    foreach ($rules as $k => $rules) {
        if($ext['source'] != $k)
            continue;
        $result = rules_filter($ext,$rules);
    }
    return $result;
}
//业务消息-转发
function msg_transpond($connection,$msg)
{
    global $worker;
    $count = 0;
    $data = @json_decode($msg['data'],true);
    if(!$data)
        return false;
    $checkKeys = ['rules','content'];
    if(!valid_keys_exists($checkKeys,$data))
        return false;
    
    foreach ($worker->connections as $con) {
        //过滤未登录，过波不同环境
        if(!isset($con->ext) || $con->ext['environment'] != $msg['environment'] )
            continue;
        //规则过滤
        if(!rules_filter($con->ext,$data['rules']))
            continue;
        if(isset($data['source_rules']) && !source_rules_filter($con->ext,$data['source_rules']))
            continue;
        log_msg('ext-'.json_encode($con->ext));
        send_data($con,$data['content']);
        // break;   //如果只发一个,则无需注释
        $count ++;
    }
    
    return $count;
}
//发送数据,没有type的值不发送
function send_data($con,$data)
{
    //string应为json格式,且有type字段
    if(is_string($data))
    {
        log_msg($data,'syslog');
        $con->send($data);
        return;
    }
    if(isset($data['type']))
    {
        log_msg(json_encode($data),'syslog');
        $con->send(json_encode($data));
    }
    else 
        return;
}
//发送错误消息
function send_error($con,$type)
{
    $data['type'] = $type;
    send_data($con,$data);
}
//表示是否以daemon(守护进程)方式运行。如果启动命令使用了 -d参数，则该属性会自动设置为true
// Worker::$daemonize = true;
// 日志输出文件
Worker::$stdoutFile = ROOT . '/logs/stdout.log';
// 注意：这里与上个例子不通，使用的是websocket协议
$worker = new Worker("websocket://0.0.0.0:30002");
// 启动4个进程对外提供服务
$worker->count = 1;
//建立连接
$worker->onConnect = function($connection)
{
    $time = time();
    $connection->connectionTime = $time;
    $connection->lastMessageTime = $time;
    log_msg('new connection');
};
//关闭连接
$worker->onClose = function($connection){
    log_msg('connection closed');
};
//接收消息回调
$worker->onMessage = function($connection, $data)
{
    $time = time();
    //主心跳
    if($data == 'peng')
    {
        if(!isset($connection->ext))
        {
            send_error($connection,SYS_LOGIN_OUT);
            return;
        }
        $connection->lastMessageTime = $time;//心跳
        return;
    }
    //子心跳
    if(preg_match('/^peng_([a-z0-9]+)$/', $data,$matchs))
    {
        $roomId = $matchs[1];
        if(!isset($connection->ext['rooms'][$roomId]))
        {
            send_error($connection,SYS_ROOM_LOGIN_OUT);
            return;
        }
        $connection->ext['rooms'][$roomId]->lastMessageTime = $time; //心跳
        return;
    }
    log_msg($data);
    $msg = @json_decode($data, true);
    //验证消息
    if(!valid_msg($msg)) return;
    //消息处理
    msg_process($connection,$msg);
};
//错误
$worker->onError = function($connection, $code, $msg)
{
    log_msg($code.'-'.$msg , 'error');
};
// 进程启动后设置一个每秒运行一次的定时器
$worker->onWorkerStart = function($worker) {
     // 开启一个内部端口，方便内部系统推送数据，Text协议格式 文本+换行符
    $inner_text_worker = new Worker('text://0.0.0.0:30003');
    $inner_text_worker->onMessage = function($connection, $buffer)
    {
        log_msg($buffer,'syslog');
        $msg = @json_decode($buffer, true);
        //验证消息
        if(!valid_msg($msg)) 
        {
            send_error($connection,SYS_VALID_ERROR);
            return;
        }
        //消息处理,转发数量
        $transpond_count = msg_transpond($connection,$msg);
        // 返回推送结果
        send_data($connection,['type'=>SYS_RESULT,'transpond_count'=>$transpond_count]);
    };
    $inner_text_worker->onClose = function(){
        log_msg('connection closed','syslog');
    };
    // ## 执行监听 ##
    $inner_text_worker->listen();
    //关掉离线的链接
    Timer::add(1, function()use($worker){
        $time_now = time();
        foreach($worker->connections as $connection) {
            //关闭超时的room
            if(isset($connection->ext['rooms']))
            {
                foreach ($connection->ext['rooms'] as $k=>$room) {
                    // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭room
                    if ($time_now - $room->lastMessageTime > HEARTBEAT_TIME) {
                        unset($connection->ext['rooms'][$k]);
                        log_msg('room login out','syslog');
                    }
                }
            }
            // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
            if (!isset($connection->lastMessageTime)) {
                $connection->lastMessageTime = $time_now;
                continue;
            }
            // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
            if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                $connection->close();
            }
        }
    });
};
Worker::runAll();
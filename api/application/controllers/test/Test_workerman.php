<?php
/**
 * @Author: binghe
 * @Date:   2018-04-28 17:12:35
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-28 18:00:52
 */
/**
* worker man test
*/
class Test_workerman extends base_controller
{
    
    public function index()
    {
        $end = '<br/>';
        //端口111 
        $service_port = 30002; 
        //地址
        $address = '121.41.177.151'; 
        //创建 TCP/IP socket 
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); 
        if ($socket < 0) { 
            echo "socket创建失败原因: " . socket_strerror($socket) . "\n"; 
        } else { 
            echo "OK，HE HE.".$end; 
        } 
        $result = socket_connect($socket, $address, $service_port); 
        if ($result < 0) { 
            echo "SOCKET连接失败原因: ($result) " . socket_strerror($result) . "\n"; 
        } else { 
            echo "OK.".$end; 
        } 
        //发送命令 
        $in = "new msg new message"; 

        $out = ''; 
        echo "Send Command..........".$end; 

        socket_write($socket, $in, strlen($in)); 
        echo "OK.".$end; 
        echo "Reading Backinformatin:".$end; 
        while ($out = socket_read($socket, 1024)) { 
            echo $out.$end; 
        } 
        echo "Close socket........".$end; 
        socket_close($socket); 
        echo "OK,He He.".$end; 

    }
}
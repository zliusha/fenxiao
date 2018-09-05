<?php
/**
 * @Author: binghe
 * @Date:   2017-09-19 15:57:01
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-09-19 15:57:06
 */
$config['socket_type'] = 'tcp'; //`tcp` or `unix`
$config['socket'] = '/var/run/redis.sock'; // in case of `unix` socket type
$config['host'] = '127.0.0.1';
$config['password'] = NULL;
$config['port'] = 6379;
$config['timeout'] = 0;
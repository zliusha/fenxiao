<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Redis适配
 * @author dadi
 */
class ci_redis
{

    private $_ci;
    private $handler;
    public $key_prefix='ydb_waimai:';
    public function __construct($c_name = 'redis_default')
    {
        $inc = &inc_config('redis');
        $config = $inc[$c_name];

        $this->handler = new Redis();
        $this->handler->pconnect($config['host'], $config['port']);

        if (!empty($config['password'])) {
            $this->handler->auth($config['password']);
        }
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->handler, $method], $args);
    }
    //生成平台区分
    public function generate_key($init_key='')
    {
        return $this->key_prefix.$init_key;
    }
    //释放类时，关闭连接
    public function __destruct()
    {
        $this->handler->close();
    }

}

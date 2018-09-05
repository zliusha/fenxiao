<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/27
 * Time: 上午9:22
 */
class task_controller extends base_controller
{
    public $start_time = '';

    function __construct()
    {
        parent::__construct();
        //初始化请求
        $this->initialize();
    }

    /**
     * 初始化
     */
    public function initialize()
    {
        $this->start_time = $this->getMillisecond();//记录开始执行时间
//        ini_set('default_socket_timeout', -1);//设置socket流永久
        set_time_limit(0);//设置页面永久不超时
    }


    public function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    /**
     * 日志
     * @param $filename
     * @param $logData
     * @param string $type
     * @author ahe<ahe@iyenei.com>
     */
    public function log($filename, $logData, $type = 'task')
    {
        $logPath = '/data/logs/ydb/' . $type;
        //检测日志文件是否存在
        if (!is_dir($logPath)) {
            mkdir($logPath, 0777, true);
        }

        $logPath = $logPath . '/' . $filename . '_' . date("Y_m_d", time()) . '.log';
        $logData = var_export($logData, true);
        $logData = "[" . date("Y/m/d H:i:s", time()) . "]: " . $logData . PHP_EOL;

        //写入日志
        file_put_contents($logPath, $logData, FILE_APPEND);

        if (IS_CLI) {
            static $pid = 0;
            empty($pid) && $pid = getmypid();

            $logData = sprintf("% 6d|", $pid) . $logData;
            echo $logData;
        }
    }
}
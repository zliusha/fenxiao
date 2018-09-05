<?php

/**
 * @Author: binghe
 * @Date:   2018-06-21 13:25:32
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-21 14:19:10
 */
namespace Service\Support;
class Logger
{
    /**
     * 记录日志
     *
     * @param string $message
     * @param string $fileName
     * @param string $logType
     */
    public static function log($message, $fileName = 'log')
    {
        static $defaultLogger = null;
        if ($defaultLogger == null) {
            $defaultLogger = new static('log');
        }

        $defaultLogger->info($message);
    }

    private $logPath;
    private $fileName;

    /**
     * @var resource
     */
    private $handle;

    public function __construct($fileName = 'log')
    {
        $this->fileName = $fileName;
        //定义日志文件
        if(defined('LOGPATH'))
        	$this->logPath = LOGPATH;
        elseif(defined('APPPATH'))
        	$this->logPath = APPPATH . 'logs/';
        elseif(defined('ROOT'))
        	$this->logPath = ROOT . 'logs/';
        else
        	$this->logPath = '/logs/';
    }

    public function info($message, array $context = [])
    {
        $this->writeLog('[INFO] --> ' . $message, $context);
        return $this;
    }

    public function warning($message, array $context = [])
    {
        $this->writeLog("[WARNING] --> " . $message, $context);
        return $this;
    }

    public function debug($message, array $context = [])
    {
        $this->writeLog("[DEBUG] --> " . $message, $context);
        return $this;
    }

    public function error($message, array $context = [])
    {
        $this->writeLog("[ERROR] --> " . $message, $context);
        return $this;
    }

    /**
     * 关闭日志文件
     */
    public function close()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    private function writeLog($message, array $context = [])
    {
        if (!is_resource($this->handle)) {
            $logPath = $this->logPath . date('Y-m');

            //检测日志文件是否存在
            if (!is_dir($logPath)) {
                mkdir($logPath, 0777, true);
            }

            $logPath = $logPath . '/' . $this->fileName . '_' . date('Y-m-d') . '.log';
            $this->handle = fopen($logPath, "a");
            if (!$this->handle) {
                throw new \RuntimeException("无法打开日志文件: {$logPath}");
            }
        }

        $replace = array();
        foreach ($context as $key => $val) {
            if (is_string($val)) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        $logData = str_replace(array_keys($replace), array_values($replace), $message);
        if (!empty($context)) {
            $logData .= print_r($context, true);
        }

        $logData = "[" . date("Y-m-d H:i:s") . "] " . $logData . PHP_EOL;

        if (IS_CLI) {
            static $pid = 0;
            empty($pid) && $pid = getmypid();

            $logData = sprintf("% 6d|", $pid) . $logData;
            echo $logData;
        }

        // 写入日志
        fwrite($this->handle, $logData);
    }

    public function __destruct()
    {
        $this->close();
    }
}
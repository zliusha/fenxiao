<?php
/**
 * @Author: binghe
 * @Date:   2018-04-02 18:17:08
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-09 13:42:39
 */
define('SERVICE_DIR', realpath(dirname(__FILE__)));
spl_autoload_register(function($className){
    $startWith = function($string, $needle){
        $length = strlen($needle);
        return substr($string, 0, $length) === $needle;
    };
    if ($startWith($className, 'Service\\')) {
        $explode = explode('\\', $className);
        $rest = array_slice($explode, 1);
        $filename = SERVICE_DIR . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $rest) . '.php';
        require_once $filename;
        return ;
    }
});
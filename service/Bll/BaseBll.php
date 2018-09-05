<?php
/**
 * @Author: binghe
 * @Date:   2018-04-03 10:18:32
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-07 15:15:07
 */
namespace Service\Bll;
/**
 *
 */
class BaseBll
{
    public function __construct()
    {
        
    }
    /**
     * 事件触发
     * @param $func
     * @param array $params
     * @author ahe<ahe@iyenei.com>
     */
    public function trigger($func, $params = [])
    {
        if (is_callable($func)) {
            call_user_func($func, $params);
        } elseif (is_string($func) && method_exists($this, $func)) {
            call_user_func([$this, $func], $params);
        }
    }
}
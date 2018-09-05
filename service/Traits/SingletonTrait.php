<?php
/**
 * @Author: binghe
 * @Date:   2018-05-16 16:05:11
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-05-16 16:05:36
 */
namespace Service\Traits;


trait SingletonTrait
{
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}
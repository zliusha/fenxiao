<?php
/**
 * @Author: binghe
 * @Date:   2017-08-23 19:32:43
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-09 10:54:02
 */
use Service\Traits\HttpTrait;
/**
* 测试
*/
class test_controller extends base_controller
{
    use HttpTrait;
    public $auth;
    const ACCESS_KEY='2c3d4895891376ce1a4006d2e1a10521';
    const SECRET_KEY='731ad55b3e74db23d6fbb61fd89a9709';
    function __construct()
    {
        parent::__construct();
        $this->auth = new auth(self::ACCESS_KEY,self::SECRET_KEY);
    }
}
<?php

/**
 * @Author: binghe
 * @Date:   2018-08-14 16:29:50
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-14 16:30:54
 */
namespace Service\Cache\Main;

use Service\DbFrame\DataBase\MainDbModels\MainAreaDao;


/**
 * 子账号有权限的saas缓存
 */
class AreaCityCache extends \Service\Cache\BaseCache
{
    
    /**
     * 获取缓存,不存在并保存
     * @return array
     */
    public function getDataASNX()
    {
        return $this->getASNX(function () {
            $rows = MainAreaDao::i()->getAllArray('level = 2','*','first_char asc');
	        return $rows;
        });
    }

    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "Main:AreaCity";
    }

}
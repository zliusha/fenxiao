<?php

/**
 * @Author: binghe
 * @Date:   2018-07-14 10:52:02
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 11:55:37
 */
namespace Service\DbFrame\DataBase;

use Service\Cache\AidToShardNodeCache;
use Service\Cache\VisitIdToAidCache;
use Service\DbFrame\Config;
use Service\DbFrame\Db;

/**
 * database
 */
class WmMainDb extends Db 
{
    protected $groupName = 'wm_main';
}
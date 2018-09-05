<?php

/**
 * @Author: binghe
 * @Date:   2018-07-04 19:24:56
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-09 17:16:13
 */
namespace Service\DbFrame\DataBase\HcityMainDbModels;
use Service\DbFrame\Table;
use Service\DbFrame\DataBase\HcityMainDb;
/**
* 测试表
*/
abstract class BaseDao extends Table
{

  /**
   * 实现tb接口
   */
  public function createDb($shardNode)
  {
    return HcityMainDb::i($shardNode);
  }
}
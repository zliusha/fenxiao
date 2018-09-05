<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 15:47:47
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-28 20:50:13
 */
namespace Service\DbFrame\DataBase\MainDbModels;
use Service\DbFrame\Table;
use Service\DbFrame\DataBase\MainDb;
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
    return MainDb::i($shardNode);
  }
}
<?php

/**
 * @Author: binghe
 * @Date:   2018-08-21 16:47:43
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-21 16:48:08
 */
namespace Service\DbFrame\DataBase\ManageMainDbModels;
use Service\DbFrame\Table;
use Service\DbFrame\DataBase\ManageMainDb;
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
    return ManageMainDb::i($shardNode);
  }
}
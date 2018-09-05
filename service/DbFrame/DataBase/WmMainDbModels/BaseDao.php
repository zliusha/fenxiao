<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 15:47:47
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 11:13:34
 */
namespace Service\DbFrame\DataBase\WmMainDbModels;
use Service\DbFrame\Table;
use Service\DbFrame\DataBase\WmMainDb;
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
    return WmMainDb::i($shardNode);
  }
}
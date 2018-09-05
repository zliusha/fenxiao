<?php

/**
 * @Author: binghe
 * @Date:   2018-07-10 15:20:56
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-16 16:30:20
 */
namespace Service\DbFrame\DataBase\HcityShardDbModels;

use Service\DbFrame\DataBase\HcityMainDbModels\HcityOrderKzDao;

class ShcityOrderDao extends BaseDao
{
    public $orderStatus = ['待支付', '待核销', '已核销', '订单关闭'];

}
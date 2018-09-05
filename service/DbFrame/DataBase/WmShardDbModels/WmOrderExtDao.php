<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:07:18
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:03
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmOrderExtDao extends BaseDao
{
	/**
   * 获取固定时间段的下单商品数量
   * @param int $userId  uid
   * @param int $goodsId  商品id
   * @param int $startTime 开始时间
   * @param int $endTime  结束时间
   * @return int
   */
    public function orderUserGoodsNumber($goodsId=0, $userId=0,$startTime=0, $endTime=0, $aid=0)
    {

        $where['aid'] = $aid;
        $where['goods_id'] = $goodsId;
        $where['discount_type >'] = 0;
        if(!empty($userId))
        {
          $where['uid'] = $userId;
        }
        if(!empty($startTime))
        {
            $where['time >'] = $startTime;
        }
        if(!empty($endTime))
        {
          $where['time <'] = $endTime;
        }

        return $this->getSum("num", $where);
    }
}
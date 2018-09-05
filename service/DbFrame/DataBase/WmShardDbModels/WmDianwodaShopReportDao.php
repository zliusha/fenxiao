<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:01:45
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:01
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmDianwodaShopReportDao extends BaseDao
{
	/**
     * 报表当日初始化
     * @param  integer $shopId [description]
     * @return id           [description]
     */
    public function _initDaily($shopId = 0)
    {
        if (!$shopId) {
            return false;
        }

        $date = strtotime(date('Y-m-d'));

        $record = $this->getOne(['shop_id' => $shopId, 'date' => $date], 'id');

        if (isset($record->id) && $record->id > 0) {
            return $record->id;
        } else {
        	$wmShopDao = WmShopDao::i($this->shardNode);
            $shop = $wmShopDao->getOne(['id' => $shopId]);

            if (!$shop) {
                return false;
            }

            return $this->create([
                'aid' => $shop->aid,
                'date' => $date,
                'shop_id' => $shop->id,
                'shop_name' => $shop->shop_name,
                'push_num' => 0,
                'finish_num' => 0,
                'cancel_num' => 0,
                'cost_amount' => 0,
            ]);
        }
    }

    public function pushOrder($shopId = 0)
    {
        $record_id = $this->_initDaily($shopId);

        $this->db->set('push_num', 'push_num+1', false);
        $this->db->where('id', $record_id);
        $this->db->update($this->tableName);

        return $this->db->affected_rows();
    }

    public function finishOrder($shopId = 0)
    {
        $record_id = $this->_initDaily($shopId);

        $this->db->set('finish_num', 'finish_num+1', false);
        $this->db->where('id', $record_id);
        $this->db->update($this->tableName);

        return $this->db->affected_rows();
    }

    public function cancelOrder($shopId = 0)
    {
        $record_id = $this->_initDaily($shopId);

        $this->db->set('cancel_num', 'cancel_num+1', false);
        $this->db->where('id', $record_id);
        $this->db->update($this->tableName);

        return $this->db->affected_rows();
    }

    public function payOrder($shopId = 0, $amount = 0)
    {
        $record_id = $this->_initDaily($shopId);

        $this->db->set('cost_amount', 'cost_amount+' . $amount, false);
        $this->db->where('id', $record_id);
        $this->db->update($this->tableName);

        return $this->db->affected_rows();
    }
}
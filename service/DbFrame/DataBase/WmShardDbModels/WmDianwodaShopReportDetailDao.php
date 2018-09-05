<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:02:16
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:01
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmDianwodaShopReportDetailDao extends BaseDao
{
	/**
     * 点我达发单
     */
    public function pushOrder($order = null, $dwd = [], $freight = 0)
    {
        if ($this->getCount(['tid' => $order->tid]) > 0) {
            return false;
        }

        return $this->create([
            'aid' => $order->aid,
            'shop_id' => $order->shop_id,
            'shop_name' => $order->shop_name,
            'tid' => $order->tid,
            'receiver_name' => $order->receiver_name,
            'receiver_phone' => $order->receiver_phone,
            'receiver_site' => $order->receiver_site,
            'receiver_address' => $order->receiver_address,
            'dwd_order_id' => $dwd['dwd_order_id'],
            'distance' => $dwd['distance'],
            'skycon' => $dwd['skycon']?$dwd['skycon']:'',
            'skycon_price' => $dwd['price']?$dwd['price']:'',
            'freight' => $freight,
            'amount' => $freight,
            'send_time' => time(),
            'accept_time' => 0,
            'take_time' => 0,
            'finish_time' => 0,
            'cancel_time' => 0,
        ]);
    }

    /**
     * 点我达骑手接单
     */
    public function acceptOrder($tid = '')
    {
        return $this->update(['accept_time' => time()], ['tid' => $tid, 'accept_time' => 0]);
    }

    /**
     * 点我达骑手取货
     */
    public function takeOrder($tid = '')
    {
        return $this->update(['take_time' => time()], ['tid' => $tid, 'take_time' => 0]);
    }

    /**
     * 点我达骑手完成
     */
    public function finishOrder($tid = '')
    {
        return $this->update(['finish_time' => time()], ['tid' => $tid, 'finish_time' => 0]);
    }

    /**
     * 点我达骑手取消
     */
    public function cancelOrder($tid = '', $amount = 0)
    {
        return $this->update(['amount' => $amount, 'cancel_time' => time()], ['tid' => $tid, 'cancel_time' => 0]);
    }
}
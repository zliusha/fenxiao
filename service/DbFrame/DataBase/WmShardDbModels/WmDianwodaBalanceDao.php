<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 10:16:51
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:00
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmDianwodaBalanceDao extends BaseDao
{
	/*
     * 点我达运费支付
     */
    public function payFreight($order = null, $amount = 0, $remark = '点我达发单扣款')
    {
        $account = $this->getOne(['aid' => $order->aid]);

        if (!isset($account->balance)) {
            return false;
        }

        if ($account->balance > $amount) {
            $this->db->set('balance', 'balance-' . $amount, false);
            $this->db->where('aid', $order->aid);
            $this->db->update($this->tableName);
            $result = $this->db->affected_rows();

            if ($result) {
                // 增加余额金额变动记录
                $pay['amount'] = -$amount;
                $pay['code'] = $order->tid;
                $pay['shop_id'] = $order->shop_id;
                $pay['outer_id'] = $order->id;
                $pay['aid'] = $order->aid;
                $pay['type'] = 2;
                $pay['remark'] = $remark;
                $wmDianwodaBalanceRecordDao = WmDianwodaBalanceRecordDao::i($this->shardNode);
                $wmDianwodaBalanceRecordDao->create($pay);

                // 运费日报统计 - 支付运费
                $wmDianwodaShopReportDao = WmDianwodaShopReportDao::i($this->shardNode);
                $wmDianwodaShopReportDao->payOrder($order->shop_id, $amount);

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /*
     * 点我达运费退款
     */
    public function refundFreight($order = null, $amount = 0, $remark = '发单失败退款')
    {
        $this->db->set('balance', 'balance+' . $amount, false);
        $this->db->where('aid', $order->aid);
        $this->db->update($this->tableName);
        $result = $this->db->affected_rows();

        if ($result) {
            // 增加余额金额变动记录
            $pay['amount'] = $amount;
            $pay['code'] = $order->tid;
            $pay['shop_id'] = $order->shop_id;
            $pay['outer_id'] = $order->id;
            $pay['aid'] = $order->aid;
            $pay['type'] = 3;
            $pay['remark'] = $remark;

            $wmDianwodaBalanceRecordDao = WmDianwodaBalanceRecordDao::i($this->shardNode);
            $wmDianwodaBalanceRecordDao->create($pay);

            // 运费日报统计 - 支付运费
            $wmDianwodaShopReportDao = WmDianwodaShopReportDao::i($this->shardNode);
            $wmDianwodaShopReportDao->payOrder($order->shop_id, -$amount);

            return true;
        } else {
            return false;
        }
    }
}
<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:00:21
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:01
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class WmDianwodaMoneyRecordDao extends BaseDao
{
	/**
     * 商家充值到账逻辑
     * @param  [type] $outTradeNo [站内订单号]
     * @param  [type] $tradeNo     [外部返回的订单号]
     * @return boolen               [入账结果]
     */
    public function deposit($outTradeNo, $tradeNo)
    {
        if (!$outTradeNo || !$tradeNo) {
            return false;
        }
        // TODO::加锁控制
        $record = $this->getOne(array('code' => $outTradeNo));

        if ($record->status == 0) {

            $this->update(array('status' => 1, 'trade_no' => $tradeNo), array('id' => $record->id));
            $wmDianwodaBalanceDao = WmDianwodaBalanceDao::i($this->shardNode);

            $dianwoda_balance = $wmDianwodaBalanceDao->getOne(['aid' => $record->aid]);

            if (!$dianwoda_balance) {
                $result = $wmDianwodaBalanceDao->create([
                    'aid' => $record->aid,
                    'balance' => $record->money,
                    'update_time' => time(),
                ]);
            } else {
                $this->db->set('balance', 'balance+' . $record->money, false);
                $this->db->where('aid', $record->aid);
                $this->db->update($this->db->tables['wm_dianwoda_balance']);
                $result = $this->db->affected_rows();
            }

            // 商家资金记录
            $pay['amount'] = $record->money;
            $pay['code'] = $outTradeNo;
            $pay['shop_id'] = 0;
            $pay['outer_id'] = $record->id;
            $pay['aid'] = $record->aid;
            $pay['type'] = 1;
            $pay['remark'] = '商家充值';

            $wmDianwodaBalanceRecordDao =  WmDianwodaBalanceRecordDao::i($this->shardNode);;

            $result = $wmDianwodaBalanceRecordDao->create($pay);

            if ($result) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }
}
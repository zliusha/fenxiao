<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 09:52:48
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:55:58
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class MealPaymentRecordDao extends BaseDao
{
	/**
     * 微信订单支付成功回调
     * @param  [type] $siteTradeNo [站内订单号]
     * @param  [type] $outTradeNo  [外部返回的订单号]
     * @return boolen               [入账结果]
     */
    public function weixinOrderCallback($siteTradeNo, $outTradeNo)
    {
        if (!$siteTradeNo || !$siteTradeNo) {
            return false;
        }
        // TODO::加锁控制
        $record = $this->getOne(['code' => $siteTradeNo, 'gateway' => 'weixin']);

        if ($record->status == 0) {

            $this->update(['status' => 1, 'trade_no' => $outTradeNo], ['id' => $record->id]);

            // 调用流水生成器
            $meal_statements_bll = new \meal_statements_bll();
            $result = $meal_statements_bll->checkout(['pay_trade_no' => $outTradeNo, 'pay_type' => 3, 'pay_source' => 1, 'source_type'=>$record->source_type,'payment_record_id' => $record->id, 'order_table_id' => $record->order_table_id, 'sum_pay_money' => $record->money,'aid'=>$record->aid], $is_t1 = false);

            return $result ? true : false;

        } else {
            return false;
        }
    }

    /**
     * 微信订单支付成功回调
     * @param  [type] $siteTradeNo [站内订单号]
     * @param  [type] $outTradeNo  [外部返回的订单号]
     * @return boolen               [入账结果]
     */
    public function wxMicroOrderCallback($siteTradeNo, $outTradeNo)
    {
        if (!$siteTradeNo || !$siteTradeNo) {
            return false;
        }
        // TODO::加锁控制
        $record = $this->getOne(['code' => $siteTradeNo, 'gateway' => 'wx_micro']);

        if ($record->status == 0) {

            $this->update(['status' => 1, 'trade_no' => $outTradeNo], ['id' => $record->id]);

            // 调用流水生成器
            $meal_statements_bll = new \meal_statements_bll();
            $result = $meal_statements_bll->checkout(['pay_trade_no' => $outTradeNo, 'pay_type' => 3, 'pay_source' => 1, 'source_type'=>$record->source_type,'payment_record_id' => $record->id, 'order_table_id' => $record->order_table_id, 'sum_pay_money' => $record->money,'aid'=>$record->aid,'tid'=>$record->tid], $is_t1 = false);

            return $result ? true : false;

        } else {
            return false;
        }
    }
    /**
     * 支付宝订单支付成功回调
     * @param  [type] $site_trade_no [站内订单号]
     * @param  [type] $out_trade_no  [外部返回的订单号]
     * @return boolen               [入账结果]
     */
    public function alipayOrderCallback($site_trade_no, $out_trade_no)
    {
        if (!$site_trade_no || !$out_trade_no) {
            return false;
        }
        // TODO::加锁控制
        //        $record = $this->get_one(['code' => $site_trade_no, 'gateway' => 'weixin']);
        $record = $this->getOne(['code' => $site_trade_no]);

        if ($record->status == 0) {

            $this->update(['status' => 1, 'trade_no' => $out_trade_no], ['id' => $record->id]);

            // 调用流水生成器
            $meal_statements_bll = new \meal_statements_bll();
            $result = $meal_statements_bll->checkout(['pay_trade_no' => $out_trade_no, 'pay_type' => 2, 'pay_source' => 2, 'source_type'=>$record->source_type, 'payment_record_id' => $record->id, 'order_table_id' => $record->order_table_id, 'sum_pay_money' => $record->money,'tid'=>$record->tid,'aid'=>$record->aid], $is_t1 = false);

            return $result ? true : false;

        } else {
            return false;
        }
    }
}
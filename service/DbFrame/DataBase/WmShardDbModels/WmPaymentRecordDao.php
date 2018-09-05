<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:19:26
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:56:03
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
/**
 * 公司
 */
class WmPaymentRecordDao extends BaseDao
{
	/**
     * 微信订单支付成功回调
     * @param  [type] $outTradeNo [站内订单号]
     * @param  [type] $tradeNo     [外部返回的订单号]
     * @return boolen               [入账结果]
     */
    public function weixinOrderCallback($outTradeNo, $tradeNo)
    {
        if (!$outTradeNo || !$tradeNo) {
            return false;
        }
        // TODO::加锁控制
        $record = $this->getOne(['code' => $outTradeNo, 'gateway' => 'weixin']);

        if ($record->status == 0) {

            $this->update(['status' => 1, 'trade_no' => $tradeNo], ['id' => $record->id]);

            if ($record->type == 'order') {
                // 通知订单支付成功
                $fdata = [
                    'uid' => $record->uid,
                    'tradeno' => $outTradeNo,
                    'pay_trade_no' => $tradeNo,
                    'pay_type' => 2,
                    'aid'=>$record->aid
                ];
                $wm_order_event_bll = new \wm_order_event_bll();
                $result = $wm_order_event_bll->notifyPaidSuccess($fdata, true);

                return $result ? true : false;
            } else if ($record->type == 'deposit') {
                // 通知SCRM充值金额
                if (ENVIRONMENT != 'production') {
                    $openId = 'o9FFD0zWJn5eLaUd3rRFVPAnpLT0';
                    $visitId = '159929';
                    $phone = '15505885185';
                } else {
                    // 获取openid
                    $wmUserWxbindDao = WmUserWxbindDao::i($this->shardNode);
                    $wxbind = $wmUserWxbindDao->getOne(['uid' => $record->uid, 'type' => 'weixin'], 'open_id', 'id desc');
                    $openId = $wxbind->open_id;

                    // 获取visit_id
                    $mainCompanyDao = MainCompanyDao::i();
                    $m_main_company = $mainCompanyDao->getOne(['id' => $record->aid], 'visit_id');
                    $visitId = $m_main_company->visit_id;

                    // 获取手机号
                    $wmUserDao = WmUserDao::i($this->shardNode);
                    $user = $wmUserDao->getOne(['id' => $record->uid], 'mobile');
                    $phone = $user->mobile;
                }

                $api = \ci_scrm::WECHAT_FANS_PAY;
                $params = ['visit_id' => $visitId, 'openid' => $openId, 'phone' => $phone, 'money' => (int) $record->money];
                // print_r($params);exit;
                $result = \ci_scrm::call($api, $params);

                if (!$result || $result['code'] != 0) {
                    log_message('error', __METHOD__ . ' 会员储值SCRM通知失败提示: ' . json_encode($result));
                    return false;
                }

                return true;
            }

        } else {
            return false;
        }
    }

    /**
     * 余额支付成功回调
     * @param  [type] $outTradeNo [站内订单号]
     * @param  [type] $tradeNo     [外部返回的订单号]
     * @return boolen               [入账结果]
     */
    public function balanceOrderCallback($outTradeNo, $tradeNo)
    {
        if (!$outTradeNo || !$tradeNo) {
            return false;
        }
        // TODO::加锁控制
        $record = $this->getOne(['code' => $outTradeNo, 'gateway' => 'balance']);

        if ($record->status == 0) {

            $this->update(['status' => 1, 'trade_no' => $tradeNo], ['id' => $record->id]);

            if ($record->type == 'order') {
                // 通知订单支付成功
                $fdata = [
                    'uid' => $record->uid,
                    'tradeno' => $outTradeNo,
                    'pay_trade_no' => $tradeNo,
                    'pay_type' => 1,
                    'aid'=>$record->aid
                ];
                $wm_order_event_bll = new \wm_order_event_bll();
                $result = $wm_order_event_bll->notifyPaidSuccess($fdata, true);

                return $result ? true : false;
            }

        } else {
            return false;
        }
    }
}
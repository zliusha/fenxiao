<?php
use Service\DbFrame\DataBase\WmShardDbModels\WmPromotionDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmAfsDao;
use Service\DbFrame\DataBase\WmShardDbModels\wmPaymentRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponSettingDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDb;
/**
 * 定时处理任务
 */
class Task extends CI_Controller
{

    public function retry_redfund()
    {
        $input['afsno'] = '17121817170566';
        $input['aid'] = 1226;
        $wmAfsDao = WmAfsDao::i($input['aid']);
        $wmPaymentRecordDao = wmPaymentRecordDao::i($input['aid']);
        // 获取售后单信息
        $afs = $wmAfsDao->getOneArray(['afsno' => $input['afsno']]);

        // 调用微信退款接口
        // 查询付款信息
        $pay_order = $wmPaymentRecordDao->getOneArray(['code' => $afs['tid'], 'gateway' => 'weixin', 'status' => 1]);

        if (!$pay_order) {
            $this->unlock();
            $this->json_do->set_error('004', '支付信息异常');
        }

        ci_wxpay::load('jsapi', $afs['aid']);

        $total_fee = bcmul($pay_order['money'], 100);
        $refund_fee = bcmul($afs['tk_money'], 100);
        $wx_input = new WxPayRefund();
        $wx_input->SetTransaction_id($pay_order['trade_no']);
        $wx_input->SetTotal_fee($total_fee);
        $wx_input->SetRefund_fee($refund_fee);
        $wx_input->SetOut_refund_no(WXPAY_MCHID . date("YmdHis"));
        $wx_input->SetOp_user_id(WXPAY_MCHID);
        $result = WxPayApi::refund($wx_input);

        pdump($result);exit;
    }

    // 促销活动开始
    public function promoStart()
    {
        $wmPromotionDao = WmPromotionDao::i();
        $result = $wmPromotionDao->update(['status' => 2], ['start_time <= ' => time(), 'status' => 1]);

        echo 'ok';
    }

    // 促销活动结束
    public function promoEnd()
    {
        $wmShardDb = WmShardDb::i();


        $timestamp = time();

        // 释放总店商品库活动数据
        $sql = "UPDATE `{$wmShardDb->tables['wm_store_goods']}` SET promo_id = 0 WHERE promo_id IN (SELECT `id` FROM `{$wmShardDb->tables['wm_promotion']}` WHERE `status` = 2 AND  `end_time` <= {$timestamp} AND `shop_id` = 0);";
        $wmShardDb->simple_query($sql);

        // 释放门店商品库活动数据
        $sql = "UPDATE `{$wmShardDb->tables['wm_goods']}` SET promo_id = 0 WHERE promo_id IN (SELECT id FROM `{$wmShardDb->tables['wm_store_goods']}` WHERE `status` = 2 AND  `end_time` <= {$timestamp});";
        $wmShardDb->simple_query($sql);
        $wmPromotionDao = WmPromotionDao::i();
        $wmPromotionDao->update(['status' => 3], ['end_time <= ' => $timestamp, 'status' => 2]);

        echo 'ok';
    }

    // 优惠券活动开始
    public function couponStart()
    {
        $wmCouponSettingDao = WmCouponSettingDao::i();
        $result = $wmCouponSettingDao->update(['status' => 2], ['start_time <= ' => time(), 'status' => 1]);
        echo 'ok';
    }

    // 优惠券活动结束
    public function couponEnd()
    {
        $wmCouponSettingDao = WmCouponSettingDao::i();
        $wmCouponSettingDao->update(['status' => 3], ['end_time <= ' => time(), 'status' => 2]);
        echo 'ok';
    }

    // 优惠券过期
    public function couponExpire()
    {
        $wmCouponDao = WmCouponDao::i();
        $wmCouponDao->update(['status' => 3], ['use_end_time <= ' => time(), 'status' => 1]);

        echo 'ok';
    }

    // 定时取消过期未付款订单
    public function autoCancelOrder()
    {
        // SELECT * FROM `wd_wm_order` WHERE time <= 1503632932 AND `status` = 1010;
        $timestamp = order_pay_expire(time(), true);
        $wmOrderDao = WmOrderDao::i();

        $result = $wmOrderDao->getAllArray(['time <= ' => $timestamp, 'status' => 1010], 'tid,uid,aid', 'id asc', 100);

        $wm_order_event_bll = new wm_order_event_bll();

        foreach ($result as $key => $row) {
            $wm_order_event_bll->notifyUnpaidTimeout(['tradeno' => $row['tid'], 'uid' => $row['uid'],'aid'=>$row['aid']], true);
        }

        echo 'ok';
    }

    // 定时完成商家配送订单（24小时）
    public function autoFinishSellerDeliveryOrder()
    {
        $timestamp = time() - 86400;
        $wmOrderDao = WmOrderDao::i();

        $result = $wmOrderDao->getAllArray(['pay_time <= ' => $timestamp, 'status' => 2030], 'tid, aid', 'id asc', 100);

        $wm_order_event_bll = new wm_order_event_bll();

        foreach ($result as $key => $row) {
            $wm_order_event_bll->sellerOrderDelivered(['tradeno' => $row['tid'], 'aid' => $row['aid']], true);
        }

        echo 'ok';
    }

    // 商家已完成的订单（24小时后过退款申请期）
    public function settleFinishOrder()
    {
        $timestamp = time() - 24 * 3600;
        $wmShardDb = WmShardDb::i();

        $wmOrderDao = WmOrderDao::i();
        $wmShopDao = WmShopDao::i();

        // 售后未处理的订单不会进行结算操作
        // $result = $wmOrderDao->getAllArray(['pay_time <= ' => $timestamp, 'settle_time' => 0], 'id, tid, aid, shop_id, pay_money, freight_money, tk_money, expect_settle_money', 'id asc', 100);
        $result = $wmShardDb->query("SELECT id, tid, aid, shop_id, pay_money, freight_money, tk_money, expect_settle_money FROM `{$wmShardDb->tables['wm_order']}` WHERE pay_time > 0 AND pay_time <= {$timestamp} AND settle_time = 0 AND (afsno = 0 OR (afsno > 0 AND is_afs_finished = 1)) AND `status` IN (6060, 6061) AND expect_settle_money > 0 ORDER BY id DESC LIMIT 100;")->result_array();

        foreach ($result as $key => $row) {
            $update_data = [];
            $update_data['settle_money'] = $row['expect_settle_money'];
            $update_data['settle_time'] = time();

            // if ($update_data['settle_money'] > 0) {
            // 更新订单记录
            $wmOrderDao->update($update_data, ['id' => $row['id']]);
            if ($row['expect_settle_money'] > 0) {
                // 增加金额记录
                $wmShopDao->addMoney($update_data['settle_money'], $row['aid'], $row['shop_id'], '订单编号：' . $row['tid'] . ' 结算收益');
            }
            // } else {
            //     log_message('error', '订单编号：' . $row['tid'] . ' 结算收益');
            // }
        }

        echo 'ok';
    }

    

}

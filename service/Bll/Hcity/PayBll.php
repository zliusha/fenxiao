<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/21
 * Time: 16:55
 */
namespace Service\Bll\Hcity;

use Service\Bll\Hcity\Commission\Config;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionPlatformDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\Enum\BalanceRecordTypeEnum;
use Service\Enum\XcxTemplateMessageEnum;
use Service\Bll\Hcity\Xcx\XcxBll;
use Service\Cache\Hcity\HcityUserCache;
use Service\Bll\BaseBll;
use Service\Bll\Hcity\Xcx\XcxFormIdBll;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityPaymentRecordDao;

class PayBll extends BaseBll
{
    /**
     * 扫码支付
     * @param int $shop_id
     * @param int $pay_tid
     * @param string $body
     * @return bool|\成功时返回
     * @throws \Exception
     */
    public function wxNativePay(int $shop_id, $pay_tid, string $body = '订单支付')
    {
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['shop_id' => $shop_id, 'pay_tid' => $pay_tid, 'pay_type' => 3]);
        if (!$mPaymentRecord || $mPaymentRecord->status == 1) {
            log_message('error', __METHOD__ . ":shop_id={shop_id},pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在或状态异常');
        }

        //测试环境 1 分钱
        if (in_array(ENVIRONMENT, ['development', 'testing'])) {
            $mPaymentRecord->money = 0.01;
        }

        \ci_wxpay::loadHcity('native');
        $tools = new \NativePay();

        // 调用统一下单接口
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach('');
        $input->SetOut_trade_no($pay_tid);
        $input->SetTotal_fee($mPaymentRecord->money * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($body);
        $input->SetNotify_url(API_URL . 'xhcity/paynotify/wxpay');
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($pay_tid);

        return $tools->GetPayUrl($input);
    }

    /**
     * JSAPI支付
     * @param int $uid
     * @param string $openid
     * @param int $pay_tid
     * @param string $body
     * @return \json数据
     * @throws \Exception
     * @throws \WxPayException
     */
    public function wxJsapiPay(int $uid, string $openid, string $pay_tid, string $body = '订单支付')
    {
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['uid' => $uid, 'pay_tid' => $pay_tid, 'pay_type' => 3]);
        if (!$mPaymentRecord || $mPaymentRecord->status == 1) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在或状态异常');
        }

        \ci_wxpay::loadHcity();
        $tools = new \JsApiPay();

        //测试环境 1 分钱
        if (in_array(ENVIRONMENT, ['development', 'testing'])) {
            $mPaymentRecord->money = 0.01;
        }

        // 调用统一下单接口
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach('');
        $input->SetOut_trade_no($pay_tid);
        $input->SetTotal_fee($mPaymentRecord->money * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($body);
        $input->SetNotify_url(API_URL . 'xhcity/paynotify/wxpay');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openid);
        $pay_order = \WxPayApi::unifiedOrder($input);

        if ($pay_order['return_code'] == 'FAIL' || $pay_order['result_code'] == 'FAIL') {
            log_message('error', __METHOD__ . "支付订单异常：" . json_encode($pay_order));
            throw new \Exception('支付订单异常');
        }
        $params = $tools->GetJsApiParameters($pay_order);

        //收集FORMID wx prepay_id
        try {
            $params_arr = json_decode($params, true);
            list($name, $prepay_id) = explode("=", $params_arr['package']);
            $xcxFormIdBll = new XcxFormIdBll();
            $xcxFormIdBll->add(['form_id' => $prepay_id, 'open_id' => $openid], 1);
        } catch (\Exception $e) {
            log_message('error', __METHOD__ . "添加FORMID异常：" . $e->getMessage());
        }
        return $params;
    }

    /**
     * 账户余额支付
     * @param int $uid
     * @param int $pay_tid
     * @param bool $is_trans
     * @return bool
     * @throws \Exception
     */
    public function balancePay(int $uid, string $pay_tid, bool $is_trans = true)
    {
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['uid' => $uid, 'pay_tid' => $pay_tid, 'pay_type' => 2]);
        if (!$mPaymentRecord || $mPaymentRecord->status == 1) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在或状态异常');
        }

        $hcityMainDb = HcityMainDb::i();
        if ($is_trans) {
            $hcityMainDb->trans_start();
        }
        $hcityUserDao = HcityUserDao::i();
        $mHcityUser = $hcityUserDao->getOne(['id' => $uid]);
        if (!$mHcityUser) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            if ($is_trans) $hcityMainDb->trans_rollback();
            throw new \Exception('用户信息不存在');
        }
        //判断用户金额是否足够
        if ($mHcityUser->balance < $mPaymentRecord->money) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            if ($is_trans) $hcityMainDb->trans_rollback();
            throw new \Exception('账户余额不足');
        }
        $options['where'] = [
            'id' => $uid
        ];
        $hcityUserDao->setDec('balance', $mPaymentRecord->money, $options);
        if ($is_trans && $hcityMainDb->trans_status() === FALSE) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            $hcityMainDb->trans_rollback();
            throw new \Exception('支付失败');
        }
        if ($is_trans) $hcityMainDb->trans_complete();

        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();
        // 小程序消息通知
        try {
            $userWxbind = HcityUserWxbindDao::i()->getOne(['uid' => $uid]);
            $params = [
                'openid' => $userWxbind->open_id,
                'type' => '消费成功',
                'change_money' => '-' . $mPaymentRecord->money,
                'change_time' => date('Y-m-d H:i:s', time()),
                'current_money' => bcsub($mHcityUser->balance, $mPaymentRecord->money, 2)
            ];

            (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::MONEY_CHANGE);

        } catch (\Exception $e) {
            log_message('error', __METHOD__ . '通知失败:' . $e->getMessage());
        }

        return true;
    }

    /**
     * 嘿卡币支付
     * @param int $uid
     * @param int $pay_tid
     * @param bool $is_trans
     * @return bool
     * @throws \Exception
     */
    public function heyCoinPay(int $uid, string $pay_tid, bool $is_trans = true)
    {
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['uid' => $uid, 'pay_tid' => $pay_tid, 'pay_type' => 4]);
        if (!$mPaymentRecord || $mPaymentRecord->status == 1) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在或状态异常');
        }

        $hcityMainDb = HcityMainDb::i();
        if ($is_trans) {
            $hcityMainDb->trans_start();
        }
        $hcityUserDao = HcityUserDao::i();
        $mHcityUser = $hcityUserDao->getOne(['id' => $uid]);
        if (!$mHcityUser) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            if ($is_trans) $hcityMainDb->trans_rollback();
            throw new \Exception('用户信息不存在');
        }

//        if($mHcityUser->is_open_hcard!=1 || $mHcityUser->hcard_expire_time<time())
//        {
//            log_message('error',__METHOD__.":uid={$uid},pay_tid={$pay_tid}");
//            if($is_trans) $hcityMainDb->trans_rollback();
//            throw new \Exception('用户未开启嘿卡会员或已到期');
//        }
        //判断用户金额是否足够
        if ($mHcityUser->hey_coin < $mPaymentRecord->money) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            if ($is_trans) $hcityMainDb->trans_rollback();
            throw new \Exception('用户嘿卡币/绑定余额不足');
        }
        $options['where'] = [
            'id' => $uid
        ];
        $hcityUserDao->setDec('hey_coin', $mPaymentRecord->money, $options);
        //更改支付记录状态
        if ($is_trans && $hcityMainDb->trans_status() === FALSE) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            $hcityMainDb->trans_rollback();
            throw new \Exception('支付失败');
        }
        if ($is_trans) $hcityMainDb->trans_complete();

        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();
        return true;
    }

    /**
     * scrm 会员储值支付
     */
    public function scrmPay()
    {

    }

    /**
     * 支付成功通知
     * @param int $uid
     * @param int $pay_tid
     * @param string $trade_no
     * @throws \Exception
     * @throws \Service\Exceptions\Exception
     */
    public function notifyPaySuccess(string $pay_tid, string $trade_no = '')
    {
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();

        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['pay_tid' => $pay_tid]);
        if (!$mPaymentRecord) {
            log_message('error', __METHOD__ . ":pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在');
        }
        if ($mPaymentRecord->status != 0) {
            log_message('error', __METHOD__ . ":pay_tid={$pay_tid}");
            throw new \Exception('支付记录已支付处理');
        }
        switch ($mPaymentRecord->type) {
            case 1://处理订单支付通知
                $orderEventBll = new OrderEventBll();
                $orderEventBll->notifyPaySuccessOrder(['aid' => $mPaymentRecord->aid, 'tid' => $mPaymentRecord->inner_trade_no]);
                break;
            case 2://处理开卡支付
                $this->_notifyHcard($mPaymentRecord->uid, $pay_tid);
                break;
            case 3://会员充值
                $this->_notifyRecharge($mPaymentRecord->uid, $pay_tid);
                break;
            case 4://开通一店一码
                $this->_notifyOpenYdym($pay_tid);
                break;
            case 5://一元体验30天嘿卡会员
                $this->_notifyTrialHcard($mPaymentRecord->uid, $pay_tid);
                break;
            default:
                break;
        }
        //变更记录状态
        $hcityPaymentRecordDao->update(['status' => 1, 'trade_no' => $trade_no], ['pay_tid' => $pay_tid]);
        return true;
    }

    /**
     * 开卡/续费回调处理
     * @param int $uid
     * @param int $pay_tid 支付单号
     * @throws \Exception
     * @throws \Service\Exceptions\Exception
     */
    private function _notifyHcard(int $uid, string $pay_tid)
    {
        $hcityUserDao = HcityUserDao::i();
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['uid' => $uid, 'pay_tid' => $pay_tid], '*', 'id DESC');
        if (!$mPaymentRecord) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在');
        }
        if ($mPaymentRecord->status != 0) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录已支付处理');
        }
        $user = $hcityUserDao->getOne(['id' => $uid]);
        if (!$user) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('用户信息不存在');
        }

        $curr_time = time();
        $is_open_hcard = false;
        //一年时间
        $year_time = 365 * 24 * 3600;

        if ($user->is_open_hcard < 1 || $user->is_trial_hcard == 1)//开卡或体验时，都是首次开卡
        {
            $update['is_open_hcard'] = 1;
            $update['hcard_expire_time'] = $curr_time + $year_time;
            $update['is_trial_hcard'] = 0; //取消嘿卡体验
            $is_open_hcard = true;

            //判断是否送嘿卡币 不是店铺邀请码送嘿卡币
            if (empty($user->inviter_shop_id)) {
                $user_where['where'] = [
                    'id' => $uid
                ];
                $hcityUserDao->setInc('hey_coin', $mPaymentRecord->money, $user_where);
            }

        } else //续费
        {
            if ($user->hcard_expire_time < $curr_time)//过期
            {
                $update['hcard_expire_time'] = $curr_time + $year_time;
            } else//未过期
            {
                $update['hcard_expire_time'] = $user->hcard_expire_time + $year_time;
            }
            $update['is_trial_hcard'] = 0; //取消嘿卡体验
            //送99嘿卡币

            $user_where['where'] = [
                'id' => $uid
            ];
            $hcityUserDao->setInc('hey_coin', $mPaymentRecord->money, $user_where);

        }

        $hcityUserDao->update($update, ['id' => $uid]);
        //累计消费总额
        $user_where['where'] = [
            'id' => $uid
        ];
        $hcityUserDao->setInc('consumption', $mPaymentRecord->money, $user_where);

        //保存余额流水消费
        $recordCreateData = [
            'fid' => create_order_number(),
            'money' => $mPaymentRecord->money,
            'type' => 6,
            'time' => time(),
            'uid' => $uid,
            'remark' => '开嘿卡消费',
            'lower_uid' => 0,
            'lower_shop_id' => 0
        ];
        HcityUserBalanceRecordDao::i()->create($recordCreateData);

        try {
            //发送分佣任务
            if ($is_open_hcard) {
                //YDYM门店内开卡
                if (!empty($user->inviter_shop_id) && $user->inviter_shop_id > 0) {
                    $shop = MainShopDao::i()->getOne(['id' => $user->inviter_shop_id]);
                    if (!$shop)
                        throw new \Exception('门店信息不存在');
                    (new \Service\Bll\Hcity\FinanceBll())->openHcardByMerchant($uid, $shop->aid, $user->inviter_shop_id, $mPaymentRecord->money);
                } else {
                    (new \Service\Bll\Hcity\FinanceBll())->openHcard($uid, $mPaymentRecord->money);
                }

            }
        } catch (\Exception $e) {
            log_message('error', __METHOD__ . '发送开卡分佣任务失败pay_tid:' . $pay_tid);
            log_message('error', $e->getMessage());
        }

        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();
        return true;
    }

    /**
     * 充值成功通知
     * @param int $uid
     * @param int $pay_tid 支付单号
     * @throws \Exception
     * @throws \Service\Exceptions\Exception
     */
    private function _notifyRecharge(int $uid, string $pay_tid)
    {
        $hcityUserDao = HcityUserDao::i();
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['uid' => $uid, 'pay_tid' => $pay_tid], '*', 'id DESC');
        if (!$mPaymentRecord) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在');
        }
        if ($mPaymentRecord->status != 0) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录已支付处理');
        }
        $user = $hcityUserDao->getOne(['id' => $uid]);
        if (!$user) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('用户信息不存在');
        }
        $user_where['where'] = [
            'id' => $uid
        ];
        $return = $hcityUserDao->setInc('balance', $mPaymentRecord->money, $user_where);

        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();

        //保存余额流水
        $createData = [
            'fid' => create_order_number(),
            'money' => $mPaymentRecord->money,
            'type' => BalanceRecordTypeEnum::RECHARGE,
            'time' => time(),
            'uid' => $uid,
            'remark' => '充值',
            'lower_uid' => 0,
            'lower_shop_id' => 0
        ];
        HcityUserBalanceRecordDao::i()->create($createData);
        // 小程序消息通知
        try {
            $userWxbind = HcityUserWxbindDao::i()->getOne(['uid' => $uid]);
            $params = [
                'openid' => $userWxbind->open_id,
                'type' => '充值成功',
                'change_money' => '+' . $mPaymentRecord->money,
                'change_time' => date('Y-m-d H:i:s', time()),
                'current_money' => bcadd($user->balance, $mPaymentRecord->money, 2)
            ];

            (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::MONEY_CHANGE);

        } catch (\Exception $e) {
            log_message('error', __METHOD__ . '通知失败:' . $e->getMessage());
        }

        return $return;
    }

    /**
     * 开通一店一码
     * @param int $uid
     * @param int $pay_tid
     * @return int
     * @throws \Exception
     */
    public function _notifyOpenYdym(string $pay_tid)
    {
        $hcityShopExtDao = HcityShopExtDao::i();
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['pay_tid' => $pay_tid]);
        if (!$mPaymentRecord) {
            log_message('error', __METHOD__ . ":pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在');
        }
        if ($mPaymentRecord->status != 0) {
            log_message('error', __METHOD__ . ":pay_tid={$pay_tid}");
            throw new \Exception('支付记录已支付处理');
        }
        $mShopExt = $hcityShopExtDao->getOne(['aid' => $mPaymentRecord->aid, 'shop_id' => $mPaymentRecord->shop_id]);
        if (!$mShopExt) {
            log_message('error', __METHOD__ . ":pay_tid={$pay_tid}");
            throw new \Exception('门店信息不存在');
        }

        return (new ShopExtBll())->openYDYM($mPaymentRecord->aid, $mPaymentRecord->shop_id);
    }


    /**
     * 一元体验嘿卡
     * @param int $uid
     * @param string $pay_tid
     * @return bool
     * @throws \Exception
     * @author ahe<ahe@iyenei.com>
     */
    private function _notifyTrialHcard(int $uid, string $pay_tid)
    {
        $hcityUserDao = HcityUserDao::i();
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $mPaymentRecord = $hcityPaymentRecordDao->getOne(['uid' => $uid, 'pay_tid' => $pay_tid], '*', 'id DESC');
        if (!$mPaymentRecord) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录不存在');
        }
        if ($mPaymentRecord->status != 0) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('支付记录已支付处理');
        }
        $user = $hcityUserDao->getOne(['id' => $uid]);
        if (!$user) {
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid}");
            throw new \Exception('用户信息不存在');
        }
        $hcityUserDao->db->trans_start();
        try {
            $update['is_open_hcard'] = 1;
            $update['hcard_expire_time'] = time() + 30 * 24 * 3600;
            $update['is_trial_hcard'] = 1; //标记嘿卡体验

            $hcityUserDao->update($update, ['id' => $uid]);
            //累计消费总额
            $user_where['where'] = [
                'id' => $uid
            ];
            $hcityUserDao->setInc('consumption', 1, $user_where);

            $fid = create_order_number();
            //保存消费余额流水
            $recordCreateData = [
                'fid' => $fid,
                'money' => 1,
                'type' => 6,
                'time' => time(),
                'uid' => $uid,
                'remark' => '开通体验会员消费',
                'lower_uid' => 0,
                'lower_shop_id' => 0
            ];
            HcityUserBalanceRecordDao::i()->create($recordCreateData);

            //手动记录平台分佣
            $platformFyData = [
                'fid' => $fid,
                'time' => time(),
                'money' => 1,
                'confirm_status' => 1,
                'confirm_time' => time(),
                'income_type' => Config::TYPE_OPEN_HCARD,
                'ext' => json_encode(['uid' => $uid])
            ];
            HcityCommissionPlatformDao::i()->create($platformFyData);

            $hcityUserDao->db->trans_complete();
        } catch (\Exception $e) {
            $hcityUserDao->db->trans_rollback();
            log_message('error', __METHOD__ . ":uid={$uid},pay_tid={$pay_tid},err=" . $e->getMessage());
            return false;
        }

        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();
        return true;
    }
}

<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/28
 * Time: 下午2:34
 */

namespace Service\Bll\Hcity;


use Service\Bll\BaseBll;
use Service\Bll\Hcity\Commission\Config;
use Service\Bll\Hcity\Commission\QueueParamsBll;
use Service\Bll\Hcity\Commission\SenderBll;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityInviteMerchantViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityQsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityQsTaskDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopFinanceDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityCompanyRedPacketDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderHxDetailDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserRedPacketDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Exceptions\Exception;
use Service\Support\FLock;

/**
 * 核心财务、分佣计算逻辑
 * Class FinanceBll
 * @package Service\Bll\Hcity
 */
class FinanceBll extends BaseBll
{
    /**
     * 开通嘿卡，分佣：一级、二级、城市合伙人、平台（个人区分普通会员、网点合伙人和城市合伙人）并送嘿卡币
     * @param int $uid 当前登录会员id
     * @param float $money 开卡金额
     * @author ahe<ahe@iyenei.com>
     */
    public function openHcard(int $uid, float $money)
    {
        if ($money <= 0) {
            throw new Exception('开卡金额错误');
        }

        $user = HcityUserDao::i()->getOne(['id' => $uid, 'status' => 0]);
        if (empty($user)) {
            throw new Exception('会员不存在或不符合开卡条件');
        }
        //送嘿卡币
//        $options['where'] = [
//            'id' => $uid
//        ];
//        HcityUserDao::i()->setInc('hey_coin', $money, $options);
        if (FLock::getInstance()->lock(__METHOD__ . $uid)) {
            //取得锁
            try {
                //@todo 投递开卡分佣任务
                $params = QueueParamsBll::getInstance()
                    ->setType(Config::TYPE_OPEN_HCARD)
                    ->setFid(create_order_number())
                    ->setMoney($money)
                    ->setRemark($user->username)
                    ->setExt(['uid' => $uid]);

                SenderBll::getInstance()->send($params);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }
    }

    /**
     * 商户邀请码邀请开通嘿卡，分佣：一级、城市合伙人、平台（并送店铺红包）
     * @param int $uid 当前开嘿卡的会员id
     * @param int $aid 店铺所属公司id
     * @param string $shopId 店铺id
     * @param float $money 开卡金额
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function openHcardByMerchant(int $uid, int $aid, string $shopId, float $money)
    {
        $shop = HcityShopExtDao::i()->getOne(['shop_id' => $shopId, 'barcode_status' => 1, 'barcode_expire_time >' => time()]);
        if (empty($shop)) {
            throw new Exception('店铺未开通一店一码');
        }
        $packet = ShcityCompanyRedPacketDao::i(['aid' => $aid])->getOne(['aid' => $aid, 'uid' => $uid, 'shop_id' => $shopId]);
        if (!empty($packet)) {
            //当前只能第一次开卡送红包
            throw new Exception('当前会员已开通嘿卡，到期请进行续费。');
        }
        $user = HcityUserDao::i()->getOne(['id' => $uid, 'status' => 0]);
        if (empty($user)) {
            throw new Exception('会员不存在或不符合开卡条件');
        }
        if (FLock::getInstance()->lock(__METHOD__ . $uid)) {
            //取得锁
            try {
                //@todo 发店铺红包
                $companyCreateData = [
                    'aid' => $aid,
                    'uid' => $uid,
                    'shop_id' => $shopId,
                    'username' => $user->username,
                    'mobile' => $user->mobile,
                    'balance' => $money,
                    'status' => 1,
                    'time' => time(),
                    'expire_time' => $shop->barcode_expire_time
                ];
                $companyInsertId = ShcityCompanyRedPacketDao::i(['aid' => $aid])->create($companyCreateData);
                if ($companyInsertId > 0) {
                    $userCreateData = [
                        'aid' => $aid,
                        'uid' => $uid,
                        'shop_id' => $shopId,
                        'time' => $companyCreateData['time'],
                        'expire_time' => $companyCreateData['expire_time'],
                        'money' => $money,
                        'red_packet_id' => $companyInsertId,
                        'status' => 1
                    ];
                    ShcityUserRedPacketDao::i(['uid' => $uid])->create($userCreateData);
                }
                //@todo 投递开卡分佣任务
                $params = QueueParamsBll::getInstance()
                    ->setType(Config::TYPE_OPEN_HCARD_BY_MERCHANT)
                    ->setFid(create_order_number())
                    ->setMoney($money)
                    ->setRemark($user->username)
                    ->setExt(['shop_id' => $shopId]);

                SenderBll::getInstance()->send($params);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }
    }

    /**
     * 邀请开通一店一码，分佣：一级、二级、城市合伙人、平台
     * @param int $shopId 当前开通一店一码的店铺id
     * @param float $money 开通金额
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function openBarcode(int $shopId, float $money)
    {
        $mainShop = MainShopDao::i()->getOne(['id' => $shopId]);
        if (empty($mainShop)) {
            throw new Exception('店铺不存在');
        }
        if (FLock::getInstance()->lock(__METHOD__ . $shopId)) {
            //取得锁
            try {
                $shopInvite = HcityInviteMerchantViewDao::i()->getOne(['shop_id' => $shopId]);
                if (!empty($shopInvite->inviter_uid)) {
                    $knight = HcityQsDao::i()->getOne(['uid' => $shopInvite->inviter_uid, 'status' => 1]);
                    if (!empty($knight)) {
                        //存在有效骑士，标记任务状态
                        $update = [
                            'ydym_task_status' => 1//完成开通一店一码任务
                        ];
                        $this->_saveKnightTask($shopInvite->inviter_uid, $shopInvite->aid, $shopInvite->shop_id, $update);
                    }
                }
                //@todo 投递开卡分佣任务
                $params = QueueParamsBll::getInstance()
                    ->setType(Config::TYPE_OPEN_BARCODE)
                    ->setFid(create_order_number())
                    ->setMoney($money)
                    ->setRemark($mainShop->shop_name)
                    ->setExt(['shop_id' => $shopId]);

                SenderBll::getInstance()->send($params);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                //释放锁
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }
    }


    /**
     * 入驻商圈商品交易，分佣：一级、二级、城市合伙人、平台
     * @param int $uid 当前购买商品的会员id
     * @param int $shopId 当前购买店铺id
     * @param int $tid 当前订单交易流水号
     * @param int $sourceType 来源入口 1商圈订单 2一店一码订单
     * @param array $data 核销封装数据
     *      - hx_code 核销码
     *      - pay_money 单个商品实付金额
     *      - cost_money 单个商品成本金额
     *      - discount_money 单个商品优惠金额
     * 格式:[
     *          ['hx_code'=>'11111','pay_money'=>10,'cost_money'=>10,'discount_money'=>0],
     *          ['hx_code'=>'22222','pay_money'=>10,'cost_money'=>10,'discount_money'=>2]
     *      ]
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function hcityGoodsTrade(int $uid, int $shopId, int $tid, array $data, int $sourceType)
    {
        if (FLock::getInstance()->lock(__METHOD__ . $tid)) {
            //取得锁
            try {
                foreach ($data as $item) {
                    if (!isset($item['pay_money']) || !isset($item['cost_money']) || !isset($item['discount_money']) || !isset($item['hx_code'])) {
                        throw new Exception('数据格式错误');
                    }
                    //@todo 交易分佣计算
                    //结算金额 = 实付金额-成本金额    结算金额＞０,分佣；否则不分但存分拥记录
                    $jsMoney = $item['pay_money'] - $item['cost_money'] > 0 ? $item['pay_money'] - $item['cost_money'] : 0;
                    $params = QueueParamsBll::getInstance()
                        ->setType(Config::TYPE_GOODS_TRADE)
                        ->setFid($tid)
                        ->setMoney($jsMoney)
                        ->setRemark('商品交易')
                        ->setExt(['hx_code' => $item['hx_code'], 'uid' => $uid, 'shop_id' => $shopId]);
                    SenderBll::getInstance()->send($params);
                    //@todo 商家结算
                    //商家结算金额 = 成本金额 - 优惠金额
                    $bMoney = $item['cost_money'] - $item['discount_money'] > 0 ? $item['cost_money'] - $item['discount_money'] : 0;
                    $params = QueueParamsBll::getInstance()
                        ->setType(Config::TYPE_MERCHANT_PRE_JS)
                        ->setFid($tid)
                        ->setMoney($bMoney)
                        ->setRemark('入驻商圈商品交易商家预结算')
                        ->setExt(['hx_code' => $item['hx_code'], 'shop_id' => $shopId, 'source_type' => $sourceType]);
                    SenderBll::getInstance()->send($params);
                }
            } catch (\Exception $e) {
                throw $e;
            } finally {
                //释放锁
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }
    }

    /**
     * 普通商品（未入驻商圈商品）交易，不分佣，只对商家成本结算
     * @param int $shopId 当前购买店铺id
     * @param int $tid 当前订单交易流水号
     * @param array $data 核销封装数据
     *      - pay_money 单个商品实付金额
     * 格式:[
     *          ['hx_code'=>'11111','pay_money'=>10],
     *          ['hx_code'=>'22222','pay_money'=>20]
     *      ]
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function goodsTrade(int $shopId, int $tid, array $data)
    {
        if (FLock::getInstance()->lock(__METHOD__ . $tid)) {
            //取得锁
            try {
                foreach ($data as $item) {
                    if (!isset($item['pay_money']) || !isset($item['hx_code'])) {
                        throw new Exception('数据格式错误');
                    }
                    //@todo 商家结算
                    //商家结算金额 = 实付金额
                    $bMoney = $item['pay_money'];
                    $params = QueueParamsBll::getInstance()
                        ->setType(Config::TYPE_MERCHANT_PRE_JS)
                        ->setFid($tid)
                        ->setMoney($bMoney)
                        ->setRemark('未入驻商圈商品交易商家预结算')
                        ->setExt(['hx_code' => $item['hx_code'], 'shop_id' => $shopId, 'source_type' => 2]);
                    SenderBll::getInstance()->send($params);
                }
            } catch (\Exception $e) {
                throw $e;
            } finally {
                //释放锁
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }
    }

    /**
     * 福利池商品交易，支付超出部分分佣：一级、二级、城市合伙人、平台
     * @param int $uid 当前购买商品的会员id
     * @param int $shopId 当前购买店铺id
     * @param int $tid 当前订单交易流水号
     * @param array $data 核销封装数据
     *      - hx_code 核销码
     *      - over_money 支付超出部分
     * 格式:[['hx_code'=>'11111','over_money'=>10],['hx_code'=>'22222','over_money'=>10]]]
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function welfareTrade(int $uid, int $shopId, int $tid, array $data)
    {
        if (FLock::getInstance()->lock(__METHOD__ . $tid)) {
            //取得锁
            try {
                foreach ($data as $item) {
                    if (!isset($item['over_money']) || !isset($item['hx_code'])) {
                        throw new Exception('数据格式错误');
                    }
                    $params = QueueParamsBll::getInstance()
                        ->setType(Config::TYPE_WELFARE_TRADE)
                        ->setFid($tid)
                        ->setMoney($item['over_money'])
                        ->setRemark('福利池商品交易')
                        ->setExt(['hx_code' => $item['hx_code'], 'uid' => $uid, 'shop_id' => $shopId]);
                    SenderBll::getInstance()->send($params);
                }
            } catch (\Exception $e) {
                throw $e;
            } finally {
                //释放锁
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }
    }


    /**
     * 活动商品交易，分佣：一级、二级、城市合伙人、平台
     * @param int $uid 当前购买商品的会员id
     * @param int $shopId 当前购买店铺id
     * @param int $tid 当前订单交易流水号
     * @param int $sourceType 来源入口 1商圈订单 2一店一码订单
     * @param array $data 核销封装数据
     *      - hx_code 核销码
     *      - fy_money 单个商品分佣金额
     *      - js_money 单个商品结算金额
     * 格式:[
     *          ['hx_code'=>'11111','fy_money'=>10,'js_money'=>10]
     *      ]
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function activityGoodsTrade(int $uid, int $shopId, int $tid, array $data, int $sourceType)
    {
        if (FLock::getInstance()->lock(__METHOD__ . $tid)) {
            //取得锁
            try {
                foreach ($data as $item) {
                    if (!isset($item['fy_money']) || !isset($item['js_money'])) {
                        throw new Exception('数据格式错误');
                    }
                    //@todo 活动商品交易分佣
                    $params = QueueParamsBll::getInstance()
                        ->setType(Config::TYPE_GOODS_TRADE)
                        ->setFid($tid)
                        ->setMoney($item['fy_money'])
                        ->setRemark('商品交易')
                        ->setExt(['hx_code' => $item['hx_code'], 'uid' => $uid, 'shop_id' => $shopId]);
                    SenderBll::getInstance()->send($params);
                    //@todo 商家结算
                    $params = QueueParamsBll::getInstance()
                        ->setType(Config::TYPE_MERCHANT_PRE_JS)
                        ->setFid($tid)
                        ->setMoney($item['js_money'])
                        ->setRemark('活动商品交易商家预结算')
                        ->setExt(['hx_code' => $item['hx_code'], 'shop_id' => $shopId, 'source_type' => $sourceType]);
                    SenderBll::getInstance()->send($params);
                }
            } catch (\Exception $e) {
                throw $e;
            } finally {
                //释放锁
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }
    }

    /**
     * 核销订单,确认分佣
     * @param int $tid
     * @param int $hxCode
     * @param float $hxMoney
     * @author ahe<ahe@iyenei.com>
     */
    public function hxOrder(int $tid, int $hxCode)
    {
        if (FLock::getInstance()->lock(__METHOD__ . $tid . $hxCode)) {
            //取得锁
            try {
                $params = QueueParamsBll::getInstance()
                    ->setType(Config::TYPE_HEXIAO)
                    ->setFid($tid)
                    ->setMoney(0)
                    ->setRemark('核销订单')
                    ->setExt(['hx_code' => $hxCode]);
                SenderBll::getInstance()->send($params);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                //释放锁
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }
    }

    /**
     * 取消订单核销
     * @param int $tid 订单号
     * @author ahe<ahe@iyenei.com>
     */
    public function hxCancel(int $tid)
    {
        if (FLock::getInstance()->lock(__METHOD__ . $tid)) {
            //取得锁
            try {
                $params = QueueParamsBll::getInstance()
                    ->setType(Config::TYPE_HEXIAO_INVALID)
                    ->setFid($tid)
                    ->setMoney(0)
                    ->setRemark('订单核销失效')
                    ->setExt(['tid' => $tid]);
                SenderBll::getInstance()->send($params);
            } catch (\Exception $e) {
                throw $e;
            } finally {
                //释放锁
                FLock::getInstance()->unlock();
            }
        } else {
            throw new Exception('操作过于频繁');
        }

    }

    /**
     * 商家周期结算打款
     * @param int $aid
     * @param int $shopId
     * @author ahe<ahe@iyenei.com>
     */
    public function doMerchantJs()
    {
        $hasNext = true;
        while ($hasNext) {
            $hcityMainDb = HcityMainDb::i();
            $hcityMainDb->trans_start();
            try {
                $limit = 1000;
                $shopFinance = HcityShopFinanceDao::i()->getAll(['confirm_status' => 1, 'js_status' => 0], '*', 'id asc', $limit);
                if (count($shopFinance) < 1000) {
                    $hasNext = false;
                }
                if (!empty($shopFinance)) {
                    //更新结算状态
                    $idList = array_column($shopFinance, 'id');
                    $options = [
                        'where_in' => ['id' => $idList]
                    ];
                    HcityShopFinanceDao::i()->updateExt(['js_status' => 1], $options);
                    //给店铺添加余额
                    $tmp = [];
                    array_walk($shopFinance, function ($item) use (&$tmp) {
                        $tmp[$item->shop_id][] = $item;
                    });
                    foreach ($tmp as $shopId => $value) {
                        $jsMoney = array_sum(array_column($value, 'money'));
                        if ($jsMoney > 0) {
                            $options['where'] = [
                                'shop_id' => $shopId
                            ];
                            HcityShopExtDao::i()->setInc('balance', $jsMoney, $options);
                            HcityShopExtDao::i()->setInc('income', $jsMoney, $options);
                        }
                    }
                }
                if ($hcityMainDb->trans_status()) {
                    $hcityMainDb->trans_complete();
                } else {
                    $hcityMainDb->trans_rollback();
                }
            } catch (\Throwable $e) {
                $hcityMainDb->trans_rollback();
                throw $e;
            }
        }
    }

    /**
     * 骑士奖励
     * @param int $shopId
     * @throws Exception
     * @throws \Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function knightReward(int $shopId)
    {
        if (!FLock::getInstance()->lock(__METHOD__ . $shopId)) {
            throw new Exception('操作过于频繁');
        }
        //取得锁
        try {
            //验证店铺有效性
            if (!$this->_knightInviteShop($shopId)) {
                throw new Exception('不是有效店铺');
            }
            $shopInvite = HcityInviteMerchantViewDao::i()->getOne(['shop_id' => $shopId]);
            if (empty($shopInvite->inviter_uid)) {
                log_message('error', __METHOD__ . '发放骑士奖励失败:非会员邀请:' . json_encode(['shop_id' => $shopId]));
                throw new Exception('非会员邀请');
            }
            $knight = HcityQsDao::i()->getOne(['uid' => $shopInvite->inviter_uid, 'status' => 1]);
            if (empty($knight)) {
                log_message('error', __METHOD__ . '发放骑士奖励失败:骑士不存在:' . json_encode(['uid' => $shopInvite->inviter_uid, 'status' => 1]));
                throw new Exception('骑士不存在');
            }

            $qsTask = HcityQsTaskDao::i()->getOne(['uid' => $shopInvite->inviter_uid, 'shop_id' => $shopId]);

            if (empty($qsTask) || $qsTask->shop_task_status == 0) {
                //投递店铺拉新任务
                $params = QueueParamsBll::getInstance()
                    ->setType(Config::TYPE_KNIGHT_INVITE_SHOP)
                    ->setFid(create_order_number())
                    ->setMoney(20)
                    ->setRemark('商家拉新任务')
                    ->setExt(['uid' => $shopInvite->inviter_uid, 'shop_id' => $shopId]);
                SenderBll::getInstance()->send($params);
                //更新任务状态
                $update = [
                    'shop_task_status' => 1//完成店铺拉新任务
                ];
                $this->_saveKnightTask($shopInvite->inviter_uid, $shopInvite->aid, $shopInvite->shop_id, $update);
            }

            if ($knight->newbie_task_status == 0) {
                //邀请两个有效店铺
                $shopNum = HcityQsTaskDao::i()->getCount(['uid' => $shopInvite->inviter_uid, 'shop_task_status' => 1]);
                if ($shopNum >= 2) {
                    //已完成新手任务，投递奖励任务
                    //骑士拉新任务
                    $fid = create_order_number();
                    $params = QueueParamsBll::getInstance()
                        ->setType(Config::TYPE_KNIGHT_INVITE_KNIGHT)
                        ->setFid($fid)
                        ->setMoney(20)
                        ->setRemark('骑士拉新任务')
                        ->setExt(['uid' => $shopInvite->inviter_uid]);
                    SenderBll::getInstance()->send($params);
                    //骑士新手任务
                    $params = QueueParamsBll::getInstance()
                        ->setType(Config::TYPE_KNIGHT_NEWBIE)
                        ->setFid($fid)
                        ->setMoney(20)
                        ->setRemark('骑士新手任务')
                        ->setExt(['uid' => $shopInvite->inviter_uid]);
                    SenderBll::getInstance()->send($params);
                    //更新完成新手任务
                    HcityQsDao::i()->update(['newbie_task_status' => 1], ['id' => $knight->id]);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            //释放锁
            FLock::getInstance()->unlock();
        }
    }

    /**
     * 标记骑士邀请店铺是否有效
     * @param int $shopId
     * @return bool
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    private function _knightInviteShop(int $shopId)
    {
        //验证邀请商家有效性
        $shopExt = HcityShopExtDao::i()->getOne(['shop_id' => $shopId], 'qs_shop_status');
        if ($shopExt->qs_shop_status == 1) {
            return true;
        }
        //验证商家是否入驻商圈
        $shop = HcityShopExtDao::i()->getOne(['shop_id' => $shopId]);
        if (empty($shop) || $shop->hcity_show_status != 1) {
            return false;
        }
        //验证商家是否发布总价值100元的福利池商品
        $hcityMainDb = HcityMainDb::i();
        $sql = sprintf("select IFNULL(sum(hcard_price * free_num),0) as total_price from %s where aid = %d and shop_id = %d", $hcityMainDb->tables['hcity_welfare_goods'], $shop->aid, $shopId);
        $welfareGoods = $hcityMainDb->query($sql)->row();
        if ($welfareGoods->total_price < 100) {
            return false;
        }
        //验证是否完成一笔有效核销
        $order = ShcityOrderDao::i(['aid' => $shop->aid])->getOne(['aid' => $shop->aid, 'shop_id' => $shopId, 'status' => 2]);
        if (empty($order)) {
            $orderArr = ShcityOrderDao::i(['aid' => $shop->aid])->getAllArray(['aid' => $shop->aid, 'shop_id' => $shopId]);
            if (!empty($orderArr)) {
                $tids = array_column($orderArr, 'tid');
                $sql = sprintf("aid = %d and tid in (%s) and hx_status = 1", $shop->aid, implode(',', $tids));
                $hxDetail = ShcityOrderHxDetailDao::i(['aid' => $shop->aid])->getOne($sql);
                if (empty($hxDetail)) {
                    return false;
                }
            } else {
                return false;
            }
        }
        HcityShopExtDao::i()->update(['qs_shop_status' => 1], ['shop_id' => $shopId]);
        return true;
    }

    /**
     * 保存骑士任务
     * @param int $uid
     * @param int $aid
     * @param int $shopId
     * @param array $update
     * @author ahe<ahe@iyenei.com>
     */
    private function _saveKnightTask(int $uid, int $aid, int $shopId, array $update = [])
    {
        $qsTask = HcityQsTaskDao::i()->getOne(['uid' => $uid, 'shop_id' => $shopId]);
        if (empty($qsTask)) {
            $data = [
                'uid' => $uid,
                'aid' => $aid,
                'shop_id' => $shopId,
                'shop_task_status' => 0,
                'ydym_task_status' => 0,
            ];
            $data = array_merge($data, $update);
            HcityQsTaskDao::i()->create($data);
        } else {
            $data = [
                'uid' => $uid,
                'aid' => $aid,
                'shop_id' => $shopId
            ];
            HcityQsTaskDao::i()->update($update, $data);
        }
    }
}
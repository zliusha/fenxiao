<?php


/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/26
 * Time: 下午9:24
 */
namespace Service\Bll\Hcity\Commission;

use Service\Bll\Hcity\Xcx\XcxBll;
use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionQueueDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityQsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Enum\BalanceRecordTypeEnum;
use Service\Enum\XcxTemplateMessageEnum;
use Service\Exceptions\Exception;

class QueueItemBll
{
    /** @var float 分佣金额 */
    private $money;

    /**
     * @var array 任务数据源
     * [
     *  'task_id' => $taskId,
     *  'fid' => $queue->fid,
     *  'money' => $queue->money,
     *  'remark' => $queue->remark,
     *  'type' => $queue->type,
     *  'ext' => $queue->ext
     * ]
     */
    private $task;


    public function __construct(array $task)
    {
        $this->money = $task['money'];
        $this->task = $task;
    }

    /**
     * 执行财务计算
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function run()
    {
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        try {
            $ext = json_decode($this->task['ext']);
            switch ($this->task['type']) {
                case Config::TYPE_GOODS_TRADE:
                    //正常商品交易
                    $this->_goodsTrade($ext->uid, $ext->shop_id, $ext->hx_code);
                    break;
                case Config::TYPE_WELFARE_TRADE:
                    //福利池商品交易
                    $this->_welfareTrade($ext->uid, $ext->shop_id, $ext->hx_code);
                    break;
                case Config::TYPE_OPEN_HCARD:
                    //TYPE_OPEN_HCARD
                    $this->_openHcard($ext->uid);
                    break;
                case Config::TYPE_OPEN_HCARD_BY_MERCHANT:
                    //商户邀请码邀请开通嘿卡
                    $this->_openHcardByMerchant($ext->shop_id);
                    break;
                case Config::TYPE_OPEN_BARCODE:
                    //邀请开通一店一码
                    $this->_openBarcode($ext->shop_id);
                    break;
                case Config::TYPE_HEXIAO:
                    //核销订单
                    $this->_hxOrder($ext->hx_code);
                    break;
                case Config::TYPE_HEXIAO_INVALID:
                    //核销失效
                    $this->_hxCancel();
                    break;
                case Config::TYPE_MERCHANT_PRE_JS:
                    //商家预结算
                    $this->_merchantJs($ext->shop_id, $ext->hx_code, $ext->source_type);
                    break;
                case Config::TYPE_KNIGHT_INVITE_KNIGHT:
                    //骑士拉新任务
                    $this->_knightInviteKnight($ext->uid);
                    break;
                case Config::TYPE_KNIGHT_INVITE_SHOP:
                    //骑士商家拉新任务
                    $this->_knightInviteShop($ext->uid, $ext->shop_id);
                    break;
                case Config::TYPE_KNIGHT_NEWBIE:
                    //骑士新手任务
                    $this->_knightNewbie($ext->uid);
                    break;
                default:
                    throw new Exception('类型错误');
                    break;
            }
            //更新任务进度为已计算
            $this->updateQueueStatus(1);
            $hcityMainDb->trans_complete();

        } catch (\Throwable $e) {
            $hcityMainDb->trans_rollback();
            throw $e;
        }

    }

    /**
     * 更新任务状态
     * @param int $status
     * @author ahe<ahe@iyenei.com>
     */
    public function updateQueueStatus(int $status)
    {
        HcityCommissionQueueDao::i()->update(['status' => $status], ['id' => $this->task['task_id']]);
    }

    /**
     * 获取任务数据源
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getTaskData()
    {
        return $this->task;
    }

    /**
     * 商家结算
     * @param int $shopId
     * @param int $hxCode
     * @param int $sourceType
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    private function _merchantJs(int $shopId, int $hxCode, int $sourceType)
    {
        $mainShop = MainShopDao::i()->getOne(['id' => $shopId]);
        if (empty($mainShop)) {
            throw new Exception('店铺不存在');
        }

        $taskParams = (new CalculateParamsBll())
            ->setFid($this->task['fid'])//设置流水号
            ->setHxcode($hxCode)//设置核销码
            ->setMoney($this->money)//设置分佣金额
            ->setType(Config::TYPE_MERCHANT_PRE_JS)//设置分佣类型
            ->setSourceData($this->task)//设置来源数据
            ->setRemark($this->task['remark']);//设置分佣备注

        (new CalculateBll($taskParams))->doMerchantPreJs($mainShop->aid, $shopId, $sourceType);//商家预结算
    }

    /**
     * 正常商品交易，分佣：一级、二级、城市合伙人、平台
     * 福利池商品交易，支付超出部分分佣：一级、二级、城市合伙人、平台
     * @param int $uid
     * @param int $shopId
     * @param int $hxCode
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    private function _goodsTrade(int $uid, int $shopId, int $hxCode)
    {
        $mainShop = MainShopDao::i()->getOne(['id' => $shopId], 'aid,region');
        if (empty($mainShop)) {
            throw new Exception('店铺不存在');
        }
        $taskParams = (new CalculateParamsBll())
            ->setConfirm(false)//设置是否确认分佣
            ->setFid($this->task['fid'])//设置流水号
            ->setHxcode($hxCode)//设置核销码
            ->setMoney($this->money)//设置分佣金额
            ->setType(Config::TYPE_GOODS_TRADE)//设置分佣类型
            ->setSourceData($this->task)//设置来源数据
            ->setLevelRel($uid)//设置一二级等级
            ->setHcityPartner($mainShop->region)//设置城市合伙人
            ->setShopInviter($mainShop->aid, $shopId)//设置店铺邀请人
            ->setRemark($this->task['remark']);//设置分佣备注

        (new CalculateBll($taskParams))->doCal();//执行计算
    }

    /**
     * 福利池商品交易，支付超出部分分佣：一级、二级、城市合伙人、平台
     * @param int $uid
     * @param int $shopId
     * @param int $hxCode
     * @author ahe<ahe@iyenei.com>
     */
    private function _welfareTrade(int $uid, int $shopId, int $hxCode)
    {
        $mainShop = MainShopDao::i()->getOne(['id' => $shopId], 'aid,region');
        if (empty($mainShop)) {
            throw new Exception('店铺不存在');
        }
        $taskParams = (new CalculateParamsBll())
            ->setConfirm(false)//设置是否确认分佣
            ->setFid($this->task['fid'])//设置流水号
            ->setHxcode($hxCode)//设置核销码
            ->setMoney($this->money)//设置分佣金额
            ->setType(Config::TYPE_WELFARE_TRADE)//设置分佣类型
            ->setSourceData($this->task)//设置来源数据
            ->setLevelRel($uid)//设置一二级等级
            ->setHcityPartner($mainShop->region)//设置城市合伙人
            ->setShopInviter($mainShop->aid, $shopId)//设置店铺邀请人
            ->setRemark($this->task['remark']);//设置分佣备注

        (new CalculateBll($taskParams))->doCal();//执行计算
    }

    /**
     * 个人邀请开通嘿卡，分佣：一级、二级、城市合伙人、平台（个人区分普通会员、网点合伙人和城市合伙人）并送嘿卡币
     * @param int $uid
     * @author ahe<ahe@iyenei.com>
     */
    private function _openHcard(int $uid)
    {
        $taskParams = (new CalculateParamsBll())
            ->setConfirm(true)//设置是否确认分佣
            ->setFid($this->task['fid'])//设置流水号
            ->setMoney($this->money)//设置分佣金额
            ->setType(Config::TYPE_OPEN_HCARD)//设置分佣类型
            ->setSourceData($this->task)//设置来源数据
            ->setLevelRel($uid)//设置一二级等级
            ->setHcityPartnerByRel($uid)//设置城市合伙人
            ->setRemark($this->task['remark']);//设置分佣备注

        (new CalculateBll($taskParams))->doCal();//执行计算
        //送嘿卡币（此处不做处理，放投递任务时处理）
    }

    /**
     * 商户邀请码邀请开通嘿卡，分佣：一级、城市合伙人、平台（并送店铺红包）
     * @param string $shopId 店铺id
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    private function _openHcardByMerchant(int $shopId)
    {
        $shop = HcityShopExtDao::i()->getOne(['shop_id' => $shopId, 'barcode_status' => 1, 'barcode_expire_time >' => time()]);
        if (empty($shop)) {
            throw new Exception('店铺不存在');
        }
        $mainShop = MainShopDao::i()->getOne(['id' => $shopId]);
        if (empty($mainShop)) {
            throw new Exception('店铺不存在');
        }
        $taskParams = (new CalculateParamsBll())
            ->setConfirm(true)//设置是否确认分佣
            ->setFid($this->task['fid'])//设置流水号
            ->setMoney($this->money)//设置分佣金额
            ->setType(Config::TYPE_OPEN_HCARD_BY_MERCHANT)//设置分佣类型
            ->setSourceData($this->task)//设置来源数据
            ->setHcityPartner($mainShop->region)//设置城市合伙人
            ->setRemark($this->task['remark']);//设置分佣备注

        (new CalculateBll($taskParams))->doMerchantCal($mainShop->aid, $shopId);//执行计算
        //送店铺红包（此处不做处理，放投递任务时处理）
    }

    /**
     * 邀请开通一店一码，分佣：一级、二级、城市合伙人、平台
     * @param int $shopId
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    private function _openBarcode(int $shopId)
    {
        $mainShop = MainShopDao::i()->getOne(['id' => $shopId]);
        if (empty($mainShop)) {
            throw new Exception('店铺不存在');
        }
        $taskParams = (new CalculateParamsBll())
            ->setConfirm(true)//设置是否确认分佣
            ->setFid($this->task['fid'])//设置流水号
            ->setMoney($this->money)//设置分佣金额
            ->setType(Config::TYPE_OPEN_BARCODE)//设置分佣类型
            ->setSourceData($this->task)//设置来源数据
            ->setLevelRel($shopId, true)//设置一二级等级
            ->setHcityPartner($mainShop->region)//设置城市合伙人
            ->setShopInviter($mainShop->aid, $shopId)//设置店铺邀请人
            ->setRemark($this->task['remark']);//设置分佣备注

        (new CalculateBll($taskParams))->doCal();//执行计算
    }

    /**
     * 核销订单，确认分佣
     * @param int $hxCode
     * @author ahe<ahe@iyenei.com>
     */
    private function _hxOrder(int $hxCode)
    {
        $taskParams = (new CalculateParamsBll())
            ->setFid($this->task['fid'])//设置流水号
            ->setHxcode($hxCode)//设置核销码
            ->setType(Config::TYPE_HEXIAO);//设置分佣类型

        (new CalculateBll($taskParams))->doConfirm();//执行核销
    }

    /**
     * 设置核销失效
     * @author ahe<ahe@iyenei.com>
     */
    private function _hxCancel()
    {
        $taskParams = (new CalculateParamsBll())
            ->setFid($this->task['fid'])//设置流水号
            ->setType(Config::TYPE_HEXIAO_INVALID);//设置分佣类型

        (new CalculateBll($taskParams))->doCancel();//设置核销失效
    }

    /**
     * 骑士新手任务
     * @param int $uid
     * @author ahe<ahe@iyenei.com>
     */
    public function _knightNewbie(int $uid)
    {
        //给骑士奖励
        $options = [
            'where' => ['id' => $uid]
        ];
        HcityUserDao::i()->setInc('balance', $this->money, $options);
        HcityUserDao::i()->setInc('income', $this->money, $options);
        HcityQsDao::i()->setInc('income', $this->money, ['where' => ['uid' => $uid]]);
        //记录骑士余额流水
        $recordCreateData = [
            'fid' => $this->task['fid'],
            'money' => $this->money,
            'type' => BalanceRecordTypeEnum::KNIGHT_NEWBIE,
            'time' => time(),
            'uid' => $uid,
            'remark' => $this->task['remark'],
            'lower_uid' => 0,
            'lower_shop_id' => 0
        ];
        HcityUserBalanceRecordDao::i()->create($recordCreateData);
        //投递小程序消息通知任务
        $userWx = HcityUserWxbindDao::i()->getOne(['uid' => $uid]);
        $user = HcityUserDao::i()->getOne(['id' => $uid], 'balance');
        if (!empty($userWx)) {
            $params = [
                'openid' => $userWx->open_id,
                'type' => '骑士新手任务达成收益',
                'change_money' => '+' . $this->money,
                'change_time' => date('Y-m-d H:i:s', time()),
                'current_money' => $user->balance
            ];
            (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::MONEY_CHANGE);
        }
        (new HcityUserCache(['uid' => $uid]))->delete();
    }

    /**
     * 骑士拉新
     * @param int $uid
     * @param int $lowerUid
     * @author ahe<ahe@iyenei.com>
     */
    public function _knightInviteKnight(int $uid)
    {
        //给上级邀请骑士奖励
        $user = HcityUserDao::i()->getOne(['id' => $uid]);
        if (!empty($user->inviter_uid)) {
            $inviter = HcityUserDao::i()->getOne(['id' => $user->inviter_uid]);
            if (!empty($inviter) && $inviter->is_qs = 1) {
                //上级是骑士，给上级骑士奖励
                //给骑士奖励
                $options = [
                    'where' => ['id' => $user->inviter_uid]
                ];
                HcityUserDao::i()->setInc('balance', $this->money, $options);
                HcityUserDao::i()->setInc('income', $this->money, $options);
                HcityQsDao::i()->setInc('income', $this->money, ['where' => ['uid' => $user->inviter_uid]]);
                //记录骑士余额流水
                $recordCreateData = [
                    'fid' => $this->task['fid'],
                    'money' => $this->money,
                    'type' => BalanceRecordTypeEnum::INVITE_KNIGHT,
                    'time' => time(),
                    'uid' => $user->inviter_uid,
                    'remark' => $this->task['remark'],
                    'lower_uid' => $uid,
                    'lower_shop_id' => 0
                ];
                HcityUserBalanceRecordDao::i()->create($recordCreateData);
                //投递小程序消息通知任务
                $userWx = HcityUserWxbindDao::i()->getOne(['uid' => $user->inviter_uid]);
                if (!empty($userWx)) {
                    $params = [
                        'openid' => $userWx->open_id,
                        'type' => '骑士拉新任务达成收益',
                        'change_money' => '+' . $this->money,
                        'change_time' => date('Y-m-d H:i:s', time()),
                        'current_money' => number_format($inviter->balance + $this->money, 2)
                    ];
                    (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::MONEY_CHANGE);
                }
                (new HcityUserCache(['uid' => $user->inviter_uid]))->delete();
            } else {
                log_message('error', __METHOD__ . '发放骑士拉新奖励失败:骑士不存在:' . json_encode($inviter));
            }
        } else {
            log_message('error', __METHOD__ . '发放骑士拉新奖励失败:不存在上级骑士:' . json_encode($user));
        }
    }

    /**
     * 骑士邀请商家入驻
     * @param int $uid
     * @param int $shopId
     * @author ahe<ahe@iyenei.com>
     */
    public function _knightInviteShop(int $uid, int $shopId)
    {
        //给骑士奖励
        $options = [
            'where' => ['id' => $uid]
        ];
        HcityUserDao::i()->setInc('balance', $this->money, $options);
        HcityUserDao::i()->setInc('income', $this->money, $options);
        HcityQsDao::i()->setInc('income', $this->money, ['where' => ['uid' => $uid]]);
        //记录骑士余额流水
        $recordCreateData = [
            'fid' => $this->task['fid'],
            'money' => $this->money,
            'type' => BalanceRecordTypeEnum::INVITE_MERCHANT_BY_KNIGHT,
            'time' => time(),
            'uid' => $uid,
            'remark' => $this->task['remark'],
            'lower_uid' => 0,
            'lower_shop_id' => $shopId
        ];
        HcityUserBalanceRecordDao::i()->create($recordCreateData);
        //投递小程序消息通知任务
        $userWx = HcityUserWxbindDao::i()->getOne(['uid' => $uid]);
        $user = HcityUserDao::i()->getOne(['id' => $uid], 'balance');
        if (!empty($userWx)) {
            $params = [
                'openid' => $userWx->open_id,
                'type' => '骑士商家拉新任务达成收益',
                'change_money' => '+' . $this->money,
                'change_time' => date('Y-m-d H:i:s', time()),
                'current_money' => $user->balance
            ];
            (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::MONEY_CHANGE);
        }
        (new HcityUserCache(['uid' => $uid]))->delete();
    }
}
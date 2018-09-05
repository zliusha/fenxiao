<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/24
 * Time: 下午4:33
 */

namespace Service\Bll\Hcity\Commission;

use Service\Bll\Hcity\Xcx\XcxBll;
use Service\Cache\Hcity\HcityCommissionConfigCache;
use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionManagerDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionPlatformDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManagerBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityQsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopFinanceDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\Enum\BalanceRecordTypeEnum;
use Service\Enum\XcxTemplateMessageEnum;
use Service\Exceptions\Exception;

/**
 *
 * 开通一店一码主账号自动成为嘿卡会员
 * 成为网点合伙人条件：下线（个人+商家）发展数量有50个时
 *
 * 个人分佣金：
 * 判断个人账号是否开通嘿卡
 *
 * 店铺邀请：
 * 判断店铺主账号是否开通嘿卡
 *
 * 个人购买嘿卡：送嘿卡币
 * 店铺邀请开通嘿卡：送店铺红包
 *
 * 分销逻辑：
 * 一级等级不能比二级等级低，即A邀请了B，A是普通会员，当B成为了网点合伙人或城市合伙人时，关系断掉。
 * 若A是网点合伙人，B成为网点合伙人时，关系还存在，当B成为城市合伙人时，关系才断掉。
 *
 * 正常商品交易，分佣：一级、二级、城市合伙人、平台
 * 福利池商品交易，支付超出部分（余额）分佣：一级、二级、城市合伙人、平台
 * 个人邀请成为嘿卡会员，分佣：一级、二级、城市合伙人、平台（个人区分普通会员、网点合伙人和城市合伙人）
 * 商家邀请码邀请成为嘿卡会员，分佣：一级、城市合伙人、平台（并送店铺红包）
 * 邀请开通一店一码，分佣：一级、二级、城市合伙人、平台
 * Class CommissionBll
 * @package Service\Bll\Hcity
 */
class CalculateBll
{
    /**
     * @var CalculateParamsBll 任务封装对象
     */
    private $task;

    public function __construct(CalculateParamsBll $params)
    {
        $this->task = $params;
    }

    /**
     * 计算分佣
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function doCal()
    {
        //读配置
        $config = $this->_getConfig();
        if (empty($config)) {
            throw new Exception('未设置分佣比例');
        }
        if (empty($this->task->getLevelRel())) {
            //@todo 一二级分销关系为空，佣金全部给平台和城市合伙人
            $this->_doPlatCal($this->task->getMoney(), $config);
        } else {
            //@todo 处理一二级分销
            $otherMoney = $this->task->getMoney();
            //获取店铺邀请者
            $shopInviter = $this->task->getShopInviter();
            $hasGaveShopInviter = false;
            foreach ($this->task->getLevelRel() as $rel) {
                if ($rel['fx_level'] == 1) {
                    //@todo 一级分销
                    if (isset($rel['user'])) {
                        //会员level 1普通会员,2网点合伙人,3城市合伙人
                        if ($rel['user']['level'] == 1) {
                            $firstMoney = $this->_formatMoney($this->task->getMoney(), $config['first_hcard_user']);
                            if (!empty($shopInviter) && $shopInviter['uid'] == $rel['user']['uid']) {
                                //店铺邀请者就是当前被分佣者
                                $firstMoney += $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                                $hasGaveShopInviter = true;
                            }
                            $otherMoney -= $firstMoney;
                            //保存会员分佣
                            $this->_saveCommissionUser($rel['user']['uid'], $firstMoney, 1);
                        } elseif ($rel['user']['level'] == 2) {
                            $firstMoney = $this->_formatMoney($this->task->getMoney(), $config['first_website_partner']);
                            if (!empty($shopInviter) && $shopInviter['uid'] == $rel['user']['uid']) {
                                //店铺邀请者就是当前被分佣者
                                $firstMoney += $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                                $hasGaveShopInviter = true;
                            }
                            $otherMoney -= $firstMoney;
                            //保存会员分佣
                            $this->_saveCommissionUser($rel['user']['uid'], $firstMoney, 1);
                        } elseif ($rel['user']['level'] == 3) {
                            //验证城市合伙人有效性
                            $hcityPartnerTmp = HcityManageAccountDao::i()->getOne(['hcity_uid' => $rel['user']['uid']]);
                            if (empty($hcityPartnerTmps) || $hcityPartnerTmp->status == 1 || $hcityPartnerTmp->expire_time <= time()) {
                                //城市合伙人已过期,按普通嘿卡会员分佣
                                $firstMoney = $this->_formatMoney($this->task->getMoney(), $config['first_hcard_user']);
                                if (!empty($shopInviter) && $shopInviter['uid'] == $rel['user']['uid']) {
                                    //店铺邀请者就是当前被分佣者
                                    $firstMoney += $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                                    $hasGaveShopInviter = true;
                                }
                                $otherMoney -= $firstMoney;
                                //保存会员分佣
                                $this->_saveCommissionUser($rel['user']['uid'], $firstMoney, 1);
                            }
                        }
                    } elseif (isset($rel['shop'])) {
                        $firstMoney = $this->_formatMoney($this->task->getMoney(), $config['first_shop']);
                        if (!empty($shopInviter) && $shopInviter['shop_id'] == $rel['shop']['shop_id']) {
                            //店铺邀请者就是当前被分佣者
                            $firstMoney += $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                            $hasGaveShopInviter = true;
                        }
                        $otherMoney -= $firstMoney;
                        //保存店铺分佣
                        $this->_saveCommissionShop($rel['shop']['shop_id'], $rel['shop']['aid'], $firstMoney);
                    }
                }
                if ($rel['fx_level'] == 2) {
                    //@todo 二级分销
                    if (isset($rel['user'])) {
                        //会员level 1普通会员,2网点合伙人,3城市合伙人
                        if ($rel['user']['level'] == 1) {
                            $secondMoney = $this->_formatMoney($this->task->getMoney(), $config['second_hcard_user']);
                            if (!empty($shopInviter) && $shopInviter['uid'] == $rel['user']['uid']) {
                                //店铺邀请者就是当前被分佣者
                                $secondMoney += $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                                $hasGaveShopInviter = true;
                            }
                            $otherMoney -= $secondMoney;
                            //保存会员分佣
                            $this->_saveCommissionUser($rel['user']['uid'], $secondMoney, 2);
                        } elseif ($rel['user']['level'] == 2) {
                            $secondMoney = $this->_formatMoney($this->task->getMoney(), $config['second_website_partner']);
                            if (!empty($shopInviter) && $shopInviter['uid'] == $rel['user']['uid']) {
                                //店铺邀请者就是当前被分佣者
                                $secondMoney += $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                                $hasGaveShopInviter = true;
                            }
                            $otherMoney -= $secondMoney;
                            //保存会员分佣
                            $this->_saveCommissionUser($rel['user']['uid'], $secondMoney, 2);
                        } elseif ($rel['user']['level'] == 3) {
                            //验证城市合伙人有效性
                            $hcityPartnerTmp = HcityManageAccountDao::i()->getOne(['hcity_uid' => $rel['user']['uid']]);
                            if (empty($hcityPartnerTmps) || $hcityPartnerTmp->status == 1 || $hcityPartnerTmp->expire_time <= time()) {
                                //城市合伙人已过期,按普通嘿卡会员分佣
                                $secondMoney = $this->_formatMoney($this->task->getMoney(), $config['second_hcard_user']);
                                if (!empty($shopInviter) && $shopInviter['uid'] == $rel['user']['uid']) {
                                    //店铺邀请者就是当前被分佣者
                                    $secondMoney += $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                                    $hasGaveShopInviter = true;
                                }
                                $otherMoney -= $secondMoney;
                                //保存会员分佣
                                $this->_saveCommissionUser($rel['user']['uid'], $secondMoney, 2);
                            }
                        }
                    } elseif (isset($rel['shop'])) {
                        $secondMoney = $this->_formatMoney($this->task->getMoney(), $config['second_shop']);
                        if (!empty($shopInviter) && $shopInviter['shop_id'] == $rel['shop']['shop_id']) {
                            //店铺邀请者就是当前被分佣者
                            $secondMoney += $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                            $hasGaveShopInviter = true;
                        }
                        $otherMoney -= $secondMoney;
                        //保存店铺分佣
                        $this->_saveCommissionShop($rel['shop']['shop_id'], $rel['shop']['aid'], $secondMoney);
                    }
                }
            }

            //@todo 给店铺邀请者额外分佣
            if (!$hasGaveShopInviter && !empty($shopInviter)) {
                $shopInviterMoney = $this->_formatMoney($this->task->getMoney(), $config['shop_inviter']);
                if (!empty($shopInviter['uid'])) {
                    //保存会员分佣
                    $this->_saveCommissionUser($shopInviter['uid'], $shopInviterMoney, 0, ['lower_shop_id' => $shopInviter['lower_shop_id']]);
                    $otherMoney -= $shopInviterMoney;
                } elseif (!empty($shopInviter['shop_id'])) {
                    //保存店铺分佣
                    $this->_saveCommissionShop($shopInviter['shop_id'], $shopInviter['aid'], $shopInviterMoney);
                    $otherMoney -= $shopInviterMoney;
                }
            }
            //@todo 剩余钱，平台和城市合伙人分
            $this->_doPlatCal($otherMoney, $config);
        }
    }

    /**
     * 商户邀请码邀请开通嘿卡,分佣计算
     * @param int $aid
     * @param int $shopId
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function doMerchantCal(int $aid, int $shopId)
    {
        $config = $this->_getConfig();
        if (empty($config)) {
            throw new Exception('未设置分佣比例');
        }
        if ($config['first_shop'] > $this->task->getMoney()) {
            throw new Exception('店铺邀请码邀请开通嘿卡分佣比例错误');
        }
        //保存店铺分佣
        $this->_saveCommissionShop($shopId, $aid, $config['first_shop']);
        //@todo 剩余钱，平台和城市合伙人分
        $this->_doPlatCal($this->task->getMoney() - $config['first_shop'], $config);
    }

    /**
     * 对剩余金额，平台和城市合伙人分佣
     * @param float $otherMoney
     * @param array $config
     * @author ahe<ahe@iyenei.com>
     */
    private function _doPlatCal(float $otherMoney, array $config)
    {
        if ($otherMoney < 0) return;
        $managerFyData = $platformFyData = [];
        if (empty($this->task->getHcityPartner())) {
            //@todo 城市合伙人为空，全部给平台
            $platformFyData = [
                'fid' => $this->task->getFid(),
                'time' => time(),
                'money' => $otherMoney,
                'confirm_status' => 0,
                'income_type' => $this->task->getType(),
                'ext' => json_encode($this->task->getSourceData()),
                'hx_code' => $this->task->getHxcode()
            ];
        } else {
            //@todo 城市合伙人和平台分佣
            //计算合伙人分佣
            $managerMoney = $this->_formatMoney($otherMoney, $config['hcity_partner']);
            $managerFyData = [
                'fid' => $this->task->getFid(),
                'manager_id' => $this->task->getHcityPartner()->id,
                'hcity_uid' => $this->task->getHcityPartner()->hcity_uid,
                'time' => time(),
                'money' => $managerMoney,
                'confirm_status' => 0,
                'income_type' => $this->task->getType(),
                'ext' => json_encode($this->task->getSourceData()),
                'hx_code' => $this->task->getHxcode()
            ];
            //平台方分佣
            $platformFyData = [
                'fid' => $this->task->getFid(),
                'time' => time(),
                'money' => $otherMoney - $managerMoney,
                'confirm_status' => 0,
                'income_type' => $this->task->getType(),
                'ext' => json_encode($this->task->getSourceData()),
                'hx_code' => $this->task->getHxcode()
            ];
        }
        if (!empty($managerFyData)) {
            //记录城市合伙人分佣
            $insertId = HcityCommissionManagerDao::i()->create($managerFyData);

            if ($this->task->getConfirm()) {
                $this->_confirmManagerCommission($this->task->getHcityPartner()->id, $this->task->getHcityPartner()->hcity_uid, $insertId, $managerMoney);
            }
        }
        if (!empty($platformFyData)) {
            //记录平台分佣
            $insertId = HcityCommissionPlatformDao::i()->create($platformFyData);
            if ($this->task->getConfirm()) {
                $this->_confirmPlatformCommission($insertId);
            }
        }
    }

    /**
     * 确认平台分佣入账
     * @param int $commissionId
     * @author ahe<ahe@iyenei.com>
     */
    private function _confirmPlatformCommission(int $commissionId)
    {
        //确认分佣
        HcityCommissionPlatformDao::i()->update(['confirm_status' => 1, 'confirm_time' => time()], ['id' => $commissionId]);
    }

    /**
     * 确认平台合伙人分佣入账
     * @param int $managerId
     * @param int $hcityUid
     * @param int $commissionId
     * @param float $confirmMoney
     * @author ahe<ahe@iyenei.com>
     */
    private function _confirmManagerCommission(int $managerId, int $hcityUid, int $commissionId, float $confirmMoney)
    {
        //确认分佣
        HcityCommissionManagerDao::i()->update(['confirm_status' => 1, 'confirm_time' => time()], ['id' => $commissionId]);
        //增加城市合伙人得到的金额
        $options['where'] = [
            'id' => $managerId,
        ];
        if ($confirmMoney > 0) {
            HcityManageAccountDao::i()->setInc('balance', $confirmMoney, $options);
            HcityManageAccountDao::i()->setInc('income', $confirmMoney, $options);
        }
        //保存余额流水
        $recordCreateData = [
            'fid' => $this->task->getFid(),
            'money' => $confirmMoney,
            'type' => $this->_getBalanceType(),
            'time' => time(),
            'manager_id' => $managerId,
            'hcity_uid' => $hcityUid,
            'remark' => $this->task->getRemark()
        ];
        HcityManagerBalanceRecordDao::i()->create($recordCreateData);
    }

    /**
     * 保存店铺分佣
     * @param int $shopId
     * @param int $aid
     * @param float $money
     * @author ahe<ahe@iyenei.com>
     */
    private function _saveCommissionShop(int $shopId, int $aid, float $money)
    {
        $shopFyData = [
            'fid' => $this->task->getFid(),
            'shop_id' => $shopId,
            'aid' => $aid,
            'time' => time(),
            'money' => $money,
            'confirm_status' => 0,
            'income_type' => $this->task->getType(),
            'ext' => json_encode($this->task->getSourceData()),
            'hx_code' => $this->task->getHxcode()
        ];
        //记录店铺分佣
        $insertId = HcityCommissionShopDao::i()->create($shopFyData);

        if ($this->task->getConfirm()) {
            $this->_confirmShopCommission($aid, $shopId, $insertId, $money);
        }

    }

    /**
     * 确认店铺分佣入账
     * @param int $aid
     * @param int $shopId
     * @param int $commissionId
     * @param float $confirmMoney
     * @author ahe<ahe@iyenei.com>
     */
    private function _confirmShopCommission(int $aid, int $shopId, int $commissionId, float $confirmMoney)
    {
        //确认分佣
        HcityCommissionShopDao::i()->update(['confirm_status' => 1, 'confirm_time' => time()], ['id' => $commissionId]);
        //增加店铺余额
        $options['where'] = [
            'aid' => $aid,
            'shop_id' => $shopId,
        ];
        if ($confirmMoney > 0) {
            HcityShopExtDao::i()->setInc('balance', $confirmMoney, $options);
            HcityShopExtDao::i()->setInc('income', $confirmMoney, $options);
        }
        //保存余额流水
        $recordCreateData = [
            'fid' => $this->task->getFid(),
            'money' => $confirmMoney,
            'type' => $this->_getBalanceType(),
            'time' => time(),
            'aid' => $aid,
            'shop_id' => $shopId,
            'remark' => $this->task->getRemark()
        ];
        HcityShopBalanceRecordDao::i()->create($recordCreateData);
    }

    /**
     * 保存会员分佣
     * @param int $uid
     * @param float $money
     * @param int $level
     * @param array $lowerUpdate
     * @author ahe<ahe@iyenei.com>
     */
    private function _saveCommissionUser(int $uid, float $money, int $level, array $lowerUpdate = [])
    {
        $memberFyData = [
            'fid' => $this->task->getFid(),
            'uid' => $uid,
            'time' => time(),
            'money' => $money,
            'confirm_status' => 0,
            'income_type' => $this->task->getType(),
            'ext' => json_encode($this->task->getSourceData()),
            'hx_code' => $this->task->getHxcode(),
            'lower_uid' => 0,
            'lower_shop_id' => 0
        ];
        if (empty($lowerUpdate)) {
            $lowerArr = $this->task->getLowerArr();
            if (isset($lowerArr['level_' . $level])) {
                if (!empty($lowerArr['level_' . $level]['shop_id'])) {
                    $memberFyData['lower_shop_id'] = $lowerArr['level_' . $level]['shop_id'];
                }
                if (!empty($lowerArr['level_' . $level]['uid'])) {
                    $memberFyData['lower_uid'] = $lowerArr['level_' . $level]['uid'];
                }
            }
        } else {
            $memberFyData = array_merge($memberFyData, $lowerUpdate);
        }
        //记录会员分佣
        $insertId = HcityCommissionUserDao::i()->create($memberFyData);
        if ($this->task->getConfirm()) {
            $this->_confirmUserCommission($uid, $insertId, $money, $memberFyData['lower_uid'], $memberFyData['lower_shop_id']);
        }
    }

    /**
     * 确认会员分佣入账
     * @param int $uid
     * @param int $commissionId
     * @param float $confirmMoney
     * @param int $lowerUid
     * @param int $lowerShopid
     * @author ahe<ahe@iyenei.com>
     */
    private function _confirmUserCommission(int $uid, int $commissionId, float $confirmMoney, int $lowerUid, int $lowerShopid)
    {
        $user = HcityUserDao::i()->getOne(['id' => $uid]);
        //确认分佣
        HcityCommissionUserDao::i()->update(['confirm_status' => 1, 'confirm_time' => time()], ['id' => $commissionId]);
        //增加会员余额
        $options['where'] = [
            'id' => $uid,
        ];
        if ($confirmMoney > 0) {
            HcityUserDao::i()->setInc('balance', $confirmMoney, $options);
            HcityUserDao::i()->setInc('income', $confirmMoney, $options);
            if ($user->is_qs == 1) {
                //给骑士加收入
                HcityQsDao::i()->setInc('income', $confirmMoney, ['where' => ['uid' => $uid]]);
            }
        }
        //保存余额流水
        $recordCreateData = [
            'fid' => $this->task->getFid(),
            'money' => $confirmMoney,
            'type' => $this->_getBalanceType(),
            'time' => time(),
            'uid' => $uid,
            'remark' => $this->task->getRemark(),
            'lower_uid' => $lowerUid,
            'lower_shop_id' => $lowerShopid
        ];
        HcityUserBalanceRecordDao::i()->create($recordCreateData);
        //投递小程序消息通知任务
        $userWx = HcityUserWxbindDao::i()->getOne(['uid' => $uid]);

        if (!empty($userWx)) {
            $params = [
                'openid' => $userWx->open_id,
                'type' => $this->_getXcxTypeMsg(),
                'change_money' => '+' . $confirmMoney,
                'change_time' => date('Y-m-d H:i:s', time()),
                'current_money' => $user->balance
            ];
            (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::MONEY_CHANGE);
        }
        (new HcityUserCache(['uid' => $uid]))->delete();
    }

    /**
     * 获取小程序通知分佣类型
     * @return string
     * @author ahe<ahe@iyenei.com>
     */
    private function _getXcxTypeMsg()
    {
        $msg = '收益';
        switch ($this->task->getType()) {
            case Config::TYPE_GOODS_TRADE:
            case Config::TYPE_WELFARE_TRADE:
                $msg = '商品分享收益';
                break;
            case Config::TYPE_OPEN_HCARD:
            case Config::TYPE_OPEN_HCARD_BY_MERCHANT:
                $msg = '邀请办卡收益';
                break;
            case Config::TYPE_OPEN_BARCODE:
                $msg = '邀请店铺收益';
                break;
            default:
                break;
        }
        return $msg;
    }

    /**
     * 金额格式化，保留两位小数，超出部分向前进一，即2.222 -> 2.23
     * @param float $money 金额
     * @param float $percentage 比例
     * @return float
     * @author ahe<ahe@iyenei.com>
     */
    private function _formatMoney(float $money, float $percentage)
    {
        //超过两位小数时，做进位取整处理
        return bcdiv(ceil($percentage * $money), 100, 2);
    }

    /**
     * 获取分佣比例
     * @return array
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    private function _getConfig()
    {
        $config = (new HcityCommissionConfigCache(['type' => $this->task->getType()]))->getDataASNX();
        return pos($config);
    }

    /**
     * 获取分佣记录类型
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    private function _getBalanceType()
    {
        $type = $this->task->getType();
        if ($type == Config::TYPE_GOODS_TRADE || $type == Config::TYPE_WELFARE_TRADE) {
            return BalanceRecordTypeEnum::GOODS_COMMISSION;
        }
        if ($type == Config::TYPE_OPEN_HCARD || $type == Config::TYPE_OPEN_HCARD_BY_MERCHANT) {
            return BalanceRecordTypeEnum::INVITE_OPEN_CARD;
        }
        if ($type == Config::TYPE_OPEN_BARCODE) {
            return BalanceRecordTypeEnum::INVITE_OPEN_YDYM;
        }
        return 0;
    }

    /**
     * 确认分佣/商家结算
     * @author ahe<ahe@iyenei.com>
     */
    public function doConfirm()
    {
        //@todo 确认分佣
        $fid = $this->task->getFid();
        $hxcode = $this->task->getHxcode();
        $userCommission = HcityCommissionUserDao::i()->getAll(['fid' => $fid, 'hx_code' => $hxcode]);
        $shopCommission = HcityCommissionShopDao::i()->getAll(['fid' => $fid, 'hx_code' => $hxcode]);
        $managerCommission = HcityCommissionManagerDao::i()->getOne(['fid' => $fid, 'hx_code' => $hxcode]);
        $platformCommission = HcityCommissionPlatformDao::i()->getOne(['fid' => $fid, 'hx_code' => $hxcode]);
        //验证分佣未确认且未失效的
        if (!empty($userCommission)) {
            foreach ($userCommission as $userVal) {
                if ($userVal->confirm_status == 0) {
                    $userExt = json_decode($userVal->ext);
                    $this->task->setType($userExt->type);
                    $this->task->setRemark($userExt->remark);
                    $this->_confirmUserCommission($userVal->uid, $userVal->id, $userVal->money, $userVal->lower_uid, $userVal->lower_shop_id);
                }
            }
        }
        if (!empty($shopCommission)) {
            foreach ($shopCommission as $shopVal) {
                if ($shopVal->confirm_status == 0) {
                    $shopExt = json_decode($shopVal->ext);
                    $this->task->setType($shopExt->type);
                    $this->task->setRemark($shopExt->remark);
                    $this->_confirmShopCommission($shopVal->aid, $shopVal->shop_id, $shopVal->id, $shopVal->money);
                }
            }
        }
        if (!empty($managerCommission) && $managerCommission->confirm_status == 0) {
            $managerExt = json_decode($managerCommission->ext);
            $this->task->setType($managerExt->type);
            $this->task->setRemark($managerExt->remark);
            $this->_confirmManagerCommission($managerCommission->manager_id, $managerCommission->hcity_uid, $managerCommission->id, $managerCommission->money);
        }
        if (!empty($platformCommission) && $platformCommission->confirm_status == 0) {
            $platformExt = json_decode($platformCommission->ext);
            $this->task->setType($platformExt->type);
            $this->task->setRemark($platformExt->remark);
            $this->_confirmPlatformCommission($platformCommission->id);
        }
        //@todo 确认商家结算
        $shopFinance = HcityShopFinanceDao::i()->getOne(['fid' => $fid, 'hx_code' => $hxcode]);
        if (!empty($shopFinance) && $shopFinance->confirm_status == 0) {
            HcityShopFinanceDao::i()->update(['confirm_status' => 1, 'confirm_time' => time()], ['id' => $shopFinance->id]);
            //保存商品销售收入余额流水
            $recordCreateData = [
                'fid' => $fid,
                'money' => $shopFinance->money,
                'type' => BalanceRecordTypeEnum::GOODS_SALES_INCOME,
                'time' => time(),
                'aid' => $shopFinance->aid,
                'shop_id' => $shopFinance->shop_id
            ];
            HcityShopBalanceRecordDao::i()->create($recordCreateData);
        }
    }

    /**
     * 设置核销失效(佣金不用给买家退，外部已退款)
     * @author ahe<ahe@iyenei.com>
     */
    public function doCancel()
    {
        $fid = $this->task->getFid();
        HcityCommissionUserDao::i()->update(['confirm_status' => 2], ['fid' => $fid, 'confirm_status' => 0]);
        HcityCommissionShopDao::i()->update(['confirm_status' => 2], ['fid' => $fid, 'confirm_status' => 0]);
        HcityCommissionManagerDao::i()->update(['confirm_status' => 2], ['fid' => $fid, 'confirm_status' => 0]);
        HcityCommissionPlatformDao::i()->update(['confirm_status' => 2], ['fid' => $fid, 'confirm_status' => 0]);
        HcityShopFinanceDao::i()->update(['confirm_status' => 2, 'js_status' => 1], ['fid' => $fid, 'confirm_status' => 0]);
    }

    /**
     * 商家预结算
     * @param int $aid
     * @param int $shopId
     * @param int $sourceType
     * @author ahe<ahe@iyenei.com>
     */
    public function doMerchantPreJs(int $aid, int $shopId, int $sourceType)
    {
        $createData = [
            'fid' => $this->task->getFid(),
            'aid' => $aid,
            'shop_id' => $shopId,
            'time' => time(),
            'money' => $this->task->getMoney(),
            'confirm_status' => 0,
            'js_status' => 0,
            'hx_code' => $this->task->getHxcode(),
            'source_type' => $sourceType,
        ];
        HcityShopFinanceDao::i()->create($createData);
    }
}
<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/11
 * Time: 下午7:25
 */

namespace Service\Bll\Hcity;

use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityInviteMerchantViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserBindBankCardDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserRedPacketDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;

class AccountBll extends \Service\Bll\BaseBll
{
    /**
     * 获取登录用户信息（含邀请人信息）
     * @param int $uid
     * @return object
     * @author ahe<ahe@iyenei.com>
     */
    public function getLoginUserInfo(int $uid)
    {
        $user = HcityUserDao::i()->getOne(['id' => $uid]);
        if (!empty($user)) {
            if (!empty($user->hcard_expire_time) && $user->hcard_expire_time < time()) {
                //嘿卡过期
                $user->is_open_hcard = 2;
            }
            $user->img = conver_picurl($user->img);
            if (!empty($user->inviter_uid)) {
                $user->inviter = HcityUserDao::i()->getOne(['id' => $user->inviter_uid]);
                if (!empty($user->inviter)) {
                    $user->inviter->img = conver_picurl($user->img);
                }
            } else {
                $user->inviter = null;
            }
            if (!empty($user->inviter_shop_id)) {
                $user->inviter_shop = MainShopDao::i()->getOne(['id' => $user->inviter_shop_id]);
            } else {
                $user->inviter_shop = null;
            }
        }
        return $user;
    }

    /**
     * 获取会员简单统计信息
     * @param int $uid
     * @return object
     * @author ahe<ahe@iyenei.com>
     */
    public function getUserSimpleInfo(int $uid)
    {
        $hcityUserDao = HcityUserDao::i();
        $user = $hcityUserDao->getOne(['id' => $uid]);
        if (!empty($user)) {
            $hcityUserCollectShopDao = HcityUserCollectShopDao::i();
            $hcityUserCollectGoodsDao = HcityUserCollectGoodsDao::i();
            $shcityUserRedPacketDao = ShcityUserRedPacketDao::i();
            $hcityCommissionUserDao = HcityCommissionUserDao::i();
            $shcityUserOrderDao = ShcityUserOrderDao::i(['uid' => $uid]);
            if (!empty($user->hcard_expire_time) && $user->hcard_expire_time > time()) {
                //嘿卡过期
                $user->is_open_hcard = 2;
            }
            //获取店铺收藏数
            $user->shop_collect_num = $hcityUserCollectShopDao->getCount(['uid' => $uid]);
            //获取商品关注数
            $user->goods_collect_num = $hcityUserCollectGoodsDao->getCount(['uid' => $uid]);
            //红包数
            $user->red_packet_num = $shcityUserRedPacketDao->getCount(['uid' => $uid, 'status' => 1, 'expire_time >' => time(), 'money >' => 0]);
            //今日预估分佣
            $todayStartTime = strtotime(date('Y-m-d', time()));
            $user->today_pre_income = $hcityCommissionUserDao->getSum('money', ['uid' => $uid, 'time >=' => $todayStartTime, 'time <=' => time()]);
            $user->today_pre_income = sprintf('%0.2f', $user->today_pre_income);
            //本月预估分佣
            $monthStartTime = strtotime(date('Y-m-01', time()));
            $user->month_pre_income = $hcityCommissionUserDao->getSum('money', ['uid' => $uid, 'time >=' => $monthStartTime, 'time <=' => time()]);
            $user->month_pre_income = sprintf('%0.2f', $user->month_pre_income);
            //待使用订单
            $user->wait_used_order_num = $shcityUserOrderDao->getCount(['uid' => $uid, 'status' => 1]);
            //待付款订单
            $user->wait_pay_order_num = $shcityUserOrderDao->getCount(['uid' => $uid, 'status' => 0]);
        }
        return $user;
    }

    /**
     * 更新会员信息
     * @param int $uid
     * @param array $data
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function editUserInfo(int $uid, array $data)
    {
        $updateData = [];
        $hcityUserDao = HcityUserDao::i();
        if (!empty($data['img'])) $updateData['img'] = $data['img'];
        if (!empty($data['username'])) $updateData['username'] = $data['username'];
        if (!empty($data['inviter_code'])) {
            $inviter = $hcityUserDao->getOne(['id_code' => $data['inviter_code']]);
            if (empty($inviter)) {
                //去查店铺码
                $shop = HcityShopExtDao::i()->getOne(['id_code' => $data['inviter_code']]);
                if (empty($shop)) {
                    throw new Exception('邀请码错误');
                }
                //设置邀请店铺id
                $updateData['inviter_shop_id'] = $shop->shop_id;
            } else {
                if ($inviter->id == $uid) {
                    throw new Exception('邀请码错误，不能邀请自己');
                }
                //设置邀请人id
                $updateData['inviter_uid'] = $inviter->id;
            }
            $updateData['inviter_code'] = $data['inviter_code'];
        }
        if (!empty($data['mobile'])) {
            $user = $hcityUserDao->getOne(['mobile' => $data['mobile']]);
            if (!empty($user)) {
                throw new Exception('手机号已存在');
            }
            $updateData['mobile'] = $data['mobile'];
        }
        $return = $hcityUserDao->update($updateData, ['id' => $uid]);
        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();
        return $return;
    }

    /**
     * 通过邀请码设置邀请者
     * @param int $uid
     * @param string $inviterCode
     * @return int
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function setInviterByCode(int $uid, string $inviterCode)
    {
        $inviter = HcityUserDao::i()->getOne(['id_code' => $inviterCode]);
        if (empty($inviter)) {
            //去查店铺码
            $shop = HcityShopExtDao::i()->getOne(['id_code' => $inviterCode]);
            if (empty($shop)) {
                throw new Exception('邀请码错误');
            }
            //设置邀请店铺id
            $updateData['inviter_shop_id'] = $shop->shop_id;
        } else {
            if ($inviter->id == $uid) {
                throw new Exception('邀请码错误，不能邀请自己');
            }
            //设置邀请人id
            $updateData['inviter_uid'] = $inviter->id;
        }
        $updateData['inviter_code'] = $inviterCode;

        $return = HcityUserDao::i()->update($updateData, ['id' => $uid]);
        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();
        return $return;
    }

    /**
     * 获取我的团队
     * @param int $uid
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getTeamList(int $uid)
    {
        $hcityMainDb = HcityMainDb::i(['uid' => $uid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_user']}";
        $p_conf->where = sprintf(" inviter_uid = %d", $uid);
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        if (!empty($rows['rows'])) {
            //计算下级佣金贡献
            $lowerUids = array_column($rows['rows'], 'id');
            $incomeArr = HcityUserBalanceRecordDao::i()->contributeIncomeByUid($uid, $lowerUids);
            $incomeTmp = [];
            if (!empty($incomeArr)) {
                $incomeTmp = array_column($incomeArr, 'money', 'lower_uid');
            }

            foreach ($rows['rows'] as &$item) {
                if (isset($incomeTmp[$item['id']])) {
                    $item['gx_income'] = sprintf('%0.2f', $incomeTmp[$item['id']]);
                } else {
                    $item['gx_income'] = '0.00';
                }
            }
        }
        $rows['total'] = $count;
        $hcityUserDao =  HcityUserDao::i();
        $rows['hcard_count'] = $hcityUserDao->getCount(['inviter_uid' => $uid, 'is_open_hcard' => 1]);
        $rows['qs_count'] = $hcityUserDao->getCount(['inviter_uid' => $uid, 'is_qs' => 1]);
        return $rows;
    }

    /**
     * 获取我的受邀商家
     * @param int $uid
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getMerchants(int $uid)
    {
        $hcityMainDb = HcityMainDb::i(['uid' => $uid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_invite_merchant_view']}";
        $p_conf->where = sprintf(" inviter_uid = %d", $uid);
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        if (!empty($rows['rows'])) {
            $shopIds = array_column($rows['rows'], 'shop_id');
            $incomeArr = HcityUserBalanceRecordDao::i()->contributeIncomeByShopId($uid, $shopIds);
            $incomeTmp = [];
            if (!empty($incomeArr)) {
                $incomeTmp = array_column($incomeArr, 'money', 'lower_shop_id');
            }

            $options = [
                'where_in' => ['id' => $shopIds]
            ];
            $mainShops = MainShopDao::i()->getAllExt($options);
            $mainShopsArr = [];
            foreach ($mainShops as $shop) {
                $mainShopsArr[$shop->id] = $shop;
            }
            foreach ($rows['rows'] as &$val) {
                $val['shop_name'] = isset($mainShopsArr[$val['shop_id']]) ? $mainShopsArr[$val['shop_id']]->shop_name : '';
                $val['shop_logo'] = isset($mainShopsArr[$val['shop_id']]) ? conver_picurl($mainShopsArr[$val['shop_id']]->shop_logo) : '';
                if (isset($incomeTmp[$val['shop_id']])) {
                    $val['gx_income'] = sprintf('%0.2f', $incomeTmp[$val['shop_id']]);
                } else {
                    $val['gx_income'] = '0.00';
                }
            }
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取会员收入明细
     * @param int $uid
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getIncomeList(int $uid)
    {
        $shardDb = HcityMainDb::i();
        $page = new PageList($shardDb);
        $sql = "uid = {$page->filter($uid)} and type in (3,4,5,7,8,9,10) and money > 0 ";
        $p_conf = $page->getConfig();
        $p_conf->table = "{$shardDb->tables['hcity_user_balance_record']} ";
        $p_conf->where = $sql;
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取收入信息
     * @param int $uid
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getIncomeInfo(int $uid)
    {
        $todayStartTime = strtotime(date('Y-m-d', time()));
        //预估总收入
        $list['today_pre_income'] = HcityCommissionUserDao::i()->getSum('money', ['uid' => $uid, 'time >=' => $todayStartTime, 'time <=' => time()]);
        $list['today_pre_income'] = sprintf('%0.2f', $list['today_pre_income']);
        //预估分佣
        $options = [
            'where' => ['uid' => $uid, 'time >=' => $todayStartTime, 'time <=' => time()],
            'where_in' => ['income_type' => [1, 2]]
        ];
        $list['today_pre_money'] = HcityCommissionUserDao::i()->getSumExt('money', $options);
        $list['today_pre_money'] = sprintf('%0.2f', $list['today_pre_money']);
        //新增嘿卡
        $hcardSql = sprintf("uid = %d and time >= '%s' and time <= '%s' and income_type in (3,4)", $uid, $todayStartTime, time());
        $list['today_hcard_count'] = HcityCommissionUserDao::i()->getCount($hcardSql);
        //新增商家
        $list['today_merchant_count'] = HcityCommissionUserDao::i()->getCount(['uid' => $uid, 'time >=' => $todayStartTime, 'time <=' => time(), 'income_type' => 5]);

        //历史总收入
        $list['toatl_income'] = HcityCommissionUserDao::i()->getSum('money', ['uid' => $uid, 'confirm_status' => 1]);
        $list['toatl_income'] = sprintf('%0.2f', $list['toatl_income']);
        //历史总分佣
        $options = [
            'where' => ['uid' => $uid, 'confirm_status' => 1],
            'where_in' => ['income_type' => [1, 2]]
        ];
        $list['toatl_money'] = HcityCommissionUserDao::i()->getSumExt('money', $options);
        $list['toatl_money'] = sprintf('%0.2f', $list['toatl_money']);
        //新增嘿卡
        $hcardOptions = [
            'where' => ['uid' => $uid, 'confirm_status' => 1],
            'where_in' => ['income_type' => [3, 4]]
        ];
        $list['hcard_total_money'] = HcityCommissionUserDao::i()->getSumExt('money', $hcardOptions);
        $list['hcard_total_money'] = sprintf('%0.2f', $list['hcard_total_money']);
        //新增商家
        $list['merchant_total_money'] = HcityCommissionUserDao::i()->getSum('money', ['uid' => $uid, 'confirm_status' => 1, 'income_type' => 5]);
        $list['merchant_total_money'] = sprintf('%0.2f', $list['merchant_total_money']);
        return $list;
    }

    /**
     * 绑定银行卡
     * @param \s_xhcity_user_do $user
     * @param array $fdata
     * @author ahe<ahe@iyenei.com>
     */
    public function bindBankCard(\s_xhcity_user_do $user, array $fdata)
    {
        $createData = [
            'uid' => $user->uid,
            'user_name' => $fdata['user_name'],
            'bank_card_number' => $fdata['bank_card_number'],
            'bank_name' => $fdata['bank_name'],
        ];
        return ShcityUserBindBankCardDao::i()->create($createData);
    }

    /**
     * 修改绑定银行卡
     * @param \s_xhcity_user_do $user
     * @param array $fdata
     * @return int
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function editBankCard(\s_xhcity_user_do $user, array $fdata)
    {
        $bankCard = ShcityUserBindBankCardDao::i()->getOne(['id' => $fdata['bind_card_id'], 'uid' => $user->uid]);
        if (empty($bankCard)) {
            throw new Exception('银行卡不存在');
        } else {
            $updateData = [
                'user_name' => $fdata['user_name'],
                'bank_card_number' => $fdata['bank_card_number'],
                'bank_name' => $fdata['bank_name'],
            ];
            return ShcityUserBindBankCardDao::i()->update($updateData, ['id' => $fdata['bind_card_id'], 'uid' => $user->uid]);
        }
    }

    /**
     * 获取默认银行卡
     * @param int $uid
     * @return object
     * @author ahe<ahe@iyenei.com>
     */
    public function getDefaultBankCard(int $uid)
    {
        return ShcityUserBindBankCardDao::i()->getOne(['uid' => $uid, 'is_default' => 1]);
    }

    /**
     * 升级成为网点合伙人
     * @param int $uid
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function upgradeWebsiteUser(int $uid)
    {
        $hcityUserDao = HcityUserDao::i();
        $user = $hcityUserDao->getOne(['id' => $uid]);
        if (empty($user) || $user->level != 1) {
            throw new Exception('当前会员不符合升级条件');
        }
        $list = [];
        $list['user_count'] = $hcityUserDao->getCount(['inviter_uid' => $uid, 'is_open_hcard' => 1]);
        $list['shop_count'] = HcityInviteMerchantViewDao::i()->getCount(['inviter_uid' => $uid, 'barcode_status' => 1]);
        if ($list['user_count'] + $list['shop_count'] >= 50) {
            //有资格成为网点合伙人
            $hcityUserDao->update(['level' => 2], ['id' => $uid]);
            $list['status'] = 1;
        } else {
            $list['status'] = 0;
        }

        //删除缓存
        (new HcityUserCache(['uid' => $uid]))->delete();
        return $list;
    }
}
<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午5:30
 */
namespace Service\Bll\Hcity;

use Service\Bll\Hcity\Xcx\XcxBll;
use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManagerBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserBindBankCardDao;
use Service\Enum\XcxTemplateMessageEnum;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWithdrawalDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopBalanceRecordDao;
use Service\Support\FLock;


class WithdrawalBll extends \Service\Bll\BaseBll
{
    /**
     * 提现列表 分页
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     * @author yize<yize@iyenei.com>
     */

    public function withdraList(array $fdata)
    {
        $shardDb = HcityMainDb::i();
        $page = new PageList($shardDb);
        $sql = '1=1';
        if (!empty($fdata['applicant_id'])) {
            $sql .= " and applicant_id = {$page->filter($fdata['applicant_id'])}";
        }
        if (!empty($fdata['apply_time'])) {
            $timeArr = explode(' - ', $fdata['apply_time']);
            if(!isset($timeArr[0]) || !isset($timeArr[1]))
            {
                throw new Exception('搜索时间不正确');
            }
            $timeArr[1]=$timeArr[1] .' 23:59:59';
            $sql .= ' and apply_time>=' . strtotime($timeArr[0]);
            $sql .= ' and apply_time<=' . strtotime($timeArr[1]);
        }
        if (!empty($fdata['phone'])) {
            $sql .= " and phone={$fdata['phone']} ";
        }
        if (!empty($fdata['status'])) {
            $sql .= " and status={$fdata['status']}";
        }
        if (!empty($fdata['type'])) {
            $sql .= " and type={$fdata['type']}";
        }
        $p_conf = $page->getConfig();
        $p_conf->table = "{$shardDb->tables['hcity_withdrawal']} ";
        $p_conf->where = $sql;
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }



    /**
     * 详情
     * @return int $id
     * @author yize<yize@iyenei.com>
     * @author yize<yize@iyenei.com>
     */
    public function withdraDetail(int $id)
    {
        //订单支付详情
        $data = HcityWithdrawalDao::i()->getOne(['id' => $id]);
        if (!$data) {
            throw new Exception('信息不存在');
        }
        if ($data->type == 1) {
            $users = HcityUserDao::i()->getOne(['id' => $data->applicant_id]);
            $username = $users ? $users->username : '';
        } else {
            $shop = MainShopDao::i()->getOne(['id' => $data->applicant_id]);
            $username = $shop ? $shop->shop_name : '';
        }
        $data->username = $username;
        return $data;
    }


    /**
     * 提现审核
     * @param array $fdata status审核状态   id提现列表id
     * @return int
     * @author yize<yize@iyenei.com>
     * @author yize<yize@iyenei.com>
     */
    public function editWithdra(array $fdata)
    {
        //加锁
        if(!FLock::getInstance()->lock('WithdrawalBll:editWithdra:' . $fdata['id']))
        {
            throw new Exception('系统繁忙,请稍后再试。');
        }


        $withdrwDb = HcityWithdrawalDao::i();
        $info = $withdrwDb->getOne(['id' => $fdata['id']]);
        if (!$info) {
            throw new Exception('信息不存在');
        }
        if ($info->status == $fdata['status']) {
            throw new Exception('状态未发生改变');
        }
        if ($info->status != 1) {
            throw new Exception('此状态不可修改');
        }
        $where['id'] = $fdata['id'];
        $params = [
            'status' => $fdata['status'],
            'verify_time' => time(),
        ];
        //开启事物
        $withdrwDb->db->trans_start();
        $withdrwDb->update($params, $where);
        switch ($fdata['status']) {
            //同意打款
            case 2:
                //个人申请
                if ($info->type == 1) {
                    $money = $info->money;
                    //个人
                    $options['where'] = [
                        'id' => $info->applicant_id,
                    ];
                    $user = HcityUserDao::i()->getOne(['id' => $info->applicant_id], 'balance');
                    if(!$user)
                    {
                        $withdrwDb->db->trans_rollback();
                        throw new Exception('用户信息不存在');
                    }
                    HcityUserDao::i()->setDec('lock_balance', $money, $options);
                    $uParams = [
                        'fid' => $info->fid,
                        'money' => $info->money,
                        'type' => 1,
                        'uid' => $info->applicant_id,
                    ];
                    HcityUserBalanceRecordDao::i()->create($uParams);
                    //投递小程序消息通知任务
                    $userWx = HcityUserWxbindDao::i()->getOne(['uid' => $info->applicant_id]);
                    if (!empty($userWx)) {
                        $params = [
                            'openid' => $userWx->open_id,
                            'type' => '提现',
                            'change_money' => '-' . $money,
                            'change_time' => date('Y-m-d H:i:s', time()),
                            'current_money' => $user->balance
                        ];
                        (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::MONEY_CHANGE);
                    }
                    (new HcityUserCache(['uid' => $info->applicant_id]))->delete();
                } elseif ($info->type == 2) {
                    $money = $info->money;
                    //门店
                    $options['where'] = [
                        'shop_id' => $info->applicant_id,
                    ];
                    $shop = HcityShopExtDao::i()->getOne(['shop_id'=>$info->applicant_id]);
                    if(!$shop)
                    {
                        $withdrwDb->db->trans_rollback();
                        throw new Exception('门店信息不存在');
                    }
                    HcityShopExtDao::i()->setDec('lock_balance', $money, $options);
                    $sParams = [
                        'aid' => $info->aid,
                        'fid' => $info->fid,
                        'money' => $info->money,
                        'type' => 1,
                        'shop_id' => $info->applicant_id,
                    ];
                    HcityShopBalanceRecordDao::i()->create($sParams);
                } else {
                    $money = $info->money;
                    //城市合伙人
                    $options['where'] = [
                        'id' => $info->applicant_id,
                    ];
                    $manageAccountDb = HcityManageAccountDao::i();
                    $manage = HcityManageAccountDao::i()->getOne(['id'=>$info->applicant_id]);
                    if(!$manage)
                    {
                        $withdrwDb->db->trans_rollback();
                        throw new Exception('合伙人信息不存在');
                    }
                    $manageAccountDb->setDec('lock_balance', $money, $options);
                    $manageInfo = $manageAccountDb->getOne(['id' => $info->applicant_id]);
                    if (!$manageAccountDb) {
                        $withdrwDb->db->trans_rollback();
                        throw new Exception('城市合伙人信息不存在');
                    }
                    $sParams = [
                        'fid' => $info->fid,
                        'money' => $info->money,
                        'type' => 1,
                        'manager_id' => $info->applicant_id,
                        'hcity_uid' => $manageInfo->id,
                    ];
                    HcityManagerBalanceRecordDao::i()->create($sParams);
                }
                break;
            //拒绝打款
            case 3:
                //个人申请
                if ($info->type == 1) {
                    $money = $info->money;
                    //用户
                    $options['where'] = [
                        'id' => $info->applicant_id,
                    ];
                    $userDb = HcityUserDao::i();
                    $user = $userDb->getOne(['id' => $info->applicant_id]);
                    if(!$user)
                    {
                        $withdrwDb->db->trans_rollback();
                        throw new Exception('用户信息不存在');
                    }
                    $userDb->setDec('lock_balance', $money, $options);
                    $userDb->setInc('balance', $money, $options);
                } elseif ($info->type == 2) {
                    $money = $info->money;
                    //门店
                    $options['where'] = [
                        'shop_id' => $info->applicant_id,
                    ];
                    $shopExtDb = HcityShopExtDao::i();
                    $shop = HcityShopExtDao::i()->getOne(['shop_id'=>$info->applicant_id]);
                    if(!$shop)
                    {
                        $withdrwDb->db->trans_rollback();
                        throw new Exception('门店信息不存在');
                    }
                    $shopExtDb->setDec('lock_balance', $money, $options);
                    $shopExtDb->setInc('balance', $money, $options);
                } else {
                    $money = $info->money;
                    //城市合伙人
                    $options['where'] = [
                        'id' => $info->applicant_id,
                    ];
                    $manageAccountDb = HcityManageAccountDao::i();
                    $manage = HcityManageAccountDao::i()->getOne(['id'=>$info->applicant_id]);
                    if(!$manage)
                    {
                        $withdrwDb->db->trans_rollback();
                        throw new Exception('合伙人信息不存在');
                    }
                    $manageAccountDb->setDec('lock_balance', $money, $options);
                    $manageAccountDb->setInc('balance', $money, $options);
                }
                break;
            default:
                throw new Exception('状态错误');
        }

        if ($withdrwDb->db->trans_status()) {
            $withdrwDb->db->trans_complete();
            return true;
        } else {
            $withdrwDb->db->trans_rollback();
            throw new Exception('修改失败');
        }

    }

    /**
     * 申请提现
     * @param \s_xhcity_user_do $user
     * @param array $fdata
     * @return bool
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function apply(\s_xhcity_user_do $user, array $fdata)
    {
        $hcityUserDao = HcityUserDao::i();
        $userInfo = $hcityUserDao->getOne(['id' => $user->uid]);
        if (empty($userInfo)) {
            throw new Exception('会员不存在');
        }
        if ($userInfo->status == 1) {
            throw new Exception('您已被管理员拉黑，禁止提现!');
        }
        if ($userInfo->balance < $fdata['money']) {
            throw new Exception('提现金额超出可提现金额');
        }
        if ($fdata['money'] < 100) {
            throw new Exception('个人提现金额最低可提现100');
        }
        $bankCard = ShcityUserBindBankCardDao::i()->getOne(['id' => $fdata['bind_card_id'], 'uid' => $user->uid]);
        if (empty($bankCard)) {
            throw new Exception('银行卡不存在');
        }
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        $createData = [
            'applicant_name' => $bankCard->user_name,
            'applicant_id' => $user->uid,
            'phone' => $user->mobile,
            'apply_time' => time(),
            'type' => 1,
            'money' => $fdata['money'],
            'payment_account' => $bankCard->bank_card_number,
            'payment_method' => $bankCard->bank_name,
            'status' => 1,
            'fid' => create_order_number()
        ];
        HcityWithdrawalDao::i()->create($createData);
        $options['where'] = [
            'id' => $userInfo->id
        ];
        $hcityUserDao->setInc('lock_balance', $fdata['money'], $options);
        $hcityUserDao->setDec('balance', $fdata['money'], $options);
        if ($hcityMainDb->trans_status()) {
            $hcityMainDb->trans_complete();
            return true;
        } else {
            $hcityMainDb->trans_rollback();
            return false;
        }
    }


    /**
     * 已经提现完成的财务列表
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     * @author yize<yize@iyenei.com>
     */

    public function billList(array $fdata)
    {
        $shardDb = HcityMainDb::i();
        $page = new PageList($shardDb);
        $p_conf = $page->getConfig();
        $p_conf->where = "status=2";
        if (!empty($fdata['type'])) {
            $p_conf->where .= " and type={$fdata['type']}";
        }
        if (!empty($fdata['verify_time'])) {
            $timeArr = explode(' - ', $fdata['verify_time']);
            if(!isset($timeArr[0]) || !isset($timeArr[1]))
            {
                throw new Exception('搜索时间不正确');
            }
            $timeArr[1]=$timeArr[1] .' 23:59:59';
            $p_conf->where .= ' and verify_time>=' . strtotime($timeArr[0]);
            $p_conf->where .= ' and verify_time<=' . strtotime($timeArr[1]);
        }
        $p_conf->table = "{$shardDb->tables['hcity_withdrawal']} ";
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/16
 * Time: 19:58
 */
namespace Service\Bll\Hcity;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWithdrawalDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManagerBalanceRecordDao;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\Support\FLock;
use Service\DbFrame\DataBase\HcityMainDb;

class ManagerBalanceRecordBll extends \Service\Bll\BaseBll
{


    /**
     * 城市合伙人财务列表
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function managerBalancelist(\s_hmanage_user_do $sUser ,array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->table="{$hcityMainDb->tables['hcity_manager_balance_record']}";
        $p_conf->where="manager_id = {$sUser->id}";
        if($fdata['time'])
        {
            $timeArr=explode(' - ',$fdata['time']);
            $p_conf->where .=' and time>='.strtotime($timeArr[0]);
            $p_conf->where .=' and time<='.strtotime($timeArr[1]);
        }
        if($fdata['type']!=null)
        {
            $p_conf->where .=" and type='{$fdata['type']}'";

        }
        if($fdata['fid'])
        {
            $p_conf->where .=" and fid='{$fdata['fid']}'";
        }
        $p_conf->order= 'id desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        $status=HcityManagerBalanceRecordDao::i()->managerBalanceStatus;
        foreach ($rows['rows'] as &$v) {
            $v['typeAlias']=$status[$v['type']];
        }
        $rows['total']=$count;
        $fields='balance,lock_balance';
        $rows['list']=HcityManageAccountDao::i()->getOneArray(['id'=>$sUser->id],$fields);
        return $rows;
    }


    /**
     * 申请提现
     * @param \s_hmanage_user_do $user
     * @param array $fdata
     * @return bool
     * @throws Exception
     * @author yize<yize@iyenei.com>
     */
    public function applyMoney(\s_hmanage_user_do $sUser, array $fdata)
    {
        if (!FLock::getInstance()->lock('ManageAccountBll:applyMoney:' . $sUser->id)) {
            throw new Exception('提现过于频繁,请稍后再试!');
        }
        $manageInfo = HcityManageAccountDao::i()->getOne(['id' => $sUser->id]);
        if (empty($manageInfo)) {
            throw new Exception('城市合伙人不存在');
        }
        if ($manageInfo->status == 1) {
            throw new Exception('您已被管理员拉黑，禁止提现!');
        }
        if ($manageInfo->balance < $fdata['money']) {
            throw new Exception('提现金额超出可提现金额');
        }
        if ($manageInfo->balance < 100) {
            throw new Exception('个人提现金额最低可提现100，最高可提现500');
        }
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        $createData = [
            'applicant_name' => $fdata['applicant_name'],
            'applicant_id' => $manageInfo->id,
            'phone' => $manageInfo->mobile,
            'apply_time' => time(),
            'type' => 3,
            'money' => $fdata['money'],
            'payment_account' => $fdata['payment_account'],
            'payment_method' => $fdata['payment_method'],
            'status' => 1,
            'fid' => create_order_number()
        ];
        HcityWithdrawalDao::i()->create($createData);
        $options['where'] = [
            'id' => $manageInfo->id
        ];
        HcityManageAccountDao::i()->setInc('lock_balance', $fdata['money'], $options);
        HcityManageAccountDao::i()->setDec('balance', $fdata['money'], $options);
        if ($hcityMainDb->trans_status()) {
            $hcityMainDb->trans_complete();
            return true;
        } else {
            $hcityMainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 申请提现
     * @param \s_hmanage_user_do $user
     * @return bool
     * @throws Exception
     * @author yize<yize@iyenei.com>
     */
    public function cardInfo(\s_hmanage_user_do $sUser)
    {
        $hcityWithdrawalDao = HcityWithdrawalDao::i();
        $fields = 'applicant_id,payment_account,payment_method,applicant_name';
        $orderBy = 'id desc';
        $cardInfo = $hcityWithdrawalDao->getOne(['applicant_id'=>$sUser->id,'type'=>3],$fields,$orderBy);
        if($cardInfo){
            return $cardInfo;
        }else{
            $cardInfo->applicant_id = '';
            $cardInfo->payment_account = '';
            $cardInfo->payment_method = '';
            $cardInfo->applicant_name = '';
            return $cardInfo;
        }
    }


}
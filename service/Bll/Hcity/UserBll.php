<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/25
 * Time: 14:30
 */
namespace Service\Bll\Hcity;

use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityInviteMerchantViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCompanyExtDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserOrderDao;
use Service\Support\Page\PageList;
use Service\Exceptions\Exception;


class UserBll extends \Service\Bll\BaseBll
{

    /**
     * 获取用户列表 分页
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */

    public function userList(array $fdata)
    {
        $mainDb = HcityMainDb::i();
        $page = new PageList($mainDb);
        $sql='1=1';
        if ($fdata['username']) {
            $sql .= " and username like '%{$page->filterLike($fdata['username'])}%'";
        }
        if ($fdata['mobile']) {
            $sql .= " and mobile={$fdata['mobile']} ";
        }
        // 邀请人用户Id
        if (isset($fdata['inviter_uid']) && is_numeric($fdata['inviter_uid'])) {
            $sql .= " and inviter_uid={$fdata['inviter_uid']} ";
        }
        // 是否骑士
        if (isset($fdata['is_qs']) && is_numeric($fdata['is_qs'])) {
            $sql .= " and is_qs={$fdata['is_qs']} ";
        }
        if ($fdata['level']!=null) {
            //普通会员和嘿卡会员
            if($fdata['level']==1 || $fdata['level']==4 || $fdata['level']==5)
            {
                $sql .= " and level=1 ";
                if($fdata['level']== 1)
                {
                    $sql .= "  and  (hcard_expire_time is null or hcard_expire_time <".time().")";
                }
                if($fdata['level']== 4)
                {
                    $sql .= " and is_open_hcard='1' and is_trial_hcard ='1' and hcard_expire_time>".time();
                }
                if($fdata['level']==5)
                {
                    $sql .= " and is_open_hcard='1' and is_trial_hcard ='0' and hcard_expire_time>".time();
                }
                
            }else
            {
                $sql .= " and level={$fdata['level']} ";
            }

        }
        if($fdata['status']!=null) {
            $sql.=' and status='.$fdata['status'];
        }
        $p_conf=$page->getConfig();
        $p_conf->fields='id,username,mobile,status,img,time,hcard_expire_time,is_open_hcard,balance,lock_balance,income,level,is_trial_hcard,is_qs';
        $p_conf->table="{$mainDb->tables['hcity_User']}";
        $p_conf->where =$sql;
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach($rows['rows'] as &$v){
            if($v['level'] == 1){
                if($v['is_trial_hcard'] == 1 && $v['hcard_expire_time'] > time()){
                    $v['level'] = "4";
                }
                //开通黑卡会员
                if($v['is_open_hcard'] == 1 && $v['hcard_expire_time'] > time() && $v['is_trial_hcard'] == 0){
                   $v['level'] = "5";
                }
            } 
        }
        $rows['total'] = $count;
        return $rows;
    }




    /**
     * 用户详情
     * @return int $id
     * @author yize<yize@iyenei.com>
     */
    public function userDetail(int $id)
    {
        $userDb = HcityUserDao::i();
        $where=['id'=>$id];
//        $fields='id,shop_id,tid,uid,buyer_username,buyer_phone,total_money,total_num,payment,pay_type,pay_time,status,time,hx_time';
        $data= $userDb->getOne($where);
        if(!$data)
        {
            throw new Exception('用户不存在');
        }
        //邀请商家数量
        $data->companyCount=HcityCompanyExtDao::i()->getCount(['inviter_uid'=>$id]);
        //邀请用户数量
        $data->userCount=$userDb->getCount(['inviter_uid'=>$id]);
        //邀请嘿卡数量
        $data->userHcardCount=$userDb->getCount(['inviter_uid'=>$id,'is_open_hcard'=>1]);
        //邀请一店一码数量
        $data->merchantCount=HcityInviteMerchantViewDao::i()->getCount(['inviter_uid'=>$id,'barcode_status'=>1]);
        //订单总数
        $data->orderCount=ShcityUserOrderDao::i(['uid'=>$data->id])->getCount(['uid'=>$id]);
        $hcityUserBalanceRecordDb=HcityUserBalanceRecordDao::i();
        //邀请办卡总收入
        $data->userHcardMoney=$hcityUserBalanceRecordDb->getSum('money',"uid={$id}  and type =4");
        //邀请商家总收入
        $data->userCompanyMoney=$hcityUserBalanceRecordDb->getSum('money',"uid={$id}  and type = 5");
        //商品佣金总收入
        $data->userGoodsMoney=$hcityUserBalanceRecordDb->getSum('money',"uid={$id} and type =3");
        return $data;
    }

    /**
     * 用户修改状态
     * @return int $id
     * @author yize<yize@iyenei.com>
     */
    public function userEditStatus(array $fdata)
    {
        $UserDb = HcityUserDao::i();
        $where=['id'=>$fdata['id']];
        $data= $UserDb->getOne($where);
        if(!$data)
        {
            throw new Exception('用户不存在');
        }
        $ret=$UserDb->update(['status'=>$fdata['status']],['id'=>$fdata['id']]);

        //删除user缓存
        (new HcityUserCache(['uid'=>$fdata['id']]))->delete();

        if(!$ret)
        {
            throw new Exception('修改失败');
        }
    }


    /**
     * 某个用户财务记录
     * @return int $id
     * @author yize<yize@iyenei.com>
     */
    public function userBill(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->table="{$hcityMainDb->tables['hcity_user_balance_record']}";
        $userInfo= HcityUserDao::i()->getOne(['id'=>$fdata['uid']]);
        if(!$userInfo)
        {
            throw new Exception('用户不存在');
        }
        $p_conf->where="uid={$fdata['uid']}";
        if($fdata['time'])
        {
            $timeArr=explode(' - ',$fdata['time']);
            if(!isset($timeArr[0]) || !isset($timeArr[1]))
            {
                throw new Exception('搜索时间不正确');
            }
            $timeArr[1]=$timeArr[1] .' 23:59:59';
            $p_conf->where .=' and time>='.strtotime($timeArr[0]);
            $p_conf->where .=' and time<='.strtotime($timeArr[1]);
        }
        if ($fdata['fid']) {
            $p_conf->where .= " and fid={$fdata['fid']} ";
        }
        if ($fdata['type']) {
            $p_conf->where .= " and type={$fdata['type']} ";
        }
        $p_conf->order= 'id desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        //状态别名转换
        $orderDb = HcityUserBalanceRecordDao::i();
        $statusArr = $orderDb->userBalanceStatus;
        foreach($rows['rows'] as &$v)
        {
            $v['typeAlias']=$statusArr[$v['type']];
        }
        $rows['total']=$count;
        return $rows;
    }
}
<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/17
 * Time: 上午9:45
 */
namespace Service\Bll\Hcity;

use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCompanyExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityInviteMerchantViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountApplyDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManagerBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\Enum\MessageTagEnum;
use Service\Enum\MessageTemplateEnum;
use Service\DbFrame\DataBase\MainDbModels\MainKvDao;

class ManageAccountApplyBll extends \Service\Bll\BaseBll
{

    /**
     * 城市合伙人列表
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function manageApplylist(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->table="{$hcityMainDb->tables['hcity_manage_account_apply']}";
        $p_conf->where .=" and status != 1";
        if($fdata['username'])
        {
            $p_conf->where .=" and username={$page->filter($fdata['username'])}";
        }
        if($fdata['mobile'])
        {
            $p_conf->where .=" and mobile='{$fdata['mobile']}'";
        }
        if($fdata['status'] !=null)
        {
            $p_conf->where .=" and status='{$fdata['status']}'";
        }
        $p_conf->order= 'id desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        $rows['total']=$count;
        return $rows;
    }



    /**
     * 城市合伙人详情
     * @param  int  $id
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function manageApplyDetail(int $id)
    {
        $rows=HcityManageAccountApplyDao::i()->getOne(['id'=>$id]);
        if(!$rows)
        {
            throw new Exception('信息不存在');
        }
        $userDb = HcityUserDao::i();
        $uid=$rows->uid;
//        $fields='id,shop_id,tid,uid,buyer_username,buyer_phone,total_money,total_num,payment,pay_type,pay_time,status,time,hx_time';
        $data= $userDb->getOne(['id'=>$uid]);
        if(!$data)
        {
            throw new Exception('用户不存在');
        }
        //邀请商家数量
        $rows->companyCount=HcityCompanyExtDao::i()->getCount(['inviter_uid'=>$uid]);
        //邀请用户数量
        $rows->userCount=$userDb->getCount(['inviter_uid'=>$uid]);
        //邀请嘿卡数量
        $rows->userHcardCount=$userDb->getCount(['inviter_uid'=>$uid,'is_open_hcard'=>1]);
        //邀请一店一码数量
        $rows->merchantCount=HcityInviteMerchantViewDao::i()->getCount(['inviter_uid'=>$uid,'barcode_status'=>1]);
        $rows->income=$data->income;
        //近6个月得到的钱
        $sixTime= strtotime("-6 month");
        $rows->six_money=HcityUserBalanceRecordDao::i()->getSum('money',"uid = {$uid} and type > 1 and time>= {$sixTime}");
        return $rows;
    }


    /**
     * 城市合伙人申请审核
     * @param  int  $id
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function editApplyStatus(array $fdata)
    {
        $manageApplyDb=HcityManageAccountApplyDao::i();
        $manageDb=HcityManageAccountDao::i();
        $applyInfo=$manageApplyDb->getOne(['id'=>$fdata['id']]);
        if(!$applyInfo)
        {
            throw new Exception('信息不存在');
        }
        if($applyInfo->status==$fdata['status'])
        {
            throw new Exception('状态未发生改变');
        }
        if($applyInfo->status!=0)
        {
            throw new Exception('此状态不可改变');
        }
        if($fdata['status']==1)
        {

            if(!$fdata['expire_time'])
            {
                throw new Exception('请填写合作过期时间');
            }
            //合作到期时间
            $fdata['expire_time']=$fdata['expire_time'] . ' 23:59:59';
            $expire_time=strtotime($fdata['expire_time']);
            $randPassword=rand(10000000,99999999);
            $password = md5($randPassword.SECRET_KEY);
            //地区
            $region_name['0'] = $applyInfo->shop_state;
            $region_name['1'] = $applyInfo->shop_city;
            if(!empty($applyInfo->shop_district)){
                //城市合伙人允许只到市
                $region_name['2'] = $applyInfo->shop_district;
            }
            $list = implode('-',$region_name);

            $params=[
                'username'=>$applyInfo->username,
                'mobile'=>$applyInfo->mobile,
                'region'=>$applyInfo->region,
                'region_name' =>$list,
                'hcity_uid'=>$applyInfo->uid,
                'password'=>$password,
                'expire_time'=>$expire_time,
                'apply_time'=>$applyInfo->time,
            ];
            $time=time();
            $mdate=$manageDb->getOne(['mobile'=>$applyInfo->mobile]);
            if($mdate)
            {
                throw new Exception('已存在此手机号');
            }
            $regionArr = explode('-', $applyInfo->region);
            if (count($regionArr) == 3) {
                //找市级城市合伙人
                array_pop($regionArr);
                $regionNew = implode('-', $regionArr);
                $manage = $manageDb->getOne(['region'=>$regionNew,'expire_time>'=>$time,'type'=>0]);
                if(!empty($manage)){
                    throw new Exception('此地区已存在市级合伙人，禁止添加区级合伙人');
                }
            }elseif(count($regionArr) == 2){
                $sql = sprintf(" region like '%s%%' and expire_time > %s and type = 0", $applyInfo->region, $time);
                $manage = $manageDb->getOne($sql);
                if (!empty($manage)) {
                    throw new Exception('此地区已存在区级合伙人，禁止添加市级合伙人');
                }
            }

            $rdate=$manageDb->getOne(['region'=>$applyInfo->region,'expire_time>'=>$time]);
            if($rdate)
            {
                //存在城市合伙人时 则判断是否过期
                if($rdate->type==0 && $rdate->expire_time>$time)
                {
                    throw new Exception('此地区已存在合伙人');
                }
            }
            //开启事物
            $manageDb->db->trans_start();
            //更新审核状态
            $manageApplyDb->update(['status'=>$fdata['status']],['id'=>$fdata['id']]);
            //添加城市合伙人
            $manageDb->create($params);
            //延迟嘿卡时间
            $userDb=HcityUserDao::i();
            $userInfo=$userDb->getOne(['id'=>$applyInfo->uid]);
            if(!$userInfo)
            {
                $manageApplyDb->db->trans_rollback();
                throw new Exception('申请合伙人的用户信息不存在');
            }
            $time=time();

            //嘿卡到期时间  如果以前嘿卡时间还有余则加上
            if($userInfo->hcard_expire_time>$time)
            {
                $hcard_expire_time=$userInfo->hcard_expire_time+$expire_time-$time;
            }else
            {
                $hcard_expire_time=$expire_time;
            }
            //更新合作用户信息
            $userDb->update(['level'=>3,'is_open_hcard'=>1,'hcard_expire_time'=>$hcard_expire_time],['id'=>$applyInfo->uid]);

            //删除user缓存
            (new HcityUserCache(['uid'=>$applyInfo->uid]))->delete();

            $mainKvDao = MainKvDao::i();
            $msg=$mainKvDao->getOne(['key'=>MessageTemplateEnum::HCITY_MANAGE]);
            if(!$msg || empty($msg->value)){
                $manageApplyDb->db->trans_rollback();
                throw new Exception('短信模板不存在');
            }

            $mobile_api=new \mobile_code();
            $msg_v=str_replace(['{mobile}','{code}'],[$applyInfo->mobile,$randPassword],$msg->value);

            $res=$mobile_api->send($applyInfo->mobile,$msg_v,MessageTagEnum::HCITY);
            if($res) {
                $manageApplyDb->db->trans_rollback();
                throw new Exception('短信发送失败');
            }
            if ($manageApplyDb->db->trans_status()) {
                $manageApplyDb->db->trans_complete();
                return true;
            } else {
                $manageApplyDb->db->trans_rollback();
                throw new Exception('审核失败');
            }
        }else
        {
            if(!$fdata['refuse_remark'])
            {
                throw new Exception('请填写拒绝原因');
            }
            $ret=$manageApplyDb->update(['status'=>$fdata['status'],'refuse_remark'=>$fdata['refuse_remark']],['id'=>$fdata['id']]);
            if(!$ret)
            {
                throw new Exception('审核失败');
            }
        }

        return ture;

    }




}
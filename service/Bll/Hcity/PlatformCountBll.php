<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/8
 * Time: 19:58
 */
namespace Service\Bll\Hcity;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionManagerDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionPlatformDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsApplyViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWithdrawalDao;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCompanyExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManagerBalanceRecordDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityGoodsJzDao;
class PlatformCountBll extends \Service\Bll\BaseBll
{

    /**
     * 首页统计数据
     * @param array $sUser
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function indexCount(\s_hmanage_user_do $sUser)
    {
        $data=(object)array();
        if($sUser->type==0)
        {
            //待审核商品
            $data->goodsApplyCount=HcityGoodsApplyViewDao::i()->getCount("audit_status =1 and region like '{$sUser->region}%'");
            $shopData=MainShopDao::i()->getAll( "region like '{$sUser->region}%'");
            $shopIds=array_column($shopData,'id');
            $shopIds=empty($shopIds) ? '0' : implode(',', $shopIds);
            //属于商圈门店才需要审核
            $shopData=MainShopRefitemDao::i()->getAll(['saas_id'=>3]);
            $shopRefIds=array_column($shopData,'shop_id');
            $shopRefIds=empty($shopRefIds) ? '0' : implode(',', $shopRefIds);
            //待审核商家
            $data->shopApplyCount=HcityShopExtDao::i()->getCount("hcity_audit_status = 1 and shop_id in ($shopIds) and shop_id in ($shopRefIds)");
            $info=HcityManageAccountDao::i()->getone(['id'=>$sUser->id]);
            //邀请嘿卡用户
            $data->hcardUserCount=HcityUserDao::i()->getCount(['inviter_uid'=>$info->hcity_uid,'is_open_hcard'=>1]);
            //商家数量
            $data->companyCount=HcityShopExtDao::i()->getCount("(hcity_show_status = 1 or barcode_status = 1) and shop_id in ($shopIds)");
            //商品数量
            $data->goodsCount=HcityGoodsKzViewDao::i()->getCount("region like '{$sUser->region}%'");
            // 当天的零点
            $dayBegin = strtotime(date('Y-m-d', time()));
            // 当天的24
            $dayEnd = $dayBegin + 24 * 60 * 60;
            //今日预计收入
            $commissionManagerDb=HcityCommissionManagerDao::i();
            $data->dayExpectMoney=$commissionManagerDb->getSum('money',"manager_id={$sUser->id} and time >= {$dayBegin} and time <= {$dayEnd}");
            //今日预估分佣收入
            $data->dayGoodsMoney=$commissionManagerDb->getSum('money',"manager_id={$sUser->id} and time >= {$dayBegin} and time <= {$dayEnd} and  (income_type =1  or income_type=2)");
            $hcityManagerBalanceRecordDb=HcityManagerBalanceRecordDao::i();
            //今日邀请办卡收入
            $data->dayHcardMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id = {$sUser->id}   and type =4  and time >= {$dayBegin} and time <= {$dayEnd}");
            //今日邀请商户收入
            $data->dayCompanyMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id ={$sUser->id}   and type = 5 and time >= {$dayBegin} and time <= {$dayEnd}");
            //历史总收入
            $data->allMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id = {$sUser->id} and (type =3 or type=4 or type=5 )");
            //邀请办卡总收入
            $data->manageHcardMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id = {$sUser->id}  and type =4");
            //邀请商家总收入
            $data->manageCompanyMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id ={$sUser->id}   and type = 5");
            //历史商品总收入
            $data->manageGoodsMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id = {$sUser->id}  and type =3");
            //点赞活动统计
            $data->activityJzCount=HcityActivityGoodsJzDao::i()->getCount("audit_status = 1 and region like '{$sUser->region}%'");
        }else
        {
            //待审核商品
            $data->goodsApplyCount=HcityGoodsApplyViewDao::i()->getCount(['audit_status'=>1]);
            //属于商圈门店才需要审核
            $shopData=MainShopRefitemDao::i()->getAll(['saas_id'=>3]);
            $shopRefIds=array_column($shopData,'shop_id');
            $shopRefIds=empty($shopRefIds) ? '0' : implode(',', $shopRefIds);
            //待审核商家
            $data->shopApplyCount=HcityShopExtDao::i()->getCount("hcity_audit_status = 1 and shop_id in ($shopRefIds)");
            //待处理提现
            $data->withdrawalCount=HcityWithdrawalDao::i()->getCount(['status'=>1]);
            //嘿卡数量
            $data->userHcardCount=HcityUserDao::i()->getCount(['is_open_hcard'=>1]);
            //商家数量
            $data->companyCount=HcityShopExtDao::i()->getCount("hcity_show_status = 1 or barcode_status = 1");
            //商品总数
            $data->goodsCount=HcityGoodsKzViewDao::i()->getCount(false);
            //点赞活动统计
            $data->activityJzCount=HcityActivityGoodsJzDao::i()->getCount("audit_status = 1");
        }

        return $data;
    }



    /**
     * 平台统计数据
     * @param array $sUser
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function platformCount()
    {
        $data=(object)array();
        //平台收入表
        $hcityCommissionPlatformDb=HcityCommissionPlatformDao::i();
        //提现表
        $hcityWithdrawalDb=HcityWithdrawalDao::i();

        //上月时间
        $lastDayBegin = mktime(0, 0 , 0,date("m")-1,1,date("Y"));
        $lastDayEnd = mktime(23,59,59,date("m") ,0,date("Y"));
        //本月时间
        //php获取本月起始时间戳和结束时间戳
        $thisDayBegin=mktime(0,0,0,date('m'),1,date('Y'));
        $thisDayEnd=mktime(23,59,59,date('m'),date('t'),date('Y'));
        //账户总收入
        $data->allMoneyCount=$hcityCommissionPlatformDb->getSum('money',[1=>1]);
        //上个月收入
        $data->lastMonthMoneyCount=$hcityCommissionPlatformDb->getSum('money',"time >= {$lastDayBegin} and time <= {$lastDayEnd}");
        //本月收入
        $data->thisMonthMoneyCount=$hcityCommissionPlatformDb->getSum('money',"time >= {$thisDayBegin} and time <= {$thisDayEnd}");
        //本月新增嘿卡用户
        $data->thisMonthUserCount=HcityUserDao::i()->getCount("is_open_hcard = 1 and time >= {$thisDayBegin} and time <= {$thisDayEnd}");
        //本月新增商圈商户
        $data->thisMonthCompanyCount=HcityShopExtDao::i()->getCount("hcity_show_status = 1 and  hcity_pass_time >= {$thisDayBegin} and hcity_pass_time <= {$thisDayEnd}");
        //本月新增一店一码数量
        $data->thisMonthShopCount=HcityShopExtDao::i()->getCount("barcode_status=1 and barcode_open_time >= {$thisDayBegin} and barcode_open_time <= {$thisDayEnd}");
        //总支出
        $data->allPayMoneyCount=$hcityWithdrawalDb->getSum('money',"status=2");
        //上个月支出
        //$data->lastPayMoneyCount=$hcityWithdrawalDb->getSum('money',"apply_time >= {$thisDayBegin} and apply_time <= {$thisDayEnd}");
        $data->lastPayMoneyCount=$hcityWithdrawalDb->getSum('money',"status=2 and apply_time >= {$lastDayBegin} and apply_time <= {$lastDayEnd}");
        //本月支出
        $data->thisPayMoneyCount=$hcityWithdrawalDb->getSum('money',"status=2 and apply_time >= {$thisDayBegin} and apply_time <= {$thisDayEnd}");
        //本月个人支出
        $data->thisUserPayMoneyCount=$hcityWithdrawalDb->getSum('money',"status=2 and apply_time >= {$thisDayBegin} and apply_time <= {$thisDayEnd} and type =1");
        //本月商户支出
        $data->thisShopPayMoneyCount=$hcityWithdrawalDb->getSum('money',"status=2 and apply_time >= {$thisDayBegin} and apply_time <= {$thisDayEnd} and type =2");
        //本月城市合伙人支出
        $data->thisManagePayMoneyCount=$hcityWithdrawalDb->getSum('money',"status=2 and apply_time >= {$thisDayBegin} and apply_time <= {$thisDayEnd} and type =3");
        return $data;
    }



    /**
     * 平台收入记录
     * @param array $sUser
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function platformBill(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();

        $p_conf->table="{$hcityMainDb->tables['hcity_commission_platform']}";

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
        if ($fdata['income_type']) {
            $p_conf->where .= " and income_type={$fdata['income_type']} ";
        }
        $p_conf->order= 'id desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        //状态别名转换
        $orderDb = HcityCommissionPlatformDao::i();
        $statusArr = $orderDb->platformBalanceStatus;
        foreach($rows['rows'] as &$v)
        {
            $v['typeAlias']=$statusArr[$v['income_type']];
        }
        $rows['total']=$count;
        return $rows;
    }

    /**
     * 平台充值记录
     * @param array $sUser
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function platformRecharge(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();

        $p_conf->table="{$hcityMainDb->tables['hcity_payment_record']} a left join {$hcityMainDb->tables['hcity_user']} b on a.uid=b.id ";
       
        if($fdata['time'])
        {
            $timeArr=explode(' - ',$fdata['time']);
            if(!isset($timeArr[0]) || !isset($timeArr[1]))
            {
                throw new Exception('搜索时间不正确');
            }
            $timeArr[1]=$timeArr[1] .' 23:59:59';
            $p_conf->where .=' and a.time>='.strtotime($timeArr[0]);
            $p_conf->where .=' and a.time<='.strtotime($timeArr[1]);
        }
        if ($fdata['pay_tid']) {
            $p_conf->where .= " and a.pay_tid={$fdata['pay_tid']} ";
        }
        if ($fdata['mobile']) {
            $p_conf->where .= " and b.mobile like '%".$page->filterLike($fdata['mobile'])."%' ";
        }
        $p_conf->where .= " and a.type = '3' and a.status = '1'";
         $p_conf->fields = "a.*,b.mobile";
        $p_conf->order= 'id desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        $rows['total']=$count;
        return $rows;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/16
 * Time: 19:58
 */
namespace Service\Bll\Hcity;
use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionManagerDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionPlatformDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsApplyViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWithdrawalDao;
use Service\DbFrame\DataBase\MainDbModels\MainAreaDao;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountApplyDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCompanyExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityInviteMerchantViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManagerBalanceRecordDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Cache\Hcity\HcityManageAccountCache;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\Enum\MessageTagEnum;
use Service\Enum\MessageTemplateEnum;
use Service\DbFrame\DataBase\MainDbModels\MainKvDao;
class ManageAccountBll extends \Service\Bll\BaseBll
{
    /**
     * 申请城市合伙人
     * @param array $input
     * @return int
     */
    public function applyManageAccount(array $input)
    {
        $hcityManageAccountApplyDao = HcityManageAccountApplyDao::i();
        $applyData = [
            'uid' => $input['uid'],
            'mobile' => $input['mobile'],
            'username' => $input['username'],
            'region' => $input['region'],
            'shop_state' => $input['shop_state'],
            'shop_city' => $input['shop_city'],
            'shop_district' => $input['shop_district'],
            'status' => 0,
            'time' => time(),
        ];
        return $hcityManageAccountApplyDao->create($applyData);
    }


    /**
     * 城市合伙人列表
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function managelist(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->fields='a.id,a.username,a.mobile,a.status,a.type,a.region_name,a.time,a.expire_time,a.balance,a.lock_balance,b.balance as user_balance';
        $p_conf->table="{$hcityMainDb->tables['hcity_manage_account']} a left join {$hcityMainDb->tables['hcity_user']} b on a.hcity_uid=b.id";
        $p_conf->where .= " and a.type=0";
        if($fdata['region'])
        {
            $p_conf->where .= " and a.region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if($fdata['mobile'])
        {
            $p_conf->where .=" and a.mobile='{$fdata['mobile']}'";
        }
        if($fdata['username'])
        {
            $p_conf->where .=" and a.username='{$fdata['username']}'";
        }
        if($fdata['status'] !=null )
        {
            $time=time();
            if($fdata['status']==2)
            {
                $p_conf->where .=" and a.expire_time < " .$time;
            }else
            {
                $p_conf->where .=" and a.status='{$fdata['status']}'";
                $p_conf->where .=" and a.expire_time >= " .$time;
            }
        }

        $p_conf->order= 'a.id desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        $time=time();
        foreach($rows['rows'] as &$v)
        {
            if($v['expire_time']<$time)
            {
                $v['status']=2;
            }
        }
        $rows['total']=$count;
        return $rows;
    }

    /**
     * 添加城市合伙人
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function addManage(array $fdata)
    {
        $userDb=HcityUserDao::i();
        $mangeAccountDb=HcityManageAccountDao::i();
        $fdata['expire_time']=$fdata['expire_time'] . ' 23:59:59';
        $time=time();
        if(strtotime($fdata['expire_time'])<=$time)
        {
            throw new Exception('过期时间必须小于当前时间');
        }
        $mobileAccountInfo=$mangeAccountDb->getOne(['mobile'=>$fdata['mobile']]);
        if($mobileAccountInfo)
        {
            throw new Exception('已存在此手机号');
        }
        $regionAccountInfo=$mangeAccountDb->getOne(['region'=>$fdata['region'],'expire_time>'=>$time]);
        if($regionAccountInfo)
        {
            //存在城市合伙人时 则判断是否过期
            if($regionAccountInfo->expire_time>$time)
            {
                throw new Exception('此地区已存在城市合伙人');
            }
        }
        $regionArr=explode('-',$fdata['region']);
        if (count($regionArr) == 3) {
            //找市级城市合伙人
            array_pop($regionArr);
            $regionNew = implode('-', $regionArr);
            $manage = $mangeAccountDb->getOne(['region'=>$regionNew,'expire_time>'=>$time,'type'=>0]);
            if(!empty($manage)){
                throw new Exception('此地区已存在市级合伙人，禁止添加区级合伙人');
            }
        }elseif(count($regionArr) == 2){
            $sql = sprintf(" region like '%s%%' and expire_time > %s and type = 0", $fdata['region'], $time);
            $manage = $mangeAccountDb->getOne($sql);
            if (!empty($manage)) {
                throw new Exception('此地区已存在区级合伙人，禁止添加市级合伙人');
            }
        }
        $regionArr=explode('-',$fdata['region']);
        $region_name='';
        foreach($regionArr as $v)
        {
            $areaInfo=MainAreaDao::i()->getOne(['id'=>$v]);
            if(!$areaInfo)
            {
                throw new Exception('地址错误');
            }
            $region_name.=$areaInfo->name.'-';
        }
        $region_name=rtrim($region_name,'-');
        //开启事物
        $userDb->db->trans_start();
        $userInfo=$userDb->getOne(['mobile'=>$fdata['mobile']]);
        $expire_time=strtotime($fdata['expire_time']);
        $pass=$fdata['password'];
        if(!$pass)
        {
            $pass=rand(10000000,99999999);
        }
        $password = md5($pass.SECRET_KEY);
        //存在用户时
        if($userInfo)
        {
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
            $userDb->update(['level'=>3,'is_open_hcard'=>1,'hcard_expire_time'=>$hcard_expire_time],['id'=>$userInfo->id]);
            $manageParams=[
                'hcity_uid'=>$userInfo->id,
                'expire_time'=>$expire_time,
                'password'=>$password,
                'region_name'=>$region_name,
                'region'=>$fdata['region'],
                'mobile'=>$fdata['mobile'],
                'username'=>$fdata['username'],
            ];
            $mangeAccountDb->create($manageParams);
            //删除user缓存
            (new HcityUserCache(['uid'=>$userInfo->id]))->delete();
        }else
        {
            $params=[
                'username'=>$fdata['username'],
                'mobile'=>$fdata['mobile'],
                'id_code'=>generate_id_code(),
                'level'=>3,
                'hcard_expire_time'=>$expire_time,
                'is_open_hcard'=>1,
                'shard_node' => '0-0-0'
            ];
            $uid=$userDb->create($params);
            if(!$uid)
            {
                $userDb->db->trans_rollback();
                throw new Exception('添加用户失败');
            }
            $userInfo=$userDb->getOne(['mobile'=>$fdata['mobile']]);
            //插入合伙人数据
            $manageParams=[
                'hcity_uid'=>$userInfo->id,
                'expire_time'=>$expire_time,
                'password'=>$password,
                'region_name'=>$region_name,
                'region'=>$fdata['region'],
                'mobile'=>$fdata['mobile'],
                'username'=>$fdata['username'],
            ];
            $mangeAccountDb->create($manageParams);
        }
         
        $mainKvDao = MainKvDao::i();
        $msg=$mainKvDao->getOne(['key'=>MessageTemplateEnum::HCITY_MANAGE]);
        if(!$msg || empty($msg->value)){
            $userDb->db->trans_rollback();
            throw new Exception('短信模板不存在');
        }

        $mobile_api=new \mobile_code();
        $msg_v=str_replace(['{mobile}','{code}'],[$fdata['mobile'],$pass],$msg->value);
        $res=$mobile_api->send($fdata['mobile'],$msg_v,MessageTagEnum::HCITY);
        if($res) {
            $userDb->db->trans_rollback();
            throw new Exception('短信发送失败');
        }

        if ($userDb->db->trans_status())
        {
            $userDb->db->trans_complete();
            return true;
        }else
        {
            $userDb->db->trans_rollback();
            throw new Exception('修改失败');
        }

    }


    /**
     * 编辑城市合伙人
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function editManage(array $fdata)
    {
        $userDb=HcityUserDao::i();
        $mangeAccountDb=HcityManageAccountDao::i();
        $info=$mangeAccountDb->getOne(['id'=>$fdata['id']]);
        if(!$info)
        {
            throw new Exception('信息不存在');
        }
        if($info->type==1)
        {
            throw new Exception('超级管理员不可修改');
        }
        $fdata['expire_time']=$fdata['expire_time'] . ' 23:59:59';
        $time=time();
        //嘿卡管理员区分一下
        $mobileAccountInfo=$mangeAccountDb->getOne("mobile= {$fdata['mobile']} and id != {$fdata['id']}");
        if($mobileAccountInfo)
        {
            throw new Exception('已存在此手机号');
        }
        //地址
        $regionArr=explode('-',$fdata['region']);
        if (count($regionArr) == 3) {
            //找市级城市合伙人
            array_pop($regionArr);
            $regionNew = implode('-', $regionArr);
            $manage = $mangeAccountDb->getOne(['region'=>$regionNew,'expire_time>'=>$time,'type'=>0]);
            if(!empty($manage)){
                throw new Exception('此地区已存在市级合伙人，禁止添加区级合伙人');
            }
        }elseif(count($regionArr) == 2){
            $sql = sprintf(" region like '%s%%' and expire_time > %s and type = 0", $fdata['region'], $time);
            $manage = $mangeAccountDb->getOne($sql);
            if (!empty($manage)) {
                throw new Exception('此地区已存在区级合伙人，禁止添加市级合伙人');
            }
        }
        $regionArr=explode('-',$fdata['region']);
        $region_name='';
        foreach($regionArr as $v)
        {
            $areaInfo=MainAreaDao::i()->getOne(['id'=>$v]);
            if(!$areaInfo)
            {
                throw new Exception('地址错误');
            }
            $region_name.=$areaInfo->name.'-';
        }
        $region_name=rtrim($region_name,'-');
        if(strtotime($fdata['expire_time'])<=$time)
        {
            throw new Exception('过期时间必须大于当前时间');
        }
        $regionAccountInfo=$mangeAccountDb->getOne("region = '{$fdata['region']}' and id !={$fdata['id']} and expire_time > {$time}");
        if($regionAccountInfo)
        {
            throw new Exception('此地区已存在城市合伙人');
        }
        $userInfo=$userDb->getOne(['id'=>$info->hcity_uid]);
        if(!$userInfo)
        {
            throw new Exception('嘿卡用户不存在');
        }
        $expire_time=strtotime($fdata['expire_time']);
        if($fdata['password'])
        {
            $password = md5($fdata['password'].SECRET_KEY);
            $params['password']=$password;
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
        //开启事物
        $userDb->db->trans_start();
        $params['hcity_uid']=$userInfo->id;
        $params['expire_time']=$expire_time;
        $params['mobile']=$fdata['mobile'];
        $params['username']=$fdata['username'];
        $params['region']=$fdata['region'];
        $params['region_name']=$region_name;
        $mangeAccountDb->update($params,['id'=>$fdata['id']]);
        //更新合作用户信息
        $userDb->update(['level'=>3,'is_open_hcard'=>1,'hcard_expire_time'=>$hcard_expire_time],['id'=>$userInfo->id]);

        //删除user缓存
        (new HcityUserCache(['uid'=>$userInfo->id]))->delete();

        if($userDb->db->trans_status())
        {
            $hcityManageAccountCache = new HcityManageAccountCache(['id'=>$info->id]);
            $hcityManageAccountCache->delete();
            $userDb->db->trans_complete();
            return true;
        }else
        {
            $userDb->db->trans_rollback();
            throw new Exception('修改失败');
        }


    }


    /**
     * 城市合伙人详情
     * @param  int  $id
     * @return array $data
     * @author yize<yize@iyenei.com>
     */
    public function manageDetail(int $id)
    {
        $userDb = HcityUserDao::i();
        $fields='id,username,mobile,status,type,region_name,region,time,expire_time,balance,lock_balance,income,apply_time,hcity_uid';
        $data=HcityManageAccountDao::i()->getOne(['id'=>$id],$fields);
        if(!$data)
        {
            throw new Exception('信息不存在');
        }
        if($data->expire_time<time())
        {
            $data->status=2;
        }
        //邀请商家数量
        $companyAll=HcityCompanyExtDao::i()->getAll(['inviter_uid'=>$data->hcity_uid]);
        $aids=array_column($companyAll,'aid');
        $aids=empty($aids) ? '0' : implode(',', $aids);
        $data->companyCount=HcityShopExtDao::i()->getCount("aid in ($aids)");
        //邀请用户数量
        $data->userCount=$userDb->getCount(['inviter_uid'=>$data->hcity_uid]);
        //邀请嘿卡数量
        $data->userHcardCount=$userDb->getCount(['inviter_uid'=>$data->hcity_uid,'is_open_hcard'=>1]);
        //邀请一店一码数量
        $data->merchantCount=HcityInviteMerchantViewDao::i()->getCount(['inviter_uid'=>$data->hcity_uid,'barcode_status'=>1]);
        //近6个月得到的钱
        $sixTime= strtotime("-6 month");
        $hcityManagerBalanceRecordDb=HcityManagerBalanceRecordDao::i();
        $data->six_money=$hcityManagerBalanceRecordDb->getSum('money',"manager_id = {$id} and 1 < type and  type < 6 and time>= {$sixTime}");
        //邀请办卡总收入
        $data->manageHcardMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id = {$id}  and type =4");
        //邀请商家总收入
        $data->manageCompanyMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id ={$id}  and type = 5");
        //商品佣金总收入
        $data->manageGoodsMoney=$hcityManagerBalanceRecordDb->getSum('money',"manager_id = {$id} and type =3");
        return $data;
    }


    /**
     * 某一个城市合伙人财务记录
     * param array $fdata
     * @return int $row
     * @author yize<yize@iyenei.com>
     */
    public function manageBill(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->table="{$hcityMainDb->tables['hcity_manager_balance_record']}";
        $manageInfo= HcityManageAccountDao::i()->getOne(['id'=>$fdata['manage_id']]);
        if(!$manageInfo)
        {
            throw new Exception('合伙人不存在');
        }
        $p_conf->where="manager_id={$fdata['manage_id']}";
        if($fdata['time'])
        {
            $timeArr=explode(' - ',$fdata['time']);
            if(!isset($timeArr[0]) || !isset($timeArr[1]))
            {
                throw new Exception('搜索时间不正确');
            }
            $timeArr[1]=$timeArr[1] .' 23:59:59';
            $p_conf->where .=' and apply_time>='.strtotime($timeArr[0]);
            $p_conf->where .=' and apply_time<='.strtotime($timeArr[1]);
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
        $orderDb = HcityManagerBalanceRecordDao::i();
        $statusArr = $orderDb->managerBalanceStatus;
        foreach($rows['rows'] as &$v)
        {
            $v['typeAlias']=$statusArr[$v['type']];
        }
        $rows['total']=$count;
        return $rows;
    }


    /**
     * 修改城市合伙人状态
     * @param  int  $id
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function editManageStatus(array $fdata)
    {
        $manageDb=HcityManageAccountDao::i();
        $info=$manageDb->getOne(['id'=>$fdata['id']]);
        if(!$info)
        {
            throw new Exception('信息不存在');
        }
        if($info->type==1)
        {
            throw new Exception('超级管理员不可修改');
        }
        $ret=$manageDb->update(['status'=>$fdata['status']],['id'=>$fdata['id']]);
        if(!$ret)
        {
            throw new Exception('修改失败');
        }
        return true;

    }
    /**
     * 获取sUser
     * @param  string $mobile   手机号
     * @param  stirng $password 未加密密码
     * @return object(s_hmamage_user_do)
     */
    public function getHmSuser(string $mobile,string $password)
    {
        $where['mobile'] = $mobile;
        $where['password'] = md5($password.SECRET_KEY);
        $mHcityManageAccount = HcityManageAccountDao::i()->getOne($where);
        $manageInfo = explode('-',$mHcityManageAccount->region);
        $number = count($manageInfo);
        if(!$mHcityManageAccount)
            throw new Exception('账号和密码错误');
        if($mHcityManageAccount->status==1)
            throw new Exception('账号已被被禁用，请联系管理员');
        $sUser = new \s_hmanage_user_do;
        $sUser->id = $mHcityManageAccount->id;
        $sUser->mobile = $mHcityManageAccount->mobile;
        $sUser->username = $mHcityManageAccount->username;
        $sUser->type = $mHcityManageAccount->type;
        $sUser->region = $mHcityManageAccount->region;
        $sUser->region_name = $mHcityManageAccount->region_name;
        $sUser->region_type = $number > 2 ? "1" : "2";
        return $sUser;
    }









}
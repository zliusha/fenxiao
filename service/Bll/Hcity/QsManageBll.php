<?php
/**
 * 骑士管理
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/8/27
 * Time: 上午9:53
 */
namespace Service\Bll\Hcity;

use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityQsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcitySalesAccountDao;
use Service\DbFrame\DataBase\MainDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Cache\Hcity\HcityQsCache;

class QsManageBll extends \Service\Bll\BaseBll
{
   
	/**
     * 获取所有骑士申请上架列表
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function applyList(\s_hmanage_user_do $sUser, array $fdata)
    {	
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_qs']} a left join {$hcityMainDb->tables['hcity_sales_account']} b on a.sales_id=b.id ";
        $p_conf->where = "  a.audit_status in(1,3) ";
        if ($sUser->type == 0) {
            $p_conf->where .= " and a.region like '%{$page->filterLike($sUser->region)}%'";
        }
        if ($fdata['region']) {
            $p_conf->where .= " and a.region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['audit_status'] != null) {
            $p_conf->where .= ' and a.audit_status=' . $fdata['audit_status'];
        }
        if($fdata['time']){
            $timeArr=explode(' - ',$fdata['time']);
            $p_conf->where .=" and a.time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and a.time<".$lasttime; 
        }
        if ($fdata['experience_type'] != null) {
            $p_conf->where .= ' and a.experience_type=' . $fdata['experience_type'];
        }
        if ($fdata['name']) {
            $p_conf->where .= " and a.name like '%{$page->filterLike($fdata['name'])}%'";
        }
        if ($fdata['mobile']) {
            $p_conf->where .= " and a.mobile like '%{$page->filterLike($fdata['mobile'])}%'";
        }
        if ($fdata['salename']) {
            $p_conf->where .= " and b.mobile like '%{$page->filterLike($fdata['salename'])}%'";
        }
       	$p_conf->fields = 'a.*,b.name as sname,b.mobile as bmobile';
        $p_conf->order = 'a.id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach($rows['rows'] as $key=>$value){
            if(empty($value['bmobile'])){
                $rows['rows'][$key]['bmobile'] = "无";
            }
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * @param $where
     * @param array $timeArr 时间条件数组[['key'=>['stime','etime','alias]]]
     * @return mixed
     * @liusha
     */
    public function incomeStatistics($where, $timeArr=[])
    {
        $hcityMainDb = HcityMainDb::i();
        $sql = "SELECT
                IFNULL(SUM(money), 0) AS `total_money`";
        foreach ($timeArr as $k => $v) {
            $sql .= " ,IFNULL(SUM(CASE WHEN `time`>={$v['stime']} AND `time`<{$v['etime']} THEN `money` ELSE 0 END),0) AS `{$k}` ";
        }

        $sql .= " FROM {$hcityMainDb->tables['hcity_user_balance_record']} ";
        if($where)
            $sql .= ' WHERE '.$where;

        return $hcityMainDb->query($sql)->result_array();
    }

    /**
     * 获取骑士收入明细
     * @param int $uid
     * @return mixed
     * @liusha
     */
    public function getIncomeList(int $uid, $input)
    {
        $shardDb = HcityMainDb::i();
        $page = new PageList($shardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$shardDb->tables['hcity_user_balance_record']} ";
        $p_conf->where .= " AND uid = {$page->filter($uid)} and type in (5,7,8,9) and money > 0 ";
        $p_conf->order = 'id desc';
        //时间段判断
        if($input['stime'])
        {
            $p_conf->where .= " AND time>={$input['stime']}";
        }
        if($input['etime'])
        {
            $p_conf->where .= " AND time<{$input['etime']}";
        }

        $count = 0;
        $data['rows'] = $page->getList($p_conf, $count);
        $data['total'] = $count;
        return $data;
    }


    /**
     * 黑卡骑士审核列表审核
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function editAuditStatus(array $fdata)
    {   
        $hcityMainDb = HcityMainDb::i();
        $hcityQsDao = HcityQsDao::i();
        //审核通过
        $info = $hcityQsDao->getOneArray(['id' => $fdata['id']]);
        if (!$info) {
            throw new Exception('此用户信息不存在，无法审核');
        }
        //如果审核通过
        if ($fdata['audit_status'] == 2) {
            //开启事务
            $hcityMainDb->trans_start();
            $HcityUserDao = HcityUserDao::i();
            $userinfo = $HcityUserDao->getOneArray(['id'=>$info['uid']]);
            if(!$userinfo){
                throw new Exception('此会员信息不存在，无法审核');
            }
            $hcard_expire_time = 365*86400;
            $userdata = [];
            if($userinfo['hcard_expire_time']>time()){
                $userdata['hcard_expire_time'] = $userinfo['hcard_expire_time'] + $hcard_expire_time;
            } else {
                $userdata['hcard_expire_time'] = time() + $hcard_expire_time;
            } 
            $userdata['is_open_hcard'] = 1;
            $userdata['is_trial_hcard'] = 0;
            $userdata['is_qs'] = 1;
            $userstatus = $HcityUserDao->update($userdata,['id'=>$userinfo['id']]);
            $status = $hcityQsDao->update(['status' => '1', 'audit_status' => $fdata['audit_status'], 'audit_time' => time()], ['id' => $fdata['id']]);
            //删除骑士缓存
            (new HcityQsCache(['uid'=>$info['uid']]))->delete();
            if ($hcityMainDb->trans_status()) {
                $hcityMainDb->trans_complete();
                return true;
            } else {
                $hcityMainDb->trans_rollback();
                return false;
            }
        }
        //审核不通过
        if ($fdata['audit_status'] == 3) {
            $status = $hcityQsDao->update(['audit_status' => $fdata['audit_status'], 'audit_time' => time()], ['id' => $fdata['id']]);
            //删除骑士缓存
            (new HcityQsCache(['uid'=>$info['uid']]))->delete();
            if ($status) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 申请骑士详情(总后台)
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function applyDetail(array $fdata)
    {
        $hcityQsDao = HcityQsDao::i();
        $rows['qsinfo'] = $hcityQsDao->getOneArray(['id' => $fdata['id']]);
        if(!empty($rows['qsinfo']['sales_id'])){
            $rows['salesinfo'] = HcitySalesAccountDao::i()->getOneArray(['id' => $rows['qsinfo']['sales_id']],'mobile');
        } else {
            $rows['salesinfo']['mobile']  = "无"; 
        }
        $HcityUserDao = HcityUserDao::i();
        $userinfo = $HcityUserDao->getOneArray(['id'=>$rows['qsinfo']['uid']]);
        if($userinfo['inviter_uid']){
          $inviterinfo = $HcityUserDao->getOneArray(['id'=>$userinfo['inviter_uid']],'mobile'); 
          $rows['inviterinfo']['mobile'] = $inviterinfo['mobile'];        
        }
        if($userinfo['inviter_shop_id']){
          $inviterinfo = MainShopDao::i()->getOneArray(['id'=>$userinfo['inviter_shop_id']],'contact'); 
          $rows['inviterinfo']['mobile'] = $inviterinfo['contact'];        
        }
        if(empty($userinfo['inviter_uid']) && empty($userinfo['inviter_shop_id'])){
          $rows['inviterinfo']['mobile'] = "无";  
        }
        //统计骑士
        if($rows['qsinfo']['audit_status'] == 2){
        $userinfo = [
                'id' => $rows['qsinfo']['id'],
                'uid' => $rows['qsinfo']['uid'],
                'audit_time' => $rows['qsinfo']['audit_time']
            ];
        $rows['invite']['qscount'] = $this->getCountqs($userinfo);
        $rows['invite']['hcitycount'] = $this->getCounthcity($userinfo);
        $rows['invite']['ydymcount'] = $this->getCountydym($userinfo);
        }
        return $rows;
    }

    /**
     * 获取所有骑士申请上架列表
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function qsList(\s_hmanage_user_do $sUser, array $fdata)
    {   
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_qs']} a left join {$hcityMainDb->tables['hcity_sales_account']} b on a.sales_id=b.id ";
        $p_conf->where = "  a.audit_status = '2' ";
        if ($sUser->type == 0) {
            $p_conf->where .= " and a.region like '%{$page->filterLike($sUser->region)}%'";
        }
        if ($fdata['region']) {
            $p_conf->where .= " and a.region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['status'] != null) {
            $p_conf->where .= ' and a.status=' . $fdata['status'];
        }
        if($fdata['time']){
            $timeArr=explode(' - ',$fdata['time']);
            $p_conf->where .=" and a.time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and a.time<".$lasttime; 
        }
        if ($fdata['experience_type'] != null) {
            $p_conf->where .= ' and a.experience_type=' . $fdata['experience_type'];
        }
        if ($fdata['name']) {
            $p_conf->where .= " and a.name like '%{$page->filterLike($fdata['name'])}%'";
        }
        if ($fdata['mobile']) {
            $p_conf->where .= " and a.mobile like '%{$page->filterLike($fdata['mobile'])}%'";
        }
        if ($fdata['salename']) {
            $p_conf->where .= " and b.mobile like '%{$page->filterLike($fdata['salename'])}%'";
        }
        $p_conf->fields = 'a.*,b.name as sname,b.mobile as bmobile';
        $p_conf->order = 'a.id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach($rows['rows'] as $key=>$value){
            if(empty($value['bmobile'])){
                $rows['rows'][$key]['bmobile'] = "无";
            }
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 黑卡骑士审核
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function qsStatus(array $fdata)
    {   
        $hcityMainDb = HcityMainDb::i();
        $hcityQsDao = HcityQsDao::i();
        //审核通过
        $info = $hcityQsDao->getOneArray(['id' => $fdata['id']]);
        if (!$info) {
            throw new Exception('此用户信息不存在，无法审核');
        }
        $hcityMainDb->trans_start();
        $HcityUserDao = HcityUserDao::i();
        $userinfo = $HcityUserDao->getOneArray(['id'=>$info['uid']]);
        if(!$userinfo){
            throw new Exception('此会员信息不存在，无法审核');
        }
        //如果重新设置为黑卡骑士
        if ($fdata['status'] == 1) {
            //开启事务 
            $userstatus = $HcityUserDao->update(['is_qs'=>'1'],['id'=>$userinfo['id']]);
            $status = $hcityQsDao->update(['status' => $fdata['status']], ['id' => $fdata['id']]);
        }
        //如果拉黑黑卡骑士
        if ($fdata['status'] == 2) {
            $userstatus = $HcityUserDao->update(['is_qs'=>'0'],['id'=>$userinfo['id']]);
            $status = $hcityQsDao->update(['status' => $fdata['status']], ['id' => $fdata['id']]);
        }
        //删除骑士缓存
        (new HcityQsCache(['uid'=>$info['uid']]))->delete();
            
        if ($hcityMainDb->trans_status()) {
            $hcityMainDb->trans_complete();
            return true;
        } else {
            $hcityMainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 获取邀请骑士列表
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function inviteQs( array $fdata)
    {   
        $hcityMainDb = HcityMainDb::i();
        $hcityQsDao = HcityQsDao::i();
        $qsinfo = $hcityQsDao->getOneArray(['id' => $fdata['id']]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_qs']} a left join {$hcityMainDb->tables['hcity_user']} b on a.uid=b.id ";
        $p_conf->where .= " and b.inviter_uid ={$qsinfo['uid']} ";
        if ($fdata['region']) {
            $p_conf->where .= " and a.region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['status'] != null) {
            $p_conf->where .= ' and a.status=' . $fdata['status'];
        }
        if($fdata['time']){
            $timeArr=explode(' - ',$fdata['time']);
            $p_conf->where .=" and a.time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and a.time<".$lasttime; 
        }
        if ($fdata['name']) {
            $p_conf->where .= " and a.name like '%{$page->filterLike($fdata['name'])}%'";
        }
        if ($fdata['mobile']) {
            $p_conf->where .= " and a.mobile like '%{$page->filterLike($fdata['mobile'])}%'";
        }
        $p_conf->fields = 'a.*';
        $p_conf->order = 'a.id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach($rows['rows'] as $key=>$value){
            $userinfo = [
                'id' => $value['id'],
                'uid' => $value['uid'],
                'audit_time' => $value['audit_time']
            ];
            $rows['rows'][$key]['qscount'] = $this->getCountqs($userinfo);
            $rows['rows'][$key]['hcitycount'] = $this->getCounthcity($userinfo);
            $rows['rows'][$key]['ydymcount'] = $this->getCountydym($userinfo);
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取邀请商圈列表
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function inviteHcity( array $fdata)
    {   
        $hcityQsDao = HcityQsDao::i();
        //审核通过
        $info = $hcityQsDao->getOneArray(['id' => $fdata['id']]);
        if (!$info) {
            throw new Exception('此用户信息不存在，无法审核');
        }
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->fields ='a.id,a.aid,a.shop_id,a.hcity_pass_time,a.qs_shop_status';
        $p_conf->table = "{$hcityMainDb->tables['hcity_shop_ext']} a left join {$hcityMainDb->tables['hcity_company_ext']} b on a.aid=b.aid";
        $p_conf->where .= " and b.inviter_uid={$info['uid']} AND a.hcity_show_status=1 and a.hcity_pass_time >".$info['audit_time'];
        if ($fdata['qs_shop_status'] != null) {
            $p_conf->where .= ' and a.qs_shop_status=' . $fdata['qs_shop_status'];
        }
        if($fdata['time']){
            $timeArr=explode(' - ',$fdata['time']);
            $p_conf->where .=" and a.hcity_pass_time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and a.hcity_pass_time<".$lasttime; 
        }
        $ret=[];
        //当搜索主表信息时
        if ($fdata['shop_name'] || $fdata['contact'] || $fdata['region'])
        {
            $shopWhere='1=1';
            $shopWhere.= $fdata['shop_name'] ? " and shop_name like '%{$page->filterLike($fdata['shop_name'])}%'" : '';
            $shopWhere.= $fdata['contact'] ? " and contact = {$page->filter($fdata['contact'])}" : '';
            $shopWhere.= $fdata['region'] ? " and region like '%{$page->filterLike($fdata['region'])}%'" : '';
            $shopDb=MainShopDao::i();
            $ret=$shopDb->getAllArray($shopWhere);
            $shopIds = array_column($ret,'id');
            $shopIds=empty($shopIds) ? '0' : implode(',', $shopIds);
            $p_conf->where .= " and shop_id in ($shopIds) ";
        }
        $p_conf->order= 'a.id desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        //当搜索过主表时 直接取店铺信息  没有则查询一次
        if(!$ret)
        {
            $shopIds = array_column($rows['rows'],'shop_id');
            $shopIds=empty($shopIds) ? '0' : implode(',', $shopIds);
            $where = " id in ($shopIds) ";
            $shopDb=MainShopDao::i();
            $ret=$shopDb->getAllArray($where);
        }
        //循环赋值
        foreach($rows['rows'] as &$val)
        {
            foreach($ret as $v)
            {
                if($val['shop_id']==$v['id'])
                {
                    $val['shop_name']=$v['shop_name'];
                    $val['contact']=$v['contact'];
                    $addsinfo = [];
                    $addsinfo[0] = $v['shop_state'];
                    $addsinfo[1] = $v['shop_city'];
                    $addsinfo[2] = $v['shop_district'];
                    $val['address']=implode('-',$addsinfo);
                }
            }
            if(!isset($val['shop_name']))
            {
                $val['shop_name']='';
                $val['contact']='';
                $val['address']= '';
            }
        }
        $rows['total']=$count;
        return $rows;
    }

    /**
     * 获取邀请一店一码列表
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function inviteYdym( array $fdata)
    {   
        $hcityQsDao = HcityQsDao::i();
        //审核通过
        $info = $hcityQsDao->getOneArray(['id' => $fdata['id']]);
        if (!$info) {
            throw new Exception('此用户信息不存在，无法审核');
        }
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->fields ='a.id,a.aid,a.shop_id,a.barcode_open_time';
        $p_conf->table = "{$hcityMainDb->tables['hcity_shop_ext']} a left join {$hcityMainDb->tables['hcity_company_ext']} b on a.aid=b.aid";
        $p_conf->where .= " and b.inviter_uid={$info['uid']} AND a.barcode_status=1 and a.barcode_open_time >".$info['audit_time'];
        if($fdata['time']){
            $timeArr=explode(' - ',$fdata['time']);
            $p_conf->where .=" and a.barcode_open_time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and a.barcode_open_time<".$lasttime; 
        }
        $ret=[];
        //当搜索主表信息时
        if ($fdata['shop_name'] || $fdata['contact'] || $fdata['region'])
        {
            $shopWhere='1=1';
            $shopWhere.= $fdata['shop_name'] ? " and shop_name like '%{$page->filterLike($fdata['shop_name'])}%'" : '';
            $shopWhere.= $fdata['contact'] ? " and contact = {$page->filter($fdata['contact'])}" : '';
            $shopWhere.= $fdata['region'] ? " and region like '%{$page->filterLike($fdata['region'])}%'" : '';
            $shopDb=MainShopDao::i();
            $ret=$shopDb->getAllArray($shopWhere);
            $shopIds = array_column($ret,'id');
            $shopIds=empty($shopIds) ? '0' : implode(',', $shopIds);
            $p_conf->where .= " and shop_id in ($shopIds) ";
        }
        $p_conf->order= 'a.id desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        //当搜索过主表时 直接取店铺信息  没有则查询一次
        if(!$ret)
        {
            $shopIds = array_column($rows['rows'],'shop_id');
            $shopIds=empty($shopIds) ? '0' : implode(',', $shopIds);
            $where = " id in ($shopIds) ";
            $shopDb=MainShopDao::i();
            $ret=$shopDb->getAllArray($where);
        }
        //循环赋值
        foreach($rows['rows'] as &$val)
        {
            foreach($ret as $v)
            {
                if($val['shop_id']==$v['id'])
                {
                    $val['shop_name']=$v['shop_name'];
                    $val['contact']=$v['contact'];
                    $addsinfo = [];
                    $addsinfo[0] = $v['shop_state'];
                    $addsinfo[1] = $v['shop_city'];
                    $addsinfo[2] = $v['shop_district'];
                    $val['address']=implode('-',$addsinfo);
                }
            }
            if(!isset($val['shop_name']))
            {
                $val['shop_name']='';
                $val['contact']='';
                $val['address']= '';
            }
        }
        $rows['total']=$count;
        return $rows;
    }

    /**
     * 销售邀请骑士列表
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function salesInvite(array $fdata)
    {   
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_qs']} ";
        $p_conf->where = "  sales_id = {$fdata['id']} ";
        if ($fdata['region']) {
            $p_conf->where .= " and region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['status'] != null) {
            $p_conf->where .= ' and status=' . $fdata['status'];
        }
        if($fdata['time']){
            $timeArr=explode(' - ',$fdata['time']);
            $p_conf->where .=" and time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and time<".$lasttime; 
        }
        if ($fdata['name']) {
            $p_conf->where .= " and name like '%{$page->filterLike($fdata['name'])}%'";
        }
        if ($fdata['mobile']) {
            $p_conf->where .= " and mobile like '%{$page->filterLike($fdata['mobile'])}%'";
        }
        $p_conf->fields = '*';
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        foreach($rows['rows'] as $key=>$value){
            $userinfo = [
                'id' => $value['id'],
                'uid' => $value['uid'],
                'audit_time' => $value['audit_time']
            ];
            $rows['rows'][$key]['qscount'] = $this->getCountqs($userinfo);
            $rows['rows'][$key]['hcitycount'] = $this->getCounthcity($userinfo);
            $rows['rows'][$key]['ydymcount'] = $this->getCountydym($userinfo);
        }
        return $rows;
    }

    /**
     * 统计邀请的黑卡骑士
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function getCountqs($fdata){
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_qs']} a left join {$hcityMainDb->tables['hcity_user']} b on a.uid=b.id ";
        $p_conf->where .= " and b.inviter_uid ={$fdata['uid']} ";
        $p_conf->fields = 'a.*';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows['total'];    
    }

    /**
     * 统计邀请的商圈
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function getCounthcity($fdata){
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->fields ='a.id,a.aid,a.shop_id,a.hcity_apply_time,a.hcity_pass_time,a.hcity_audit_status,a.hcity_show_status,a.barcode_status,a.category_name_list';
        $p_conf->table = "{$hcityMainDb->tables['hcity_shop_ext']} a left join {$hcityMainDb->tables['hcity_company_ext']} b on a.aid=b.aid";
        $p_conf->where .= " and b.inviter_uid={$fdata['uid']} AND a.hcity_show_status=1 AND a.qs_shop_status =1 and a.hcity_pass_time >".$fdata['audit_time'];
        //时间段判断
        $p_conf->order= 'id desc';
        $count = 0;
        $row=$page->getList($p_conf,$count);
        $row['total']=$count;
        return $row['total'];
    }

    /**
     * 统计邀请的商圈
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function getCountydym($fdata){
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->fields ='a.id,a.aid,a.shop_id,a.hcity_apply_time,a.hcity_pass_time,a.hcity_audit_status,a.hcity_show_status,a.barcode_status,a.category_name_list';
        $p_conf->table = "{$hcityMainDb->tables['hcity_shop_ext']} a left join {$hcityMainDb->tables['hcity_company_ext']} b on a.aid=b.aid";
        $p_conf->where .= " and b.inviter_uid={$fdata['uid']} AND a.barcode_status=1  and a.barcode_open_time >".$fdata['audit_time'];
        //时间段判断
        $p_conf->order= 'id desc';
        $count = 0;
        $row=$page->getList($p_conf,$count);
        //当搜索过主表时 直接取店铺信息  没有则查询一次
        $row['total']=$count;
        return $row['total'];
    }


    /**
     * 获取小程序端骑士列表
     * @param array $uid
     * @return array $input
     * @author liusha
     */
    public function qsXcxList(array $input)
    {
        $mainDb = HcityMainDb::i();
        $page = new PageList($mainDb);
        $sql='1=1';
        if ($input['username']) {
            $sql .= " and a.name like '%{$page->filterLike($input['username'])}%'";
        }
        if ($input['mobile']) {
            $sql .= " and a.mobile={$input['mobile']} ";
        }
        // 邀请人用户Id
        if (isset($input['inviter_uid']) && is_numeric($input['inviter_uid'])) {
            $sql .= " and b.inviter_uid={$input['inviter_uid']} ";
        }
        // 是否骑士
        if (isset($input['is_qs']) && is_numeric($input['is_qs'])) {
            $sql .= " and a.is_qs={$input['is_qs']} ";
        }

        if(isset($input['status']) && is_numeric($input['status'])) {
            $sql.=' and a.status='.$input['status'];
        }

        $p_conf=$page->getConfig();
        $p_conf->fields='a.id,a.uid,a.name,a.mobile,a.region,a.region_name,a.experience_type,a.income,a.status,a.audit_status,b.img,b.status user_status';
        $p_conf->table="{$mainDb->tables['hcity_qs']} a left join {$mainDb->tables['hcity_user']} b on a.uid=b.id";
        $p_conf->where = $sql;
        $p_conf->order = 'a.id asc';
        $count = 0;
        $list = $page->getList($p_conf, $count);
        $data['rows'] = convert_client_list($list, [['type'=>'img','field'=>'img']]);
        $data['total'] = $count;
        return $data;
    }

    /**
     * 根据邀请人获取被邀请人的数量
     * @param int $uid
     * @param array $input
     * @return mixed
     */
    public function getQsCountByInviterUid(int $uid ,array $input)
    {
        $hcityMainDb = HcityMainDb::i();
        $sql = "SELECT COUNT(*) as count FROM {$hcityMainDb->tables['hcity_user']} a left join {$hcityMainDb->tables['hcity_qs']} b on a.id=b.uid ";
        $sql .= " WHERE a.inviter_uid={$uid}";

        //时间段判断
        if($input['stime'])
        {
            $sql .= " AND a.time>={$input['stime']}";
        }
        if($input['etime'])
        {
            $sql .= " AND a.time<{$input['etime']}";
        }
        // 骑士状态
        if($input['is_qs'] && is_numeric($input['is_qs']))
        {
            $sql .= " AND a.is_qs={$input['is_qs']}";
        }
        // 骑士状态
        if($input['newbie_task_status'] && is_numeric($input['newbie_task_status']))
        {
            $sql .= " AND b.newbie_task_status={$input['newbie_task_status']}";
        }

        return $hcityMainDb->query($sql)->row()->count;
    }

    /**
     * 获取收入排行
     * @param int $uid
     * @return mixed
     */
    public function getIncomeRank(int $uid)
    {
        $hcityMainDb = HcityMainDb::i();
        $hcityQsDao = HcityQsDao::i();
        //获取店铺所有商品
        $goods_config_arr = array(
            'field' => 'a.*,b.img',
            'table' => "{$hcityMainDb->tables['hcity_qs']} a",
            'join' => array(
                array("{$hcityMainDb->tables['hcity_user']} b", "a.uid=b.id", 'left')
            ),
            'where' => " a.status=1",
            'limit' => 10,
            'order_by' => 'a.income desc,audit_time asc,id asc'
        );

        //获取排行骑士列表
        $rank_list = $hcityQsDao->getEntitysByAR($goods_config_arr, true);
        $data['list'] = convert_client_list($rank_list, [['type'=>'img','field'=>'img']]);

        // 我的排行
        $mQs = $hcityQsDao->getOne(['uid'=>$uid]);
        $income = floatval($mQs->income);
        $my_rank = $hcityQsDao->getCount("status=1 AND (income>{$income} OR (income={$income} AND audit_time<{$mQs->audit_time}))");
        $data['my_rank'] = $my_rank+1;
        return $data;
    }
}
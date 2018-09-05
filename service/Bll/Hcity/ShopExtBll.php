<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/17
 * Time: 上午9:45
 */
namespace Service\Bll\Hcity;

use Service\Cache\Hcity\HcityUserCache;
use Service\Cache\Hcity\HcityShopExtCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\Enum\SaasEnum;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopBalanceRecordDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;

class ShopExtBll extends \Service\Bll\BaseBll
{
    /**
     * 门店列表
     * @param object  $sUser  登陆信息
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function shopList(\s_hmanage_user_do $sUser ,array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->fields='id,shop_id,hcity_apply_time,hcity_pass_time,hcity_audit_status,hcity_show_status,barcode_status,category_name_list';
        $p_conf->table="{$hcityMainDb->tables['hcity_shop_ext']}";
        $time=time();
        //一店一码状态
        if(isset($fdata['barcode_status']) &&  $fdata['barcode_status']!=null)
        {
            //一点一码已过期
            if($fdata['barcode_status']==2)
            {
                $p_conf->where .=" and barcode_status = 1 and barcode_expire_time < {$time} ";
            }else
            {
                $p_conf->where .=' and barcode_status='.$fdata['barcode_status'];
            }

        }
        //入住商圈状态
        if(isset($fdata['hcity_show_status']) &&  $fdata['hcity_show_status']!=null)
        {
            $p_conf->where .=' and hcity_show_status='.$fdata['hcity_show_status'];
        }
        $ret=[];
        //当搜索主表信息时  城市合伙人
        if ($sUser->type==0 || $fdata['shop_name'] || $fdata['contact'] || $fdata['region'])
        {
            $shopWhere='1=1';
            //$shopWhere.=" and region like '{$sUser->region}%'";
            //$shopWhere.=" and region like '{$fdata['region']}%'";
            $shopWhere.= $sUser->type==0 ? " and region like '{$sUser->region}%'" : '';
            $shopWhere.= $fdata['shop_name'] ? " and shop_name like '%{$page->filterLike($fdata['shop_name'])}%'" : '';
            $shopWhere.= $fdata['contact'] ? " and contact = {$page->filter($fdata['contact'])}" : '';
            $shopWhere.= $fdata['region'] ? " and region like '%{$page->filterLike($fdata['region'])}%'" : '';
            $shopDb=MainShopDao::i();
            $ret=$shopDb->getAllArray($shopWhere);
            $shopIds = array_column($ret,'id');
            $shopIds=empty($shopIds) ? '0' : implode(',', $shopIds);
            $p_conf->where .= " and shop_id in ($shopIds) ";
        }
        $p_conf->order= 'id desc';
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
                    $val['shop_state']=$v['shop_state'];
                    $val['shop_city']=$v['shop_city'];
                    $val['shop_district']=$v['shop_district'];
                    $val['shop_address']=$v['shop_address'];
                }
            }
            if(!isset($val['shop_name']))
            {
                $val['shop_name']='';
                $val['contact']='';
                $val['shop_state']='';
                $val['shop_city']='';
                $val['shop_district']='';
                $val['shop_address']='';
            }
        }
        $rows['total']=$count;
        return $rows;
    }


    /**
     * 获取所有门店申请列表
     * @param object  $sUser  登陆信息
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function shopApplyList(\s_hmanage_user_do $sUser ,array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->fields='id,shop_id,hcity_apply_time,hcity_pass_time,hcity_audit_status,hcity_show_status,barcode_status,category_name_list';
        $p_conf->table="{$hcityMainDb->tables['hcity_shop_ext']}";
        //属于商圈门店才需要审核
        $shopData=MainShopRefitemDao::i()->getAll(['saas_id'=>3]);
        $shopRefIds=array_column($shopData,'shop_id');
        $shopRefIds=empty($shopRefIds) ? '0' : implode(',', $shopRefIds);
        if($shopRefIds)
        {
            $p_conf->where .= " and shop_id in ($shopRefIds) ";
        }
        //审核状态
        if(isset($fdata['hcity_audit_status']) && $fdata['hcity_audit_status']!=null)
        {
            $p_conf->where .=' and hcity_audit_status='.$fdata['hcity_audit_status'];
        }
        $ret=[];
        //当搜索主表信息时  城市合伙人
        if ($sUser->type==0 || $fdata['shop_name'] || $fdata['contact'] || $fdata['region'])
        {
            $shopWhere='1=1';
            //$shopWhere.=" and region like '{$sUser->region}%'";
            //$shopWhere.=" and region like '{$fdata['region']}%'";
            $shopWhere.= $sUser->type==0 ? " and region like '{$sUser->region}%'" : '';
            $shopWhere.= $fdata['region'] ? " and region like '%{$page->filterLike($fdata['region'])}%'" : '';
            $shopWhere.= $fdata['shop_name'] ? " and shop_name like '%{$page->filterLike($fdata['shop_name'])}%'" : '';
            $shopWhere.= $fdata['contact'] ? " and contact = {$page->filter($fdata['contact'])}" : '';
            $shopDb=MainShopDao::i();
            $ret=$shopDb->getAllArray($shopWhere);
            $shopIds = array_column($ret,'id');
            $shopIds=empty($shopIds) ? '0' : implode(',', $shopIds);
            $p_conf->where .= " and shop_id in ($shopIds) ";
        }

        $p_conf->order= 'id desc';
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
                    $val['shop_state']=$v['shop_state'];
                    $val['shop_city']=$v['shop_city'];
                    $val['shop_district']=$v['shop_district'];
                    $val['shop_address']=$v['shop_address'];
                }
            }
            if(!isset($val['shop_name']))
            {
                $val['shop_name']='';
                $val['contact']='';
                $val['shop_state']='';
                $val['shop_city']='';
                $val['shop_district']='';
                $val['shop_address']='';
            }
        }
        $rows['total']=$count;
        return $rows;
    }

    /**
     * 门店详情
     * @param array $fdata
     * @return array  $data
     * @author yize<yize@iyenei.com>
     */
    public function shopDetail(int $id)
    {
        $shopExt=HcityShopExtDao::i()->getOne(['id'=>$id]);
        if(!$shopExt)
        {
            throw new Exception('门店信息不存在');
        }
        $shopExt->notice = htmlspecialchars_decode($shopExt->notice);
        $fieldsMain= 'shop_logo,shop_name,contact,shop_state,shop_city,shop_district,shop_address';
        $shopMain=MainShopDao::i()->getOne(['id'=>$shopExt->shop_id],$fieldsMain);
        if(!$shopExt)
        {
            throw new Exception('主门店信息不存在');
        }
        $data=(object) array_merge((array) $shopExt, (array) $shopMain);
        return $data;
    }


    /**
     * 门店清退
     * @param array $fdata
     * @return int
     * @author yize<yize@iyenei.com>
     */
    public function ShopClear(\s_hmanage_user_do $sUser,array $fdata)
    {
        $hcityShopExtDb=HcityShopExtDao::i();
        $info=$hcityShopExtDb->getOne(['id'=>$fdata['id']]);
        if(!$info)
        {
            throw new Exception('信息不存在');
        }
        $like=[];
        if($sUser->type==0)
        {
            $like=[
                'field'=>'region',
                'str'=>$sUser->region,
                'type'=>'after',
            ];
        }
        $shopInfo=MainShopDao::i()->getOneLike($like,['id'=>$info->shop_id]);
        if(!$shopInfo)
        {
            throw new Exception('店铺信息不存在');
        }
        if($info->hcity_show_status==2)
        {
            throw new Exception('此门店已被清退，无需重复操作');
        }
        //开始事物
        $shcityGoodsDb = ShcityGoodsDao::i(['aid' => $info->aid]);
        $hcityGoodsKzDb = HcityGoodsKzDao::i();
        $shcityGoodsDb->db->trans_start();
        $hcityGoodsKzDb->db->trans_start();
        $ret=$hcityShopExtDb->update(['hcity_show_status'=>2],['id'=>$fdata['id']]);
        if(!$ret)
        {
            $shcityGoodsDb->db->trans_rollback();
            $hcityGoodsKzDb->db->trans_rollback();
            throw new Exception('清退失败');
        }
        //修改商品表和快照表状态
        $hcityGoodsKzDb->update(['hcity_status' => 0], ['aid'=>$info->aid,'shop_id' => $info->shop_id]);
        $shcityGoodsDb->update(['hcity_status' => 0], ['aid'=>$info->aid,'shop_id' => $info->shop_id]);
        if ($shcityGoodsDb->db->trans_status() && $hcityGoodsKzDb->db->trans_status()) {
            $shcityGoodsDb->db->trans_complete();
            $hcityGoodsKzDb->db->trans_complete();
            return true;
        } else {
            $shcityGoodsDb->db->trans_rollback();
            $hcityGoodsKzDb->db->trans_rollback();
            throw new Exception('修改失败');
        }
    }


    /**
     * 门店清退恢复
     * @param array $fdata
     * @return int
     * @author yize<yize@iyenei.com>
     */
    public function shopRecover(\s_hmanage_user_do $sUser,array $fdata)
    {
        $hcityShopExtDb=HcityShopExtDao::i();
        $info=$hcityShopExtDb->getOne(['id'=>$fdata['id']]);
        if(!$info)
        {
            throw new Exception('信息不存在');
        }
        $like=[];
        if($sUser->type==0)
        {
            $like=[
                'field'=>'region',
                'str'=>$sUser->region,
                'type'=>'after',
            ];
        }
        $shopInfo=MainShopDao::i()->getOneLike($like,['id'=>$info->shop_id]);
        if(!$shopInfo)
        {
            throw new Exception('店铺信息不存在');
        }
        if($info->hcity_show_status==1)
        {
            throw new Exception('此门店已入驻，无需重复操作');
        }
        $ret=$hcityShopExtDb->update(['hcity_show_status'=>1],['id'=>$fdata['id']]);
        if(!$ret)
        {
            throw new Exception('恢复失败');
        }
        return true;
    }


    /**
     * 修改门店审核状态
     * @param array $fdata
     * @return int
     * @author yize<yize@iyenei.com>
     */
    public function editApplyStatus(\s_hmanage_user_do $sUser,array $fdata)
    {
        if($fdata['hcity_audit_status']==2)
        {
            $params['hcity_show_status']=1;
            $params['hcity_pass_time']=time();
            $params['hcity_audit_status']=2;
        }else
        {
            $params['hcity_audit_status']=3;
        }
        $hcityShopExtDb=HcityShopExtDao::i();
        $info=$hcityShopExtDb->getOne(['id'=>$fdata['id']]);
        if(!$info)
        {
            throw new Exception('信息不存在');
        }

        if($info->hcity_audit_status!=1)
        {
            throw new Exception('此状态不能修改');
        }
        $like=[];
        if($sUser->type==0)
        {
            $like=[
                'field'=>'region',
                'str'=>$sUser->region,
                'type'=>'after',
            ];
        }
        $shopInfo=MainShopDao::i()->getOneLike($like,['id'=>$info->shop_id]);
        if(!$shopInfo)
        {
            throw new Exception('店铺信息不存在');
        }
        $ret=$hcityShopExtDb->update($params,['id'=>$fdata['id']]);
        if(!$ret)
        {
            throw new Exception('修改失败');
        }
        //删除店铺缓存
        (new HcityShopExtCache(['shop_id' => $info->shop_id]))->delete();

        return true;
    }



    /**
     * 某个门店财务记录
     * param array $fdata
     * @return int $row
     * @author yize<yize@iyenei.com>
     */
    public function shopBill(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->table="{$hcityMainDb->tables['hcity_shop_balance_record']}";
        $shopInfo= HcityShopExtDao::i()->getOne(['shop_id'=>$fdata['shop_id']]);
        if(!$shopInfo)
        {
            throw new Exception('门店不存在');
        }
        $p_conf->where="shop_id={$fdata['shop_id']}";
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
        $orderDb = HcityShopBalanceRecordDao::i();
        $statusArr = $orderDb->shopBalanceStatus;
        foreach($rows['rows'] as &$v)
        {
            $v['typeAlias']=$statusArr[$v['type']];
        }
        $rows['total']=$count;
        return $rows;
    }

    /**
     * @param int $aid
     * @param int $shop_id
     * @return bool
     * @throws \Exception
     */
    public function openYDYM(int $aid, int $shop_id)
    {
        $hcityUserDao = HcityUserDao::i();
        //获取门店信息表
        $HcityMainDb = HcityMainDb::i();
        $hcityShopExtDao = HcityShopExtDao::i();
        $mShopExt = $hcityShopExtDao->getOne(['aid'=>$aid,'shop_id'=>$shop_id]);
        if(!$mShopExt)
        {
            log_message('error',__METHOD__.":shop_id={$shop_id}");
            throw new \Exception('门店信息不存在');
        }
        //获取商家信息
        $mainCompanyAccountDao = MainCompanyAccountDao::i();;
        $m_main_company_account = $mainCompanyAccountDao->getOne(['aid' => $aid, 'shop_id' => 0]);
        if(!$m_main_company_account)
        {
            log_message('error',__METHOD__.":shop_id={$shop_id}");
            throw new \Exception('商家信息不存在');
        }

        try{
            $erp_sdk = new \erp_sdk();
            $params[] = (int)$m_main_company_account->visit_id;
            $params[] = (int)$m_main_company_account->user_id;
            $res = $erp_sdk->getUserById($params);
            $mobile = $res['phone'];
            $username = $res['user_name'];
        }catch(\Exception $e){
            throw new \Exception('商家信息不存在[ERP]');
        }

        $curr_time = time();
        $year_time = 365*24*3600;
        $is_open_ydym = false;
        //判断是否开通或过期
        if($mShopExt->barcode_status==0 )
        {
            $update['barcode_status'] = 1;
            $update['barcode_open_time'] = $curr_time;
            $update['barcode_expire_time'] = $curr_time+$year_time;
            $is_open_ydym = true;
        }
        else
        {
            //过期续费
            if($mShopExt->barcode_status==1 && $mShopExt->barcode_expire_time<$curr_time)
            {
                $update['barcode_status'] = 1;
                $update['barcode_expire_time'] = $curr_time+$year_time;
            }
            else
            {
                $update['barcode_status'] = 1;
                $update['barcode_expire_time'] = $mShopExt->barcode_expire_time+$year_time;
            }

        }
        //########数据入库#################
        $HcityMainDb->trans_start();
        $shopRefitem = MainShopRefitemDao::i()->getOne(['aid' => $aid, 'saas_id' => SaasEnum::YDYM, 'shop_id' => $shop_id]);
        if (empty($shopRefitem)) {
            $shopRefData = [
                'aid' => $aid,
                'saas_id' => SaasEnum::YDYM,
                'shop_id' => $shop_id,
                'ext_shop_id' => $mShopExt->id
            ];
            MainShopRefitemDao::i()->create($shopRefData);
        }
        $hcityShopExtDao->update($update, ['aid'=>$aid,'shop_id'=>$shop_id]);

        //判断是否存在用户,开通一店一码自动赠送嘿卡会员
        $user = $hcityUserDao->getOne(['mobile'=>$mobile]);
        if($user)
        {
            if($user->is_open_hcard < 1)//开卡
            {
                $user_update['is_open_hcard'] = 1;
                $user_update['hcard_expire_time'] = $curr_time+$year_time;

            }
            else //续费
            {
                if($user->hcard_expire_time<$curr_time)//过期
                {
                    $user_update['hcard_expire_time'] = $curr_time+$year_time;
                }
                else//未过期
                {
                    $user_update['hcard_expire_time'] = $user->hcard_expire_time+$year_time;
                }

            }
            $hcityUserDao->update($user_update, ['id'=>$user->id]);
            $uid = $user->id;
        }
        else
        {
            $params=[
                'username'=>$username,
                'mobile'=>$mobile,
                'id_code'=>generate_id_code(),
                'level'=>1,
                'hcard_expire_time'=>$curr_time+$year_time,
                'is_open_hcard'=>1,
                'shard_node' => '0-0-0'
            ];
            $uid = $hcityUserDao->create($params);
        }
        //加载配置
        $xcx_inc = &inc_config('xcx_hcity');
        //累计消费总额
        $user_where['where'] = [
            'id' => $uid
        ];
        $hcityUserDao->setInc('consumption', $xcx_inc['ydym_money'], $user_where);

        //保存消费余额流水
        $recordCreateData = [
            'fid' => create_order_number(),
            'money' => $xcx_inc['ydym_money'],
            'type' => 6,
            'time' => time(),
            'uid' => $uid,
            'remark' => '开通一店一码消费',
            'lower_uid' => 0,
            'lower_shop_id' => 0
        ];
        HcityUserBalanceRecordDao::i()->create($recordCreateData);

        if ($HcityMainDb->trans_status()===false)
        {
            $HcityMainDb->trans_rollback();
            throw new \Exception('开通一店一码失败');
        }
        $HcityMainDb->trans_complete();

        //删除店铺缓存
        (new HcityShopExtCache(['shop_id' => $shop_id]))->delete();
        //删除user缓存
        (new HcityUserCache(['uid'=>$uid]))->delete();

        try{
            //发送分佣任务
            if($is_open_ydym)
                (new \Service\Bll\Hcity\FinanceBll())->openBarcode($shop_id, $xcx_inc['ydym_money']);
        }catch(\Exception $e){
            log_message('error', __METHOD__.'发送开店分佣任务失败shop_id:'.$shop_id);
            log_message('error', $e->getMessage());
        }

        return true;
    }

    /**
     * 获取用户邀请的店铺
     * @param int $uid 邀请人UID
     * @param array $input
     * @return mixed
     */
    public function getListByInviterUid(int $uid ,array $input)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->fields ='a.id,a.aid,a.shop_id,a.hcity_apply_time,a.hcity_pass_time,a.hcity_audit_status,a.hcity_show_status,a.barcode_status,a.category_name_list,a.time';
        $p_conf->table = "{$hcityMainDb->tables['hcity_shop_ext']} a left join {$hcityMainDb->tables['hcity_company_ext']} b on a.aid=b.aid";

        $p_conf->where .= " AND b.inviter_uid={$uid}";
        //时间段判断
        if($input['stime'])
        {
            $p_conf->where .= " AND a.time>={$input['stime']}";
        }
        if($input['etime'])
        {
            $p_conf->where .= " AND a.time<{$input['etime']}";
        }
        $ret=[];

        $p_conf->order= 'id desc';
        $count = 0;
        $data['rows']=$page->getList($p_conf,$count);
        //当搜索过主表时 直接取店铺信息  没有则查询一次
        if(!$ret)
        {
            $shopIds = array_column($data['rows'],'shop_id');
            $shopIds = empty($shopIds) ? '0' : implode(',', $shopIds);
            $where  = " id in ($shopIds) ";
            $shopDb = MainShopDao::i();
            $ret = $shopDb->getAllArray($where);
        }
        //循环赋值
        foreach($data['rows'] as &$val)
        {
            foreach($ret as $v)
            {
                if($val['shop_id']==$v['id'])
                {
                    $val['shop_name']=$v['shop_name'];
                    $val['shop_logo']=conver_picurl($v['shop_logo']);
                    $val['contact']=$v['contact'];
                    $val['shop_state']=$v['shop_state'];
                    $val['shop_city']=$v['shop_city'];
                    $val['shop_district']=$v['shop_district'];
                    $val['shop_address']=$v['shop_address'];
                }
            }
            if(!isset($val['shop_name']))
            {
                $val['shop_name']='';
                $val['shop_logo']='';
                $val['contact']='';
                $val['shop_state']='';
                $val['shop_city']='';
                $val['shop_district']='';
                $val['shop_address']='';
            }
        }
        $data['total']=$count;
        return $data;
    }

    /**
     * 根据邀请人获取店铺数量
     * @param int $uid
     * @param array $input
     * @return mixed
     */
    public function getCountByInviterUid(int $uid ,array $input)
    {
        $hcityMainDb = HcityMainDb::i();
        $sql = "SELECT COUNT(*) as count FROM {$hcityMainDb->tables['hcity_shop_ext']} a left join {$hcityMainDb->tables['hcity_company_ext']} b on a.aid=b.aid ";
        $sql .= " WHERE b.inviter_uid={$uid}";

        //时间段判断
        if($input['stime'])
        {
            $sql .= " AND a.time>={$input['stime']}";
        }
        if($input['etime'])
        {
            $sql .= " AND a.time<{$input['etime']}";
        }
        // 骑士商圈任务状态
        if($input['qs_shop_status'] && is_numeric($input['qs_shop_status']))
        {
            $sql .= " AND a.qs_shop_status={$input['qs_shop_status']}";
        }
        // 一店一码状态
        if($input['barcode_status'] && is_numeric($input['barcode_status']))
        {
            $sql .= " AND a.barcode_status={$input['barcode_status']}";
        }
        return $hcityMainDb->query($sql)->row()->count;
    }

    /**
     * 根据邀请人获取商圈已完成任务店铺数量
     * @param int $uid
     * @param array $input
     * @return mixed
     * @user liusha
     * @date 2018/8/30 14:25
     */
    public function getTaskCountByInviterUid(int $uid ,array $input)
    {
        $hcityMainDb = HcityMainDb::i();
        $sql = "SELECT COUNT(*) as count FROM {$hcityMainDb->tables['hcity_qs_task']} a ";
        $sql .= " WHERE a.uid={$uid}";

        // 骑士商圈任务状态
        if($input['shop_task_status'] && is_numeric($input['shop_task_status']))
        {
            $sql .= " AND a.shop_task_status={$input['shop_task_status']}";
        }
        // 一店一码状态
        if($input['ydym_task_status'] && is_numeric($input['ydym_task_status']))
        {
            $sql .= " AND a.ydym_task_status={$input['ydym_task_status']}";
        }
        return $hcityMainDb->query($sql)->row()->count;
    }
}
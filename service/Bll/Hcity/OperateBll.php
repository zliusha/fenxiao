<?php

/**
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/7/23
 * Time: 上午11:40
 */
namespace Service\Bll\Hcity;

use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWithdrawalDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopFinanceDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectShopDao;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\Exceptions\Exception;

class OperateBll extends \Service\Bll\BaseBll
{

    /**
     * 申请提现
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function applyMoney(array $info, array $data)
    {  
        //提现列表
        //获取主账号的信息手机号码
        $apply = new \erp_sdk();
        $infos = $apply->getUserById([$info['visit_id'],$info['user_id']]);
        $hcityMainDb = HcityMainDb::i(['aid' => $info['aid']]);
        $shopExtInfo = HcityShopExtDao::i(['aid' => $info['aid']])->getOneArray(['aid' => $info['aid'], 'shop_id' => $data['shop_id']], "balance");
        if($data['money']>$shopExtInfo['balance']){
            throw new Exception('提现金额超出可提现金额');
        }
        //开启事务
        $hcityMainDb->trans_start();
        $fid = create_order_number();
        $applyData = [
            'aid' => $info['aid'],
            'applicant_name' => $infos['user_name'],
            'applicant_id' => $data['shop_id'],
            'phone' => $infos['phone'],
            'apply_time' => time(),
            //type为2时，代表门店
            'type' => 2,
            'money' => $data['money'],
            'payment_account' => $data['payment_account'],
            'payment_method' => $data['payment_method'],
            'status' => 1,
            'fid' => $fid
        ];
        $attrgroupId = HcityWithdrawalDao::i(['aid' => $info['aid']])->create($applyData);
        $options['where'] = [
            'shop_id' => $data['shop_id'],
            'aid'     => $info['aid']
        ];
        $lockBalanceInfo = HcityShopExtDao::i(['aid' => $info['aid']])->setInc('lock_balance', $data['money'],$options);
        $balanceInfo = HcityShopExtDao::i(['aid' => $info['aid']])->setDec('balance', $data['money'], $options);
        if ($hcityMainDb->trans_status()) {
            $hcityMainDb->trans_complete();
            return true;
        } else {
            $hcityMainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 提现导出列表
     * @param int $uid
     * @param array $data
     * @User: feiying
     */
     public function applyMoneyExcel(int $aid, array $data)
    {
        $hcityMainDb = HcityMainDb::i(['aid' => $aid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_shop_balance_record']}";
        if(!empty($data['shop_id'])){
            $p_conf->where .= " and shop_id={$page->filter($data['shop_id'])}";
        }
        if($data['type']){
            $p_conf->where .= " and type={$page->filter($data['type'])}";
        }
        if($data['fid']){
            $p_conf->where .= " and fid like '%".$page->filterLike($data['fid'])."%'";
        }
        if($data['time']){
            $timeArr=explode(' - ',$data['time']);
            $p_conf->where .=" and time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and time<".$lasttime; 
        }
        $p_conf->where .= " and aid = {$aid}";
        $p_conf->fields = '*';
        $p_conf->order = 'id desc';
        $count = 0;
        $rows = $page->getList($p_conf, $count);

        $balanceDao = HcityShopBalanceRecordDao::i(['aid'=>$aid]);
        $shopBalanceStatus = $balanceDao->shopBalanceStatus;
        foreach($rows as &$v)
        {
            $v['type']=$shopBalanceStatus[$v['type']];
        }
        $excelinfo = [];
        foreach($rows  as $key=>$value){
            $_row = [];
            $_row['fid'] = $value['fid'];
            $_row['money'] = $value['money'];
            $_row['type'] = $value['type'];
            $_row['time'] = date('Y-m-d H:i:s', $value['time']);
            $excelinfo[] = $_row;
        }
        return $excelinfo;
    }

    /**
     * 获取经营数据列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function operateSearch(int $aid, array $data)
    {
        $hcityMainDb = HcityMainDb::i(['aid' => $aid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_shop_balance_record']}";
        if(!empty($data['shop_id'])){
            $p_conf->where .= " and shop_id={$page->filter($data['shop_id'])}";
        }
        if($data['type']){
            $p_conf->where .= " and type={$page->filter($data['type'])}";
        }
        if($data['fid']){
            $p_conf->where .= " and fid like '%".$page->filterLike($data['fid'])."%'";
        }
        if($data['time']){
            $timeArr=explode(' - ',$data['time']);
            $p_conf->where .=" and time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and time<".$lasttime; 
        }
        $p_conf->where .= " and aid = {$aid}";
        $p_conf->fields = '*';
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        if(!empty($data['shop_id'])){
            $balanceInfo = [
                'aid' => $aid,
                'shop_id' => $data['shop_id'],
            ];
            $rows['tixian'] = HcityShopExtDao::i(['aid' => $aid])->getSum('balance',$balanceInfo);
            $rows['applysum'] = HcityShopExtDao::i(['aid' => $aid])->getSum('lock_balance',$balanceInfo);
            //$rows['sum'] = strval($rows['tixian'] + $rows['applysum']);
            //新增数据
            $comInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                ];
            //预估分佣收入
            $list = HcityCommissionShopDao::i(['aid' => $aid])->getEntitysByAR(["where" =>$comInfo,'where_in' => ['confirm_status' => [0,1]]]);
            $_rows =[];
            $_rows['yfenyong_sum'] = 0;
            foreach($list as $key=>$value) {
                $_rows['yfenyong_sum'] += $value->money;
            }
            $rows['yfenyong_sum'] = strval($_rows['yfenyong_sum']);
            //预估商品收入
            $goodsInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id']
                ];
            $goodslist = HcityShopFinanceDao::i(['aid' => $aid])->getEntitysByAR(["where" =>$goodsInfo,'where_in' => ['confirm_status' => [0,1]]]);
            $_rows['ygoods_sum'] = 0;
            foreach($goodslist as $key=>$value) {
                $_rows['ygoods_sum'] += $value->money;
            }
            $rows['ygoods_sum'] = strval($_rows['ygoods_sum']);
            //预估总收入
            $rows['ysum'] = strval($rows['ygoods_sum'] + $rows['yfenyong_sum']);
            //实际分佣收入
            $scomInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'confirm_status' => 1
                ];
            $slist = HcityCommissionShopDao::i(['aid' => $aid])->getSum('money',$scomInfo);
            if($slist)  $rows['sfenyong_sum'] = strval($slist);
            else $rows['sfenyong_sum'] = 0;
            //实际商品收入
            $sgoodsInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'confirm_status' => 1
                ];
            $sgoodslist = HcityShopFinanceDao::i(['aid' => $aid])->getSum('money',$sgoodsInfo);
            if($sgoodslist)  $rows['sgoods_sum'] = strval($sgoodslist);
            else $rows['sgoods_sum'] = 0;
           //付款订单
            $hxInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'status' => 1
                ];
            $rows['pay_count'] = ShcityOrderDao::i(['aid' => $aid])->getCount($hxInfo);
            //统计已经核销订单
            $hxInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'status' => 2
                ];
            $rows['hx_count'] = ShcityOrderDao::i(['aid' => $aid])->getCount($hxInfo);
            $rows['fk_count'] =strval($rows['pay_count']+$rows['hx_count']);
            $rows['sum'] = strval($rows['sgoods_sum'] + $rows['sfenyong_sum']);
        }else{
            $balanceInfo = [
                'aid' => $aid,
            ];
            $rows['tixian'] = HcityShopExtDao::i(['aid' => $aid])->getSum('balance',$balanceInfo);
            $rows['applysum'] = HcityShopExtDao::i(['aid' => $aid])->getSum('lock_balance',$balanceInfo);
            //$rows['sum'] = strval($rows['tixian'] + $rows['applysum']);
            //新增数据
            $comInfo = [
                    'aid' => $aid,
                ];
            //预估分佣收入
            $list = HcityCommissionShopDao::i(['aid' => $aid])->getEntitysByAR(["where" =>$comInfo,'where_in' => ['confirm_status' => [0,1]]]);
            $_rows =[];
            $_rows['yfenyong_sum'] = 0;
            foreach($list as $key=>$value) {
                $_rows['yfenyong_sum'] += $value->money;
            }
            $rows['yfenyong_sum'] = strval($_rows['yfenyong_sum']);
            //预估商品收入
            $goodsInfo = [
                    'aid' => $aid,
                ];
            $goodslist = HcityShopFinanceDao::i(['aid' => $aid])->getEntitysByAR(["where" =>$goodsInfo,'where_in' => ['confirm_status' => [0,1]]]);
            $_rows['ygoods_sum'] = 0;
            foreach($goodslist as $key=>$value) {
                $_rows['ygoods_sum'] += $value->money;
            }
            $rows['ygoods_sum'] = strval($_rows['ygoods_sum']);
            //预估总收入
            $rows['ysum'] = strval($rows['ygoods_sum'] + $rows['yfenyong_sum']);
            //实际分佣收入
            $scomInfo = [
                    'aid' => $aid,
                    'confirm_status' => 1
                ];
            $slist = HcityCommissionShopDao::i(['aid' => $aid])->getSum('money',$scomInfo);
            if($slist)  $rows['sfenyong_sum'] = strval($slist);
            else $rows['sfenyong_sum'] = 0;
            //实际商品收入
            $sgoodsInfo = [
                    'aid' => $aid,
                    'confirm_status' => 1
                ];
            $sgoodslist = HcityShopFinanceDao::i(['aid' => $aid])->getSum('money',$sgoodsInfo);
            if($sgoodslist)  $rows['sgoods_sum'] = strval($sgoodslist);
            else $rows['sgoods_sum'] = 0;
           //付款订单
            $hxInfo = [
                    'aid' => $aid,
                    'status' => 1
                ];
            $rows['pay_count'] = ShcityOrderDao::i(['aid' => $aid])->getCount($hxInfo);
            //统计已经核销订单
            $hxInfo = [
                    'aid' => $aid,
                    'status' => 2
                ];
            $rows['hx_count'] = ShcityOrderDao::i(['aid' => $aid])->getCount($hxInfo);
            $rows['fk_count'] =strval($rows['pay_count']+$rows['hx_count']);
            $rows['sum'] = strval($rows['sgoods_sum'] + $rows['sfenyong_sum']);
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 首页经营数据列表(一店一码首页数据)
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function homeInfo(int $aid, array $data)
    {
       //查看当天的销售额
       if(!empty($data['shop_id'])){
            $comInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'time >' => $data['time']
                ];
            //预估分佣收入
            $list = HcityCommissionShopDao::i(['aid' => $aid])->getEntitysByAR(["where" =>$comInfo,'where_in' => ['confirm_status' => [0,1]]]);
            $_rows =[];
            $_rows['fenyong_sum'] = 0;
            foreach($list as $key=>$value) {
                $_rows['fenyong_sum'] += $value->money;
            }
            $rows['fenyong_sum'] = strval($_rows['fenyong_sum']);
            //预估商品收入
            $goodsInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'time >' => $data['time']
                ];
            $goodslist = HcityShopFinanceDao::i(['aid' => $aid])->getEntitysByAR(["where" =>$goodsInfo,'where_in' => ['confirm_status' => [0,1]]]);
            $_rows['goods_sum'] = 0;
            foreach($goodslist as $key=>$value) {
                $_rows['goods_sum'] += $value->money;
            }
            $rows['goods_sum'] = strval($_rows['goods_sum']);
            $rows['sum'] = strval($rows['goods_sum'] + $rows['fenyong_sum']);
           //付款订单
            $orderInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'pay_time >' => $data['time'],
                    'status' => 1
                ];
            $rowsInfo['pay_count'] = ShcityOrderDao::i(['aid' => $aid])->getCount($orderInfo);
            //统计已经核销订单
            $hxInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'hx_time >' => $data['time'],
                    'status' => 2
                ];
            $rows['hx_count'] = ShcityOrderDao::i(['aid' => $aid])->getCount($hxInfo);
            $rows['pay_sum'] = strval($rows['hx_count']+ $rowsInfo['pay_count']);
            //当天店铺收藏人数
            $collectInfo = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'time >' => $data['time'],
                    'status' => 0
                ];
            $rows['collect_count'] = HcityUserCollectShopDao::i(['aid' => $aid])->getCount($collectInfo);

       } else {
            $comInfo = [
                    'aid' => $aid,
                    'time >' => $data['time']
                ];
            //预估分佣收入
            $list = HcityCommissionShopDao::i(['aid' => $aid])->getEntitysByAR(["where" =>$comInfo,'where_in' => ['confirm_status' => [1,2]]]);
            $_rows =[];
            $_rows['fenyong_sum'] = 0;
            foreach($list as $key=>$value) {
                $_rows['fenyong_sum'] += $value->money;
            }
            $rows['fenyong_sum'] = strval($_rows['fenyong_sum']);
            //预估商品收入
            $goodsInfo = [
                    'aid' => $aid,
                    'time >' => $data['time']
                ];
            $goodslist = HcityShopFinanceDao::i(['aid' => $aid])->getEntitysByAR(["where" =>$goodsInfo,'where_in' => ['confirm_status' => [1,2]]]);
            $_rows['goods_sum'] = 0;
            foreach($goodslist as $key=>$value) {
                $_rows['goods_sum'] += $value->money;
            }
            $rows['goods_sum'] = strval($_rows['goods_sum']);
            $rows['sum'] = strval($rows['goods_sum'] + $rows['fenyong_sum']);
           //付款订单
            $orderInfo = [
                    'aid' => $aid,
                    'pay_time >' => $data['time'],
                    'status' => 1
                ];
            $rowsInfo['pay_count'] = ShcityOrderDao::i(['aid' => $aid])->getCount($orderInfo);
            //统计已经核销订单
            $hxInfo = [
                    'aid' => $aid,
                    'hx_time >' => $data['time'],
                    'status' => 2
                ];
            $rows['hx_count'] = ShcityOrderDao::i(['aid' => $aid])->getCount($hxInfo);
            $rows['pay_sum'] = strval($rows['hx_count']+ $rowsInfo['pay_count']);
            //当天店铺收藏人数
            $collectInfo = [
                    'aid' => $aid,
                    'time >' => $data['time'],
                    'status' => 0
                ];
            $rows['collect_count'] = HcityUserCollectShopDao::i(['aid' => $aid])->getCount($collectInfo);
       }
       return $rows;
       
    }

    /**
     * 银行卡信息
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function cardInfo(array $data)
    {  
        $hcityWithdrawalDao = HcityWithdrawalDao::i();
        $fields = 'applicant_id,payment_account,payment_method';
        $orderBy = 'id desc';
        $cardInfo = $hcityWithdrawalDao->getOne(['applicant_id'=>$data['shop_id'],'type'=>2],$fields,$orderBy);
        if($cardInfo){
            return $cardInfo;
        }else{
            $cardInfo->applicant_id = '';
            $cardInfo->payment_account = '';
            $cardInfo->payment_method = '';
            return $cardInfo;
        }
        
    }

}
<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午5:30
 */
namespace Service\Bll\Hcity;

use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityOrderKzDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserOrderDao;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Exceptions\Exception;


class OrderKzBll extends \Service\Bll\BaseBll
{

    /**
     * 获取订单列表 分页
     * @param array $data  $shop_type=1时代表获取门店信息
     * @return array $rows
     * @author yize<yize@iyenei.com>
     * @author feiying<feiying@iyenei.com>update
     */

    public function orderList(\s_hmanage_user_do $sUser,array $fdata)
    {
        $mainDb = HcityMainDb::i();
        $page = new PageList($mainDb);
        $sql='1=1';
        if($sUser->type==0)
        {
            $sql .= " and region like '{$sUser->region}%'";
        }
        if($fdata['shop_id'])
        {
            $sql .=" and shop_id= {$fdata['shop_id']}";
        }
        if($fdata['time'])
        {
            $timeArr=explode(' - ',$fdata['time']);
            if(!isset($timeArr[0]) || !isset($timeArr[1]))
            {
                throw new Exception('搜索时间不正确');
            }
            $timeArr[1]=$timeArr[1] .' 23:59:59';
            $sql .=' and time>='.strtotime($timeArr[0]);
            $sql .=' and time<='.strtotime($timeArr[1]);
        }
        if ($fdata['source_type']) {
            $sql .= " and source_type={$fdata['source_type']} ";
        }
        if ($fdata['buyer_phone']) {
            $sql .= " and buyer_phone like '%{$page->filterLike($fdata['buyer_phone'])}%'";
        }
        if ($fdata['tid']) {
            $sql .= " and tid={$fdata['tid']} ";
        }
        if($fdata['status']!=null)
        {
            $sql.=" and status={$fdata['status']}";
        }
        $p_conf=$page->getConfig();
        $p_conf->table="{$mainDb->tables['hcity_order_kz']}";
        $p_conf->where =$sql;
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        //订单状态别名转换
        $orderDb = ShcityOrderDao::i();
        $statusArr = $orderDb->orderStatus;
        foreach($rows['rows'] as &$v)
        {
            $v['statusAlias']=$statusArr[$v['status']];
        }
        return $rows;
    }




    /**
     * 订单详情
     * @param int $tid
     * @return int $id
     * @author yize<yize@iyenei.com>
     * @author feiying<feiying@iyenei.com>update
     */
    public function orderDetail(int $id)
    {
        $fields='id,aid,tid,shop_id';
        $orderKz=HcityOrderKzDao::i()->getOne(['id'=>$id],$fields);
        if(!$orderKz)
        {
            throw new Exception('商圈订单不存在');
        }
        $aid=$orderKz->aid;
        $tid=$orderKz->tid;

        $orderDb = ShcityOrderDao::i(['aid'=>$aid]);
        $order=$orderDb->getOne(['tid'=>$tid]);
        if(!$order)
        {
            throw new Exception('订单不存在');
        }
        //订单状态别名转换
        $statusArr = $orderDb->orderStatus;
        $status = $order->status;
        $order->statusAlias=$statusArr[$status];
        //订单商品信息
        $orderExtDb = ShcityOrderExtDao::i(['aid'=>$aid]);
//        $ext_fields = 'id,tid,sku_id,uid,goods_id,goods_title,goods_pic_url,num,time';
        //订单详情
        $orderext=$orderExtDb->getOne(['aid'=>$aid,'tid'=>$tid]);
        if($orderext)
        {
            $orderext->goods_pic_url=conver_picurl($orderext->goods_pic_url);
        }
        $shop=MainShopDao::i()->getOne(['id'=>$order->shop_id]);
        if($shop)
        {
            $order->shop_state=$shop->shop_state;
            $order->shop_city=$shop->shop_city;
            $order->shop_district=$shop->shop_district;
            $order->shop_name=$shop->shop_name;
        }else
        {
            $order->region_name='';
            $order->shop_name='';
        }

        $order->orderext=$orderext;
        //新增数据查询订单信息feiying
        $data['orderinfo'] = $orderDb->getOneArray(['tid'=>$tid]);
        $data['orderext'] = $orderExtDb->getOneArray(['aid'=>$aid,'tid'=>$tid]);
        $data['fenyong'] = [];
        //订单商圈订单
        if ($data['orderinfo']['source_type'] == 1) {
            //扣减商圈库存
            if($data['orderinfo']['stock_type'] == 1){
                //总成本
                $chengben = $data['orderext']['cost_price']*$data['orderext']['num'];
                $data['fenyong']['money'] = $data['orderinfo']['payment'] - $chengben;
                $data['fenyong']['money'] = number_format($data['fenyong']['money'],2);
                if($data['fenyong']['money'] < 0)   $data['fenyong']['money'] = 0;
                else  $data['fenyong']['money'] = strval($data['fenyong']['money']);
                $data['fenyong']['payment'] = strval($data['orderinfo']['payment'] + $data['orderinfo']['pay_hey_coin'] + $data['orderinfo']['packet_money']);
                //商圈支付订单
                $data['fenyong']['youhui'] = strval($data['orderinfo']['pay_hey_coin'] + $data['orderinfo']['packet_money']);
                $data['fenyong']['status'] = 1;
                //单个商品成本
                $data['fenyong']['chengben'] = $data['orderext']['cost_price'];
                $data['fenyong']['chengben'] = number_format($data['orderext']['cost_price'],2);
            }
            //扣减活动库存
            if($data['orderinfo']['stock_type'] == 4){
                $data['fenyong']['payment'] = strval($data['orderinfo']['payment']);
                $data['fenyong']['money'] = strval($data['orderinfo']['payment']*0.05);
                $data['fenyong']['money'] = number_format($data['fenyong']['money'],2);
                $data['fenyong']['youhui'] = 0;
                $data['fenyong']['status'] = 5;
                $data['fenyong']['chengben'] = strval($data['orderinfo']['payment']*0.95);
                $data['fenyong']['chengben'] = number_format($data['fenyong']['chengben'],2);
            }
        }
        //一店一码订单
        if ($data['orderinfo']['source_type'] == 2) {
            //扣减商圈库存
            if($data['orderinfo']['stock_type'] == 1) {
                //总成本
                $chengben = $data['orderext']['cost_price']*$data['orderext']['num'];
                $data['fenyong']['money'] = $data['orderinfo']['payment'] - $chengben;
                $data['fenyong']['money'] = number_format($data['fenyong']['money'],2);
                if($data['fenyong']['money'] < 0)   $data['fenyong']['money'] = 0;
                else  $data['fenyong']['money'] = strval($data['fenyong']['money']);
                $data['fenyong']['payment'] = strval($data['orderinfo']['payment'] + $data['orderinfo']['pay_hey_coin'] + $data['orderinfo']['packet_money']);
                $data['fenyong']['youhui'] = strval($data['orderinfo']['pay_hey_coin'] + $data['orderinfo']['packet_money']);
                //商圈库存扣减订单
                $data['fenyong']['status'] = 2;
                $data['fenyong']['chengben'] = number_format($data['orderext']['cost_price'],2);
            }
            //扣减一店一码库存
            if($data['orderinfo']['stock_type'] == 2) {
                $data['fenyong']['money'] = 0;
                $data['fenyong']['payment'] = strval($data['orderinfo']['payment'] + $data['orderinfo']['pay_hey_coin'] + $data['orderinfo']['packet_money']);
                $data['fenyong']['youhui'] = strval($data['orderinfo']['pay_hey_coin'] + $data['orderinfo']['packet_money']);
                $data['fenyong']['status'] = 3;
                $data['fenyong']['chengben'] = $data['orderext']['group_price'];
            }
            //扣减一店一码库存活动库存
             if($data['orderinfo']['stock_type'] == 4){
                $data['fenyong']['payment'] = strval($data['orderinfo']['payment']);
                $data['fenyong']['money'] = strval($data['orderinfo']['payment']*0.05);
                $data['fenyong']['money'] = number_format($data['fenyong']['money'],2);
                $data['fenyong']['youhui'] = 0;
                $data['fenyong']['status'] = 6;
                $data['fenyong']['chengben'] = strval($data['orderinfo']['payment']*0.95);
                $data['fenyong']['chengben'] = number_format($data['fenyong']['chengben'],2);
            }
        }
        //福利池订单
        if ($data['orderinfo']['source_type'] == 3) {
            $data['fenyong']['money'] = $data['orderinfo']['payment'];
            $data['fenyong']['payment'] = strval($data['orderinfo']['payment'] + $data['orderinfo']['pay_hey_coin'] + $data['orderinfo']['packet_money']);
            $data['fenyong']['youhui'] = strval($data['orderinfo']['pay_hey_coin'] + $data['orderinfo']['packet_money']);
            $data['fenyong']['status'] = 4;
            $data['fenyong']['chengben'] = 0;
        }
        $order->fenyong = $data['fenyong'];
        return $order;
    }



}
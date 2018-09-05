<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午5:30
 */
namespace Service\Bll\Hcity;

use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderExtDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderHxDetailDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserOrderExtDao;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Exceptions\Exception;


class OrderBll extends \Service\Bll\BaseBll
{

    /**
     * 获取订单列表 分页
     * @param array $data  $shop_type=1时代表获取门店信息
     * @return array $rows
     * @author yize<yize@iyenei.com>
     * @author feiying<feiying@iyenei.com>update
     */

    public function orderList(int $aid,array $fdata)
    {
        $shardDb = HcityShardDb::i(['aid'=>$aid]);
        $page = new PageList($shardDb);
        $sql = "a.aid = {$aid}";
        if($fdata['shop_id'])
        {
            $sql .=' and a.shop_id='.$fdata['shop_id'];
        }
        if($fdata['time'])
        {
            $timeArr=explode(' - ',$fdata['time']);
            $sql .=' and a.time>='.strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $sql .=' and a.time<'.$lasttime;
        }
        if ($fdata['goods_title']) {
            $sql .= " and b.goods_title like '%{$page->filterLike($fdata['goods_title'])}%'";
        }
        if ($fdata['source_type']) {
            $sql .= " and a.source_type={$fdata['source_type']} ";
        }
        if ($fdata['buyer_phone']) {
            $sql .= " and a.buyer_phone like '%{$page->filterLike($fdata['buyer_phone'])}%'";
        }
        if ($fdata['tid']) {
            $sql .= " and a.tid={$fdata['tid']} ";
        }
        if(isset($fdata['status']) && is_numeric($fdata['status']))    
        {
            $sql.=' and a.status='.$fdata['status'];
        }
        $p_conf=$page->getConfig();
        $p_conf->table="{$shardDb->tables['shcity_order']} a left join {$shardDb->tables['shcity_order_ext']} b on a.tid=b.tid";
        $p_conf->fields='a.*,b.goods_title,b.goods_pic_url,b.group_price';
        $p_conf->where =$sql;
        $p_conf->order = 'a.id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        //订单状态别名转换
        $orderDb = ShcityOrderDao::i(['aid'=>$aid]);
        $statusArr = $orderDb->orderStatus;
        foreach($rows['rows'] as &$v)
        {
            $v['statusAlias']=$statusArr[$v['status']];
        }
        return $rows;
    }

    /**
     * 订单列表获取门店信息
     * @param array $rows $statusArr
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    private function _manageOrderList($rows,$statusArr)
    {
        $shopIds = array_column($rows,'shop_id');
        $shopIds=empty($shopIds) ? '0' : implode(',', $shopIds);
        $where = $shopIds ? " id in ($shopIds) " : '';
        $shopDb=MainShopDao::i();
        $ret=$shopDb->getAllArray($where);
        //循环赋值
        foreach($rows as &$val)
        {
            $v['statusAlias']=$statusArr[$val['status']];
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
        return $rows;
    }



    /**
     * 订单详情
     * @param int $tid
     * @return int $id
     * @author yize<yize@iyenei.com>
     * @author feiying<feiying@iyenei.com>update
     */
    public function orderDetail(int $aid ,int $id)
    {
        $orderDb = ShcityOrderDao::i(['aid'=>$aid]);
        $where=['aid'=>$aid,'id'=>$id];
        $fields='*';
        //订单支付详情
        $data['orderinfo'] = $orderDb->getOneArray($where,$fields);
        if(!$data['orderinfo'])
        {
            throw new Exception('订单不存在');
        }
        //订单状态别名转换
        $statusArr = $orderDb->orderStatus;
        $status = $data['orderinfo']['status'];
        $data['orderinfo']['statusAlias']=$statusArr[$status];
        //订单商品信息
        $orderExtDb = ShcityOrderExtDao::i(['aid'=>$aid]);
        $ext_fields = '*';
        //订单详情
        $data['orderext']=$orderExtDb->getOneArray(['aid'=>$aid,'tid'=>$data['orderinfo']['tid']],$ext_fields);
        //订单核销详情
        $data['orderhxdetail'] = ShcityOrderHxDetailDao::i(['aid'=>$aid])->getAllArray(['aid'=>$aid,'tid'=>$data['orderinfo']['tid'],'ext_id'=>$data['orderext']['id']]);
        //订单店铺信息
        $data['storeinfo'] = MainShopDao::i(['aid'=>$aid])->getOneArray(['id' =>$data['orderinfo']['shop_id']],'shop_name,contact,shop_address');
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
        return $data;
    }


    /**
     * 小程序段列表
     * @param int $uid
     * @param array $input
     * @return mixed
     * @liusha
     */
    public function userQuery(int $uid, array $input)
    {
        $shardDb = HcityShardDb::i(['uid'=>$uid]);
        $page = new PageList($shardDb);
        $p_conf=$page->getConfig();
        $p_conf->table="{$shardDb->tables['shcity_user_order']}";
        $p_conf->where = "uid={$uid}";

        if(isset($input['shop_id']) && is_numeric($input['shop_id']) &&$input['shop_id']>0)
        {
            $p_conf->where .=" AND shop_id={$input['shop_id']}";
        }
        if(isset($input['status']) && is_numeric($input['status']))
            $p_conf->where .=" AND status={$input['status']}";
        $p_conf->order = 'time DESC';
        $count = 0;
        $list = $page->getList($p_conf, $count);

        //获取子订单信息
        $order_tid_arr = array_column($list, 'tid');
        $order_tid_arr = empty($order_tid_arr)?[0]:$order_tid_arr;
        $shcityUserOrderExtDao = ShcityUserOrderExtDao::i(['uid'=>$uid]);
        
        $order_where = [
            'where_in' => ['tid' => $order_tid_arr],
            'where'=>['uid'=>$uid]
        ];
        $order_ext_list = $shcityUserOrderExtDao->getEntitysByAR($order_where, true);
        $order_ext_list = convert_client_list($order_ext_list,  [['type'=>'img','field'=>'goods_pic_url']]);

        foreach($list as $k => $order)
        {
            $list[$k]['order_ext'] = array_find_list($order_ext_list, 'tid', $order['tid']);
            $list[$k]['use_end_time_format'] = date('Y-m-d H:i:s', $order['use_end_time']);
            $list[$k]['cancel_time'] = $order['time'] + 900;
            //砍价取消时间2分钟
            if($order['stock_type']==5)
                $list[$k]['cancel_time'] = $order['time'] + 120;
        }

        $data['rows'] = $list;
        $data['total'] = $count;

        return $data;
    }

    /**
     * 通过订单号查询订单详情
     * @param int $aid
     * @param int $tid
     * @return object
     * @throws Exception
     * @liusha
     */
    public function detailByTid(int $aid, int $tid)
    {
        $shcityOrderDao = ShcityOrderDao::i(['aid'=>$aid]);
        $shcityOrderExtDao = ShcityOrderExtDao::i(['aid'=>$aid]);
        $shcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid'=>$aid]);

        $order = $shcityOrderDao->getOne(['aid'=>$aid, 'tid'=>$tid]);
        if(!$order)
        {
            throw new Exception('订单不存在!');
        }
        //格式化使用截止时间
        $order->use_end_time_format = date('Y-m-d H:i:s', $order->use_end_time);
        //订单详情
        $order_ext_list = $shcityOrderExtDao->getAllArray(['aid'=>$aid,'tid'=>$tid]);
        $hx_list = $shcityOrderHxDetailDao->getAllArray(['aid'=>$aid,'tid'=>$tid]);
        $cost_money = 0;
        foreach($order_ext_list as $k => $order_ext)
        {
            $order_ext_list[$k]['goods_pic_url'] = conver_picurl($order_ext['goods_pic_url']);
            $order_ext_list[$k]['hx_list'] = array_find_list($hx_list, 'ext_id', $order_ext['id']);
            //成本
            // 商圈订单
            if($order_ext['source_type']==1)
            {
                //助力活动
                if($order_ext['stock_type']==4)
                {
                    $_cost_money = bcmul($order_ext['pay_price']*95/100, $order_ext['num'], 2);
                    $cost_money =bcadd($_cost_money, $cost_money, 2);
                }
                elseif($order_ext['stock_type']==5)
                {
                    $cost_money = 0;
                }
                else
                {
                    $_cost_price = $order_ext['cost_money'];
                    $cost_money = bcadd(bcmul($_cost_price, $order_ext['num'], 2), $cost_money, 2);
                }
            }
            else
            {
                $cost_money = bcadd($order_ext['payment'], $cost_money, 2);
            }
        }
        // 订单待取消时间
        $order->cancel_time = $order->time + 900;
        //砍价待取消时间2分钟
        if($order->stock_type==5)
            $order->cancel_time = $order->time + 120;

        $order->order_ext = $order_ext_list;
        $order->cost_money = $cost_money;
        //福利池订单收入0
        if($order->source_type ==3 )$order->cost_money = 0;
        //获取店铺名称
        $mMainShop = MainShopDao::i()->getOne(['aid'=>$order->aid, 'id'=>$order->shop_id]);
        if($mMainShop)
        {
            $mMainShop->shop_logo = conver_picurl($mMainShop->shop_logo);
        }
        $order->main_shop = $mMainShop;

        return $order;
    }
}
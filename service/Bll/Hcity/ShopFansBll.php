<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/23
 * Time: 下午2:28
 */

namespace Service\Bll\Hcity;

use Service\Bll\BaseBll;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityShopFansDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;


class ShopFansBll extends BaseBll
{
    /**
     * 更新或新增门店粉丝
     * @param int $uid
     * @param int $aid
     * @param int $shopId
     * @author ahe<ahe@iyenei.com>
     */
    public function addOrUpdateShopFans(int $uid, int $aid, int $shopId)
    {
        $fans = ShcityShopFansDao::i(['aid' => $aid])->getOne(['aid' => $aid, 'shop_id' => $shopId, 'uid' => $uid]);
        $shop = MainShopDao::i()->getOne(['id' => $shopId]);
        $user = HcityUserDao::i()->getOne(['id' => $uid]);
        $collectShop = HcityUserCollectShopDao::i(['uid' => $uid])->getOne(['aid' => $aid, 'shop_id' => $shopId, 'uid' => $uid, 'status' => 0]);
        if (empty($fans)) {
            //新建粉丝
            $createData = [
                'aid' => $aid,
                'shop_id' => $shopId,
                'shop_name' => empty($shop) ? '' : $shop->shop_name,
                'uid' => $uid,
                'username' => empty($user) ? '' : $user->username,
                'mobile' => empty($user) ? '' : $user->mobile,
                'is_focus_on_shop' => empty($collectShop) ? 1 : 2,
                'last_active_time' => time()
            ];
            return ShcityShopFansDao::i(['aid' => $aid])->create($createData);
        } else {
            //更新粉丝互动时间
            $updateDate = [
                'shop_name' => empty($shop) ? '' : $shop->shop_name,
                'username' => empty($user) ? '' : $user->username,
                'mobile' => empty($user) ? '' : $user->mobile,
                'is_focus_on_shop' => empty($collectShop) ? 1 : 2,
                'last_active_time' => time()
            ];
            return ShcityShopFansDao::i(['aid' => $aid])->update($updateDate, ['id' => $fans->id]);
        }
    }

    /**
     * 获取粉丝数据列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function fensSearch(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $page = new PageList($hcityShardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityShardDb->tables['shcity_shop_fans']}";
        if(!empty($data['shop_id'])){
            $p_conf->where .= " and shop_id={$page->filter($data['shop_id'])}";
        }
        if($data['is_focus_on_shop']){
            $p_conf->where .= " and is_focus_on_shop={$page->filter($data['is_focus_on_shop'])}";
        }
        if($data['username']){
            $p_conf->where .= " and username like '%".$page->filterLike($data['username'])."%'";
        }
        if($data['mobile']){
            $p_conf->where .= " and mobile like '%".$page->filterLike($data['mobile'])."%'";
        }
        if($data['time']){
            $timeArr=explode(' - ',$data['time']);
            $p_conf->where .=" and last_active_time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and last_active_time<".$lasttime; 
        }
        $p_conf->where .= " and aid = {$aid}";
        $p_conf->fields = '*';
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf,$count);
        foreach($rows['rows'] as $key=>$value){
            $orderDb = ShcityOrderDao::i(['aid'=>$aid]);
            //订单支付详情
            $sql = "uid ={$value['uid']} and shop_id = {$value['shop_id']} and status in(1,2) and aid = {$aid}";
            $info['countall'] = $orderDb->getCount($sql);
            $info['payment'] = $orderDb->getSum('payment',$sql);
            $info['pay_hey_coin'] = $orderDb->getSum('pay_hey_coin',$sql);
            $info['packet_money'] = $orderDb->getSum('packet_money',$sql);

            $info['total'] = $info['payment'] + $info['pay_hey_coin'] + $info['packet_money'];
            if($info['total'] >0 && $info['countall'] >0 ){
                $info['total'] = number_format($info['total'],2);
                $info['avg'] = number_format($info['total']/$info['countall'],2);
            }else{
                $info['total'] = 0;
                $info['avg'] = 0;
            }
            $rows['rows'][$key]['countall'] = $info['countall'];
            $rows['rows'][$key]['total'] =  $info['total'];
            $rows['rows'][$key]['avg'] =  $info['avg'];
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取粉丝详情列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function fensDetail(int $aid, array $fdata)
    {   
        $fansDb = ShcityShopFansDao::i(['aid'=>$aid]);
        $rows['fansInfo'] = $fansDb->getOneArray(['id'=>$fdata['id'],'aid'=>$aid]);
        //统计用户的消费信息
        $orderDb = ShcityOrderDao::i(['aid'=>$aid]);
        $sql = "uid ={$rows['fansInfo']['uid']} and shop_id = {$rows['fansInfo']['shop_id']} and status in(1,2) and aid = {$aid}";
        $info['countall'] = $orderDb->getCount($sql);
        $info['payment'] = $orderDb->getSum('payment',$sql);
        $info['pay_hey_coin'] = $orderDb->getSum('pay_hey_coin',$sql);
        $info['packet_money'] = $orderDb->getSum('packet_money',$sql);
        $info['total'] = $info['payment'] + $info['pay_hey_coin'] + $info['packet_money'];
        if($info['total'] >0 && $info['countall'] >0 ){
            $info['total'] = number_format($info['total'],2);
            $info['avg'] = number_format($info['total']/$info['countall'],2);
        }else{
            $info['total'] = 0;
            $info['avg'] = 0;
        }
        $rows['fansInfo']['countall'] = $info['countall'];
        $rows['fansInfo']['total'] =  $info['total'];
        $rows['fansInfo']['avg'] =  $info['avg'];
        //获取用户头像
        $useimg = HcityUserDao::i()->getOneArray(['id'=>$rows['fansInfo']['uid']],'img');
        $rows['fansInfo']['img'] = $useimg['img'];
        //用户的订单
        $shardDb = HcityShardDb::i(['aid'=>$aid]);
        $page = new PageList($shardDb);
        $sql = "  a.aid = {$aid} and a.uid ={$rows['fansInfo']['uid']} and a.shop_id = {$rows['fansInfo']['shop_id']}  and a.status in(1,2)";
        $p_conf=$page->getConfig();
        $p_conf->table="{$shardDb->tables['shcity_order']} a join {$shardDb->tables['shcity_order_ext']} b on a.tid=b.tid";
        $p_conf->fields='a.tid,a.payment,a.pay_hey_coin,a.packet_money,a.time,b.goods_title';
        if($fdata['time'])
        {
            $timeArr=explode(' - ',$fdata['time']);
            $sql .=' and a.time>='.strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $sql .=' and a.time<'.$lasttime;
        }
        if ($fdata['tid']) {
            $sql .= " and a.tid={$fdata['tid']} ";
        }
        $p_conf->where =$sql;
        $p_conf->order = 'a.id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }
}
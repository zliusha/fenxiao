<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/17
 * Time: 10:35
 */
namespace Service\Bll\Hcity;

use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserRedPacketDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityCompanyRedPacketDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;

class RedPacketBll extends \Service\Bll\BaseBll
{
    /**
     * 用户端红包列表
     * @param int $uid
     * @param array $input
     * @param bool $need_shop_info 是否需要店铺信息
     * @User: liusha
     */
    public function userPacketList(int $uid, array $input, $need_shop_info = false)
    {
        $hcityShardDb = HcityShardDb::i(['uid' => $uid]);
        $page = new PageList($hcityShardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityShardDb->tables['shcity_user_red_packet']} a ";
        $p_conf->where .= " and a.uid={$uid} ";
        //额外筛选条件
        if (isset($input['where']) && !empty($input['where']))
            $p_conf->where .= " and {$input['where']} ";
        //自定义排序
        if (isset($input['order']) && !empty($input['order']))
            $p_conf->order = $input['order'];

        $count = 0;
        $rows = $page->getList($p_conf, $count);

        //获取店铺信息
        if ($need_shop_info) {
            $shop_id_arr = array_column($rows, 'shop_id');
            $shop_ids = empty($shop_id_arr) ? 0 : implode(',', array_column($rows, 'shop_id'));
            $mainShopDao = MainShopDao::i();
            $shop_list = $mainShopDao->getAllArray("id in ({$shop_ids})");
            $shop_list = array_column($shop_list, null, 'id');
            array_walk($rows, function (&$row) use ($shop_list) {
                $row['shop_info'] = isset($shop_list[$row['shop_id']]) ? $shop_list[$row['shop_id']] : null;
                isset($row['shop_info']['shop_logo']) &&  $row['shop_info']['shop_logo']=conver_picurl($row['shop_info']['shop_logo']);
            });
        }

        $data['rows'] = $rows;
        $data['count'] = $count;

        return $data;
    }

    /**
     * 红包详情
     * @param int $aid
     * @param int $uid
     * @param int $shop_id
     * @return array
     */
    public function detail(int $aid, int $uid, int $shop_id)
    {
        $shcityUserRedPacket = ShcityUserRedPacketDao::i(['uid' => $uid])->getOne(['aid' => $aid, 'uid' => $uid, 'shop_id' => $shop_id]);
        $shcityCompanyRedPacket = ShcityCompanyRedPacketDao::i(['uid' => $uid])->getOne(['aid' => $aid, 'uid' => $uid, 'shop_id' => $shop_id]);

        $data = [
            'user_red_packet' => $shcityUserRedPacket,
            'company_red_packet' => $shcityCompanyRedPacket
        ];
        return $data;
    }

    /**
     * 发门店红包
     * @param int $aid
     * @param int $uid
     * @param int $input shop_id, money 必须
     */
    public function sendUserRedPacket(int $aid, int $uid, array $input)
    {
        $hcityUserDao = HcityUserDao::i();
        $user = $hcityUserDao->getOne(['id'=>$uid]);
        if(!$user)
        {
            log_message('error', __METHOD__."--uid:{$uid}");
            throw new \Exception("无此用户信息");
        }
        $uidhcityShardDb = HcityShardDb::i(['uid' => $uid]);
        $aidhcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $aidhcityShardDb->trans_start();
        $uidhcityShardDb->trans_start();
        $curr_time = time();
        $year_time = 365*24*3600;
        $company_data = [
            'uid'=>$uid,
            'aid'=>$aid,
            'shop_id'=>$input['shop_id'],
            'username'=>$user->username,
            'mobile'=>$user->mobile,
            'balance'=>$input['money'],
            'status'=>1,
            'time'=>$curr_time,
            'expire_time'=>$curr_time+$year_time,
        ];
        $company_red_packet_id = ShcityCompanyRedPacketDao::i(['aid' => $aid])->create($company_data);
        $user_data = [
            'uid'=>$uid,
            'aid'=>$aid,
            'shop_id'=>$input['shop_id'],
            'money'=>$input['money'],
            'status'=>1,
            'time'=>$curr_time,
            'expire_time'=>$curr_time+$year_time,
            'red_packet_id'=>$company_red_packet_id,
        ];
        ShcityUserRedPacketDao::i(['uid' => $uid])->create($user_data);

        if ($aidhcityShardDb->trans_status() === FALSE  || $uidhcityShardDb->trans_status() === FALSE) {//
            $aidhcityShardDb->trans_rollback();
            $uidhcityShardDb->trans_rollback();
            log_message('error', __METHOD__ . json_encode($input));
            throw new \Exception("发门店红包失败");
        }
        $aidhcityShardDb->trans_commit();
        $uidhcityShardDb->trans_commit();

        return true;
    }

}
<?php
/**
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/7/18
 * Time: 上午11:40
 */
namespace Service\Bll\Hcity;

use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserRedPacketDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityCompanyRedPacketDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityRedPacketConsumeDao;

class ManageRedPacketBll extends \Service\Bll\BaseBll
{
    /**
     * 红包列表
     * @param int $uid
     * @param array $data
     * @User: feiying
     */
    public function redpacketSearch(int $aid, array $data)
    {	
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $page = new PageList($hcityShardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityShardDb->tables['shcity_company_red_packet']} ";
        $p_conf->where = "aid = {$aid} ";
        if (!empty($data['shop_id'])) {
           $p_conf->where .= " and shop_id={$page->filter($data['shop_id'])}";
        }
        //正常状态
        if (isset($data['status']) && is_numeric($data['status']) && $data['status'] == 1 ) {
            $p_conf->where .= " and status={$page->filter($data['status'])} and balance > '0' and  expire_time >".time();
        }
        //到期状态
        if (isset($data['status']) && is_numeric($data['status']) && $data['status'] == 2 ) {
            $p_conf->where .= " and expire_time <".time();
        }
        //已经使用状态
        if (isset($data['status']) && is_numeric($data['status']) && $data['status'] == 3 ) {
            $p_conf->where .= " and balance = '0' ";
        }

        if (!empty($data['username'])) {
            $p_conf->where .= "  and username like '%".$page->filterLike($data['username'])."%' ";
        }
        if (!empty($data['mobile'])) {
            $p_conf->where .= " and mobile like '%".$page->filterLike($data['mobile'])."%'";
        }
        $p_conf->fields = '*';
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach($rows['rows'] as &$v){
            if($v['status'] == 1 && $v['balance'] > 0 && $v['expire_time'] > time()){
                $v['status'] = strval(1);
            }
            if($v['expire_time'] < time()){
                $v['status'] = strval(2);
            }
            if($v['balance'] == 0 ){
                $v['status'] = strval(3);
            }
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取红包使用详情列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function redDetail(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $rows['info'] = ShcityCompanyRedPacketDao::i(['aid' => $aid])->getOneArray(['aid' => $aid, 'id' => $data['id']]);
    
        if($rows['info']['status'] == 1 && $rows['info']['balance'] > 0 && $rows['info']['expire_time'] > time()){
            $rows['info']['status'] = strval(1);
        }
        if($rows['info']['expire_time'] < time()){
            $rows['info']['status'] = strval(2);
        }
        if($rows['info']['balance'] == 0 ){
            $rows['info']['status'] = strval(3);
        }
        $page = new PageList($hcityShardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityShardDb->tables['shcity_company_red_packet']} a left join {$hcityShardDb->tables['shcity_red_packet_consume']} b on a.id=b.red_packet_id";
        $p_conf->where .= "and a.id={$data['id']} and a.aid = {$aid}";
        // $p_conf->pageSize = "{$data['page_size']}";
        // $p_conf->currentPage = "{$data['current_page']}";
        $p_conf->order = 'b.id asc';
        $p_conf->fields= 'a.id as redid,b.*';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }


}
<?php
/**
 * 砍价活动bll
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/9/3
 * Time: 下午17:38
 */
namespace Service\Bll\Hcity;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainHelperDao;
use Service\DbFrame\DataBase\HcityMainDbv\HcityActivityBargainJoinDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainConfigDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainTurnsDao;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;

class ManageBargainBll extends \Service\Bll\BaseBll
{
     /**
     * 砍价活动列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function ActivityListsh(\s_hmanage_user_do $sUser, array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_activity_bargain']}";
        $p_conf->where = " audit_status in (1,3)";
        if ($sUser->type == 0) {
            $p_conf->where .= " and region like '%{$page->filterLike($sUser->region)}%'";
        }
        if ($fdata['region']) {
            $p_conf->where .= " and region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['title']) {
            $p_conf->where .= "and title like '%{$page->filterLike($fdata['title'])}%'";
        }
        if ($fdata['source_type'] != null) {
            $p_conf->where .= ' and source_type=' . $fdata['source_type'];
        }
        if ($fdata['shop_name']) {
            $p_conf->where .= "and shop_name like '%{$page->filterLike($fdata['shop_name'])}%'";
        }
        if ($fdata['audit_status'] != null) {
            $p_conf->where .= ' and audit_status=' . $fdata['audit_status'];
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 砍价活动审核
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function editAuditStatus(array $fdata)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        //审核通过
        $info = $hcityActivityBargainDao->getOneArray(['id' => $fdata['id']]);
        if (!$info) {
            throw new Exception('此活动不存在，无法审核');
        }
        //审核通过
        if ($fdata['audit_status'] == 2) { 
            //增加初始轮次
            $turnsData = [
                'activity_id' => $info['id'],
                'turns_id' => 1,
                'current_price' => $info['start_price'],
            ];
            $turnsId = HcityActivityBargainTurnsDao::i()->create($turnsData);
            if($turnsId){
                $status = $hcityActivityBargainDao->update(['status' => '1', 'audit_status' => $fdata['audit_status'],'current_turns_id' =>1], ['id' => $fdata['id']]);
            }
        }
        if ($fdata['audit_status'] == 3) {
            $status = $hcityActivityBargainDao->update(['audit_status' => $fdata['audit_status']], ['id' => $fdata['id']]);
        }
        //如果是设置活动过期
        if ($fdata['status'] == 2) {
            $status = $hcityActivityBargainDao->update(['status' => $fdata['status']], ['id' => $fdata['id']]);
        }
        if ($status) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 砍价活动配置文件
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function kjConfig()
    {
        $HcityActivityBargainConfigDao = HcityActivityBargainConfigDao::i();
        $row = $HcityActivityBargainConfigDao->getAll();
        $info['id'] = 1;
        $info['min_interval'] = 0;
        $info['max_interval'] = 20;
        $info['min_price'] = 1;
        $info['max_price'] = 2;
        return $row;
    }

    /**
     * 编辑砍价活动配置文件
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function editConfig(array $data)
    {   
        $HcityActivityBargainConfigDao = HcityActivityBargainConfigDao::i();
        $config = [];
        $config[0]  =  json_decode($data['config1']);
        $config[1]  =  json_decode($data['config2']);
        $config[2]  =  json_decode($data['config3']);
        $config[3]  =  json_decode($data['config4']);
        $config[4]  =  json_decode($data['config5']);
        foreach ($config as $key=>$value){
            $configinfo = [
                    'min_interval' => $value->min_interval,
                    'max_interval' => $value->max_interval,
                    'min_price' => $value->min_price,
                    'max_price' => $value->max_price
                ];
            if(!empty($value->id)){
                $info = $HcityActivityBargainConfigDao->update($configinfo,['id'=>$value->id]);
            } else{
                $info = $HcityActivityBargainConfigDao->create($configinfo);
            }
        }
        return true;
    }

    /**
     * 砍价活动列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function ActivityList(\s_hmanage_user_do $sUser, array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_activity_bargain']}";
        $p_conf->where = " audit_status = 2";
        if ($sUser->type == 0) {
            $p_conf->where .= " and region like '%{$page->filterLike($sUser->region)}%'";
        }
        if ($fdata['region']) {
            $p_conf->where .= " and region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['title']) {
            $p_conf->where .= "and title like '%{$page->filterLike($fdata['title'])}%'";
        }
        if ($fdata['source_type'] != null) {
            $p_conf->where .= ' and source_type=' . $fdata['source_type'];
        }
        if ($fdata['shop_name']) {
            $p_conf->where .= "and shop_name like '%{$page->filterLike($fdata['shop_name'])}%'";
        }
        if ($fdata['status'] != null) {
            $p_conf->where .= ' and status=' . $fdata['status'];
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach ($rows['rows'] as $key => $value) {   
            if ($value['end_time'] < time() && $value['audit_status'] == 2) {
                //如果活动时间结束
                if ($value['status']!=2) {
                    $status = $hcityActivityBargainDao->update(['status' => '2', 'time' => time()], ['id' => $value['id']]);
                    $rows['rows'][$key]['status'] = "2";
                }
            }      
        }
        $rows['total'] = $count;
        return $rows;
    }


}
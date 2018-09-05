<?php

/**
 * Created by sublime.
 * 集赞活动
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/8/20
 * Time: 下午19:18
 */
namespace Service\Bll\Hcity;

use Service\Support\FLock;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityGoodsJzDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;


class ActivityGoodsJzBll extends \Service\Bll\BaseBll
{

    /**
     * 添加活动
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function createActivity(int $aid, array $data)
    {
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        $shop_info = MainShopDao::i()->getOneArray(['id' => $data['shop_id']],'region');
        //新增活动
        if($data['saas_id'] == 3){
            $data['source_type'] = 1;
        }
        if($data['saas_id'] == 2){
            $data['source_type'] = 2;
        }
        $activityData = [
            'aid' => $aid,
            'shop_id' => $data['shop_id'],
            'goods_id'=> $data['goods_id'],
            'title' => $data['title'],
            'pic_url' => $data['pic_url'],
            'price' => $data['price'],
            'activity_price' => $data['activity_price'],
            'stock_num' => $data['stock_num'],
            'start_time' => $data['start_time'],
			'end_time' => $data['end_time'],
			'need_help_num' => $data['need_help_num'],
            'source_type'  =>  $data['source_type'],
            'region' => $shop_info['region']
        ];
        $activityId = $hcityActivityGoodsJzDao->create($activityData);
        if($activityId){
        	return true;
        }else{
        	return false;
        }
    }

    /**
     * 编辑活动
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function editActivity(int $aid, array $data)
    {
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();

        $activityInfo = $hcityActivityGoodsJzDao->getOneArray(['id' => $data['id']], $fields = "*");
        if($activityInfo['status'] == 1){
            throw new Exception('活动正在上架，无法修改');
        }
        if($activityInfo['status'] == 2){
            throw new Exception('活动已经结束，无法修改');
        }
        if($activityInfo['audit_status'] == 1){
            throw new Exception('活动正在上架申请，无法修改');
        }
        if($activityInfo['audit_status'] == 2){
            throw new Exception('活动正在上架，无法修改');
        } 
        //修改活动
        $activityData = [
            'aid' => $aid,
            'shop_id' => $data['shop_id'],
            'goods_id'=> $data['goods_id'],
            'title' => $data['title'],
            'pic_url' => $data['pic_url'],
            'price' => $data['price'],
            'activity_price' => $data['activity_price'],
            'stock_num' => $data['stock_num'],
            'start_time' => $data['start_time'],
			'end_time' => $data['end_time'],
			'need_help_num' => $data['need_help_num'],
        ];
        $activityId = $hcityActivityGoodsJzDao->update($activityData, ['id' => $data['id']]);
        return true;
    }

    /**
     * 活动商品
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function goodsList(int $aid, array $data)
    {
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        if($data['saas_id'] == 3){
            $data['source_type'] = 1;
        }
        if($data['saas_id'] == 2){
            $data['source_type'] = 2;
        }
        $activity = $hcityActivityGoodsJzDao->getEntitysByAR(['where_in' => ['status' => [0,1]],"where"=>['aid' => $aid, 'shop_id' => $data['shop_id'],'source_type' => $data['source_type']]]);
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $page = new PageList($hcityShardDb);
        $p_conf = $page->getConfig();
        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityShardDb->tables['shcity_goods']} ";
        $p_conf->where = "aid = {$aid} and is_delete = 0 and shop_id = {$data['shop_id']} ";
        if($activity){
            $p_conf->where .= " and id not in (".implode(',',array_column($activity,'goods_id')).")";
        }
        //如果来源商圈，审核通过的商品才能添加点赞商品
        if($data['saas_id'] == 3){
           $p_conf->where .= " and hcity_status = 1";
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
       	return $rows;
    }

     /**
     * 删除活动商品
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function deleteActivity(int $aid, array $data)
    {
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        $activity = $hcityActivityGoodsJzDao->getAll(['aid' => $aid, 'id' => $data['id']]);
        if(!$activity){
        	throw new Exception('此活动不存在，无法删除');
        }
        if ($activity->status == 1) {
            throw new Exception('活动已发布，无法删除');
        }
        if ($activity->audit_status == 1) {
            throw new Exception('活动正在审核，无法删除');
        }
        if ($activity->audit_status == 2) {
            throw new Exception('活动审核通过，无法删除');
        }
        $activityInfo = $hcityActivityGoodsJzDao->delete(['id'=>$data['id']]);
        if($activityInfo){
        	return true;
        } else {
        	return false;
        }     
    }
    
    /**
     * 获取活动查找列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function ActivityList(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $hcityMainDb = HcityMainDb::i(['aid' => $aid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();

        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityMainDb->tables['hcity_activity_goods_jz']}";
        $p_conf->where = "aid = {$aid} ";
        //增加筛选条件
        if (!empty($data['shop_id'])) {
            $p_conf->where .= " and shop_id={$page->filter($data['shop_id'])}";
        } else{
            if($data['saas_id'] == 2){
                $shopId = HcityShopExtDao::i()->getAllArray(['barcode_status' => 1,'aid'=> $aid, 'barcode_expire_time >' => time()],'shop_id');
                if($shopId){
                $p_conf->where .= " and shop_id in (".implode(',',array_column($shopId,'shop_id')).")";
                }
            }
            if($data['saas_id'] == 3){
                $shopId = HcityShopExtDao::i()->getEntitysByAR(["where" =>['aid' => $aid],'where_in' => ['hcity_show_status' => [1,2]]]);
                if($shopId){
                $p_conf->where .= " and shop_id in (".implode(',',array_column($shopId,'shop_id')).")";
                }
            }
        }
        if($data['saas_id'] == 3){
            $p_conf->where .= " and source_type= '1'";
        }
        if($data['saas_id'] == 2){
            $p_conf->where .= " and source_type= '2'";
        }
        if (isset($data['status']) && is_numeric($data['status'])) {
            $p_conf->where .= " and status={$page->filter($data['status'])}";
        }
        if (isset($data['audit_status']) && is_numeric($data['audit_status'])) {
            $p_conf->where .= " and audit_status={$page->filter($data['audit_status'])}";
        }

        if (!empty($data['title'])) {
            $p_conf->where .= " and title like '%".$page->filterLike($data['title'])."%'";
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach ($rows['rows'] as $key => $value) {   
            if ($value['end_time'] < time() && $value['audit_status'] == 2) {
                //如果活动时间结束
                if ($value['status']!=2) {
                    $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
                    $status = $hcityActivityGoodsJzDao->update(['status' => '2', 'time' => time()], ['id' => $value['id']]);
                    $rows['rows'][$key]['status'] = "2";
                }
            }      
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取活动详情列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function ActivityDetail(int $aid, array $data)
    {
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        $rows['activity'] = $hcityActivityGoodsJzDao->getOneArray(['aid' => $aid, 'id' => $data['id']]);
        $rows['goods_info'] = ShcityGoodsDao::i(['aid' => $aid])->getOneArray(['id' => $rows['activity']['goods_id'],'aid' =>$aid],'show_begin_time,show_end_time,use_end_time');
        return $rows;
    }

    /**
     * 商圈商品申请上架架
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function applyStatus(int $aid, array $data)
    { 
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        $status = $hcityActivityGoodsJzDao->update(['audit_status' => $data['audit_status'],'aid' => $aid], ['id' => $data['id']]);
        if($status){
        	return true;
        }else {
        	return false;
        }
    }

    /**
     * 一点一码申请上下架
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function applyStatusYdym(int $aid, array $data)
    { 
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        if($data['audit_status'] == 2){
        $status = $hcityActivityGoodsJzDao->update(['status' => '1','audit_status' => $data['audit_status'],'aid' => $aid], ['id' => $data['id']]);
        }
        //活动结束按钮
        if($data['audit_status'] == 0){
            $status = $hcityActivityGoodsJzDao->update(['status' => '2','aid' => $aid], ['id' => $data['id']]);
        }
        if($status){
            return true;
        }else {
            return false;
        }
    }

    /**
     * 获取活动查找列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function activityuserList(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $hcityMainDb = HcityMainDb::i(['aid' => $aid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();

        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityMainDb->tables['hcity_goods_jz_join']}";
        $p_conf->where = "1=1 ";
        //增加筛选条件
        if ($data['activity_id']) {
            $p_conf->where .= " and activity_id={$page->filter($data['activity_id'])}";
        }
        if (isset($data['status']) && is_numeric($data['status'])) {
            $p_conf->where .= " and status={$page->filter($data['status'])}";
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取活动详情列表(总后台)
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function activityDetailz(array $data)
    {
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        $rows['activity'] = $hcityActivityGoodsJzDao->getOneArray(['id' => $data['id']]);
        $shop_info = MainShopDao::i()->getOneArray(['id' => $rows['activity']['shop_id']],'shop_name');
        $rows['shop_name'] = $shop_info['shop_name'];
        return $rows;
    }

    /**
     * 获取活动查找列表(总后台)
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function activityuserListz(array $data)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();

        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityMainDb->tables['hcity_goods_jz_join']}";
        $p_conf->where = "1=1 ";
        //增加筛选条件
        if ($data['activity_id']) {
            $p_conf->where .= " and activity_id={$page->filter($data['activity_id'])}";
        }
        if (isset($data['status']) && is_numeric($data['status'])) {
            $p_conf->where .= " and status={$page->filter($data['status'])}";
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }


}
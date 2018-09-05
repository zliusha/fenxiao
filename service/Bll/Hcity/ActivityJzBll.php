<?php

/**
 * Created by PhpStorm.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/7/13
 * Time: 上午10:40
 */
namespace Service\Bll\Hcity;

use Service\Bll\Hcity\Xcx\XcxBll;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityGoodsJzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsJzHelperDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsJzJoinDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopCategoryRelDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Enum\CollectGoodsSourceTypeEnum;
use Service\Enum\XcxTemplateMessageEnum;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;

class ActivityJzBll extends \Service\Bll\BaseBll
{
    /**
     * 点赞活动审核
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function editAuditStatus(array $fdata)
    {
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        //审核通过
        $info = $hcityActivityGoodsJzDao->getOneArray(['id' => $fdata['id']]);
        if (!$info) {
            throw new Exception('此活动不存在，无法审核');
        }
        if ($fdata['audit_status'] == 2) {
            $status = $hcityActivityGoodsJzDao->update(['status' => '1', 'audit_status' => $fdata['audit_status'], 'time' => time()], ['id' => $fdata['id']]);
        }
        if ($fdata['audit_status'] == 3) {
            $status = $hcityActivityGoodsJzDao->update(['audit_status' => $fdata['audit_status'], 'time' => time()], ['id' => $fdata['id']]);
        }
        //如果是设置活动过期
        if ($fdata['status'] == 2) {
            $status = $hcityActivityGoodsJzDao->update(['status' => $fdata['status'], 'time' => time()], ['id' => $fdata['id']]);
        }
        if ($status) {
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
    public function ActivityList(\s_hmanage_user_do $sUser, array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_activity_goods_jz']}";
        $p_conf->where = " audit_status = '2'";
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
        if ($fdata['shop_id']) {
            $p_conf->where .= "and shop_id=" . $fdata['shop_id'];
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $shopArr = [];
        if (!empty($rows['rows'])) {
            $shopIds = array_column($rows['rows'], 'shop_id');
            $shop_info = MainShopDao::i()->getAllArray('id in (' . implode(',', array_unique($shopIds)) . ')', 'id,shop_name');
            $shopArr = array_column($shop_info, 'shop_name', 'id');
        }
        foreach ($rows['rows'] as $key => $value) {
            if ($value['end_time'] < time()) {
                //如果活动时间结束
                if ($value['end_time'] < time() && $value['status'] != 2) {
                    $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
                    $status = $hcityActivityGoodsJzDao->update(['status' => '2', 'time' => time()], ['id' => $value['id']]);
                    $rows['rows'][$key]['status'] = "2";
                }
            }
            $rows['rows'][$key]['shop_name'] = isset($shopArr[$value['shop_id']]) ? $shopArr[$value['shop_id']] : '';
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取活动查找列表
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
        $p_conf->table = "{$hcityMainDb->tables['hcity_activity_goods_jz']}";
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
        if ($fdata['shop_id']) {
            $p_conf->where .= "and shop_id=" . $fdata['shop_id'];
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach ($rows['rows'] as $key => $value) {
            $shop_info = MainShopDao::i()->getOneArray(['id' => $value['shop_id']], 'shop_name');
            $rows['rows'][$key]['shop_name'] = $shop_info['shop_name'];
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 一店一码集赞活动商品列表
     * @param array $fdata
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getYdymJzGoodsList(array $fdata)
    {
        $mainDb = HcityMainDb::i();
        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();
        //查找符合条件的shop_id;
        $p_conf->table = "{$mainDb->tables['hcity_activity_goods_jz']}";
        $currentTime = time();
        $p_conf->where = "aid = {$page->filter($fdata['aid'])} and shop_id = {$page->filter($fdata['shop_id'])} and start_time < {$currentTime} and end_time > {$currentTime} and source_type = 2 and status = 1";
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        if (!empty($rows['rows'])) {
            $shopIds = array_unique(array_column($rows['rows'], 'shop_id'));
            $shops = MainShopDao::i()->getAllExt(['where_in' => ['id' => $shopIds]]);
            $shopTmp = [];
            foreach ($shops as $shop) {
                $shopTmp[$shop->id] = $shop;
            }
            foreach ($rows['rows'] as $key => $val) {
                $rows['rows'][$key]['pic_url'] = conver_picurl($val['pic_url']);
                $rows['rows'][$key]['shop_name'] = isset($shopTmp[$val['shop_id']]) ? $shopTmp[$val['shop_id']]->shop_name : '';
                $rows['rows'][$key]['distance'] = isset($shopTmp[$val['shop_id']]) ? get_distance($fdata['long'], $fdata['lat'], $shopTmp[$val['shop_id']]->longitude, $shopTmp[$val['shop_id']]->latitude) : '';
            }
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 商圈集赞活动商品列表
     * @param array $fdata
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getHcityJzGoodsList(array $fdata)
    {
        $mainDb = HcityMainDb::i();
        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();
        //查找符合条件的shop_id;
        $p_conf->table = "{$mainDb->tables['hcity_activity_goods_jz']}";
        $currentTime = time();
        if (!empty($fdata['city_code'])) {
            $p_conf->where = "region like '%{$page->filterLike($fdata['city_code'])}%'";
        } elseif (!empty($fdata['aid']) && !empty($fdata['shop_id'])) {
            $p_conf->where = "aid = {$page->filter($fdata['aid'])} and shop_id = {$page->filter($fdata['shop_id'])}";
        }
        $p_conf->where .= " and start_time < {$currentTime} and end_time > {$currentTime} and source_type = 1 and status = 1";
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        if (!empty($rows['rows'])) {
            $shopIds = array_unique(array_column($rows['rows'], 'shop_id'));
            $shops = MainShopDao::i()->getAllExt(['where_in' => ['id' => $shopIds]]);
            $shopTmp = [];
            foreach ($shops as $shop) {
                $shopTmp[$shop->id] = $shop;
            }
            foreach ($rows['rows'] as $key => $val) {
                $rows['rows'][$key]['pic_url'] = conver_picurl($val['pic_url']);
                $rows['rows'][$key]['shop_name'] = isset($shopTmp[$val['shop_id']]) ? $shopTmp[$val['shop_id']]->shop_name : '';
                $rows['rows'][$key]['distance'] = isset($shopTmp[$val['shop_id']]) ? get_distance($fdata['long'], $fdata['lat'], $shopTmp[$val['shop_id']]->longitude, $shopTmp[$val['shop_id']]->latitude) : '';
            }
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 点赞商品详情
     * @param array $fdata
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getJzGoodsDetail(array $fdata)
    {
        $activityDetail = HcityActivityGoodsJzDao::i()->getOne(['id' => $fdata['activity_id']]);
        if (empty($activityDetail)) {
            throw new Exception('活动不存在');
        }
        $detail = ShcityGoodsDao::i(['aid' => $activityDetail->aid])->detail($activityDetail->aid, $activityDetail->goods_id);
        $kzDetail = (new GoodsKzBll())->detailByGoodsId($activityDetail->goods_id);
        $data['goods_detail'] = $detail;
        $data['goods_kz_detail'] = $kzDetail;
        //当前用户是否已参与点赞活动
        $data['is_join_activity'] = 0;
        //判断是否收藏
        $data['is_collect'] = 0;
        if (!empty($activityDetail)) {
            $activityDetail->pic_url = conver_picurl($activityDetail->pic_url);
            $activityDetail->pic_url_list = $detail->pic_url_list;

            if ($fdata['uid'] != 0) {
                $join = HcityGoodsJzJoinDao::i()->getOne(['uid' => $fdata['uid'], 'activity_id' => $fdata['activity_id']]);
                $data['is_join_activity'] = !empty($join) ? 1 : 0;
                $ret = HcityUserCollectGoodsDao::i(['uid' => $fdata['uid']])->getOne(['uid' => $fdata['uid'], 'aid' => $activityDetail->aid, 'goods_id' => $activityDetail->goods_id, 'source_type' => CollectGoodsSourceTypeEnum::ACTIVITY_JZ]);
                if ($ret) $data['is_collect'] = 1;
            }
        }
        $data['activity_detail'] = $activityDetail;
        return $data;
    }

    /**
     * 参与点赞活动
     * @param array $fdata
     * @return bool
     * @throws Exception
     * @throws \Throwable
     * @author ahe<ahe@iyenei.com>
     */
    public function joinJzActivity(array $fdata)
    {
        $activity = HcityActivityGoodsJzDao::i()->getOne(['id' => $fdata['activity_id']]);
        if (empty($activity) || $activity->status != 1) {
            throw new Exception('活动不存在');
        }
        if ($activity->stock_num == 0) {
            throw new Exception('活动库存不足，请稍后再试');
        }

        if ($activity->start_time > time() || $activity->end_time < time()) {
            throw new Exception('活动时间外，不能参与');
        }

        $activityJoin = HcityGoodsJzJoinDao::i()->getOne(['uid' => $fdata['uid'], 'activity_id' => $fdata['activity_id']]);
        if (!empty($activityJoin)) {
            throw new Exception('您已参与活动，不需要再次参与');
        }
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        try {
            //记录参与者信息
            $createData = [
                'uid' => $fdata['uid'],
                'username' => $fdata['username'],
                'mobile' => $fdata['mobile'],
                'time' => time(),
                'status' => 1,
                'activity_id' => $fdata['activity_id'],
            ];
            $ret = HcityGoodsJzJoinDao::i()->create($createData);
            if ($ret > 0) {
                //记录参数数
                HcityActivityGoodsJzDao::i()->setInc('join_num', 1, ['where' => ['id' => $fdata['activity_id']]]);
            }
            $hcityMainDb->trans_complete();
            return true;
        } catch (\Throwable $e) {
            $hcityMainDb->trans_rollback();
            throw $e;
        }
    }

    /**
     * @param array $fdata
     * @return bool
     * @throws Exception
     * @throws \Throwable
     * @author ahe<ahe@iyenei.com>
     */
    public function helpJzActivity(array $fdata)
    {
        if ($fdata['join_uid'] == $fdata['helper_uid']) {
            throw new Exception('不能给自己集赞');
        }
        $activity = HcityActivityGoodsJzDao::i()->getOne(['id' => $fdata['activity_id']]);
        if (empty($activity) || $activity->status != 1 || $activity->end_time < time()) {
            throw new Exception('活动已结束');
        }
        $activityJoin = HcityGoodsJzJoinDao::i()->getOne(['uid' => $fdata['join_uid'], 'activity_id' => $fdata['activity_id'], 'status' => 1]);
        if (empty($activityJoin)) {
            throw new Exception('集赞已满');
        }
        $helpData = HcityGoodsJzHelperDao::i()->getOne(['helper_uid' => $fdata['helper_uid'], 'join_uid' => $fdata['join_uid'], 'activity_id' => $fdata['activity_id']]);
        if (!empty($helpData)) {
            throw new Exception('您已帮朋友集赞');
        }
        $helpNum = HcityGoodsJzHelperDao::i()->getCount(['join_uid' => $fdata['join_uid'], 'activity_id' => $fdata['activity_id']]);
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        try {
            //记录参与者信息
            $createData = [
                'join_uid' => $fdata['join_uid'],
                'helper_uid' => $fdata['helper_uid'],
                'helper_name' => $fdata['helper_name'],
                'helper_img' => $fdata['helper_img'],
                'time' => time(),
                'activity_id' => $fdata['activity_id'],
            ];
            $ret = HcityGoodsJzHelperDao::i()->create($createData);
            if ($ret > 0) {
                //记录参数数
                HcityGoodsJzJoinDao::i()->setInc('has_help_num', 1, ['where' => ['id' => $activityJoin->id]]);
            }
            if ($helpNum + 1 >= $activity->need_help_num) {
                //标记集赞已满
                HcityGoodsJzJoinDao::i()->update(['status' => 2], ['id' => $activityJoin->id]);
                //发送服务通知
                $userWx = HcityUserWxbindDao::i()->getOne(['uid' => $fdata['join_uid']]);
                $shop = MainShopDao::i()->getOne(['id' => $activity->shop_id], 'shop_name');
                if (!empty($userWx)) {
                    $pageParams = [
//                        'aid' => $activity->aid,
//                        'goods_id' => $activity->goods_id,
                        'source_type' => $activity->source_type == 1 ? 'sq' : 'ydym',
                        'activity_id' => $activity->id,
                    ];
                    $params = [
                        'openid' => $userWx->open_id,
                        'page_params' => $pageParams,
                        'activity_name' => '集赞特惠',
                        'result' => '您的好友已帮您完成集赞任务',
                        'award' => $activity->title,
                        'shop_name' => !empty($shop) ? $shop->shop_name : '',
                        'tip' => '活动库存有限，请尽快购买',
                    ];
                    (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::FRIEND_HELP_RESULT);
                }
            }
            $hcityMainDb->trans_complete();
            return true;
        } catch (\Throwable $e) {
            $hcityMainDb->trans_rollback();
            throw $e;
        }
    }

    /**
     * 集赞助力榜
     * @param array $fdata
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getJzHelperList(array $fdata)
    {
        $list['rows'] = HcityGoodsJzHelperDao::i()->getAllArray(['join_uid' => $fdata['join_uid'], 'activity_id' => $fdata['activity_id']]);
        $list['total'] = HcityGoodsJzHelperDao::i()->getCount(['join_uid' => $fdata['join_uid'], 'activity_id' => $fdata['activity_id']]);
        return $list;
    }

    /**
     * 获取更多优惠商品
     * @param array $fdata
     * @return object
     * @author ahe<ahe@iyenei.com>
     */
    public function getOtherActivityGoods(array $fdata)
    {
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        $where = [
            'aid' => $fdata['aid'],
            'shop_id' => $fdata['shop_id'],
            'goods_id' => $fdata['goods_id'],
        ];
        $originalData = $hcityActivityGoodsJzDao->getOne($where, '*');
        if (empty($originalData)) {
            return $originalData;
        }
        //取当前店铺其他商品
        $currentTime = time();
        $where = [
            'aid' => $fdata['aid'],
            'shop_id' => $fdata['shop_id'],
            'goods_id!=' => $fdata['goods_id'],
            'status' => 1,
            'source_type' => $fdata['source_type'],
            'start_time <' => $currentTime,
            'end_time>' => $currentTime,
        ];
        //取当前店铺其他商品
        $out = $hcityActivityGoodsJzDao->getOne($where, '*', 'id desc');
        $isPlat = false;
        if (empty($out)) {
            //取同分类店铺下活动商品
            $categoryRel = HcityShopCategoryRelDao::i()->getAllArray(['shop_id' => $fdata['shop_id']], 'category_id');
            $categoryArr = array_column($categoryRel, 'category_id');
            $otherCategoryRel = HcityShopCategoryRelDao::i()->getAllArray("category_id in (" . implode(',', $categoryArr) . ") and shop_id != {$fdata['shop_id']}", 'shop_id');
            if (empty($otherCategoryRel)) {
                $isPlat = true;
            } else {
                $otherShopArr = array_column($otherCategoryRel, 'shop_id');
                $sql = sprintf("shop_id in (%s) and region like '%%%s%%' and status =1 and source_type = %d and start_time< %s and end_time> %s", implode(',', array_unique($otherShopArr)), $originalData->region, $fdata['source_type'], $currentTime, $currentTime);
                $out = $hcityActivityGoodsJzDao->getOne($sql, '*', 'id desc');
                if (empty($out)) {
                    $isPlat = true;
                }
            }
        }
        if ($isPlat) {
            //随机取全平台最新活动商品
            $platSql = sprintf("goods_id != %d and region like '%%%s%%' and status =1 and source_type = %d and start_time< %s and end_time> %s", $fdata['goods_id'], $originalData->region, $fdata['source_type'], $currentTime, $currentTime);
            $out = $hcityActivityGoodsJzDao->getOne($platSql, '*', 'id desc');
        }
        if (!empty($out)) {
            $out->pic_url = conver_picurl($out->pic_url);
        }
        return $out;
    }

    /**
     * 判断店铺是否有集赞活动
     * @param array $fdata
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function hasJsActivity(array $fdata)
    {
        if (!empty($fdata['city_code'])) {
            $sql = sprintf("region like '%%%s%%' and source_type = 1 ", $fdata['city_code']);
        } else {
            $sql = sprintf("aid = %d and shop_id = %d and source_type = %d", $fdata['aid'], $fdata['shop_id'], $fdata['source_type']);
        }
        $currentTime = time();
        $sql .= sprintf(" and start_time < %s and end_time > %s and status = 1", $currentTime, $currentTime);
        //取当前店铺其他商品
        $ret = HcityActivityGoodsJzDao::i()->getOne($sql, 'id');
        return !empty($ret) ? 1 : 0;
    }
}

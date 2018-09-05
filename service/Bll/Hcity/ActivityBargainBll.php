<?php
/**
 * 砍价活动bll
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/9/3
 * Time: 下午14:11
 */
namespace Service\Bll\Hcity;

use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopCategoryRelDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectGoodsDao;
use Service\Enum\CollectGoodsSourceTypeEnum;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainJoinDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainConfigDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainTurnsDao;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Support\YdbArray;

class ActivityBargainBll extends \Service\Bll\BaseBll
{
    /**
     * 砍价活动商品
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function goodsList(int $aid, array $data)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        if ($data['saas_id'] == 3) {
            $data['source_type'] = 1;
        }
        if ($data['saas_id'] == 2) {
            $data['source_type'] = 2;
        }
        $activity = $hcityActivityBargainDao->getEntitysByAR(['where_in' => ['status' => [0,1]],"where"=>['aid' => $aid, 'shop_id' => $data['shop_id'],'source_type' => $data['source_type']]]);
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $page = new PageList($hcityShardDb);
        $p_conf = $page->getConfig();
        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityShardDb->tables['shcity_goods']} ";
        $p_conf->where = "aid = {$aid} and is_delete = 0 and shop_id = {$data['shop_id']} ";
        if ($activity) {
            $p_conf->where .= " and id not in (" . implode(',', array_column($activity, 'goods_id')) . ")";
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 添加活动
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function createActivityKj(int $aid, array $data)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $shop_info = MainShopDao::i()->getOneArray(['id' => $data['shop_id']], 'region,shop_name');
        //新增活动
        if ($data['saas_id'] == 3) {
            $data['source_type'] = 1;
        }
        if ($data['saas_id'] == 2) {
            $data['source_type'] = 2;
        }
        $activityData = [
            'aid' => $aid,
            'shop_id' => $data['shop_id'],
            'shop_name' => $shop_info['shop_name'],
            'goods_id' => $data['goods_id'],
            'title' => $data['title'],
            'pic_url' => $data['pic_url'],
            'start_price' => $data['start_price'],
            'stock_num' => $data['stock_num'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'source_type' => $data['source_type'],
            'region' => $shop_info['region']
        ];
        $activityId = $hcityActivityBargainDao->create($activityData);
        if ($activityId) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 编辑砍价活动
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function editActivityKj(int $aid, array $data)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $activityInfo = $hcityActivityBargainDao->getOneArray(['id' => $data['id']], $fields = "*");
        if ($activityInfo['status'] == 1) {
            throw new Exception('活动正在上架，无法修改');
        }
        if ($activityInfo['status'] == 2) {
            throw new Exception('活动已经结束，无法修改');
        }
        if ($activityInfo['audit_status'] == 1) {
            throw new Exception('活动正在上架申请，无法修改');
        }
        if ($activityInfo['audit_status'] == 2) {
            throw new Exception('活动正在上架，无法修改');
        }
        //修改活动
        $activityData = [
            'aid' => $aid,
            'goods_id' => $data['goods_id'],
            'title' => $data['title'],
            'pic_url' => $data['pic_url'],
            'start_price' => $data['start_price'],
            'stock_num' => $data['stock_num'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ];
        $activityId = $hcityActivityBargainDao->update($activityData, ['id' => $data['id']]);
        return true;
    }

    /**
     * 删除砍价活动商品
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function deleteActivityKj(int $aid, array $data)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $activity = $hcityActivityBargainDao->getAll(['aid' => $aid, 'id' => $data['id']]);
        if (!$activity) {
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
        $activityInfo = $hcityActivityBargainDao->delete(['id' => $data['id']]);
        if ($activityInfo) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取砍价活动查找列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function ActivityListKj(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $hcityMainDb = HcityMainDb::i(['aid' => $aid]);
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();

        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityMainDb->tables['hcity_activity_bargain']}";
        $p_conf->where = "aid = {$aid} ";
        //增加筛选条件
        if (!empty($data['shop_id'])) {
            $p_conf->where .= " and shop_id={$page->filter($data['shop_id'])}";
        } else {
            if ($data['saas_id'] == 2) {
                $shopId = HcityShopExtDao::i()->getAllArray(['barcode_status' => 1, 'aid' => $aid, 'barcode_expire_time >' => time()], 'shop_id');
                if ($shopId) {
                    $p_conf->where .= " and shop_id in (" . implode(',', array_column($shopId, 'shop_id')) . ")";
                }
            }
            if ($data['saas_id'] == 3) {
                $shopId = HcityShopExtDao::i()->getEntitysByAR(["where" => ['aid' => $aid], 'where_in' => ['hcity_show_status' => [1, 2]]]);
                if ($shopId) {
                    $p_conf->where .= " and shop_id in (" . implode(',', array_column($shopId, 'shop_id')) . ")";
                }
            }
        }
        if ($data['saas_id'] == 3) {
            $p_conf->where .= " and source_type= '1'";
        }
        if ($data['saas_id'] == 2) {
            $p_conf->where .= " and source_type= '2'";
        }
        if (isset($data['status']) && is_numeric($data['status'])) {
            $p_conf->where .= " and status={$page->filter($data['status'])}";
        }
        if (isset($data['audit_status']) && is_numeric($data['audit_status'])) {
            $p_conf->where .= " and audit_status={$page->filter($data['audit_status'])}";
        }

        if (!empty($data['title'])) {
            $p_conf->where .= " and title like '%" . $page->filterLike($data['title']) . "%'";
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

    /**
     * 商圈商品申请上架
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function applyStatusKj(int $aid, array $data)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $status = $hcityActivityBargainDao->update(['audit_status' => $data['audit_status'], 'aid' => $aid], ['id' => $data['id']]);
        if ($status) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 一店一码上下架
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function applyStatusKjYdym(int $aid, array $data)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $info = $hcityActivityBargainDao->getOneArray(['id' => $data['id']]);
        if (!$info) {
            throw new Exception('此活动不存在，无法审核');
        }
        //砍价活动上架
        if ($data['audit_status'] == 2) {
            //判断是否上架后下架再次上架
            $turnsData = [
                'activity_id' => $info['id'],
                'turns_id' => 1,
                'current_price' => $info['start_price'],
            ];
            $turnsId = HcityActivityBargainTurnsDao::i()->create($turnsData);
            if ($turnsId) {
                $status = $hcityActivityBargainDao->update(['status' => '1', 'audit_status' => $data['audit_status'], 'current_turns_id' => '1', 'aid' => $aid], ['id' => $data['id']]);
            }
        }
        //砍价活动下架
        if ($data['audit_status'] == 0) {
            $status = $hcityActivityBargainDao->update(['status' => '2', 'aid' => $aid], ['id' => $data['id']]);
        }
        if ($status) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 获取一店一码商品列表
     * @param array $fdata aid,shop_id,lat,long,uid
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getYdymGoodsList(array $fdata)
    {
        $mainDb = HcityMainDb::i();
        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$mainDb->tables['hcity_activity_bargain']}  a left join {$mainDb->tables['hcity_activity_bargain_turns']} b on a.id=b.activity_id and a.current_turns_id=b.turns_id";
        $currentTime = time();
        $p_conf->where = "a.aid = {$page->filter($fdata['aid'])} and a.shop_id = {$page->filter($fdata['shop_id'])} and a.start_time < {$currentTime} and a.end_time > {$currentTime} and a.source_type = 2 and a.status = 1";
        $p_conf->order = 'a.id desc';
        $p_conf->fields = 'a.*,b.current_price,b.bargain_num,b.status as turns_status';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        if (!empty($rows['rows'])) {
            if (!empty($fdata['uid'])) {
                //登录用户，判断砍价状态
                $activityIds = array_unique(array_column($rows['rows'], 'id'));
                $options = [
                    'where' => ['uid' => $fdata['uid']],
                    'where_in' => [
                        'activity_id' => $activityIds
                    ]
                ];
                $joinData = HcityActivityBargainJoinDao::i()->getAllExt($options);
            } else {
                $joinData = [];
            }
            $joinArray = new YdbArray($joinData);
            $joinArray->reIndex('default', ['activity_id', 'turns_id', 'uid']);
            //取店铺信息
            $shopIds = array_unique(array_column($rows['rows'], 'shop_id'));
            $shops = MainShopDao::i()->getAllExt(['where_in' => ['id' => $shopIds]]);
            $shopArray = new YdbArray($shops);
            $shopArray->reIndex('default', ['id']);

            foreach ($rows['rows'] as $key => $val) {
                $shop = $shopArray->getOne('default', $val['shop_id']);
                $rows['rows'][$key]['pic_url'] = conver_picurl($val['pic_url']);
                $rows['rows'][$key]['shop_name'] = $shop ? $shop->shop_name : '';
                $rows['rows'][$key]['distance'] = $shop ? get_distance($fdata['long'], $fdata['lat'], $shop->longitude, $shop->latitude) : '';
                //取当前会员用户砍价状态
                $bargainRet = $joinArray->getOne('default', $val['id'], $val['current_turns_id'], $fdata['uid']);
                $rows['rows'][$key]['is_allow_bargain'] = $bargainRet ? 0 : 1;
            }
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取商圈活动商品列表
     * @param array $fdata city_code,aid,shop_id,lat,long,uid
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getHcityGoodsList(array $fdata)
    {
        $mainDb = HcityMainDb::i();
        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$mainDb->tables['hcity_activity_bargain']}  a left join {$mainDb->tables['hcity_activity_bargain_turns']} b on a.id=b.activity_id and a.current_turns_id=b.turns_id";
        $currentTime = time();
        if (!empty($fdata['city_code'])) {
            $p_conf->where = "a.region like '%{$page->filterLike($fdata['city_code'])}%'";
        } elseif (!empty($fdata['aid']) && !empty($fdata['shop_id'])) {
            $p_conf->where = "a.aid = {$page->filter($fdata['aid'])} and a.shop_id = {$page->filter($fdata['shop_id'])}";
        }
        $p_conf->where .= " and a.start_time < {$currentTime} and a.end_time > {$currentTime} and a.source_type = 1 and a.status = 1";
        $p_conf->order = 'a.id desc';
        $p_conf->fields = 'a.*,b.current_price,b.bargain_num,b.status';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        if (!empty($rows['rows'])) {
            if (!empty($fdata['uid'])) {
                //登录用户，判断砍价状态
                $activityIds = array_unique(array_column($rows['rows'], 'id'));
                $options = [
                    'where' => ['uid' => $fdata['uid']],
                    'where_in' => [
                        'activity_id' => $activityIds
                    ]
                ];
                $joinData = HcityActivityBargainJoinDao::i()->getAllExt($options);
            } else {
                $joinData = [];
            }
            $joinArray = new YdbArray($joinData);
            $joinArray->reIndex('default', ['activity_id', 'turns_id', 'uid']);
            //取店铺信息
            $shopIds = array_unique(array_column($rows['rows'], 'shop_id'));
            $shops = MainShopDao::i()->getAllExt(['where_in' => ['id' => $shopIds]]);
            $shopArray = new YdbArray($shops);
            $shopArray->reIndex('default', ['id']);
            foreach ($rows['rows'] as $key => $val) {
                $shop = $shopArray->getOne('default', $val['shop_id']);
                $rows['rows'][$key]['pic_url'] = conver_picurl($val['pic_url']);
                $rows['rows'][$key]['shop_name'] = $shop ? $shop->shop_name : '';
                $rows['rows'][$key]['distance'] = $shop ? get_distance($fdata['long'], $fdata['lat'], $shop->longitude, $shop->latitude) : '';
                //取当前会员用户砍价状态
                $bargainRet = $joinArray->getOne('default', $val['id'], $val['current_turns_id'], $fdata['uid']);
                $rows['rows'][$key]['is_allow_bargain'] = $bargainRet ? 0 : 1;
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
    public function ActivityDetailKj(array $data)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $rows['activity'] = $hcityActivityBargainDao->getOneArray(['id' => $data['id']]);
        $rows['goods_info'] = ShcityGoodsDao::i(['aid' => $rows['activity']['aid']])->getOneArray(['id' => $rows['activity']['goods_id'],'aid' =>$rows['activity']['aid']],'use_end_time');
        if($rows['activity']['audit_status'] == 2){
            $rows['joincount'] = HcityActivityBargainJoinDao::i()->getCount(['activity_id'=>$rows['activity']['id']]);
        }
        return $rows;
    }

    /**
     * 获取活动详情
     * @param array $fdata activity_id,uid
     * @return mixed
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function getActivityDetail(array $fdata)
    {
        $activityDetail = HcityActivityBargainDao::i()->getOne(['id' => $fdata['activity_id']]);
        if (empty($activityDetail)) {
            throw new Exception('活动不存在');
        }
        $detail = ShcityGoodsDao::i(['aid' => $activityDetail->aid])->detail($activityDetail->aid, $activityDetail->goods_id);
        $kzDetail = (new GoodsKzBll())->detailByGoodsId($activityDetail->goods_id);
        $data['goods_detail'] = $detail;
        $data['goods_kz_detail'] = $kzDetail;
        //当前用户是否允许参与砍价活动
        $data['is_allow_bargain'] = 0;
        //判断是否收藏
        $data['is_collect'] = 0;
        if (!empty($activityDetail)) {
            $activityDetail->pic_url = conver_picurl($activityDetail->pic_url);
            $activityDetail->pic_url_list = $detail->pic_url_list;

            if ($fdata['uid'] != 0) {
                $join = HcityActivityBargainJoinDao::i()->getOne(['activity_id' => $fdata['activity_id'], 'turns_id' => $activityDetail->current_turns_id, 'uid' => $fdata['uid']]);
                $data['is_allow_bargain'] = !empty($join) ? 0 : 1;
                $ret = HcityUserCollectGoodsDao::i(['uid' => $fdata['uid']])->getOne(['uid' => $fdata['uid'], 'aid' => $activityDetail->aid, 'goods_id' => $activityDetail->goods_id, 'source_type' => CollectGoodsSourceTypeEnum::ACTIVITY_BARGAIN]);
                if ($ret) $data['is_collect'] = 1;
            }
        }
        $data['activity_detail'] = $activityDetail;
        $data['activity_turns'] = HcityActivityBargainTurnsDao::i()->getOne(['activity_id' => $fdata['activity_id'], 'turns_id' => $activityDetail->current_turns_id]);

        return $data;
    }

    /**
     * 砍价
     * @param array $fdata activity_id,uid,username,mobile
     * @return float
     * @throws Exception
     * @throws \Throwable
     * @author ahe<ahe@iyenei.com>
     */
    public function doBargain(array $fdata)
    {
        $activityDetail = HcityActivityBargainDao::i()->getOne(['id' => $fdata['activity_id'], 'status' => 1]);
        if (empty($activityDetail)) {
            throw new Exception('活动已失效');
        }
        $hcityActivityBargainTurnsDao = HcityActivityBargainTurnsDao::i();
        $turns = $hcityActivityBargainTurnsDao->getOne(['activity_id' => $fdata['activity_id'], 'turns_id' => $activityDetail->current_turns_id]);
        if (empty($turns) || $turns->current_price > $activityDetail->start_price) {
            throw new Exception('活动异常');
        }
        if ($turns->status == 1) {
            throw new Exception('有人正在购买，请稍后再试');
        }
        if ($turns->status == 2) {
            throw new Exception('今日份额已抢完，请明天再来');
        }
        if ($turns->current_price == 0) {
            throw new Exception('当前活动已砍至最低价，请立即购买');
        }
        $join = HcityActivityBargainJoinDao::i()->getOne(['activity_id' => $fdata['activity_id'], 'turns_id' => $activityDetail->current_turns_id, 'uid' => $fdata['uid']]);
        if (!empty($join)) {
            throw new Exception('您已砍过价，不能再次砍价，请立即购买');
        }
        $startPrice = $activityDetail->start_price;
        $currentPrice = $turns->current_price;
        $diff = round(($startPrice - $currentPrice) / $startPrice * 100);
        $config = HcityActivityBargainConfigDao::i()->getOne(['min_interval <=' => $diff, 'max_interval >' => $diff]);
        if (empty($config)) {
            //后台未配置，默认使用1～10的百分比价格区间范围
            $minPrice = $startPrice * 1 * 0.01;
            $maxPrice = $startPrice * 10 * 0.01;
        } else {
            //后台有配置，则取配置
            $minPrice = $startPrice * $config->min_price * 0.01;
            $maxPrice = $startPrice * $config->max_price * 0.01;
        }
        if ($maxPrice < $minPrice) {
            throw new Exception('活动配置错误');
        }
        //生成需被砍金额
        $bargainPrice = number_format($minPrice + mt_rand() / mt_getrandmax() * ($maxPrice - $minPrice), 2);
        if ($currentPrice < $bargainPrice) {
            //当前价格不足时，只能砍至0
            $bargainPrice = $currentPrice;
        }
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        try {
            //记录砍价者信息
            $createData = [
                'activity_id' => $fdata['activity_id'],
                'turns_id' => $activityDetail->current_turns_id,
                'uid' => $fdata['uid'],
                'username' => $fdata['username'],
                'mobile' => $fdata['mobile'],
                'bargain_price' => $bargainPrice,
                'time' => time(),
            ];
            $ret = HcityActivityBargainJoinDao::i()->create($createData);
            if ($ret > 0) {
                $hcityActivityBargainTurnsDao->setInc('bargain_num', 1, ['where' => ['id' => $turns->id]]);
                $hcityActivityBargainTurnsDao->setDec('current_price', $bargainPrice, ['where' => ['id' => $turns->id]]);
            }
            $hcityMainDb->trans_complete();
            return $bargainPrice;
        } catch (\Throwable $e) {
            $hcityMainDb->trans_rollback();
            throw $e;
        }
    }

    /**
     * 判断店铺是否有砍价活动
     * @param array $fdata city_code，aid，shop_id，source_type
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function hasActivity(array $fdata)
    {
        if (!empty($fdata['city_code'])) {
            $sql = sprintf("region like '%%%s%%' and source_type = 1 ", $fdata['city_code']);
        } else {
            $sql = sprintf("aid = %d and shop_id = %d and source_type = %d", $fdata['aid'], $fdata['shop_id'], $fdata['source_type']);
        }
        $currentTime = time();
        $sql .= sprintf(" and start_time < %s and end_time > %s and status = 1", $currentTime, $currentTime);
        //取当前店铺其他商品
        $ret = HcityActivityBargainDao::i()->getOne($sql, 'id');
        return !empty($ret) ? 1 : 0;
    }

    /**
     * 获取更多优惠商品
     * @param array $fdata aid,shop_id,goods_id,source_type
     * @return object
     * @author ahe<ahe@iyenei.com>
     */
    public function getOtherActivityGoods(array $fdata)
    {
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $where = [
            'aid' => $fdata['aid'],
            'shop_id' => $fdata['shop_id'],
            'goods_id' => $fdata['goods_id'],
        ];
        $originalData = $hcityActivityBargainDao->getOne($where, '*');
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
        $out = $hcityActivityBargainDao->getOne($where, '*', 'id desc');
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
                $out = $hcityActivityBargainDao->getOne($sql, '*', 'id desc');
                if (empty($out)) {
                    $isPlat = true;
                }
            }
        }
        if ($isPlat) {
            //随机取全平台最新活动商品
            $platSql = sprintf("goods_id != %d and region like '%%%s%%' and status =1 and source_type = %d and start_time< %s and end_time> %s", $fdata['goods_id'], $originalData->region, $fdata['source_type'], $currentTime, $currentTime);
            $out = $hcityActivityBargainDao->getOne($platSql, '*', 'id desc');
        }
        if (!empty($out)) {
            $out->pic_url = conver_picurl($out->pic_url);
        }
        return $out;
    }

     /**
     * 获取砍价活动查找列表
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function activityturnsList(array $data)
    {     
        $hcityMainDb = HcityMainDb::i();
        $hcityActivityBargainJoinDao = HcityActivityBargainJoinDao::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityMainDb->tables['hcity_activity_bargain_turns']}";
        $p_conf->where = "1=1 ";
        //增加筛选条件lun'ci
        if ($data['activity_id']) {
            $p_conf->where .= " and activity_id={$page->filter($data['activity_id'])}";
        }
        $p_conf->order = 'id asc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach($rows['rows'] as $key=>$value){
            $rows['rows'][$key]['joincount'] = $hcityActivityBargainJoinDao->getCount(['activity_id'=>$data['activity_id'],'turns_id'=>$value['turns_id']]);
            if($value['stauts'] == 0 ||$value['stauts'] == 1){
              $rows['rows'][$key]['stauts'] = "否" ;
            }
            if($value['stauts'] == 2){
              $rows['rows'][$key]['stauts'] = "是" ;
            }
        }
        $rows['total'] = $count;
        return $rows;
    }
}
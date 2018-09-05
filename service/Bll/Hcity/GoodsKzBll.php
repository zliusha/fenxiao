<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/13
 * Time: 上午10:40
 */
namespace Service\Bll\Hcity;

use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBannerDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsSkuKzDao;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsSkuDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderExtDao;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectGoodsDao;
use Service\Enum\CollectGoodsSourceTypeEnum;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityPopularGoodsDao;


use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsAttrgroupDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsSkuDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsAttritemDao;

class GoodsKzBll extends \Service\Bll\BaseBll
{

    /**
     * 获取商品上架申请 分页
     * @param array $data
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function goodsKzList(\s_hmanage_user_do $sUser, array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        //$p_conf->fields='id,shop_id,goods_id,region,title,pic_url,price,group_price,commission_rate,hcity_stock_num,free_num,limit_num,hcity_status,show_end_time,show_begin_time';
        $p_conf->table = "{$hcityMainDb->tables['hcity_goods_kz_view']}";
        if ($sUser->type == 0) {
            $p_conf->where .= "and region like '{$sUser->region}%'";
        }
        if ($fdata['title']) {
            $p_conf->where .= "and title like '%{$page->filterLike($fdata['title'])}%'";
        }
        if ($fdata['shop_id']) {
            $p_conf->where .= ' and shop_id=' . $fdata['shop_id'];
        }
        if ($fdata['region']) {
            $p_conf->where .= " and region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['hcity_status'] != null) {
            $p_conf->where .= ' and hcity_status=' . $fdata['hcity_status'];
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 福利池商品列表
     * @param array $data
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function freeGoodsKzList(\s_hmanage_user_do $sUser,array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_welfare_goods']}";
        if ($sUser->type == 0) {
            $p_conf->where .= " and region like '%{$page->filterLike($sUser->region)}%'";
        }
        if ($fdata['region']) {
            $p_conf->where .= " and region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['title']) {
            $p_conf->where .= "and title like '%{$page->filterLike($fdata['title'])}%'";
        }
        if ($fdata['welfare_status'] != null) {
            $p_conf->where .= ' and welfare_status=' . $fdata['welfare_status'];
        }
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 福利池商品 详情
     * @param array $data
     * @return array $good
     * @author yize<yize@iyenei.com>
     */
    public function freeDetail(int $id)
    {
        $goods = HcityWelfareGoodsDao::i()->getOne(['id' => $id]);
        if (!$goods) {
            throw new Exception('福利池商品不存在');
        }
        if ($goods->pic_url) {
            $goods->pic_url = conver_picurl($goods->pic_url);
        }
        $pic = '';
        if ($goods->pic_url_list) {
            $picArr = explode(',', $goods->pic_url_list);
            foreach ($picArr as $v) {
                $pic .= conver_picurl($v) . ',';
            }
        }
        $shopInfo = MainShopDao::i()->getOne(['id' => $goods->shop_id]);
        //循环赋值
        if ($shopInfo) {
            $goods->shop_name = $shopInfo->shop_name;
            $goods->contact = $shopInfo->contact;
            $goods->shop_state = $shopInfo->shop_state;
            $goods->shop_city = $shopInfo->shop_city;
            $goods->shop_district = $shopInfo->shop_district;
            $goods->shop_address = $shopInfo->shop_address;
        } else {
            $goods->shop_name = '';
            $goods->contact = '';
            $goods->shop_state = '';
            $goods->shop_city = '';
            $goods->shop_district = '';
            $goods->shop_address = '';
        }
        $pic = rtrim($pic, ',');
        $goods->pic_url_list = $pic;
        return $goods;
    }
    /**
     * 福利池商品兑换记录
     * @param array $data
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function freeGoodsKzOrder1(array $fdata)
    {
        $goodsKz = HcityGoodsKzViewDao::i()->getOne(['id' => $fdata['id']]);
        if (!$goodsKz) {
            throw new Exception('商圈商品不存在');
        }
        $ordersData=ShcityOrderExtDao::i(['aid'=>$goodsKz->aid])->getAll(['aid'=>$goodsKz->aid,'goods_id'=>$goodsKz->goods_id]);
        $tids=array_column($ordersData,'tid');
        $tids=empty($tids) ? '0' : implode(',', $tids);
        $hcityMainDb = HcityShardDb::i(['aid'=>$goodsKz->aid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->where = " tid in ($tids) ";
        $p_conf->table = "{$hcityMainDb->tables['shcity_order']}";
        $p_conf->where .= " and aid = ".$goodsKz->aid;
        $p_conf->where .= " and source_type = 3";
        $p_conf->where .= " and (status = 1 or status = 2)";
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;

        return $rows;
    }

    /**
     * 福利池商品兑换记录
     * @param array $data
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function freeGoodsKzOrder(array $fdata)
    {   
        $goods = HcityWelfareGoodsDao::i()->getOne(['id' => $fdata['id']]);
        if (!$goods) {
            throw new Exception('福利池商品不存在');
        }
        $ordersData=ShcityOrderExtDao::i(['aid'=>$goods->aid])->getAll(['aid'=>$goods->aid,'goods_id'=>$goods->id,'stock_type'=>'3']);
        $tids=array_column($ordersData,'tid');
        $tids=empty($tids) ? '0' : implode(',', $tids);
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_order_kz']}";
        $p_conf->where = " tid in ($tids) ";
        $p_conf->where .= " and aid = ".$goods->aid;
        $p_conf->where .= " and source_type = 3";
        $p_conf->where .= " and (status = 1 or status = 2)";
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        foreach($rows['rows'] as $key=>$value){
            $ordersData=ShcityOrderExtDao::i(['aid'=>$goods->aid])->getOneArray(['aid'=>$goods->aid,'goods_id'=>$goods->id,'tid'=>$value['tid']],'num');
            $rows['rows'][$key]['total_num'] = $ordersData['num'];
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 商品上架申请 详情
     * @param array $data
     * @return array $good
     * @author yize<yize@iyenei.com>
     */
    public function goodsKzDetail(int $id)
    {
        $goodsKz = HcityGoodsKzViewDao::i()->getOne(['id' => $id]);
        if (!$goodsKz) {
            throw new Exception('商圈商品不存在');
        }
        $goods = ShcityGoodsDao::i(['aid' => $goodsKz->aid])->getOne(['aid' => $goodsKz->aid, 'id' => $goodsKz->goods_id]);
        if (!$goods) {
            throw new Exception('商品不存在');
        }
        $goods->desc = htmlspecialchars_decode($goods->desc);
        $fields = 'attr_ids,attr_names,price,group_price,hcity_stock_num,hcard_price,free_num,update_time';
        $goods->goodsSku = ShcityGoodsSkuDao::i(['aid' => $goodsKz->aid])->getAll(['aid' => $goodsKz->aid, 'goods_id' => $goodsKz->goods_id], $fields);
        if ($goods->pic_url) {
            $goods->pic_url = conver_picurl($goodsKz->pic_url);
        }
        $pic = '';
        if ($goods->pic_url_list) {
            $picArr = explode(',', $goods->pic_url_list);
            foreach ($picArr as $v) {
                $pic .= conver_picurl($v) . ',';
            }
        }
        $shopInfo = MainShopDao::i()->getOne(['id' => $goodsKz->shop_id]);
        //循环赋值
        if ($shopInfo) {
            $goods->shop_name = $shopInfo->shop_name;
            $goods->contact = $shopInfo->contact;
            $goods->shop_state = $shopInfo->shop_state;
            $goods->shop_city = $shopInfo->shop_city;
            $goods->shop_district = $shopInfo->shop_district;
            $goods->shop_address = $shopInfo->shop_address;
        } else {
            $goods->shop_name = '';
            $goods->contact = '';
            $goods->shop_state = '';
            $goods->shop_city = '';
            $goods->shop_district = '';
            $goods->shop_address = '';
        }
        $pic = rtrim($pic, ',');
        $goodsKz->pic_url_list = $pic;
        return $goods;
    }

    /**
     * 商圈商品上下架
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function editHcityStatus(\s_hmanage_user_do $sUser, array $fdata)
    {
        $like = [];
        if ($sUser->type == 0) {
            $like = [
                'field' => 'region',
                'str' => $sUser->region,
                'type' => 'after',
            ];
        }
        $goodsKz = HcityGoodsKzViewDao::i()->getOneLike($like, ['id' => $fdata['id']]);
        if (!$goodsKz) {
            throw new Exception('商圈商品不存在');
        }
        if ($goodsKz->hcity_status == $fdata['hcity_status']) {
            throw new Exception('状态没有变化，无需重复操作');
        }
        $goods = ShcityGoodsDao::i(['aid' => $goodsKz->aid])->getOne(['id' => $goodsKz->goods_id]);
        if (!$goods) {
            throw new Exception('商品不存在');
        }
        //上架时检查一下门店是否合法
        if($fdata['hcity_status']==1)
        {
            $shopExt=HcityShopExtDao::i()->getOne(['shop_id'=>$goodsKz->shop_id,'hcity_show_status>'=>0]);
            if(!$shopExt)
            {
                throw new Exception('此商品所属门店没有入住商圈');
            }
            if($shopExt->hcity_show_status==2)
            {
                throw new Exception('此商品所属名店已被清退');
            }
        }
        //开始事物
        $shcityGoodsDb = ShcityGoodsDao::i(['aid' => $goodsKz->aid]);
        $hcityGoodsKzDb = HcityGoodsKzDao::i();
        $shcityGoodsDb->db->trans_start();
        $hcityGoodsKzDb->db->trans_start();
        //修改商品表和快照表状态
        $hcityGoodsKzDb->update(['hcity_status' => $fdata['hcity_status']], ['id' => $fdata['id']]);
        if($fdata['hcity_status'] == 0)
        {
            $shcityGoodsDb->update(['hcity_status' => $fdata['hcity_status']], ['id' => $goodsKz->goods_id]);
        }
        //如果商圈商品上架，更新上架时间
        if($fdata['hcity_status'] == 1)
        {
            $shcityGoodsDb->update(['hcity_status' => $fdata['hcity_status'],'update_time'=>time()], ['id' => $goodsKz->goods_id]);
        }
        //如果是商圈下架，判断此商品是否加入商圈
        if($fdata['hcity_status'] == 0){
            $popularGoods = [
                'aid'=>$goodsKz->aid,
                'region'=>$goodsKz->region,
                'goods_id'=>$goodsKz->goods_id,
                'shop_id' =>$goodsKz->shop_id
            ];
            $popularGoodsList = HcityActivityPopularGoodsDao::i()->getAllArray($popularGoods);
            if($popularGoodsList){
                foreach($popularGoodsList as $v) {
                    $list = HcityActivityPopularGoodsDao::i()->delete(['id' => $v['id']]);
                }
            }
        }
        if ($shcityGoodsDb->db->trans_status() && $hcityGoodsKzDb->db->trans_status()) {
            $shcityGoodsDb->db->trans_complete();
            $hcityGoodsKzDb->db->trans_complete();
            return true;
        } else {
            $shcityGoodsDb->db->trans_rollback();
            $hcityGoodsKzDb->db->trans_rollback();
            throw new Exception('修改失败');
        }

    }


    /**
     * 根据店铺信息获取商品列表（小程序）
     * @param array $input long经度 lat纬度 region区域码 uid
     * @throws Exception
     * @User: liusha
     */
    public function shopGoodsKzList(array $input, $where = '')
    {
        $mainShopDao = MainShopDao::i();
        $hcityShopExtDao = HcityShopExtDao::i();

        // 筛选区域店铺
        $shop_where = '1=1';
        if (!empty($input['city_code']))
            $shop_where .= "  AND region like '%{$input['city_code']}%'";
        $shop_list = $mainShopDao->getAllArray(" {$shop_where} ");
        if (empty($shop_list))
            throw new Exception('无店铺服务通');
        $shop_ids = implode(',', array_column($shop_list, 'id'));

        // 获取有效店铺列表
        $shop_ext_list = $hcityShopExtDao->getAllArray("hcity_show_status=1 AND shop_id in ({$shop_ids})");
        if (empty($shop_ext_list))
            throw new Exception('无店铺服务通');

        // 计算店铺与当前位置的距离
        $valid_shop_list = [];
        foreach ($shop_list as $sk => $shop) {
            //有效的店铺
            if (array_find($shop_ext_list, 'shop_id', $shop['id'])) {
                $shop['distance'] = get_distance($input['long'], $input['lat'], $shop['longitude'], $shop['latitude']);
                $valid_shop_list[] = $shop;
            }
        }
        // 获得店铺排序的id数组
        $shop_sort_ids = implode(',', array_column(array_sort($valid_shop_list, 'distance', 'asc'), 'id'));

        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_goods_kz']} a";

        // 排序规则 销量 收藏量 店铺距离
        if(isset($input['sort_field']) && $input['sort_field']=='sales_num')
        {
            $p_conf->order = " a.top_sort DESC, a.sales_num DESC, FIELD(a.shop_id,{$shop_sort_ids}) ASC, a.collect_num DESC, a.id DESC";
        }
        elseif(isset($input['sort_field']) && $input['sort_field']=='collect_num')
        {
            $p_conf->order = " a.top_sort DESC, a.collect_num DESC, FIELD(a.shop_id,{$shop_sort_ids}) ASC, a.sales_num DESC, a.id DESC";
        }
        else
        {
            $p_conf->order = " a.top_sort DESC, FIELD(a.shop_id,{$shop_sort_ids}) ASC, a.sales_num DESC, a.collect_num DESC, a.id DESC";
        }

        if ($where)
            $p_conf->where = $where;
        if (!empty($input['title']))
            $p_conf->where .= " AND a.title like '%{$input['title']}%'";
        //店铺条件
        $p_conf->where .= " AND shop_id in ({$shop_sort_ids}) ";

        $count = 0;
        $goods_list = $page->getList($p_conf, $count);

        $goods_id_arr = array_column($goods_list, 'goods_id');
        $goods_ids = empty($goods_id_arr)?'0':implode(',',$goods_id_arr);
        //判断是否已收藏
        $collect_goods_list = [];
        if (!empty($input['uid'])) {
            $source_type = CollectGoodsSourceTypeEnum::HCITY;
            $hcityUserCollectGoodsDao = HcityUserCollectGoodsDao::i(['uid' => $input['uid']]);
            $collect_goods_list = $hcityUserCollectGoodsDao->getAllArray("uid={$input['uid']} AND source_type={$source_type} AND goods_id in ({$goods_ids})");
//            $collect_goods_list = $hcityUserCollectGoodsDao->getAllByGoodsIds($input['uid'], $goods_id_arr);
        }
        // 店铺与当前位置的距离
        foreach ($goods_list as $gk => $goods) {
            $shop = array_find($valid_shop_list, 'id', $goods['shop_id']);
            $goods_list[$gk]['distance'] = isset($shop['distance']) ? $shop['distance'] : 0;
            $goods_list[$gk]['shop_name'] = isset($shop['shop_name']) ? $shop['shop_name'] : '';

            $collect_goods = array_find($collect_goods_list, 'goods_id', $goods['goods_id']);
            $goods_list[$gk]['is_collect'] = (isset($collect_goods['status']) && $collect_goods['status'] == 0) ? 1 : 0;

            //格式图片
            $goods_list[$gk]['pic_url'] = conver_picurl($goods['pic_url']);
        }

        $data['total'] = $count;
        $data['rows'] = $goods_list;

        return $data;
    }

    /**
     * 获取商圈商品信息
     * @param int $goodsId
     * @return object
     * @throws Exception
     * @lisuha
     */
    public function detailByGoodsId(int $goodsId)
    {
        $goodsKz = HcityGoodsKzDao::i()->getOne(['goods_id' => $goodsId]);
        if (!$goodsKz) {
            return false;
        }
        $goodsKz->pic_url = conver_picurl($goodsKz->pic_url);
        $goodsKz->sku_list = HcityGoodsSkuKzDao::i()->getAllArray(['goods_id' => $goodsKz->goods_id]);
        return $goodsKz;
    }

    /**
     * 广告位商品列表
     * @param array $fdata
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getBannerGoodsList(array $fdata)
    {
        $detail = HcityActivityBannerDao::i()->getOne(['id' => $fdata['banner_id']]);
        if (!empty($detail)) {
            $detail->pic_url = conver_picurl($detail->pic_url);
            $detail->detail_pic_url = conver_picurl($detail->detail_pic_url);
        }
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_goods_kz_view']} a join {$hcityMainDb->tables['hcity_activity_banner_goods']} b using(aid,goods_id)";
        $p_conf->order = " b.sort asc";
        $p_conf->where = " b.banner_id = {$page->filter($fdata['banner_id'])} and a.hcity_status=1";
        $p_conf->fields = " a.*";
        //店铺条件
        $count = 0;
        $goods_list = $page->getList($p_conf, $count);
        foreach ($goods_list as &$val) {
            $val['distance'] = get_distance($fdata['long'], $fdata['lat'], $val['longitude'], $val['latitude']);
        }

        $goods_list = convert_client_list($goods_list, [['type' => 'img', 'field' => 'pic_url']]);
        return [
            'detail' => $detail,
            'rows' => $goods_list,
            'total' => $count
        ];
    }

    /**
     * 福利池商品上下架
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function editWelfareStatus(\s_hmanage_user_do $sUser, array $fdata)
    {
        $like = [];
        if ($sUser->type == 0) {
            $like = [
                'field' => 'region',
                'str' => $sUser->region,
                'type' => 'after',
            ];
        }
        $goods = HcityWelfareGoodsDao::i()->getOneLike($like, ['id' => $fdata['id']]);
        if (!$goods) {
            throw new Exception('福利池商品不存在');
        }
        if ($goods->welfare_status == $fdata['welfare_status']) {
            throw new Exception('状态没有变化，无需重复操作');
        }
        $status = HcityWelfareGoodsDao::i()->update(['welfare_status' => $fdata['welfare_status']], ['id' => $fdata['id']]);
        if($status){
            return ture;
        }
        else{
            return false;
        }
    }
}
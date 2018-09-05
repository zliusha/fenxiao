<?php

/**
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/7/10
 * Time: 上午11:40
 */
namespace Service\Bll\Hcity;

use Service\Support\FLock;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsAttrgroupDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsAttritemDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsSkuDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsSkuKzDao;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsApplyDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsSkuApplyDao;
use Service\DbFrame\DataBase\MainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Enum\CollectGoodsSourceTypeEnum;



class GoodsBll extends \Service\Bll\BaseBll
{

    /**
     * 添加商品
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function createGoods(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);

        $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        //开启事务
        $hcityShardDb->trans_start();
        $list['sku_attr'] = json_decode($data['sku_attr']);
        foreach ($list['sku_attr'] as $key => $row) {
            $price = $row->price;
            $info['price'][$key] = $price;
            $group_price = $row->group_price;
            $info['group_price'][$key] = $group_price;
        }
        //是否价格一致
        if (min($info['price']) == max($info['price'])) {
            $data['price'] = min($info['price']);
        } else {
            $data['price'] = min($info['price']) . "~" . max($info['price']);
        }
        if (min($info['group_price']) == max($info['group_price'])) {
            $data['group_price'] = min($info['group_price']);
        } else {
            $data['group_price'] = min($info['group_price']) . "~" . max($info['group_price']);
        }

        //新增商品
        $goodsData = [
            'aid' => $aid,
            'title' => $data['title'],
            'shop_id' => $data['shop_id'],
            'pic_url' => $data['pic_url'],
            'pic_url_list' => $data['pic_url_list'],
            'desc' => html_escape($data['desc']),
            'price' => $data['price'],
            'group_price' => $data['group_price'],
            'is_limit_open' => $data['is_limit_open'],
            'limit_num' => $data['limit_num'],
            'total_limit_num' => $data['total_limit_num'],
            // 'is_limit_open' => $data['limit_num'] == -1 ? 0 : 1,
            'goods_limit' => $data['goods_limit'],
            'is_goods_limit_open' => $data['goods_limit'] == -1 ? 0 : 1,
            'use_end_time' => strtotime($data['use_end_time']) + 86399,
            'time' => time(),
        ];
        $goodsId = $shcityGoodsDao->create($goodsData);
        //保存商品与分类关系
        $attrgroupData = [
            'aid' => $aid,
            'goods_id' => $goodsId,
            'title' => '商品规格',
            'time' => time(),
        ];
        $attrgroupId = ShcityGoodsAttrgroupDao::i(['aid' => $aid])->create($attrgroupData);
        //循环插入
        $attritemData = [];
        foreach ($list['sku_attr'] as $key => $row) {
            foreach ($row->attr_names as $attrName) {
                if (!isset($attritemData[$attrName])) {
                    //保存分类与分类详情关系
                    $attritemData[$attrName] = [
                        'aid' => $aid,
                        'group_id' => $attrgroupId,
                        'attr_name' => $attrName,
                        'goods_id' => $goodsId,
                        'time' => time(),
                    ];
                }
            }
        }
        //插入商品属性表
        ShcityGoodsAttritemDao::i(['aid' => $aid])->createBatch($attritemData);
        $attrItems = ShcityGoodsAttritemDao::i(['aid' => $aid])->getAll(['aid' => $aid, 'goods_id' => $goodsId]);
        $attrIds = array_column($attrItems, 'id', 'attr_name');
        $skuData = [];
        foreach ($list['sku_attr'] as $key => $row) {
            //保存商品与库存关系
            $attrIdStr = '';
            foreach ($row->attr_names as $attrName) {
                $attrIdStr .= isset($attrIds[$attrName]) ? $attrIds[$attrName] : '';
                $attrIdStr .= ',';
            }
            $skuData[] = [
                'aid' => $aid,
                'goods_id' => $goodsId,
                'shop_id' => $data['shop_id'],
                'attr_ids' => rtrim($attrIdStr, ','),
                'attr_names' => implode(',', $row->attr_names),
                'price' => $row->price,
                'group_price' => $row->group_price,

            ];
        }
        //插入sku表
        ShcityGoodsSkuDao::i(['aid' => $aid])->createBatch($skuData);

        if ($hcityShardDb->trans_status()) {
            $hcityShardDb->trans_complete();
            return true;
        } else {
            $hcityShardDb->trans_rollback();
            return false;
        }
    }

    /**
     * 编辑商品
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function editGoods(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);

        $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        $goodsInfo = ShcityGoodsDao::i(['aid' => $aid])->getOneArray(['id' => $data['id']], $fields = "*");
        if (!$goodsInfo['status'] == 0) {
             throw new Exception('修改商品,需要先将' . $goodsInfo['title'] . '商品从“商圈”及“一店一码”同时下架');
         }
        if ($goodsInfo['hcity_status'] == 1 ) {
            throw new Exception('修改商品,需要先将' . $goodsInfo['title'] . '商品从“商圈”及“一店一码”同时下架');
        }
        if ($goodsInfo['hcity_status'] == 2 ) {
            throw new Exception('商品正在商圈审核上架，此过程中不能删除商品');
        }
        //开启事务
        $hcityShardDb->trans_start();

        $list['sku_attr'] = json_decode($data['sku_attr']);
        foreach ($list['sku_attr'] as $key => $row) {
            $price = $row->price;
            $info['price'][$key] = $price;
            $group_price = $row->group_price;
            $info['group_price'][$key] = $group_price;
            $sku_id = $row->sku_id;
            $info['sku_id'][$key] = $sku_id;
        }
        //是否价格一致
        if (min($info['price']) == max($info['price'])) {
            $data['price'] = min($info['price']);
        } else {
            $data['price'] = min($info['price']) . "~" . max($info['price']);
        }
        if (min($info['group_price']) == max($info['group_price'])) {
            $data['group_price'] = min($info['group_price']);
        } else {
            $data['group_price'] = min($info['group_price']) . "~" . max($info['group_price']);
        }
        //编辑商品
        $goodsData = [
            'id' => $data['id'],
            'aid' => $aid,
            'title' => $data['title'],
            'shop_id' => $data['shop_id'],
            'pic_url' => $data['pic_url'],
            'pic_url_list' => $data['pic_url_list'],
            'desc' => html_escape($data['desc']),
            'price' => $data['price'],
            'group_price' => $data['group_price'],
            'is_limit_open' => $data['is_limit_open'],
            'limit_num' => $data['limit_num'],
            'total_limit_num' => $data['total_limit_num'],
            'goods_limit' => $data['goods_limit'],
            'is_goods_limit_open' => $data['goods_limit'] == -1 ? 0 : 1,
            'use_end_time' => strtotime($data['use_end_time']) + 86399 ,
            'update_time' => time(),
        ];
        $shcityGoodsDao->update($goodsData, ['id' => $data['id']]);
        //更新类别
        $attritemData = [];
        $attrItems = ShcityGoodsAttritemDao::i(['aid' => $aid])->getAll(['aid' => $aid, 'goods_id' => $data['id']]);
        $attrIds = array_column($attrItems, 'id', 'attr_name');
        //循环插入
        $attritemData = [];
        foreach ($list['sku_attr'] as $key => $row) {
            foreach ($row->attr_names as $attrName) {
                if (!isset($attrIds[$attrName])) {
                    if (!isset($attritemData[$attrName])) {
                        $attritemData[$attrName] = [
                            'aid' => $aid,
                            'group_id' => $data['attrgroup_id'],
                            'attr_name' => $attrName,
                            'goods_id' => $data['id'],
                            'time' => time()
                        ];
                    }
                }
            }
        }
        if (!empty($attritemData)) {
            ShcityGoodsAttritemDao::i(['aid' => $aid])->createBatch($attritemData);
        }
        $newattrItems = ShcityGoodsAttritemDao::i(['aid' => $aid])->getAll(['aid' => $aid, 'goods_id' => $data['id']]);
        $newattrIds = array_column($newattrItems, 'id', 'attr_name');
        //查找此次传递的属性
        foreach ($list['sku_attr'] as $key => $row) {
            foreach ($row->attr_names as $attrName) {
                $infos[$attrName] = $newattrIds[$attrName];
            }
        }
        $diffinfo = array_diff($newattrIds, $infos);
        if (!empty($diffinfo)) {
            foreach ($diffinfo as $key => $row) {
                $shcityGoodsAttritemDao = ShcityGoodsAttritemDao::i(['aid' => $aid])->delete(['id' => $row, 'goods_id' => $data['id']]);
            }
        }
        //循环删除此次不存在的类别sku
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid' => $aid])->getAll(['aid' => $aid, 'goods_id' => $data['id']]);
        $oldSku = array_column($shcityGoodsSkuDao, 'id');
        $diffSku = array_diff($oldSku, $info['sku_id']);
        if (!empty($diffSku)) {
            foreach ($diffSku as $key => $row) {
                $ShcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid' => $aid])->delete(['id' => $row, 'goods_id' => $data['id']]);
            }
        }
        foreach ($list['sku_attr'] as $key => $row) {
            $sku_id = $row->sku_id;
            //保存商品与库存关系
            $attrIdStr = '';
            foreach ($row->attr_names as $attrName) {
                $attrIdStr .= isset($newattrIds[$attrName]) ? $newattrIds[$attrName] : '';
                $attrIdStr .= ',';
            }
            if (!empty($sku_id)) {
                //保存商品与库存关系
                $skuData = [
                    'aid' => $aid,
                    'shop_id' => $data['shop_id'],
                    'attr_ids' => rtrim($attrIdStr, ','),
                    'attr_names' => implode(',', $row->attr_names),
                    'price' => $row->price,
                    'group_price' => $row->group_price,
                    'update_time' => time(),
                ];
                ShcityGoodsSkuDao::i(['aid' => $aid])->update($skuData, ['id' => $row->sku_id]);
            } else {
                //保存与分类详情关系
                $skuData = [
                    'aid' => $aid,
                    'goods_id' => $data['id'],
                    'shop_id' => $data['shop_id'],
                    'attr_ids' => rtrim($attrIdStr, ','),
                    'attr_names' => implode(',', $row->attr_names),
                    'price' => $row->price,
                    'group_price' => $row->group_price,
                ];
                ShcityGoodsSkuDao::i(['aid' => $aid])->create($skuData);
            }
        }
        if ($hcityShardDb->trans_status()) {
            $hcityShardDb->trans_complete();
            return true;
        } else {
            $hcityShardDb->trans_rollback();
            return false;
        }
    }

    /**
     * 删除商品
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function checkedDeleteGoods(int $aid, array $data)
    {
        $idList = explode(',', $data['id']);
        $options = [
            'where' => ['aid' => $aid],
            'where_in' => ['id' => $idList]
        ];
        $goodsList = ShcityGoodsDao::i(['aid' => $aid])->getAllExt($options);
        foreach ($goodsList as $val) {
            if (!$val->status == 0) {
                throw new Exception('删除商品,需要先将' . $val->title . '商品从“商圈”及“一店一码”同时下架');
            }
            if ($val->hcity_status == 1) {
                throw new Exception('删除商品,需要先将' . $val->title . '商品从“商圈”及“一店一码”同时下架');
            }
            if ($val->hcity_status == 2) {
                throw new Exception('删除商品,' . $val->title . '正在商圈审核上架，此过程中不能删除商品');
            }
        }
        return ShcityGoodsDao::i(['aid' => $aid])->updateExt(['is_delete' => 1], $options);
    }

    /**
     * 修改一店一码上下架
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function editStatus(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        //开启事务
        $hcityShardDb->trans_start();
        //编辑商品
        $shcityGoodsDao->update(['status' => $data['status'],'update_time'=>time()], ['id' => $data['id']]);
        //编辑商品与库存关系
        if (!empty($data['stock_num']) && !empty($data['sku_id'])) {
            $skuData = [
                'stock_num' => $data['stock_num'],
                'is_stock_open' => $data['stock_num'] == -1 ? 0 : 1,
            ];
            ShcityGoodsSkuDao::i(['aid' => $aid])->update($skuData, ['id' => $data['sku_id']]);
        }
        if ($hcityShardDb->trans_status()) {
            $hcityShardDb->trans_complete();
            return true;
        } else {
            $hcityShardDb->trans_rollback();
            return false;
        }

    }

    /**
     * 获取商品查找列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function goodsSearch(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $mainDb = MainDb::i(['aid' => $aid]);

        $page = new PageList($hcityShardDb);
        $p_conf = $page->getConfig();
        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityShardDb->tables['shcity_goods']} a left join {$hcityShardDb->tables['shcity_goods_sku']} b on a.id=b.goods_id ";
        $p_conf->where = "a.aid = {$aid} and a.is_delete = 0 ";
        //增加筛选条件
        if (!empty($data['shop_id'])) {
            $p_conf->where .= " and a.shop_id={$page->filter($data['shop_id'])}";
        } else{
            //$sasId = MainShopRefitemDao::i(['aid' => $aid])->getAllArray(['saas_id'=>$data['saas_id']],'shop_id'); 
            //$p_conf->where .= " and a.shop_id in (".implode(',',array_column($sasId,'shop_id')).")";
            if($data['saas_id'] == 2){
                $shopId = HcityShopExtDao::i()->getAllArray(['barcode_status' => 1,'aid'=> $aid, 'barcode_expire_time >' => time()],'shop_id');
                if($shopId){
                $p_conf->where .= " and a.shop_id in (".implode(',',array_column($shopId,'shop_id')).")";
                }
            }
            if($data['saas_id'] == 3){
                //$shopId = HcityShopExtDao::i(['aid' => $aid])->getAllArray(['hcity_show_status' => 1],'shop_id');
                $shopId = HcityShopExtDao::i()->getEntitysByAR(["where" =>['aid' => $aid],'where_in' => ['hcity_show_status' => [1,2]]]);
                if($shopId){
                $p_conf->where .= " and a.shop_id in (".implode(',',array_column($shopId,'shop_id')).")";
                }
            }
        }

        if (isset($data['status']) && is_numeric($data['status'])) {
            $p_conf->where .= " and a.status={$page->filter($data['status'])}";
        }
        if (isset($data['hcity_status']) && is_numeric($data['hcity_status'])) {
            $p_conf->where .= " and a.hcity_status={$page->filter($data['hcity_status'])} ";
        }
        if (!empty($data['title'])) {
            $p_conf->where .= " and a.title like '%".$page->filterLike($data['title'])."%'";
        }
        $p_conf->fields = 'a.*,b.id as skuid,b.price,b.attr_ids,b.attr_names,b.sales_num,b.is_stock_open,b.stock_num,b.free_num,b.hcity_stock_num,b.hcard_price';
        $p_conf->order = 'a.id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        //更换图片路径
        foreach($rows['rows'] as $key=>$value ){
              $rows['rows'][$key]['pic_url'] = conver_picurl($rows['rows'][$key]['pic_url']); 
              $pic_url_list = explode(',',$rows['rows'][$key]['pic_url_list']);
              foreach($pic_url_list as $row=>$value){
                $pic_url_list[$row] = conver_picurl($value); 
              }
              $rows['rows'][$key]['pic_url_list'] = implode(',',$pic_url_list);
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 获取商品详情列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function goodsDetail(int $aid, array $data)
    {
        $rows['goodsinfo'] = ShcityGoodsDao::i(['aid' => $aid])->getOneArray(['id' => $data['id']], $fields = "*");
        //更换商品图片路径
        $rows['goodsinfo']['pic_url'] = conver_picurl($rows['goodsinfo']['pic_url']);
        $pic_url_list = explode(',',$rows['goodsinfo']['pic_url_list']);
        foreach($pic_url_list as $row=>$value){
            $pic_url_list[$row] = conver_picurl($value); 
        }
        $rows['goodsinfo']['pic_url_list'] = implode(',',$pic_url_list);
        $rows['goodsinfo']['desc'] = htmlspecialchars_decode($rows['goodsinfo']['desc']);
        $rows['attrgroupinfo'] = ShcityGoodsAttrgroupDao::i(['aid' => $aid])->getOneArray(['goods_id' => $data['id']], $fields = "id as attrgroup_id ");
        $rows['skuinfo'] = ShcityGoodsSkuDao::i(['aid' => $aid])->getOneArray(['goods_id' => $data['id']], $fields = "*");
        return $rows;
    }

    /**
     * 申请商圈
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function applyBusiness(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        //开启事务
        $hcityShardDb->trans_start();
        //编辑商品
        $goodsData = [
            'hcity_status' => ShcityGoodsDao::HCITY_STATYS_AUDIT,
            'show_begin_time' => strtotime($data['show_begin_time']),
            'show_end_time' => strtotime($data['show_end_time']) + 86399,
            // 'commission_rate' => $data['commission_rate'],
            // 'expect_income' => $data['expect_income'],
            'cost_price' => $data['cost_price'],
        ];
        $shcityGoodsDao->update($goodsData, ['id' => $data['id']]);
        //编辑商品与库存关系
        $skuData = [
            'hcity_stock_num' => $data['hcity_stock_num'],
            'free_num' => (int)$data['free_num'],
            'is_hcity_stock_open' => $data['hcity_stock_num'] == -1 ? 0 : 1,
        ];
        ShcityGoodsSkuDao::i()->update($skuData, ['id' => $data['sku_id']]);
        $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        $where = ['id' => $data['id']];
        $fields = "*";
        $goodsInfo = $shcityGoodsDao->getOne($where, $fields);
        //查找sku信息
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid' => $aid]);
        $where = ['id' => $data['sku_id']];
        $fields = "*";
        $goodsSkuInfo = $shcityGoodsSkuDao->getOne($where, $fields);
        //添加商圈申请表
        $hcityGoodsApplyDao = HcityGoodsApplyDao::i(['aid' => $aid]);
        //是否申请过加入商圈
        $goodsApplyInfo = $hcityGoodsApplyDao->getOne(['goods_id' => $data['id']]);
        if (!$goodsApplyInfo) {
            //新增商品申请表
            $goodsApplyData = [
                'aid' => $aid,
                'shop_id' => $goodsInfo->shop_id,
                'goods_id' => $goodsInfo->id,
                'title' => $goodsInfo->title,
                'pic_url' => $goodsInfo->pic_url,
                'pic_url_list' => $goodsInfo->pic_url_list,
                'price' => $goodsInfo->price,
                'group_price' => $goodsInfo->group_price,
                //'commission_rate' => $goodsInfo->commission_rate,
                'cost_price' => $goodsInfo->cost_price,
                'desc' => $goodsInfo->desc,
                'free_num' => $data['free_num'],
                'is_limit_open' => (int)$goodsInfo->is_limit_open,
                'limit_num' => $goodsInfo->limit_num,
                'total_limit_num' => $goodsInfo->total_limit_num,
                'goods_limit' => $goodsInfo->goods_limit,
                'is_goods_limit_open' => (int)$goodsInfo->is_goods_limit_open,
                'is_hcity_stock_open' => (int)$data['hcity_stock_num'] == -1 ? 0 : 1,
                'hcity_stock_num' => $data['hcity_stock_num'],
                'show_begin_time' => $goodsInfo->show_begin_time,
                'show_end_time' => $goodsInfo->show_end_time,
                'use_end_time' => $goodsInfo->use_end_time,
                'audit_status' => 1,
            ];
            $goodsApplyId = $hcityGoodsApplyDao->create($goodsApplyData);
            //新增库存申请表
            $goodsSkuData = [
                'aid' => $aid,
                'shop_id' => $goodsInfo->shop_id,
                'sku_id' => $goodsSkuInfo->id,
                'goods_id' => $goodsInfo->id,
                'apply_id' => $goodsApplyId,
                'attr_ids' => $goodsSkuInfo->attr_ids,
                'attr_names' => $goodsSkuInfo->attr_names,
                'price' => $goodsInfo->price,
                'group_price' => $goodsInfo->group_price,
                'free_num' => $data['free_num'],
                'hcity_stock_num' => $data['hcity_stock_num'],
                'is_hcity_stock_open' => $data['hcity_stock_num'] == -1 ? 0 : 1,
            ];
            $goodsSkuApplyId = HcityGoodsSkuApplyDao::i(['aid' => $aid])->create($goodsSkuData);
        } else {
            //更新商品申请表
            $goodsApplyData = [
                'aid' => $aid,
                'shop_id' => $goodsInfo->shop_id,
                'goods_id' => $goodsInfo->id,
                'title' => $goodsInfo->title,
                'pic_url' => $goodsInfo->pic_url,
                'pic_url_list' => $goodsInfo->pic_url_list,
                'price' => $goodsInfo->price,
                'group_price' => $goodsInfo->group_price,
                //'commission_rate' => $goodsInfo->commission_rate,
                'cost_price' => $goodsInfo->cost_price,
                'desc' => $goodsInfo->desc,
                'free_num' => $data['free_num'],
                'is_limit_open' => (int)$goodsInfo->is_limit_open,
                'limit_num' => $goodsInfo->limit_num,
                'total_limit_num' => $goodsInfo->total_limit_num,
                'goods_limit' => $goodsInfo->goods_limit,
                'is_goods_limit_open' => (int)$goodsInfo->is_goods_limit_open,
                'is_hcity_stock_open' => (int)$data['hcity_stock_num'] == -1 ? 0 : 1,
                'hcity_stock_num' => $data['hcity_stock_num'],
                'show_begin_time' => $goodsInfo->show_begin_time,
                'show_end_time' => $goodsInfo->show_end_time,
                'use_end_time' => $goodsInfo->use_end_time,
                'audit_status' => 1,
                'time' => time(),
                'refuse_remark' => "",
            ];
            $hcityGoodsApplyDao->update($goodsApplyData, ['id' => $goodsApplyInfo->id]);
            $goodsSkuData = [
                'aid' => $aid,
                'shop_id' => $goodsInfo->shop_id,
                'sku_id' => $goodsSkuInfo->id,
                'goods_id' => $goodsInfo->id,
                'attr_ids' => $goodsSkuInfo->attr_ids,
                'attr_names' => $goodsSkuInfo->attr_names,
                'price' => $goodsInfo->price,
                'group_price' => $goodsInfo->group_price,
                'free_num' => $data['free_num'],
                'hcity_stock_num' => $data['hcity_stock_num'],
                'is_hcity_stock_open' => $data['hcity_stock_num'] == -1 ? 0 : 1,
            ];
            $hcityGoodsSkuApplyDao = HcityGoodsSkuApplyDao::i(['aid' => $aid])->update($goodsSkuData, ['apply_id' => $goodsApplyInfo->id]);
        }
        if ($hcityShardDb->trans_status()) {
            $hcityShardDb->trans_complete();
            return true;
        } else {
            $hcityShardDb->trans_rollback();
            return false;
        }
    }


    /**
     * 过滤出商圈失效的商品id
     * @param array $goodsIds
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function filterInvalidGoodsIds(array $goodsIds = [])
    {
        $options = [
            'where_in' => ['goods_id' => $goodsIds]
        ];
        $goodsKz = HcityGoodsKzDao::i()->getAllExt($options);
        $invalidShopGoodsIds = [];
        if (empty($goodsKz)) {
            //全部失效
            $invalidGoodsIds = $goodsIds;
        } else {
            $invalidGoodsIds = array_diff($goodsIds, array_column($goodsKz, 'goods_id'));
            foreach ($goodsKz as $kz) {
                if (in_array($kz->goods_id, $goodsIds) && $kz->show_end_time <= time() && $kz->hcity_status != 1) {
                    $invalidShopGoodsIds[$kz->shop_id][] = $kz->goods_id;
                }
            }
        }
        return ['invalidGoodsIds' => $invalidGoodsIds, 'invalidShopGoodsIds' => $invalidShopGoodsIds];
    }


    /**
     * 修改商品文字和宣传图
     * @param int $aid
     * @param array $data
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function editPoster(int $aid, array $data)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);
        $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid]);
        //开启事务
        $hcityShardDb->trans_start();
        //编辑商品
        $shcityGoodsDao->update(['poster_url' => $data['poster_url'],'poster_title' => $data['poster_title']], ['id' => $data['id']]);
        if ($hcityShardDb->trans_status()) {
            $hcityShardDb->trans_complete();
            return true;
        } else {
            $hcityShardDb->trans_rollback();
            return false;
        }

    }

    /**
     * 获取福利池查找列表
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function freeSearch(int $aid, array $data)
    {
        $hcityMainDb = HcityMainDb::i(['aid' => $aid]);

        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityMainDb->tables['hcity_welfare_goods']} a left join {$hcityMainDb->tables['hcity_welfare_goods_sku']} b on a.id=b.goods_id ";
        $p_conf->where = "a.aid = {$aid} and a.is_delete = 0 ";
        //增加筛选条件
        if (!empty($data['shop_id'])) {
            $p_conf->where .= " and a.shop_id={$page->filter($data['shop_id'])}";
        } else{
            if($data['saas_id'] == 2){
                $shopId = HcityShopExtDao::i()->getAllArray(['barcode_status' => 1,'aid'=> $aid, 'barcode_expire_time >' => time()],'shop_id');
                if($shopId){
                $p_conf->where .= " and a.shop_id in (".implode(',',array_column($shopId,'shop_id')).")";
                }
            }
            if($data['saas_id'] == 3){
                $shopId = HcityShopExtDao::i()->getEntitysByAR(["where" =>['aid' => $aid],'where_in' => ['hcity_show_status' => [1,2]]]);
                if($shopId){
                $p_conf->where .= " and a.shop_id in (".implode(',',array_column($shopId,'shop_id')).")";
                }
            }
        }

        if (isset($data['welfare_status']) && is_numeric($data['welfare_status'])) {
            $p_conf->where .= " and a.welfare_status={$page->filter($data['welfare_status'])}";
        }
        if (!empty($data['title'])) {
            $p_conf->where .= " and a.title like '%".$page->filterLike($data['title'])."%'";
        }
        $p_conf->fields = 'a.*,b.id as skuid,b.price,b.attr_ids,b.attr_names,b.sales_num,b.free_num';
        $p_conf->order = 'a.id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        //更换图片路径
        foreach($rows['rows'] as $key=>$value ){
              $rows['rows'][$key]['pic_url'] = conver_picurl($rows['rows'][$key]['pic_url']); 
              $pic_url_list = explode(',',$rows['rows'][$key]['pic_url_list']);
              foreach($pic_url_list as $row=>$value){
                $pic_url_list[$row] = conver_picurl($value); 
              }
              $rows['rows'][$key]['pic_url_list'] = implode(',',$pic_url_list);
        }
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
     * yDYM 商品列表
     * @param int $aid
     * @param array $input
     * @return array
     * @user liusha
     * @date 2018/9/4 15:33
     */
    public function goodsYdymList(int $aid, array $input)
    {
        $hcityShardDb = HcityShardDb::i(['aid' => $aid]);

        $page = new PageList($hcityShardDb);
        $p_conf = $page->getConfig();
        //查找符合条件的shop_id;
        $p_conf->table = "{$hcityShardDb->tables['shcity_goods']} a left join {$hcityShardDb->tables['shcity_goods_sku']} b on a.id=b.goods_id ";
        $p_conf->where = "a.aid = {$aid} and a.is_delete = 0 ";
        //增加筛选条件
        if (!empty($input['shop_id']) &&  is_numeric($input['shop_id']))
        {
            $p_conf->where .= " and a.shop_id={$input['shop_id']}";
        }

        if (isset($input['status']) && is_numeric($input['status']))
        {
            $p_conf->where .= " and a.status={$input['status']}";
        }

        if (!empty($input['title'])) {
            $p_conf->where .= " and a.title like '%".$page->filterLike($input['title'])."%'";
        }
        $p_conf->fields = 'a.*,b.id as skuid,b.price,b.attr_ids,b.attr_names,b.sales_num,b.is_stock_open,b.stock_num,b.free_num,b.hcity_stock_num,b.hcard_price';
        $p_conf->order = 'a.id desc';
        $count = 0;
        $data['rows'] = $page->getList($p_conf, $count);


        // 处理ydym内商圈商品展示
        $curr_time = time();
        $goods_ids = array_column($data['rows'], 'id');
        $goods_ids = empty($goods_ids) ? '0' : implode(',', $goods_ids);
        $list = HcityGoodsKzDao::i()->getAllArray("goods_id in ($goods_ids)");

        $collect_goods_list = [];
        if (!empty($input['uid']) && $input['uid']>0) {
            $source_type = CollectGoodsSourceTypeEnum::YDYM;
            $hcityUserCollectGoodsDao = HcityUserCollectGoodsDao::i(['uid' => $input['uid']]);
            $collect_goods_list = $hcityUserCollectGoodsDao->getAllArray("uid={$input['uid']} AND source_type={$source_type} AND goods_id in ({$goods_ids})");
        }
        //其他处理
        foreach($data['rows'] as $key => $row )
        {
            $data['rows'][$key]['pic_url'] = conver_picurl($row['pic_url']);

            $data['rows'][$key]['is_hcity_goods'] = 0;
            $goodsKz = array_find($list, 'goods_id', $row['id']);
            if($row['hcity_status']==1 && $row['show_begin_time']<=$curr_time && $row['show_end_time']>$curr_time)
            {
                // 不限库存 或 有效库存大于0
                if($goodsKz['is_hcity_stock_open']==0 || ($goodsKz['is_hcity_stock_open']==1 && $goodsKz['hcity_stock_num']>0))
                {
                    // 标记商圈商品状态
                    $data['rows'][$key]['is_hcity_goods'] = 1;
                }
            }
            // 是否收藏
            $collect_goods = array_find($collect_goods_list, 'goods_id', $row['id']);
            $data['rows'][$key]['is_collect'] = isset($collect_goods['status']) ? 1 : 0;

        }
        $data['total'] = $count;
        return $data;
    }
}
<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/16
 * Time: 下午8:07
 */

namespace Service\Bll\Hcity;


use Service\Bll\BaseBll;
use Service\Bll\Hcity\Xcx\XcxBll;
use Service\Cache\Hcity\HcityUserCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Enum\CollectGoodsSourceTypeEnum;
use Service\Enum\XcxTemplateMessageEnum;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\Support\YdbArray;


class CollectBll extends BaseBll
{
    //收藏商品后触发方法
    const EVENT_AFTER_COLLECT_GOODS = 'afterCollectGoods';
    //取消收藏商品后触发方法
    const EVENT_AFTER_CANCEL_COLLECT_GOODS = 'afterCancelCollectGoods';

    //收藏店铺后触发方法
    const EVENT_AFTER_COLLECT_SHOP = 'afterCollectShop';
    //取消收藏店铺后触发方法
    const EVENT_AFTER_CANCEL_COLLECT_SHOP = 'afterCancelCollectShop';

    /**
     * 收藏商品
     * @param int $uid
     * @param array $params aid,shop_id,goods_id,source_type
     * @return bool
     * @author ahe<ahe@iyenei.com>
     */
    public function collectGoods(int $uid, array $params)
    {
        $createData = [
            'aid' => $params['aid'],
            'shop_id' => $params['shop_id'],
            'goods_id' => $params['goods_id'],
            'uid' => $uid,
            'source_type' => $params['source_type']
        ];
        $collectGoodsDao = HcityUserCollectGoodsDao::i(['uid' => $uid]);
        $info = $collectGoodsDao->getOne($createData);
        if (!empty($info)) {
            throw new Exception('重复收藏');
        }
        $mainDb = $collectGoodsDao->db;
        $mainDb->trans_start();
        $collectGoodsDao->create($createData);
        //收藏商品后触发方法
        $this->trigger(self::EVENT_AFTER_COLLECT_GOODS, $createData);
        if ($mainDb->trans_status()) {
            $mainDb->trans_complete();
            return true;
        } else {
            $mainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 收藏商品后触发方法
     * @param array $params
     * @author ahe<ahe@iyenei.com>
     */
    protected function afterCollectGoods(array $params)
    {
        //更新收藏量
        $options = [
            'id' => $params['goods_id'],
            'aid' => $params['aid'],
            'shop_id' => $params['shop_id']
        ];
        ShcityGoodsDao::i(['aid' => $params['aid']])->setInc('collect_num', 1, ['where' => $options]);
        //更新快照
        $info = ShcityGoodsDao::i(['aid' => $params['aid']])->getOne($options, 'collect_num,hcity_status');
        if (!empty($info) && $info->hcity_status == 1) {
            $zkOptions['where'] = [
                'goods_id' => $params['goods_id'],
                'aid' => $params['aid'],
                'shop_id' => $params['shop_id'],
            ];
            HcityGoodsKzDao::i()->updateExt(['collect_num' => $info->collect_num], $zkOptions);
        }
        //保存门店粉丝
        (new ShopFansBll())->addOrUpdateShopFans($params['uid'], $params['aid'], $params['shop_id']);
    }

    /**
     * 取消收藏商品
     * @param int $uid
     * @param array $params aid,shop_id,goods_id,source_type
     * @return bool
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function cancelCollectGoods(int $uid, array $params)
    {
        $createData = [
            'aid' => $params['aid'],
            'shop_id' => $params['shop_id'],
            'goods_id' => $params['goods_id'],
            'uid' => $uid,
            'source_type' => $params['source_type']
        ];
        $collectGoodsDao = HcityUserCollectGoodsDao::i(['uid' => $uid]);
        $info = $collectGoodsDao->getOne($createData);
        if (empty($info)) {
            throw new Exception('商品未被收藏');
        }
        $mainDb = $collectGoodsDao->db;
        $mainDb->trans_start();
        $collectGoodsDao->delete(['id' => $info->id]);
        //取消收藏商品后触发方法
        $this->trigger(self::EVENT_AFTER_CANCEL_COLLECT_GOODS, $createData);
        if ($mainDb->trans_status()) {
            $mainDb->trans_complete();
            return true;
        } else {
            $mainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 取消收藏商品后触发
     * @param array $params
     * @author ahe<ahe@iyenei.com>
     */
    protected function afterCancelCollectGoods(array $params)
    {
        //更新收藏量
        $options = [
            'id' => $params['goods_id'],
            'aid' => $params['aid'],
            'shop_id' => $params['shop_id'],
        ];
        ShcityGoodsDao::i(['aid' => $params['aid']])->setDec('collect_num', 1, ['where' => $options]);
        //更新快照
        $info = ShcityGoodsDao::i(['aid' => $params['aid']])->getOne($options, 'collect_num');
        if (!empty($info) && $info->hcity_status == 1) {
            $zkOptions['where'] = [
                'goods_id' => $params['goods_id'],
                'aid' => $params['aid'],
                'shop_id' => $params['shop_id'],
            ];
            HcityGoodsKzDao::i()->updateExt(['collect_num' => $info->collect_num], $zkOptions);
        }
        //保存门店粉丝
        (new ShopFansBll())->addOrUpdateShopFans($params['uid'], $params['aid'], $params['shop_id']);
    }

    /**
     * 收藏店铺
     * @param int $uid
     * @param array $params
     * @return bool
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function collectShop(int $uid, array $params)
    {
        $createData = [
            'aid' => $params['aid'],
            'shop_id' => $params['shop_id'],
            'uid' => $uid
        ];
        $collectShopDao = HcityUserCollectShopDao::i(['uid' => $uid]);
        $info = $collectShopDao->getOne($createData);
        if (!empty($info)) {
            throw new Exception('重复收藏');
        }
        $mainDb = $collectShopDao->db;
        $mainDb->trans_start();
        $collectShopDao->create($createData);
        //收藏商品后触发方法
        $this->trigger(self::EVENT_AFTER_COLLECT_SHOP, $createData);
        if ($mainDb->trans_status()) {
            $mainDb->trans_complete();
            return true;
        } else {
            $mainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 收藏店铺后触发
     * @param array $params
     * @author ahe<ahe@iyenei.com>
     */
    protected function afterCollectShop(array $params)
    {
        //更新收藏量
        $options['where'] = [
            'aid' => $params['aid'],
            'shop_id' => $params['shop_id'],
        ];
        HcityShopExtDao::i()->setInc('collect_num', 1, $options);
        //投递小程序消息通知任务
        $user = HcityUserDao::i()->getOne(['aid' => $params['aid']], 'id');
        $collectUser = HcityUserDao::i()->getOne(['id' => $params['uid']], 'username');
        if (!empty($user) && !empty($collectUser)) {
            $userWx = HcityUserWxbindDao::i()->getOne(['uid' => $user->id]);
            if (!empty($userWx)) {
                $shop = MainShopDao::i()->getOne(['id' => $params['shop_id']]);
                $paramsXcx = [
                    'openid' => $userWx->open_id,
                    'shop_name' => $shop->shop_name,
                    'collect_time' => date('Y-m-d H:i:s', time()),
                    'collect_username' => $collectUser->username,
                ];
                (new XcxBll())->pushMessageFac($paramsXcx, XcxTemplateMessageEnum::COLLECT_SUCCESS);
            }
        }
        //保存门店粉丝
        (new ShopFansBll())->addOrUpdateShopFans($params['uid'], $params['aid'], $params['shop_id']);
    }

    /**
     * 取消收藏店铺
     * @param int $uid
     * @param array $params
     * @return bool
     * @throws Exception
     * @author ahe<ahe@iyenei.com>
     */
    public function cancelCollectShop(int $uid, array $params)
    {
        $createData = [
            'aid' => $params['aid'],
            'shop_id' => $params['shop_id'],
            'uid' => $uid
        ];
        $collectShopDao = HcityUserCollectShopDao::i(['uid' => $uid]);
        $info = $collectShopDao->getOne($createData);
        if (empty($info)) {
            throw new Exception('店铺未被收藏');
        }
        $mainDb = $collectShopDao->db;
        $mainDb->trans_start();
        $collectShopDao->delete(['id' => $info->id]);
        //取消收藏商品后触发方法
        $this->trigger(self::EVENT_AFTER_CANCEL_COLLECT_SHOP, $createData);
        if ($mainDb->trans_status()) {
            $mainDb->trans_complete();
            return true;
        } else {
            $mainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 取消收藏店铺后触发
     * @param array $params
     * @author ahe<ahe@iyenei.com>
     */
    public function afterCancelCollectShop(array $params)
    {
        //更新收藏量
        $options['where'] = [
            'aid' => $params['aid'],
            'shop_id' => $params['shop_id'],
        ];
        HcityShopExtDao::i()->setDec('collect_num', 1, $options);
        //保存门店粉丝
        (new ShopFansBll())->addOrUpdateShopFans($params['uid'], $params['aid'], $params['shop_id']);
    }

    /**
     * 获取收藏店铺列表
     * @param int $uid
     * @param array $params
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getCollectShopList(int $uid, array $params)
    {
        $count = 0;
        //店铺
        $hcityMainDb = HcityMainDb::i(['uid' => $uid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_user_collect_shop']}";
        $p_conf->where = ' uid =' . $uid;
        $p_conf->order_by = 'time desc';
        $rows['list'] = $page->getList($p_conf, $count);
        if (!empty($rows['list'])) {
            //过滤无效店铺
            $validLogs = array_filter($rows['list'], function ($item) {
                return $item['status'] == 0;
            });
            $validShopIds = array_column($validLogs, 'shop_id');
            $invalidShopIds = (new ShopBll())->filterInvalidShopIds($validShopIds);
            if (!empty($invalidShopIds)) {
                $options = [
                    'where' => ['uid' => $uid],
                    'where_in' => ['shop_id' => $invalidShopIds]
                ];
                HcityUserCollectShopDao::i(['uid' => $uid])->updateExt(['status' => 1], $options);
            }
            //拼装数据
            $shopIds = array_column($rows['list'], 'shop_id');
            $shopExt = HcityShopExtDao::i()->getAllExt(['where_in' => ['shop_id' => $shopIds]]);
            $mainShops = MainShopDao::i()->getAllExt(['where_in' => ['id' => $shopIds]]);
            $shopArray = new YdbArray($shopExt);
            $shopArray->reIndex('default', ['shop_id']);
            $mainShopsArr = new YdbArray($mainShops);
            $mainShopsArr->reIndex('default', ['id']);
            foreach ($rows['list'] as &$val) {
                if (in_array($val, $invalidShopIds)) {
                    $val['status'] = 1;//设置失效
                }
                $shopExtOne = $shopArray->getOne('default', $val['shop_id']);
                $val['collect_num'] = $shopExtOne ? $shopExtOne->collect_num : 0;
                $val['category_name_list'] = $shopExtOne ? $shopExtOne->category_name_list : 0;

                $shopMainOne = $mainShopsArr->getOne('default', $val['shop_id']);
                $val['shop_name'] = $shopMainOne ? $shopMainOne->shop_name : '';
                $val['shop_logo'] = $shopMainOne ? conver_picurl($shopMainOne->shop_logo) : '';
                $val['distance'] = $shopMainOne ? get_distance($params['long'], $params['lat'], $shopMainOne->longitude, $shopMainOne->latitude) : 0;

                //若店铺已入驻商圈，则打标商圈，否则打标一店一码
                $val['source_type'] = $shopExtOne && $shopExtOne->hcity_show_status == 1 ? CollectGoodsSourceTypeEnum::HCITY : CollectGoodsSourceTypeEnum::YDYM;
            }
        }

        $rows['count'] = $count;
        return $rows;
    }

    /**
     * 获取收藏商品列表
     * @param int $uid
     * @param array $params
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getCollectGoodsList(int $uid, array $params)
    {
        $allList = HcityUserCollectGoodsDao::i()->getAllArray(['uid' => $uid, 'status' => 0]);
        if (!empty($allList)) {
            //刷新失效商品
            $this->_refreshInvalidGoods($uid, $allList);
        }
        $count = 0;
        //店铺
        $hcityMainDb = HcityMainDb::i(['uid' => $uid]);
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_user_collect_goods']}";
        $p_conf->where = ' uid =' . $uid;
        $p_conf->order_by = 'time desc';
        $rows['list'] = $page->getList($p_conf, $count);
        if (!empty($rows['list'])) {
            //获取商品
            $collectArray = new YdbArray($rows['list']);
            $collectArray->reIndex('default', ['aid']);
            $aidArray = $collectArray->redoWithKey('default');
            $goodsList = [];
            foreach ($aidArray as $aid => $item) {
                $goodsIds = array_unique(array_column($item, 'goods_id'));
                $goods = ShcityGoodsDao::i(['aid' => $aid])->getAllExt(['where' => ['aid' => $aid], 'where_in' => ['id' => $goodsIds]]);
                $goodsList = array_merge($goodsList, $goods);
            }
            //重组商品数组数据
            $goodsArray = new YdbArray($goodsList);
            $goodsArray->reIndex('default', ['aid', 'shop_id', 'id']);

            //重组店铺数组数据
            $shopIds = array_unique(array_column($rows['list'], 'shop_id'));
            $mainShops = MainShopDao::i()->getAllExt(['where_in' => ['id' => $shopIds]]);
            $mainShopArray = new YdbArray($mainShops);
            $mainShopArray->reIndex('default', ['id']);
            foreach ($rows['list'] as &$row) {
                $goodsOne = $goodsArray->getOne('default', $row['aid'], $row['shop_id'], $row['goods_id']);
                $row['title'] = $goodsOne ? $goodsOne->title : '';
                $row['pic_url'] = $goodsOne ? conver_picurl($goodsOne->pic_url) : '';
                $row['price'] = $goodsOne ? $goodsOne->price : '';
                $row['group_price'] = $goodsOne ? $goodsOne->group_price : '';
                $row['hcard_price'] = $goodsOne ? $goodsOne->hcard_price : '';
                $row['sales_num'] = $goodsOne ? $goodsOne->sales_num : 0;
                $row['collect_num'] = $goodsOne ? $goodsOne->collect_num : 0;

                $mainShopOne = $mainShopArray->getOne('default', $row['shop_id']);
                $row['shop_name'] = $mainShopOne ? $mainShopOne->shop_name : '';
                $row['distance'] = $mainShopOne ? get_distance($params['long'], $params['lat'], $mainShopOne->longitude, $mainShopOne->latitude) : '';
            }

        }

        $rows['count'] = $count;
        return $rows;
    }

    /**
     * 刷新失效商品
     * @param int $uid
     * @param array $list
     * @author ahe<ahe@iyenei.com>
     */
    private function _refreshInvalidGoods(int $uid, array $list)
    {
        $hcityUserCollectGoodsDao = HcityUserCollectGoodsDao::i(['uid' => $uid]);
        //过滤无效店铺
        $validLogs = array_filter($list, function ($item) {
            return $item['status'] == 0;
        });
        $validShopIds = array_column($validLogs, 'shop_id');
        $invalidShopIds = (new ShopBll())->filterInvalidShopIds($validShopIds);
        if (!empty($invalidShopIds)) {
            $options = [
                'where' => ['uid' => $uid],
                'where_in' => ['shop_id' => $invalidShopIds]
            ];
            $hcityUserCollectGoodsDao->updateExt(['status' => 1], $options);
        }

//        $tArray = new YdbArray($list);
//        $tArray->reIndex('default', ['shop_id', 'goods_id']);
//        //取失效商品ID
//        $goodsIds = array_column($validLogs, 'goods_id');
//        if (!empty($goodsIds)) {
//            $invalidGoodsArr = (new GoodsBll())->filterInvalidGoodsIds($goodsIds);
//            if (!empty($invalidGoodsArr['invalidGoodsIds'])) {
//                $options = [
//                    'where' => ['uid' => $uid],
//                    'where_in' => ['goods_id' => $invalidGoodsArr['invalidGoodsIds']]
//                ];
//                $hcityUserCollectGoodsDao->updateExt(['status' => 1], $options);
//            }
//            if (!empty($invalidGoodsArr['invalidShopGoodsIds'])) {
//                $updateList = [];
//                foreach ($invalidGoodsArr['invalidShopGoodsIds'] as $shop_id => $goods) {
//                    if (in_array($shop_id, $validShopIds)) {
//                        foreach ($goods as $goodsId) {
//                            $collectOne = $tArray->getOne('default', $shop_id, $goodsId);
//                            if ($collectOne) {
//                                $updateList[] = [
//                                    'id' => $collectOne['id'],
//                                    'status' => 1
//                                ];
//                            }
//                        }
//                    }
//                }
//                $hcityUserCollectGoodsDao->updateBatch($updateList, 'id');
//            }
//        }

    }
}
<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午5:30
 */
namespace Service\Bll\Hcity;

use Service\Cache\Hcity\HcityShopExtCache;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityHomepageShopCategoryDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopCategoryDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopCategoryRelDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\MainDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\Enum\SaasEnum;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\Bll\Hcity\Xcx\XcxBll;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityXcxQrDao;

class ShopBll extends \Service\Bll\BaseBll
{
    /**
     * 获取所有门店分类
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getAllShopCategory()
    {
        $list = HcityShopCategoryDao::i()->getAll(['is_delete' => 0], '*', 'sort asc');
        foreach ($list as &$val) {
            $val->img = conver_picurl($val->img);
        }
        return $list;
    }

    /**
     * 创建店铺分类
     * @param string $categoryName
     * @param string $img
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function createShopCategory(string $categoryName, string $img)
    {
        $info = HcityShopCategoryDao::i()->selectMax('sort', ['is_delete' => 0]);
        $data = [
            'name' => $categoryName,
            'img' => $img,
            'sort' => empty($info) ? 1 : $info->sort + 1
        ];
        return HcityShopCategoryDao::i()->create($data);
    }

    /**
     * 删除门店分类
     * @param int $categoryId
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function deleteShopCategory(int $categoryId)
    {
        $homepage = HcityHomepageShopCategoryDao::i()->getOne(['category_id' => $categoryId]);
        if (!empty($homepage)) {
            throw new Exception('当前分类已被设置首页推荐，禁止删除。');
        }
        $rel = HcityShopCategoryRelDao::i()->getOne(['category_id' => $categoryId]);
        if (!empty($rel)) {
            throw new Exception('当前分类下存在店铺，禁止删除。');
        }
        return HcityShopCategoryDao::i()->update(['is_delete' => 1], ['id' => $categoryId]);
    }


    /**
     * 编辑门店分类
     * @param array $params
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function editShopCategory(array $params)
    {
        $data = [];
        if (!empty($params['category_name'])) $data['name'] = $params['category_name'];
        if (!empty($params['img'])) $data['img'] = $params['img'];
        if (isset($params['sort'])) $data['sort'] = $params['sort'];
        if (empty($data)) {
            throw new Exception('参数错误');
        }
        return HcityShopCategoryDao::i()->update($data, ['id' => $params['category_id']]);
    }

    /**
     * 申请入驻商圈
     * @param int $aid
     * @param int $shopId
     * @return int
     * @author ahe<ahe@iyenei.com>
     */
    public function applyHcity(int $aid, int $shopId)
    {
        $shopExt = HcityShopExtDao::i()->getOne(['aid' => $aid, 'shop_id' => $shopId]);
        if (empty($shopExt) || $shopExt->hcity_show_status == HcityShopExtDao::SHOW_NORMAL) {
            throw new Exception('店铺不符合入驻条件');
        }
        //删除店铺缓存
        (new HcityShopExtCache(['shop_id' => $shopId]))->delete();
        return HcityShopExtDao::i()->update(['hcity_audit_status' => HcityShopExtDao::AUDIT_WAIT, 'hcity_apply_time' => time()], ['aid' => $aid, 'shop_id' => $shopId]);
    }

    /**
     * 删除门店
     * @param int $aid
     * @param int $shopId
     * @param int $saasId
     * @return int
     * @author ahe<ahe@iyenei.com>
     * @author feiying<feiying@iyenei.com>update
     */
    public function deleteShop(int $aid, int $shopId, int $saasId)
    {
        $shopExt = HcityShopExtDao::i()->getOne(['aid' => $aid, 'shop_id' => $shopId]);
        if (empty($shopExt)) {
            throw new Exception('店铺不存在');
        }

        if ($shopExt->barcode_status == HcityShopExtDao::BARCODE_AVAILABLE && $saasId == SaasEnum::YDYM) {
            throw new Exception('已开通一店一码的门店不能删除');
        }

        if ($saasId == SaasEnum::HCITY) {
            $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $aid])->getEntitysByAR(["where" => ['shop_id' => $shopId], 'where_in' => ['hcity_status' => [1, 2]]]);
            if ($shcityGoodsDao) {
                throw new Exception('商品正在商圈审核或上架，此过程中不能删除店铺');
            }
        }
        $mainDb = MainDb::i(['aid' => $aid]);
        $mainDb->trans_start();
        //删除saas门店关系表
        $shopRefitem = MainShopRefitemDao::i()->getOne(['aid' => $aid, 'saas_id' => $saasId, 'ext_shop_id' => $shopExt->id]);
        if (!empty($shopRefitem)) {
            MainShopRefitemDao::i()->delete(['id' => $shopRefitem->id]);
        }

        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();

        $shopRefItemTmp = MainShopRefitemDao::i()->getOne(['aid' => $aid, 'ext_shop_id' => $shopExt->id]);
        if (empty($shopRefItemTmp)) {
            //若一店一码和商圈都没有该店铺，则删除扩展表
            HcityShopExtDao::i()->delete(['aid' => $aid, 'shop_id' => $shopId]);
            HcityShopCategoryRelDao::i()->delete(['aid' => $aid, 'shop_id' => $shopId]);
        } elseif ($saasId == SaasEnum::HCITY) {
            //若一店一码还存在，商圈删除，则更新状态
            HcityShopExtDao::i()->update(['hcity_show_status' => 0, 'hcity_audit_status' => 0], ['aid' => $aid, 'shop_id' => $shopId]);
        }
        //删除当前店铺缓存
        (new HcityShopExtCache(['shop_id' => $shopId]))->delete();

        if ($mainDb->trans_status() && $hcityMainDb->trans_status()) {
            log_message('info', '删除门店：' . json_encode(['aid' => $aid, 'shop_id' => $shopId]));
            $mainDb->trans_complete();
            $hcityMainDb->trans_complete();
            return true;
        } else {
            $mainDb->trans_rollback();
            $hcityMainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 创建门店
     * @param int $aid
     * @param int $visitId
     * @param array $data
     * @return bool
     * @author ahe<ahe@iyenei.com>
     */
    public function createShop(int $aid, array $data)
    {
        $mainDb = MainDb::i(['aid' => $aid]);
        $mainDb->trans_start();
        //开启事务
        //创建新店铺
        $shopData = [
            'aid' => $aid,
            'shop_name' => $data['shop_name'],
            'shop_logo' => $data['shop_logo'],
            'contact' => $data['contact'],
            'shop_state' => $data['shop_state'],
            'shop_city' => $data['shop_city'],
            'shop_district' => $data['shop_district'],
            'shop_address' => $data['shop_address'],
            'region' => $data['region'],
            'longitude' => $data['longitude'],
            'latitude' => $data['latitude']
        ];
        $shopId = MainShopDao::i()->create($shopData);
        //开启事务
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        //保存门店与分类关系
        $relData = [];
        $categoryIds = explode(',', $data['category_ids']);
        foreach ($categoryIds as $categoryId) {
            $relData[] = [
                'aid' => $aid,
                'category_id' => $categoryId,
                'shop_id' => $shopId
            ];
        }
        HcityShopCategoryRelDao::i()->createBatch($relData);
        //保存扩展表
        $categoryData = HcityShopCategoryDao::i()->getAllByCategoryId($categoryIds);
        $idCode = '';
        $generateCount = 1;
        //店铺邀请码，设置10位
        $idCodeLength = 10;
        while (true) {
            if ($generateCount >= 20) {
                $idCodeLength++;
                $generateCount = 1;
            }
            $idCode = generate_id_code($idCodeLength);
            $existShop = HcityShopExtDao::i()->getOne(['id_code' => $idCode], 'id');
            if (empty($existShop)) {
                break;
            }
            $generateCount++;
        }
        $shopExtData = [
            'aid' => $aid,
            'shop_id' => $shopId,
            'guest_unit_price' => $data['guest_unit_price'],
            'on_time' => $data['on_time'],
            'shop_imgs' => $data['shop_imgs'],
            'notice' => html_escape($data['notice']),
            'category_ids' => implode(',', $categoryIds),
            'category_name_list' => implode(',', array_column($categoryData, 'name')),
            'id_code' => $idCode
        ];
        $extShopId = HcityShopExtDao::i()->create($shopExtData);
        //保存saas门店关系表
        $shopRefData = [
            'aid' => $aid,
            'saas_id' => $data['saas_id'],
            'shop_id' => $shopId,
            'ext_shop_id' => $extShopId
        ];
        MainShopRefitemDao::i()->create($shopRefData);
        if ($mainDb->trans_status() && $hcityMainDb->trans_status()) {
            $mainDb->trans_complete();
            $hcityMainDb->trans_complete();
            return $shopId;
        } else {
            $mainDb->trans_rollback();
            $hcityMainDb->trans_rollback();
            return false;
        }
    }


    /**
     * 编辑店铺
     * @param int $aid
     * @param array $data
     * @return bool
     * @author ahe<ahe@iyenei.com>
     */
    public function editShop(int $aid, array $data)
    {
        $mainDb = MainDb::i(['aid' => $aid]);
        //开启事务
        $mainDb->trans_start();
        //创建新店铺
        $shopData = [
            'shop_name' => $data['shop_name'],
            'shop_logo' => $data['shop_logo'],
            'contact' => $data['contact'],
            'shop_state' => $data['shop_state'],
            'shop_city' => $data['shop_city'],
            'shop_district' => $data['shop_district'],
            'shop_address' => $data['shop_address'],
            'region' => $data['region'],
            'longitude' => $data['longitude'],
            'latitude' => $data['latitude']
        ];
        MainShopDao::i()->syncUpdateOne($shopData, ['id' => $data['shop_id']]);
        //开启事务
        $hcityMainDb = HcityMainDb::i(['aid' => $aid]);
        $hcityMainDb->trans_start();
        //保存门店与分类关系
        $existRelData = HcityShopCategoryRelDao::i()->getAll(['aid' => $aid, 'shop_id' => $data['shop_id']]);
        $existCategoryIds = array_column($existRelData, 'category_id');
        $createData = $deleteData = [];
        $categoryIds = explode(',', $data['category_ids']);
        foreach ($categoryIds as $categoryId) {
            if (!in_array($categoryId, $existCategoryIds)) {
                $createData[] = [
                    'aid' => $aid,
                    'category_id' => $categoryId,
                    'shop_id' => $data['shop_id']
                ];
            }
        }
        $deleteData = array_diff($existCategoryIds, $categoryIds);
        if (!empty($createData)) {
            HcityShopCategoryRelDao::i()->createBatch($createData);
        }
        if (!empty($deleteData)) {
            HcityShopCategoryRelDao::i()->deleteRel($data['shop_id'], $deleteData);
        }

        //保存扩展表
        $shopExt = HcityShopExtDao::i()->getOne(['aid' => $aid, 'shop_id' => $data['shop_id']]);
        $categoryData = HcityShopCategoryDao::i()->getAllByCategoryId($categoryIds);
        $shopExtData = [
            'guest_unit_price' => $data['guest_unit_price'],
            'on_time' => $data['on_time'],
            'shop_imgs' => $data['shop_imgs'],
            'notice' => html_escape($data['notice']),
            'category_ids' => implode(',', $categoryIds),
            'category_name_list' => implode(',', array_column($categoryData, 'name'))
        ];
        HcityShopExtDao::i()->update($shopExtData, ['id' => $shopExt->id]);

        //保存saas门店关系表
        $shopRefitem = MainShopRefitemDao::i()->getOne(['aid' => $aid, 'saas_id' => $data['saas_id'], 'shop_id' => $data['shop_id']]);
        if (empty($shopRefitem)) {
            $shopRefData = [
                'aid' => $aid,
                'saas_id' => $data['saas_id'],
                'shop_id' => $data['shop_id'],
                'ext_shop_id' => $shopExt->id
            ];
            MainShopRefitemDao::i()->create($shopRefData);
        }

        //删除店铺缓存
        (new HcityShopExtCache(['shop_id' => $data['shop_id']]))->delete();

        if ($hcityMainDb->trans_status() && $mainDb->trans_status()) {
            $hcityMainDb->trans_complete();
            $mainDb->trans_complete();
            return true;
        } else {
            $hcityMainDb->trans_rollback();
            $mainDb->trans_rollback();
            return false;
        }
    }

    /**
     * 获取门店详情
     * @param int $aid
     * @param int $shopId
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function getShopDetail(int $aid, int $shopId)
    {
        $shop = MainShopDao::i()->getOne(['aid' => $aid, 'id' => $shopId]);
        if (empty($shop)) {
            return [
                'shop' => [],
                'shop_ext' => [],
                'shop_category' => []
            ];
        }
        $shop->shop_logo = conver_picurl($shop->shop_logo);
        $shopExt = HcityShopExtDao::i()->getOne(['aid' => $aid, 'shop_id' => $shopId]);
        if (!empty($shopExt)) {
            if (!empty($shopExt->shop_imgs)) {
                $shopImgs = explode(',', $shopExt->shop_imgs);
                if (!empty($shopImgs)) {
                    $shopImgTmp = [];
                    foreach ($shopImgs as $img) {
                        $shopImgTmp[] = conver_picurl($img);
                    }
                    $shopExt->shop_imgs = $shopImgTmp;
                    //小程序二维码
                }
            } else {
                $shopExt->shop_imgs = array();
            }
            $shopExt->qr_url = $this->getShopXcxQr(['aid' => $aid, 'shop_id' => $shopId]);
            $shopExt->notice = htmlspecialchars_decode($shopExt->notice);
        }
        $shopCategory = HcityShopCategoryRelDao::i()->getAll(['aid' => $aid, 'shop_id' => $shopId]);
        return [
            'shop' => $shop,
            'shop_ext' => $shopExt,
            'shop_category' => $shopCategory,
        ];
    }

    /**
     * 获取门店小程序二维码
     * @param  array $params 必选aid,shop_id
     * @return string         小程序二维码
     * @author binghe 2018-08-21
     */
    public function getShopXcxQr(array $params)
    {
        $keyScene = ['type' => 1, 'aid' => $params['aid'], 'shop_id' => $params['shop_id']];
        ksort($keyScene);
        $key = md5(create_linkstring_urlencode($keyScene));
        $hcityXcxQrDao = HcityXcxQrDao::i();
        $mHcityXcxQr = $hcityXcxQrDao->getOne(['key' => $key], 'qr_id,qr_img');
        //数据库返回原保存
        if ($mHcityXcxQr)
            return LOCAL_UPLOAD_URL . $mHcityXcxQr->qr_img;

        $qrId = create_order_number();

        $qrParams['scene'] = 's=' . $qrId;
        //因为只有发布过的小程序才能带page,所有其它环境都不带
        if (ENVIRONMENT == 'production')
            $qrParams['page'] = 'pages/share/index/index';
        //sdk获取小程序二维
        $xcxBll = new XcxBll;
        $dirPath = 'xhcity/' . date('Ymd') . '/';
        $filePath = $xcxBll->getQrcodeStreamContents($qrParams, $dirPath);

        //保存二维码
        $qrData = ['qr_id' => $qrId, 'key' => $key, 'scene' => json_encode($keyScene), 'qr_img' => $filePath];
        if ($hcityXcxQrDao->create($qrData))
            return LOCAL_UPLOAD_URL . $filePath;
        else
            throw new Exception('保存二维码出错');

    }

    /**
     * 获取门店列表
     * @param int $aid
     * @param array $params
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getShopList(int $aid, array $params)
    {
        if (!$params['is_admin'] && empty($params['main_shop_ids'])) {
            $rows['rows'] = [];
            $rows['total'] = 0;
            return $rows;
        }

        if ($params['is_admin']) {
            $shopRefitem = MainShopRefitemDao::i()->getAll(['aid' => $aid, 'saas_id' => $params['saas_id']], 'shop_id');
            $shopIdArr = array_column($shopRefitem, 'shop_id');
        } else {
            $shopIdArr = $params['main_shop_ids'];
        }

        if ($params['hcity_show_status'] !== '' && $params['hcity_show_status'] !== null) {
            $shopExt = HcityShopExtDao::i()->getAll(['aid' => $aid, 'hcity_show_status' => $params['hcity_show_status']], 'shop_id');
            $shopIds = array_column($shopExt, 'shop_id');
            $shopIdArr = array_intersect($shopIdArr, $shopIds);
        }
        if ($params['barcode_status'] !== '' && $params['barcode_status'] !== null) {
            $shopExt = HcityShopExtDao::i()->getAll(['aid' => $aid, 'barcode_status' => $params['barcode_status']], 'shop_id');
            $shopIds = array_column($shopExt, 'shop_id');
            $shopIdArr = array_intersect($shopIdArr, $shopIds);
        }
        if (empty($shopIdArr)) {
            $rows['rows'] = [];
            $rows['total'] = 0;
            return $rows;
        }

        $mainDb = MainDb::i();
        $page = new PageList($mainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$mainDb->tables['main_shop']}";
        $p_conf->where = ' aid=' . $aid;

        $p_conf->where .= sprintf(" and id in (%s)", implode(',', array_unique($shopIdArr)));
        if ($params['shop_name']) {
            $p_conf->where .= " and shop_name like '%{$page->filterLike($params['shop_name'])}%'";
        }
        if ($params['contact']) {
            $p_conf->where .= " and contact = {$page->filter($params['contact'])}";
        }

        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        if (!empty($rows['rows'])) {
            $shopIds = array_column($rows['rows'], 'id');
            $shopExt = HcityShopExtDao::i()->getAllByShopId($aid, $shopIds);
            array_walk($rows['rows'], function (&$item) use ($shopExt) {
                $item['shop_logo'] = conver_picurl($item['shop_logo']);
                array_walk($shopExt, function ($ext) use (&$item) {
                    if ($ext->shop_id == $item['id']) {
                        $item['hcity_show_status'] = $ext->hcity_show_status;
                        $item['hcity_audit_status'] = $ext->hcity_audit_status;
                        $item['hcity_refuse_remark'] = $ext->hcity_refuse_remark;
                        $item['hcity_remove_remark'] = $ext->hcity_remove_remark;
                        $item['barcode_status'] = $ext->barcode_status;
                        $item['category_name_list'] = $ext->category_name_list;
                        $item['barcode_expire_time'] = empty($ext->barcode_expire_time) ? '' : date('Y-m-d H:i:s', $ext->barcode_expire_time);
                        $item['barcode_is_expire'] = empty($ext->barcode_expire_time) || $ext->barcode_expire_time <= time() ? 1 : 0;
                    }
                });
            });
        }
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 根据店铺ids获取店铺信息
     * @param string $ids shop_id,逗号隔开
     * @param array $input
     * @author liusha
     */
    public function getShopByIds($ids = '', array $input = [])
    {
        if (empty($ids)) return [];

        $shop_list = MainShopDao::i()->getAllArray("id in ({$ids})");
        $where = '';
        if (!empty($input['ext_where']))
            $where = " AND {$input['ext_where']}";
        $order = false;
        if (!empty($input['order_by']))
            $order = "  {$input['order_by']}";
        $curr_time = time();
        $shop_ext_list = HcityShopExtDao::i()->getAllArray("shop_id in ({$ids}) {$where}", '*', $order);
        foreach ($shop_ext_list as $ek => $shop_ext) {
            $main_shop = array_find($shop_list, 'id', $shop_ext['shop_id']);

            $main_shop['shop_logo'] = conver_picurl($main_shop['shop_logo']);
            //计算经纬度距离
            if (isset($input['lat']) && isset($input['long']))
                $main_shop['distance'] = get_distance($input['long'], $input['lat'], $main_shop['longitude'], $main_shop['latitude']);
            $shop_ext_list[$ek]['main_info'] = $main_shop;
            //判断商圈是否过期
            $shop_ext_list[$ek]['hcity_valid_status'] = 0;
            if ($shop_ext['hcity_show_status'] == 1) {
                $shop_ext_list[$ek]['hcity_valid_status'] = 2;//已入住
            } elseif ($shop_ext['hcity_audit_status'] == 1) {
                $shop_ext_list[$ek]['hcity_valid_status'] = 1;//待审核
            }

            //判断一点一码是否过期
            $shop_ext_list[$ek]['barcode_valid_status'] = 0;
            if ($shop_ext['barcode_status'] == 1) {
                if ($shop_ext['barcode_expire_time'] >= $curr_time)
                    $shop_ext_list[$ek]['barcode_valid_status'] = 2;//已开通
                else
                    $shop_ext_list[$ek]['barcode_valid_status'] = 1;//已过期
                $shop_ext_list[$ek]['hcity_valid_status'] = 2;//已入住
            }

        }

        return $shop_ext_list;
    }


    /**
     * 过滤出商圈失效的店铺id
     * @param array $shopIds
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function filterInvalidShopIds(array $shopIds = [])
    {
        if (empty($shopIds)) return [];
        $options = [
            'where_in' => ['shop_id' => $shopIds]
        ];
        $shopExt = HcityShopExtDao::i()->getAllExt($options);
        if (empty($shopExt)) {
            //全部失效
            $invalidShopIds = $shopIds;
        } else {
            $invalidShopIds = array_diff($shopIds, array_column($shopExt, 'shop_id'));
//            foreach ($shopExt as $ext) {
//                if (in_array($ext->shop_id, $shopIds) && $ext->hcity_show_status != HcityShopExtDao::SHOW_NORMAL) {
//                    $invalidShopIds[] = $ext->shop_id;
//                }
//            }
            $options = [
                'where_in' => ['shop_id' => $shopIds, 'saas_id' => [SaasEnum::HCITY, SaasEnum::YDYM]]
            ];
            $mainShops = MainShopRefitemDao::i()->getAllExt($options);
            $diffShopIds = array_diff($shopIds, array_column($mainShops, 'shop_id'));
            $invalidShopIds = array_merge($invalidShopIds, $diffShopIds);
        }
        return array_unique($invalidShopIds);
    }


    /**
     * 获取所有可用店铺列表
     * @param int $aid
     * @param array $params
     * @return array|mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getAllAllowShop(int $aid, array $params)
    {
        $list = [];
        if (!$params['is_admin'] && empty($params['main_shop_ids'])) {
            return $list;
        }

        if ($params['is_admin']) {
            $list[] = (object)['shop_id' => 0, 'shop_name' => '总店'];
            $shopRefitem = MainShopRefitemDao::i()->getAll(['aid' => $aid, 'saas_id' => $params['saas_id']], 'shop_id');
            $shopIdArr = array_column($shopRefitem, 'shop_id');
        } else {
            $shopIdArr = $params['main_shop_ids'];
        }

        if (empty($shopIdArr)) return $list;
        $options = [
            'where' => ['aid' => $aid],
            'where_in' => ['id' => $shopIdArr]
        ];

        $listTmp = MainShopDao::i()->getAllExt($options, 'id as shop_id,shop_name');
        return array_merge($list, $listTmp);
    }

    /**
     * 列表增加门店
     * @param int $aid
     * @param int $visitId
     * @param array $data
     * @return bool
     * @author feiying
     */
    public function allCreateShop(int $aid, array $data)
    {
        $mainDb = MainDb::i(['aid' => $aid]);
        $mainDb->trans_start();
        //开启事务
        //创建新店铺
        $shopData = [
            'aid' => $aid,
            'shop_name' => $data['shop_name'],
            'shop_logo' => $data['shop_logo'],
            'contact' => $data['contact'],
            'shop_state' => $data['shop_state'],
            'shop_city' => $data['shop_city'],
            'shop_district' => $data['shop_district'],
            'shop_address' => $data['shop_address'],
            'region' => $data['region'],
            'longitude' => $data['longitude'],
            'latitude' => $data['latitude']
        ];
        $shopInfo = MainShopDao::i()->update($shopData, ['id' => $data['shop_id']]);
        //开启事务
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        //保存门店与分类关系
        $relData = [];
        $categoryIds = explode(',', $data['category_ids']);
        foreach ($categoryIds as $categoryId) {
            $relData[] = [
                'aid' => $aid,
                'category_id' => $categoryId,
                'shop_id' => $data['shop_id']
            ];
        }
        HcityShopCategoryRelDao::i()->createBatch($relData);
        //保存扩展表
        $categoryData = HcityShopCategoryDao::i()->getAllByCategoryId($categoryIds);
        $idCode = '';
        $generateCount = 1;
        //店铺邀请码，设置10位
        $idCodeLength = 10;
        while (true) {
            if ($generateCount >= 20) {
                $idCodeLength++;
                $generateCount = 1;
            }
            $idCode = generate_id_code($idCodeLength);
            $existShop = HcityShopExtDao::i()->getOne(['id_code' => $idCode], 'id');
            if (empty($existShop)) {
                break;
            }
            $generateCount++;
        }
        $shopExtData = [
            'aid' => $aid,
            'shop_id' => $data['shop_id'],
            'guest_unit_price' => $data['guest_unit_price'],
            'on_time' => $data['on_time'],
            'shop_imgs' => $data['shop_imgs'],
            'notice' => $data['notice'],
            'category_ids' => implode(',', $categoryIds),
            'category_name_list' => implode(',', array_column($categoryData, 'name')),
            'id_code' => $idCode
        ];
        $extShopId = HcityShopExtDao::i()->create($shopExtData);
        //保存saas门店关系表
        $shopRefData = [
            'aid' => $aid,
            'saas_id' => $data['saas_id'],
            'shop_id' => $data['shop_id'],
            'ext_shop_id' => $extShopId
        ];
        MainShopRefitemDao::i()->create($shopRefData);
        if ($mainDb->trans_status() && $hcityMainDb->trans_status()) {
            $mainDb->trans_complete();
            $hcityMainDb->trans_complete();
            return true;
        } else {
            $mainDb->trans_rollback();
            $hcityMainDb->trans_rollback();
            return false;
        }
    }


}
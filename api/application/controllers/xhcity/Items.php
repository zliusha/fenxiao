<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/14
 * Time: 17:04
 */
use Service\Bll\Hcity\ShopBll;
use Service\Bll\Hcity\GoodsBll;
use Service\Bll\Hcity\GoodsKzBll;
use Service\Bll\Hcity\WelfareGoodsBll;

use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopCategoryDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityHomepageShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityHomepageShopCategoryDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectGoodsDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityPopularGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBannerDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBannerGoodsDao;

class Items extends xhcity_controller
{
    /**
     * 首页商品列表+店铺推荐+首页banner
     */
    public function index()
    {
        $rules = [
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'city_code', 'label' => '区域码', 'rules' => 'trim|required'],
            ['field' => 'title', 'label' => '商品标题', 'rules' => 'trim'],
            ['field' => 'current_page', 'label' => '当前页数', 'rules' => 'numeric'],
            ['field' => 'page_size', 'label' => '分页大小', 'rules' => 'numeric'],
            ['field' => 'sort_field', 'label' => '排序字段', 'rules' => 'trim'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $goodsKzBll = new GoodsKzBll();


        try {
            $curr_time = time();
            $where = " a.hcity_status=1 AND a.show_end_time>{$curr_time} AND a.show_begin_time<{$curr_time} AND (a.is_hcity_stock_open=0 or (a.is_hcity_stock_open=1 AND a.hcity_stock_num>=0))";

            $data = $goodsKzBll->shopGoodsKzList($fdata, $where);
        } catch (Exception $e) {
            //判断是否有城市合伙人
            $ManagetAccount = HcityManageAccountDao::i()->getOne("region like '%{$fdata['city_code']}%'");
            if (!$ManagetAccount) {
                $this->json_do->set_error('005', '当地服务暂未开通');
            }
            $data = [];
        }


        //获取首页推荐店铺
        $shop_list = [];
        if (empty($fdata['title']) && !(is_numeric($fdata['current_page']) && $fdata['current_page'] > 1)) {

            $shopBll = new ShopBll();
            $hcityHomepageShopDao = HcityHomepageShopDao::i();
            $mhomepageShop = $hcityHomepageShopDao->getOne("region like '%{$fdata['city_code']}%'", '*');
            if ($mhomepageShop) {
                $shop_list = array_sort(@json_decode($mhomepageShop->shop_list), 'sort', 'asc');
                $shopIdsArr = array_column($shop_list, 'shop_id');
                $shopIds = empty($shopIdsArr) ? '0' : implode(',', $shopIdsArr);
                $shop_list = $shopBll->getShopByIds($shopIds, ['ext_where' => 'hcity_show_status=1', 'order_by' => "FIELD(shop_id,{$shopIds}) ASC", 'lat' => $fdata['lat'], 'long' => $fdata['long']]);

                //判断是否收藏
                $collect_shop_list = [];
                if ($this->s_user->uid > 0) {
                    $hcityUserCollectShopDao = HcityUserCollectShopDao::i(['uid' => $this->s_user->uid]);
                    $collect_shop_list = $hcityUserCollectShopDao->getAllByShopIds($this->s_user->uid, $shopIdsArr);
                }
                array_walk($shop_list, function (&$shop) use ($collect_shop_list) {
                    $collect_shop = array_find($collect_shop_list, 'shop_id', $shop['shop_id']);
                    $shop['is_collect'] = (isset($collect_shop['status']) && $collect_shop['status'] == 0) ? 1 : 0;
                });
            }
        }

        //获取首页推荐分类
        $hcityShopCategoryDao = HcityShopCategoryDao::i();
        $hcityHomepageShopCategoryDao = HcityHomepageShopCategoryDao::i();
        $homepageShopCategory_list = $hcityHomepageShopCategoryDao->getAllArray("region like '%{$fdata['city_code']}%'", '*', "sort ASC");
        if (empty($homepageShopCategory_list)) {
            //分类无则取平台默认设置
            $homepageShopCategory_list = $hcityHomepageShopCategoryDao->getAllArray("is_default = 1", '*', "sort ASC");
        } elseif (count($homepageShopCategory_list) < 4) {
            //分类不足四个时，取平台默认设置，并不足4个
            $defaultShopCategoryList = $hcityHomepageShopCategoryDao->getAllArray("is_default = 1", '*', "sort ASC");
            $defaultShopCategoryList = array_filter($defaultShopCategoryList, function ($item) use ($homepageShopCategory_list) {
                return !in_array($item['category_id'], array_column($homepageShopCategory_list, 'category_id'));
            });
            $homepageShopCategory_list = array_merge($homepageShopCategory_list, $defaultShopCategoryList);
            $homepageShopCategory_list = array_slice($homepageShopCategory_list, 0, 4);
        }
        $categoryIds = array_column($homepageShopCategory_list, 'category_id');
        $categoryIds = empty($categoryIds) ? '0' : implode(',', $categoryIds);
        $category_list = $hcityShopCategoryDao->getAllArray("id in ($categoryIds) AND is_delete=0", "*", "FIELD(id,{$categoryIds}) ASC");


        //组合店铺商品穿插数据
        $i = 0;
        $j = 0;
        $rows = [];
        if (!empty($data['rows'])) {
            foreach ($data['rows'] as $k => $row) {
                if ($i % 4 == 0 && isset($shop_list[$j])) {
                    $rows[] = $shop_list[$j];
                    unset($shop_list[$j]);
                    $j++;
                }
                //格式图片
                $data['rows'][$k]['pic_url'] = conver_picurl($row['pic_url']);
                $rows[] = $data['rows'][$k];

                $i++;
            }
        }

        //如果第一批结束并且店铺推荐没有穿插完毕
        array_merge($rows, $shop_list);
        $data['rows'] = $rows;
        $data['category_list'] = convert_client_list($category_list, [['type' => 'img', 'field' => 'img']]);

        //获取首页banner
        $banner_list = HcityActivityBannerDao::i()->getAllArray("region like '%{$fdata['city_code']}%'", '*', 'sort asc');
        $data['banner_list'] = convert_client_list($banner_list, [['type' => 'img', 'field' => 'pic_url']]);

        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }

    /**
     * 商品详情
     */
    public function detail()
    {
        $rules = [
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'source_type', 'label' => '来源入口', 'rules' => 'trim|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $fdata['source_type'] = (int)$fdata['source_type'];
        try {

            $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $fdata['aid']]);;
            $detail = $shcityGoodsDao->detail($fdata['aid'], $fdata['goods_id']);
            $kz_detail = (new GoodsKzBll())->detailByGoodsId($fdata['goods_id']);
            //是否收藏
            $data['goods_kz_detail'] = $kz_detail;
            $data['goods_detail'] = $detail;
            $data['is_collect'] = 0;
            $ret = HcityUserCollectGoodsDao::i(['uid' => $this->s_user->uid])->getOne(['uid' => $this->s_user->uid, 'source_type'=>$fdata['source_type'], 'aid' => $fdata['aid'], 'goods_id' => $fdata['goods_id']]);
            if ($ret) $data['is_collect'] = 1;

        } catch (Exception $e) {
            $this->json_do->set_error('004', $e->getMessage());
        }

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 福利池商品
     */
    public function welfare_list()
    {
        $rules = [
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'current_page', 'label' => '当前页数', 'rules' => 'numeric'],
            ['field' => 'page_size', 'label' => '分页大小', 'rules' => 'numeric'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        // 2018/08/24版本更新处理，福利商品数据清空
        $data = [];
        $this->json_do->set_data($data);
        $this->json_do->out_put();

        $goodsKzBll = new GoodsKzBll();
        try {
            $curr_time = time();
            $where = " a.hcity_status=1 AND a.show_end_time>{$curr_time} AND a.show_begin_time<{$curr_time}  AND a.free_num>0 AND a.welfare_status =1";
            $data = $goodsKzBll->shopGoodsKzList($fdata, $where);
        } catch (\Service\Exceptions\Exception $e) {
            $this->json_do->set_error('004', $e->getMessage());
        }

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 福利池商品 2018/08/24
     */
    public function welfare_list_v103()
    {
        $rules = [
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'city_code', 'label' => '区域码', 'rules' => 'trim'],
            ['field' => 'title', 'label' => '商品标题', 'rules' => 'trim'],
            ['field' => 'current_page', 'label' => '当前页数', 'rules' => 'numeric'],
            ['field' => 'page_size', 'label' => '分页大小', 'rules' => 'numeric'],
        ];
       

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $welfareGoodsBll = new WelfareGoodsBll();
        try {
            $curr_time = time();
            $fdata['where'] = " show_begin_time<{$curr_time} and use_end_time>{$curr_time}";
            $fdata['region'] = $fdata['city_code'];
            $fdata['welfare_status'] = 1;
            $data = $welfareGoodsBll->goodsList($fdata);
        } catch (\Service\Exceptions\Exception $e) {
            $this->json_do->set_error('004', $e->getMessage());
        }

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 福利池商品
     */
    public function welfare_detail()
    {
        $rules = [
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $welfareGoodsBll = new WelfareGoodsBll();
        try {
            $data = $welfareGoodsBll->detail($fdata['aid'], $fdata['goods_id']);
        } catch (\Service\Exceptions\Exception $e) {
            $this->json_do->set_error('004', $e->getMessage());
        }

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 获取商圈店铺商品列表
     */
    public function shop_goods_list()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $curr_time = time();
        $goods_kz_list = HcityGoodsKzDao::i()->getAllArray(['aid' => $fdata['aid'], 'hcity_status' => 1, 'shop_id' => $fdata['shop_id'], 'show_begin_time <=' => $curr_time, 'show_end_time >' => $curr_time], "*", 'top_sort desc,id desc');
        $data['rows'] = convert_client_list($goods_kz_list, [['type' => 'img', 'field' => 'pic_url']]);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 获取爆款商品
     */
    public function get_popular_goods()
    {
        $rules = [
            ['field' => 'city_code', 'label' => '区域码', 'rules' => 'trim|required']
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $hcityActivityPopularGoodsDao = HcityActivityPopularGoodsDao::i();
        $popularGoods = $hcityActivityPopularGoodsDao->getAllArray("region like '%{$fdata['city_code']}%'", '*', 'sort asc');

        //优先取城市合伙人的，不存在再取平台设置
        $firstData = array_filter($popularGoods, function ($item) {
            return $item['is_default'] == 0;
        });
        $platData = array_filter($popularGoods, function ($item) {
            return $item['is_default'] == 1;
        });

        if (!empty($firstData)) {
            $goods_ids = array_column($firstData, 'goods_id');
        } else {
            $goods_ids = array_column($platData, 'goods_id');
        }

        $curr_time = time();
        $goods_ids = empty($goods_ids) ? '0' : implode(',', $goods_ids);
        $list = HcityGoodsKzDao::i()->getAllArray("hcity_status=1 AND show_begin_time<={$curr_time} AND show_end_time>{$curr_time} AND goods_id in ($goods_ids)");
        $out = [];
        if (!empty($list)) {
            foreach ($popularGoods as $val) {
                foreach ($list as $goodsKz) {
                    if ($val['aid'] == $goodsKz['aid'] && $val['goods_id'] == $goodsKz['goods_id']) {
                        $goodsKz['xc_pic_url'] = conver_picurl($val['xc_pic_url']);
                        $goodsKz['pic_url'] = $goodsKz['xc_pic_url'];
                        $out[] = $goodsKz;
                    }
                }
            }
        }
        $this->json_do->set_data($out);
        $this->json_do->out_put();
    }

    /**
     * 获取一点一码所有商品
     */
    public function get_ydym_shop_goods_list()
    {
        $rule = [
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['status'] = 1;
        $data = (new GoodsBll())->goodsYdymList($fdata['aid'], $fdata);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 广告位商品列表
     * @author ahe<ahe@iyenei.com>
     */
    public function banner_goods_list()
    {
        $rules = [
            ['field' => 'banner_id', 'label' => '公告位id', 'rules' => 'trim|required|numeric'],
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $list = (new GoodsKzBll())->getBannerGoodsList($fdata);
        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }
}
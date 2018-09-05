<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/16
 * Time: 11:12
 */
use Service\Bll\Hcity\ShopBll;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopCategoryRelDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserCollectShopDao;

class Shop extends xhcity_controller
{
    /**
     * 店铺列表
     */
    public function get_list()
    {
        $rules = [
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'trim|required|numeric'],
            ['field' => 'city_code', 'label' => '区域码', 'rules' => 'trim|required'],
            ['field' => 'shop_name', 'label' => '店铺名称', 'rules' => 'trim'],
            ['field' => 'category_id', 'label' => '分类ID', 'rules' => 'numeric'],
            ['field' => 'sort_field', 'label' => '排序字段', 'rules' => ''],
            ['field' => 'sort_type', 'label' => '排序类型', 'rules' => ''],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $mainShopDao = MainShopDao::i();
        $hcityShopExtDao = HcityShopExtDao::i();
        $hcityShopCategoryRelDao = HcityShopCategoryRelDao::i();

        $shop_where = '';
        $shop_ext_where = 'hcity_show_status=1';
        //获取分类店铺
        if($fdata['category_id'] > 0)
        {
            $category_shop_list = $hcityShopCategoryRelDao->getAllArray(['category_id'=>$fdata['category_id']]);
            $category_shop_ids = empty($category_shop_list) ? 0 : implode(',', array_column($category_shop_list, 'shop_id'));
            $shop_ext_where .= " AND  shop_id in ({$category_shop_ids})";
        }
        //搜索关键词
        if(!empty($fdata['shop_name']))
        {
            $shop_where .= " AND  shop_name like '%{$fdata['shop_name']}%'";
        }
        //获取主订单信息
        $shop_ext_list = $hcityShopExtDao->getAllArray($shop_ext_where, '*', "hcity_pass_time desc");
        $shop_ext_ids = implode(',', array_column($shop_ext_list, 'shop_id'));
        if(empty($shop_ext_ids))
            $shop_list = [];
        else
            $shop_list = $mainShopDao->getAllArray("id in ({$shop_ext_ids}) AND region like '%{$fdata['city_code']}%' {$shop_where}");

        //获取收藏店铺列表
        $collect_shop_list = HcityUserCollectShopDao::i()->getAllArray(['uid'=>$this->s_user->uid, 'status'=>0], 'id,shop_id,uid');
        $collect_shop_list = array_column($collect_shop_list, null, 'shop_id');
        // 计算店铺与当前位置的距离
        foreach($shop_ext_list as $sk => $shop_ext)
        {
            $shop = array_find($shop_list, 'id', $shop_ext['shop_id']);
            if($shop)
            {
                $shop['shop_logo'] = conver_picurl($shop['shop_logo']);
                $shop_ext_list[$sk]['main_info'] = $shop;
                $shop_ext_list[$sk]['distance'] = get_distance($fdata['long'], $fdata['lat'], $shop['longitude'], $shop['latitude']);
                // 是否收藏
                $shop_ext_list[$sk]['is_collect'] = isset($collect_shop_list[$shop_ext['shop_id']]) ? 1 : 0;
            }
            else
            {
                unset($shop_ext_list[$sk]);
            }
        }

        if($fdata['sort_type'] != 'desc')
            $fdata['sort_type'] = 'asc';
        // 排序
        if(in_array($fdata['sort_field'], ['distance', 'collect_num']))
        {
            $shop_ext_list = array_sort($shop_ext_list, $fdata['sort_field'], $fdata['sort_type']);
        }

        $data['list'] = array_values($shop_ext_list);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 店铺详情
     */
    public function detail()
    {
        $rules = [
            ['field' => 'aid', 'label' => 'AID', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'lat', 'label' => '纬度值', 'rules' => 'numeric'],
            ['field' => 'long', 'label' => '经度值', 'rules' => 'numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $shopBll = new ShopBll();
        try{
            $data = $shopBll->getShopDetail($fdata['aid'], $fdata['shop_id']);
            if(empty($data['shop_ext']))
            {
                throw new \Exception('店铺信息不存在');
            }
            $data['shop']->distance = get_distance($fdata['long'], $fdata['lat'], $data['shop']->longitude, $data['shop']->latitude);
            $data['shop_ext']->is_collect = 0;
            $ret = HcityUserCollectShopDao::i()->getOne(['uid'=>$this->s_user->uid, 'aid'=>$fdata['aid'], 'shop_id'=>$fdata['shop_id'], 'status'=>0]);
            if($ret) $data['shop_ext']->is_collect = 1;

        }catch(\Service\Exceptions\Exception $e)
        {
            $this->json_do->set_error('004', $e->getMessage());
        }

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
<?php
use Service\Cache\WmShopCache;
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopAccountDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPromotionDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCateDao;
use Service\Enum\SaasEnum;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\Bll\Main\MainShopBll;
/**
 * @Author: binghe
 * @Date:   2017-10-25 10:03:37
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-28 10:45:03
 */
/**
 * 店铺api
 */
class Shop_api extends wm_service_controller
{

    /**
     * 获取未关联账号的所有门店
     */
    public function all_nobind_list()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        //权限
        $where['aid'] = $this->s_user->aid;
        $where['is_delete'] = 0;
        $list = $wmShopDao->getAllArray($where, 'id,shop_name,status', 'id desc');

        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $user_list = $mainCompanyAccountDao->getAllArray(['aid' => $this->s_user->aid, 'is_admin <>' => 1]);

        $last_shops = [];

        foreach ($list as $item) {
            $one = array_find($user_list, 'shop_id', $item['id']);
            if (!$one) {
                array_push($last_shops, $item);
            }

        }

        $data['list'] = $last_shops;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 主门店info
     * @return [type] [description]
     */
    public function main_shop_info()
    {
        $shop_id = $this->input->get('main_shop_id');
        $info = (new MainShopBll())->info($this->s_user->aid,$shop_id,false);
        $data['info'] = $info;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 未开通云店的门店
     * @return [type] [description]
     */
    public function noref_list()
    {
        $list = (new MainShopBll())->all($this->s_user->aid,self::SAAS_ID);
        $data['list'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    } 
    /**
     * 所有门店列表
     */
    public function all_list()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        //权限
        $where['aid'] = $this->s_user->aid;
        $where['is_delete'] = 0;
        if (!$this->is_zongbu) {
            $where['id'] = $this->currentShopId;
        }

        $list = $wmShopDao->getAllArray($where, 'id,shop_name,status', 'id desc');
        $data['list'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 门店分页列表
     */
    public function list() {
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->table = $wmShardDb->tables['wm_shop'];
        $p_conf->where = " aid={$this->s_user->aid} AND is_delete=0";
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        $base_url = $this->get_shop_base_url();

        foreach ($list as $key => $shop) {
            $list[$key]['time'] = date('Y-m-d H:i:s', $shop['time']);
            $list[$key]['qr_url'] = $base_url . '/#/shop/' . $shop['id'];
        }

        $data['total'] = $count;
        $data['rows'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 添加门店
     */
    public function add()
    {
        $type = $this->input->post_get('type');
        if (!is_numeric($type)) {
            $this->json_do->set_error('001', '参数错误');
        }
        if (($type & 1 << 0) > 0) //存在外卖类型
        {
            $rules = [
                ['field'=>'main_shop_id','label'=>'父门店id','rules'=>'trim|numeric'],
                ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
                ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
                ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
                ['field' => 'send_money', 'label' => '起送价', 'rules' => 'trim|required|preg_key[PRICE]'],
                ['field' => 'shipping_fee', 'label' => '配送费', 'rules' => 'trim|required'],
                ['field' => 'use_flow', 'label' => '满减', 'rules' => 'trim|required|in_list[0,1]'],
                ['field' => 'flow_free_money', 'label' => '满减金额', 'rules' => 'trim|required|preg_key[PRICE]'],
                ['field' => 'service_radius', 'label' => '服务半径', 'rules' => 'trim|required|numeric'],
                ['field' => 'arrive_time', 'label' => '送达时间', 'rules' => 'trim|required|numeric'],
                ['field' => 'arrive_range', 'label' => '配送区域', 'rules' => 'trim'],
                ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
                ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
                ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
                ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
                ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim'],
                ['field' => 'shop_imgs', 'label' => '门店图片', 'rules' => 'trim'],
                ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
                ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required'],
                ['field' => 'on_time', 'label' => '营业时间', 'rules' => 'trim|required'],
                ['field' => 'prepare_time', 'label' => '备餐时间', 'rules' => 'trim|numeric'],
                ['field' => 'auto_receiver', 'label' => '自动接单', 'rules' => 'trim|required|in_list[0,1]'],
                ['field' => 'auto_printer', 'label' => '自动打单', 'rules' => 'trim|required|in_list[0,1]'],
                ['field' => 'notice', 'label' => '公告', 'rules' => 'trim'],
                ['field' => 'type', 'label' => '门店类型', 'rules' => 'trim|required|numeric'],
            ];

            $this->check->check_ajax_form($rules);
            $fdata = $this->form_data($rules);

            //旧版配送费（2018/2/28）
            $fdata['freight_money'] = 0;

            $fdata['aid'] = $this->s_user->aid;
            $fdata['use_flow'] = (int) $fdata['use_flow'];
            $fdata['auto_receiver'] = (int) $fdata['auto_receiver'];
            $fdata['auto_printer'] = (int) $fdata['auto_printer'];
        } else //不存在外卖类型
        {
            $rules = [
                ['field'=>'main_shop_id','label'=>'父门店id','rules'=>'trim|numeric'],
                ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
                ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
                ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
                ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
                ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
                ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
                ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
                ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim'],
                ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
                ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required'],
                ['field' => 'type', 'label' => '门店类型', 'rules' => 'trim|required|numeric'],
            ];

            $this->check->check_ajax_form($rules);
            $fdata = $this->form_data($rules);

            $fdata['freight_money'] = 0;
            $fdata['aid'] = $this->s_user->aid;
            $fdata['send_money'] = 0;
            $fdata['shipping_fee'] = 0;
            $fdata['use_flow'] = 0;
            $fdata['flow_free_money'] = 0;
            $fdata['service_radius'] = 0;
            $fdata['shop_imgs'] = '';
            $fdata['on_time'] = '';
            $fdata['prepare_time'] = 0;
            $fdata['auto_receiver'] = 0;
            $fdata['auto_printer'] = 0;
            $fdata['notice'] = '';
            $fdata['arrive_time'] = 0;
            $fdata['arrive_range'] = '';

        }

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        //判断门店，除高级版，其它版本只能添加五个门店
        if ($this->service->shop_limit != 0) {
            $shop_count = $wmShopDao->getCount(['aid' => $this->s_user->aid, 'is_delete' => 0]);
            if ($shop_count >= $this->service->shop_limit) {
                $this->json_do->set_error('004', '当前购买的版本门店数量已上限,如需增加,请联系客服');
            }

        }

        if ($shop_id = $wmShopDao->syncAutoCreate($fdata)) {
            //消息服务
            $this->_mnsShop();
            $this->json_do->set_msg('添加成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005');
        }

    }
    /**
     * 店铺编辑
     */
    public function edit($shop_id)
    {
        if (empty($shop_id) || !is_numeric($shop_id)) {
            $this->json_do->set_error('001');
        }

        $type = $this->input->post_get('type');
        if (!is_numeric($type)) {
            $this->json_do->set_error('001', '参数错误');
        }
        if (($type & 1 << 0) > 0) //存在外卖类型
        {
            $rules = [
                ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
                ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
                ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
                ['field' => 'send_money', 'label' => '起送价', 'rules' => 'trim|required|preg_key[PRICE]'],
                ['field' => 'shipping_fee', 'label' => '配送费', 'rules' => 'trim|required'],
                ['field' => 'use_flow', 'label' => '满减', 'rules' => 'trim|required|in_list[0,1]'],
                ['field' => 'flow_free_money', 'label' => '满减金额', 'rules' => 'trim|required|preg_key[PRICE]'],
                ['field' => 'service_radius', 'label' => '服务半径', 'rules' => 'trim|required|numeric'],
                ['field' => 'arrive_time', 'label' => '送达时间', 'rules' => 'trim|required|numeric'],
                ['field' => 'arrive_range', 'label' => '配送区域', 'rules' => 'trim'],
                ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
                ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
                ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
                ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
                ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim'],
                ['field' => 'shop_imgs', 'label' => '门店图片', 'rules' => 'trim'],
                ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
                ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required'],
                ['field' => 'on_time', 'label' => '营业时间', 'rules' => 'trim|required'],
                ['field' => 'prepare_time', 'label' => '备餐时间', 'rules' => 'trim|numeric'],
                ['field' => 'auto_receiver', 'label' => '自动接单', 'rules' => 'trim|required|in_list[0,1]'],
                ['field' => 'auto_printer', 'label' => '自动打单', 'rules' => 'trim|required|in_list[0,1]'],
                ['field' => 'notice', 'label' => '公告', 'rules' => 'trim'],
                ['field' => 'type', 'label' => '门店类型', 'rules' => 'trim|required|numeric'],
            ];

            $this->check->check_ajax_form($rules);
            $fdata = $this->form_data($rules);
            $fdata['use_flow'] = (int) $fdata['use_flow'];
            $fdata['auto_receiver'] = (int) $fdata['auto_receiver'];
            $fdata['auto_printer'] = (int) $fdata['auto_printer'];
        } else //不存在外卖类型
        {
            $rules = [
                ['field' => 'shop_name', 'label' => '门店名称', 'rules' => 'trim|required'],
                ['field' => 'shop_logo', 'label' => '门店logo', 'rules' => 'trim|required'],
                ['field' => 'contact', 'label' => '联系方式', 'rules' => 'trim|required'],
                ['field' => 'shop_state', 'label' => '门店所在地-省份', 'rules' => 'trim|required'],
                ['field' => 'shop_city', 'label' => '门店所在地-城市', 'rules' => 'trim|required'],
                ['field' => 'shop_district', 'label' => '门店所在地-区域', 'rules' => 'trim|required'],
                ['field' => 'shop_address', 'label' => '门店所在地-详细地址', 'rules' => 'trim|required'],
                ['field' => 'region', 'label' => '地址ID', 'rules' => 'trim'],
                ['field' => 'longitude', 'label' => '门店经度', 'rules' => 'trim|required'],
                ['field' => 'latitude', 'label' => '门店纬度', 'rules' => 'trim|required'],
                ['field' => 'type', 'label' => '门店类型', 'rules' => 'trim|required|numeric'],
            ];

            $this->check->check_ajax_form($rules);
            $fdata = $this->form_data($rules);

        }

        //判断是否总账号
        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        if ($wmShopDao->syncUpdateOne($fdata, ['id' => $shop_id, 'aid' => $this->s_user->aid]) !== false) {
            //清除缓存
            (new WmShopCache(['aid' => $this->s_user->aid, 'shop_id' => $shop_id]))->delete();
            //消息服务
            $this->_mnsShop();
            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '修改店铺信息失败');
        }

    }
    /**
     * 删除店铺
     */
    public function del()
    {

        $shop_id = $this->input->post('shop_id');
        if (!is_numeric($shop_id)) {
            $this->json_do->set_error('001', '参数错误');
        }
        $wmShopDao = WmShopDao::i($this->s_user->aid);


        if ($wmShopDao->update(['is_delete' => 1], ['id' => $shop_id]) === false) {
            $this->json_do->set_error('005', '删除失败');
        }
        $wmShopAccountDao = WmShopAccountDao::i($this->s_user->aid);
        $wmShopAccountDao->delete(['shop_id' => $shop_id]);
        //同时删除主库门店引用
        MainShopRefitemDao::i()->delete(['saas_id'=>SaasEnum::YD,'aid'=>$this->s_user->aid,'ext_shop_id'=>$shop_id]);
        //清除缓存
        (new WmShopCache(['aid' => $this->s_user->aid, 'shop_id' => $shop_id]))->delete();
        $this->json_do->set_msg('删除成功');
        $this->json_do->out_put();

    }
    /**
     * 店铺详情
     */
    public function info()
    {
        $shop_id = $this->input->get('shop_id');

        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }

        if (empty($shop_id) || !is_numeric($shop_id)) {
            $this->json_do->set_error('001');
        }
        $wmShopDao = WmShopDao::i($this->s_user->aid);

        $model = $wmShopDao->getOne(['id' => $shop_id, 'aid' => $this->s_user->aid]);
        if ($model) {
            $data['info'] = $model;
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '店铺不存在');
        }

    }
    /**
     * 修改门店状态
     */
    public function update_status()
    {
        $shop_id = $this->input->get('shop_id');

        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }

        if (empty($shop_id) || !is_numeric($shop_id)) {
            $this->json_do->set_error('001');
        }

        $rules = [
            ['field' => 'status', 'label' => '门店状态', 'rules' => 'trim|required|in_list[0,1]'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        if ($wmShopDao->update(['status' => $fdata['status']], ['aid' => $this->s_user->aid, 'id' => $shop_id]) === false) {
            $this->json_do->set_error('005');
        } else {
            $this->json_do->set_msg('修改门店状态成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 修改门店状态
     */
    public function update_meal_status()
    {
        $shop_id = $this->input->get('shop_id');

        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }

        if (empty($shop_id) || !is_numeric($shop_id)) {
            $this->json_do->set_error('001');
        }

        $rules = [
            ['field' => 'meal_status', 'label' => '门店状态', 'rules' => 'trim|required|in_list[0,1]'],
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        if ($wmShopDao->update(['meal_status' => $fdata['meal_status']], ['aid' => $this->s_user->aid, 'id' => $shop_id]) === false) {
            $this->json_do->set_error('005');
        } else {
            $this->json_do->set_msg('修改门店状态成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 活动详情
     */
    public function promotion_info()
    {
        $shop_id = intval($this->input->get('shop_id'));
        if (!$this->is_zongbu) {
            $shop_id = $this->currentShopId;
        }

        if (empty($shop_id)) {
            $this->json_do->set_error('001', '无记录');
        }

        $time = time();
        $model = WmPromotionDao::i($this->s_user->aid)->getOne("aid={$this->s_user->aid} AND shop_id={$shop_id} AND type=2 AND status=2 AND start_time < {$time} AND end_time > {$time}");

        if ($model) {
            $model->setting = array_sort(json_decode($model->setting), 'price');
        }
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $m_shop = $wmShopDao->getOne(['id' => $shop_id], 'id,aid,is_newbie_coupon,newbie_coupon');

        $data['promotion'] = $model;
        $data['shop'] = $m_shop;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 店铺分类列表
     */
    public function shop_cate_list()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $shop_list = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'is_delete' => 0], 'id,shop_name,status', 'id desc');

        $shop_ids = trim(implode(',', array_column($shop_list, 'id')), ',');
        if (empty($shop_ids)) {
            $shop_ids = '0';
        }
        $wmCateDao = WmCateDao::i($this->s_user->aid);
        $cate_list = $wmCateDao->getAllArray("aid={$this->s_user->aid} AND shop_id in ({$shop_ids})");
        //组合数据格式
        foreach ($shop_list as $key => $shop) {
            $shop_list[$key]['cate_list'] = array_find_list($cate_list, 'shop_id', $shop['id']);
        }

        $this->json_do->set_data($shop_list);
        $this->json_do->out_put();
    }
    /**
     * 更新配送开关
     */
    public function update_shpping_switch()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|numeric|required'],
            ['field' => 'value', 'label' => '配送开关', 'rules' => 'trim|numeric|required|in_list[1,2,3]'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        if (!$this->is_zongbu) {
            $fdata['shop_id'] = $this->currentShopId;
        }
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        if ($wmShopDao->update(['shpping_switch' => $fdata['value']], ['id' => $fdata['shop_id']]) !== false) {
            $this->json_do->set_data('保存成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_data('保存失败');
            $this->json_do->out_put();
        }
    }

    /**
     * 修改店铺通知
     */
    private function _mnsShop()
    {
        //消息通知
        try {
            $input['visit_id'] = $this->s_user->visit_id;
            $input['aid'] = $this->s_user->aid;
            $event_bll = new event_bll;
            return $event_bll->mnsShop($input);
        } catch (Exception $e) {
            return false;
        }

    }
}

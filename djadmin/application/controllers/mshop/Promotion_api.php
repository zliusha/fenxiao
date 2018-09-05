<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 促销活动管理API
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\WmPromotionDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmStoreGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
class Promotion_api extends wm_service_controller
{
    /**
     * 活动列表
     */
    public function index($type = 1)
    {
        $wm_promotion_query_bll = new wm_promotion_query_bll();
        $list = $wm_promotion_query_bll->query(['aid' => $this->s_user->aid, 'type' => $type])->get();

        $this->json_do->set_data($list);
        $this->json_do->out_put();
    }

    /**
     * 活动详情
     */
    public function detail()
    {
        $id = (int) $this->input->post_get('id');

        $wm_promotion_query_bll = new wm_promotion_query_bll();
        $list = $wm_promotion_query_bll->query(['aid' => $this->s_user->aid, 'id' => $id])->get();

        $this->json_do->set_data($list['rows'][0]);
        $this->json_do->out_put();
    }

    /**
     * 创建活动
     */
    public function create()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|numeric|required'],
            ['field' => 'title', 'label' => '活动标题', 'rules' => 'trim|required'],
            ['field' => 'start_time', 'label' => '活动开始时间', 'rules' => 'trim|required'],
            ['field' => 'end_time', 'label' => '活动结束时间', 'rules' => 'trim|required'],
            ['field' => 'type', 'label' => '活动类型', 'rules' => 'trim|required|numeric|in_list[1,2]'],
            ['field' => 'discount_type', 'label' => '折扣类型', 'rules' => 'trim|numeric|in_list[1,2]'],
            ['field' => 'limit_times', 'label' => '限购类型', 'rules' => 'numeric|required|in_list[0,1]'],
            ['field' => 'limit_buy', 'label' => '限购类型', 'rules' => 'numeric|required'],
            ['field' => 'setting', 'label' => '活动设置', 'rules' => 'trim|required'],
            ['field' => 'goods_arr', 'label' => '活动商品ID', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['discount_type'] = (int) $fdata['discount_type'];
        $fdata['limit_times'] = (int) $fdata['limit_times'];
        $fdata['limit_buy'] = (int) $fdata['limit_buy'];
        $fdata['start_time'] = strtotime($fdata['start_time']);
        $fdata['end_time'] = strtotime($fdata['end_time']);
        $fdata['aid'] = $this->s_user->aid;
        $fdata['status'] = 1;
        if($fdata['type']==1 && empty($fdata['goods_arr']))
        {
            $this->json_do->set_error('004', '请选择商品');
        }

        $wmPromotionDao = WmPromotionDao::i($this->s_user->aid);
        $id = $wmPromotionDao->create($fdata);

        $wmShardDb = WmShardDb::i($this->s_user->aid);

        if ($fdata['shop_id'] == 0) {
            if($fdata['type']==1)//1限时折扣
            {
                // 全部门店更新逻辑
                $sql = "UPDATE {$wmShardDb->tables['wm_store_goods']} SET `promo_id` = {$id} WHERE aid={$this->s_user->aid} AND `id` IN ({$fdata['goods_arr']});";
                $wmShardDb->query($sql);
                $sql = "UPDATE {$wmShardDb->tables['wm_goods']}  SET `promo_id` = {$id} WHERE aid={$this->s_user->aid} AND `store_goods_id` IN ({$fdata['goods_arr']}) AND `promo_id` = 0;";
                $wmShardDb->query($sql);
            }

        } else {
            if($fdata['type']==1)//1限时折扣
            {
                // 单个门店更新逻辑
                $sql = "UPDATE {$wmShardDb->tables['wm_goods']} SET `promo_id` = {$id} WHERE  aid={$this->s_user->aid} AND id IN ({$fdata['goods_arr']});";
                $wmShardDb->query($sql);
            }
        }

        if ($id) {
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '保存失败');
        }
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $rule = [
            ['field' => 'id', 'label' => '活动ID', 'rules' => 'trim|numeric|required'],
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|numeric|required'],
            ['field' => 'title', 'label' => '活动标题', 'rules' => 'trim|required'],
            ['field' => 'start_time', 'label' => '活动开始时间', 'rules' => 'trim|required'],
            ['field' => 'end_time', 'label' => '活动结束时间', 'rules' => 'trim|required'],
            ['field' => 'type', 'label' => '活动类型', 'rules' => 'trim|required|numeric|in_list[1,2]'],
            ['field' => 'discount_type', 'label' => '折扣类型', 'rules' => 'trim|numeric|in_list[1,2]'],
            ['field' => 'limit_times', 'label' => '限购类型', 'rules' => 'numeric|required|in_list[0,1]'],
            ['field' => 'limit_buy', 'label' => '限购类型', 'rules' => 'numeric|required'],
            ['field' => 'setting', 'label' => '活动设置', 'rules' => 'trim|required'],
            ['field' => 'goods_arr', 'label' => '活动商品ID', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $id = $fdata['id'];
        unset($fdata['id']);

        $fdata['discount_type'] = (int) $fdata['discount_type'];
        $fdata['limit_times'] = (int) $fdata['limit_times'];
        $fdata['limit_buy'] = (int) $fdata['limit_buy'];
        $fdata['start_time'] = strtotime($fdata['start_time']);
        $fdata['end_time'] = strtotime($fdata['end_time']);
        $fdata['update_time'] = time();

        if($fdata['type']==1 && empty($fdata['goods_arr']))
        {
            $this->json_do->set_error('004', '请选择商品');
        }

        $wmShardDb = WmShardDb::i($this->s_user->aid);

        if ($fdata['shop_id'] == 0) {
            if($fdata['type']==1)//1限时折扣
            {
                // 全部门店更新逻辑
                // 清空总商品库
                $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
                $wmStoreGoodsDao->update(['promo_id' => 0], ['promo_id' => $id, 'aid'=>$this->s_user->aid]);
                // 清空门店商品
                $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
                $wmGoodsDao->update(['promo_id' => 0], ['promo_id' => $id, 'aid'=>$this->s_user->aid]);

                $sql = "UPDATE `{$wmShardDb->tables['wm_store_goods']}` SET `promo_id` = {$id} WHERE aid={$this->s_user->aid} AND `id` IN ({$fdata['goods_arr']});";
                $wmShardDb->query($sql);
                $sql = "UPDATE `{$wmShardDb->tables['wm_goods']}` SET `promo_id` = {$id} WHERE aid={$this->s_user->aid} AND `store_goods_id` IN ({$fdata['goods_arr']}) AND `promo_id` = 0;";
                $wmShardDb->query($sql);
            }

        } else {
            if($fdata['type']==1)//1限时折扣
            {
                // 单个门店更新逻辑
                $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
                $wmGoodsDao->update(['promo_id' => 0], ['promo_id' => $id, 'aid'=>$this->s_user->aid]);
                $sql = "UPDATE `{$wmShardDb->tables['wm_goods']}` SET promo_id = {$id} WHERE aid={$this->s_user->aid} AND id IN ({$fdata['goods_arr']});";
                $wmShardDb->query($sql);
            }
        }

        $wmPromotionDao = WmPromotionDao::i($this->s_user->aid);
        $result = $wmPromotionDao->update($fdata, ['id' => $id]);

        if ($result) {
            $this->json_do->set_msg('编辑成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '编辑失败');
        }
    }

    /**
     * 结束活动
     */
    public function close()
    {
        $rule = [
            ['field' => 'id', 'label' => '活动ID', 'rules' => 'trim|numeric|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $id = $fdata['id'];
        unset($fdata['id']);

        $wmPromotionDao = WmPromotionDao::i($this->s_user->aid);
        $row = $wmPromotionDao->getOne(['id' => $id], 'status');

        // 清理优惠信息    // 清理商品库优惠活动
        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);
        
        $wmGoodsDao->update(['promo_id' => 0], ['promo_id' => $id, 'aid'=>$this->s_user->aid]);
        // 清理商品库优惠活动
        $wmStoreGoodsDao->update(['promo_id' => 0], ['promo_id' => $id, 'aid'=>$this->s_user->aid]);

        $result = $wmPromotionDao->update(['status' => 3], ['id' => $id]);

        if ($result) {
            $this->json_do->set_msg('结束成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '结束失败');
        }
    }

    /**
     * 删除活动
     */
    public function delete()
    {
        $rule = [
            ['field' => 'id', 'label' => '活动ID', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $ids = explode(',', $fdata['id']);
        unset($fdata['id']);

        $wmPromotionDao = WmPromotionDao::i($this->s_user->aid);
        $result = $wmPromotionDao->inDelete($ids, 'id');

        // 清理优惠信息
        $wmGoodsDao = WmGoodsDao::i($this->s_user->aid);
        // 清理商品库优惠活动
        $wmStoreGoodsDao = WmStoreGoodsDao::i($this->s_user->aid);

        foreach ($ids as $id) {
            $wmGoodsDao->update(['promo_id' => 0], ['promo_id' => $id, 'aid'=>$this->s_user->aid]);
            // 清理商品库优惠活动
            $wmStoreGoodsDao->update(['promo_id' => 0], ['promo_id' => $id, 'aid'=>$this->s_user->aid]);
        }

        if ($result) {
            $this->json_do->set_msg('删除成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '删除失败');
        }
    }

    /**
     * 加载新用户优惠设置
     */
    public function loadNewbieCoupon()
    {
        $shop_id = $this->input->get('shop_id');
        if (empty($shop_id) || !is_numeric($shop_id)) {
            $this->json_do->set_error('001');
        }

        $wmShopDao = WmShopDao::i($this->s_user->aid);

        $model = $wmShopDao->getOne(['id' => $shop_id, 'aid' => $this->s_user->aid], 'id as shop_id,aid,shop_name,is_newbie_coupon,newbie_coupon');
        if ($model) {
            $this->json_do->set_data($model);
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '配置信息不存在');
        }

    }

    /**
     * 修改新用户优惠设置
     */
    public function setNewbieCoupon()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '门店ID', 'rules' => 'trim|required'],
            ['field' => 'is_newbie_coupon', 'label' => '是否开启新人优惠', 'rules' => 'trim|numeric|required'],
            ['field' => 'newbie_coupon', 'label' => '新人优惠金额', 'rules' => 'trim|numeric|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $shop_id = $fdata['shop_id'];
        unset($fdata['shop_id']);

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 全部门店设置
        if ($shop_id == 0) {

            $result = $wmShopDao->update($fdata, ['aid' => $this->s_user->aid]);
        } else {
            $shop_ids = explode(',', $shop_id);
            foreach ($shop_ids as $id) {
                $result = $wmShopDao->update($fdata, ['id' => $id]);
            }
        }

        if ($result !== false) {
            $this->json_do->set_msg('保存成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '保存失败');
        }
    }

}

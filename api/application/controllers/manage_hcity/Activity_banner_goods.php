<?php

/**
 * 活动横幅商品
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/8/3
 * Time: 13:37
 */
use Service\Bll\Hcity\ActivityBannerGoodsBll;
use Service\Exceptions\Exception;

class Activity_banner_goods extends hcity_manage_controller
{
    /**
     * 列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'banner_id', 'label' => '横幅列表id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $data = (new ActivityBannerGoodsBll())->activityBannerGoodsList($fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 设置活动横幅商品
     * @author yize<yize@iyenei.com>
     */
    public function set()
    {
        $rule = [
            ['field' => 'banner_id', 'label' => '横幅列表id', 'rules' => 'integer|required'],
            ['field' => 'goods_list_id', 'label' => '商品列表id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ActivityBannerGoodsBll())->BannerSetGoods($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }


    /**
     * 批量设置活动横幅商品
     * @author yize<yize@iyenei.com>
     */
    public function set_batch()
    {
        $rule = [
            ['field' => 'banner_id', 'label' => '横幅列表id', 'rules' => 'integer|required'],
            ['field' => 'goods_id', 'label' => '商品列表id', 'rules' => 'trim|required'],
            ['field' => 'aid', 'label' => 'aid', 'rules' => 'trim|required'],
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ActivityBannerGoodsBll())->BannerSetGoodsBatch($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }
    

    /**
     * 移除活动横幅商品
     * @author yize<yize@iyenei.com>
     */
    public function remove()
    {
        $rule = [
            ['field' => 'id', 'label' => '列表id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ActivityBannerGoodsBll())->BannerRemoveGoods($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 设置横幅商品排序
     * @author yize<yize@iyenei.com>
     */
    public function set_sort()
    {
        $rule = [
            ['field' => 'id', 'label' => '列表id', 'rules' => 'integer|required'],
            ['field' => 'sort', 'label' => '排序', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ActivityBannerGoodsBll())->BannerGoodsSetSort($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }










}
<?php

/**
 * 商圈商品
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/14
 * Time: 上午15:40
 */
use Service\Bll\Hcity\GoodsKzBll;
use Service\Exceptions\Exception;

class Goods_kz extends hcity_manage_controller
{
    /**
     * 获取商圈商品列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '门店分类id', 'rules' => 'integer'],
            ['field' => 'hcity_status', 'label' => '商圈状态', 'rules' => 'integer|in_list[0,1]'],
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $data = (new GoodsKzBll())->goodsKzList($sUser,$fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 商圈商品详情
     * @author yize<yize@iyenei.com>
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '商圈商品列表id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data=(new GoodsKzBll())->goodsKzDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }


    /**
     * 商圈商品上下架
     * @author yize<yize@iyenei.com>
     */
    public function edit_hcity_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '商圈商品列表id', 'rules' => 'integer|required'],
            ['field' => 'hcity_status', 'label' => '审核状态', 'rules' => 'integer|required|in_list[0,1]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new GoodsKzBll())->editHcityStatus($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }  

    /**
     * 福利池商品详情
     * @author yize<yize@iyenei.com>
     */
    public function free_detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '福利池id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data=(new GoodsKzBll())->freeDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 福利池消费详情商品
     * @author yize<yize@iyenei.com>
     */
    public function free_goods_detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '商品列表id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new GoodsKzBll())->freeGoodsKzOrder($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 福利池商品上下架
     * @author feiying<feiying@iyenei.com>
     */
    public function edit_welfare_status()
    {
        $rule = [
            ['field' => 'id', 'label' => '商圈商品列表id', 'rules' => 'integer|required'],
            ['field' => 'welfare_status', 'label' => '审核状态', 'rules' => 'integer|required|in_list[0,1]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new GoodsKzBll())->editWelfareStatus($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

     /**
     * 福利池商品
     * @author yize<yize@iyenei.com>
     */
    public function free_goods_list()
    {
        $rule = [
            ['field' => 'title', 'label' => '商品名称', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地区', 'rules' => 'trim'],
            ['field' => 'welfare_status', 'label' => '福利池状态', 'rules' => 'trim|in_list[0,1]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        try {
            $data = (new GoodsKzBll())->freeGoodsKzList($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }


}
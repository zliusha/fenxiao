<?php

/**
 * 首页门店推荐
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/18
 * Time: 下午13:55
 */
use Service\Bll\Hcity\HomepageShopBll;

class Home_page_shop extends hcity_manage_controller
{

    /**
     * 添加首页门店推荐
     * @author yize<yize@iyenei.com>
     */
    public function add()
    {
        $rules = [
            ['field' => 'shop_ids', 'label' => '门店ids', 'rules' => 'trim|required'],
            ['field' => 'region', 'label' => '地理位置  存id,格式为省-市-区', 'rules' => 'trim|required'],
            ['field' => 'region_name', 'label' => '地理位置', 'rules' => 'trim|required'],
            ['field' => 'pid', 'label' => '上级id', 'rules' => 'integer|trim'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $data = (new HomePageShopBll())->addShop($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 修改首页门店推荐
     * @author yize<yize@iyenei.com>
     */
    public function edit()
    {
        $rules = [
            ['field' => 'shop_ids', 'label' => '门店ids', 'rules' => 'trim|required'],
            ['field' => 'id', 'label' => '门店列表id', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $data = (new HomePageShopBll())->editShop($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 首页门店列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rules = [
            ['field' => 'region', 'label' => '地理位置  存id,格式为省-市-区', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $data = (new HomePageShopBll())->shopList($fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 删除门店推荐
     * @author yize<yize@iyenei.com>
     */
    public function delete()
    {
        $rules = [
            ['field' => 'id', 'label' => '', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $data = (new HomePageShopBll())->shopDelete($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }


    /**
     * 门店推荐详情
     * @author yize<yize@iyenei.com>
     */
    public function detail()
    {
        $rules = [
            ['field' => 'id', 'label' => '', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $data = (new HomePageShopBll())->shopDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 门店子类推荐详情
     * @author yize<yize@iyenei.com>
     */
    public function children_detail()
    {
        $rules = [
            ['field' => 'id', 'label' => '', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        try {
            $data = (new HomePageShopBll())->shopChildrenDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

}
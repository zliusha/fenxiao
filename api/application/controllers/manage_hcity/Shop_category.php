<?php

/**
 * 门店分类
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午5:23
 */
use Service\Bll\Hcity\ShopBll;

class Shop_category extends hcity_manage_controller
{
    /**
     * 获取所有门店分类
     * @author ahe<ahe@iyenei.com>
     */
    public function get_list()
    {
        $data = (new ShopBll())->getAllShopCategory();
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 创建门店分类
     * @author ahe<ahe@iyenei.com>
     */
    public function create()
    {
        $rule = [
            ['field' => 'category_name', 'label' => '分类名称', 'rules' => 'trim|required'],
            ['field' => 'img', 'label' => '分类图标', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $ret = (new ShopBll())->createShopCategory($data['category_name'], $data['img']);
        if ($ret > 0) {
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '新增错误');
        }
    }

    /**
     * 删除门店分类
     * @author ahe<ahe@iyenei.com>
     */
    public function delete()
    {
        $rule = [
            ['field' => 'category_id', 'label' => '分类id', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        try {
            $ret = (new ShopBll())->deleteShopCategory($data['category_id']);
            if ($ret > 0) {
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '删除错误');
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 编辑门店分类
     * @author ahe<ahe@iyenei.com>
     */
    public function edit()
    {
        $rule = [
            ['field' => 'category_id', 'label' => '分类id', 'rules' => 'trim|required'],
            ['field' => 'category_name', 'label' => '分类名称', 'rules' => 'trim'],
            ['field' => 'img', 'label' => '分类图标', 'rules' => 'trim'],
            ['field' => 'sort', 'label' => '排序值', 'rules' => 'integer'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        try {
            (new ShopBll())->editShopCategory($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }
}
<?php

/**
 * 首页门店分类推荐
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/18
 * Time: 上午10:05
 */
use Service\Bll\Hcity\HomepageShopCateBll;

class Home_page_shop_cate extends hcity_manage_controller
{

    /**
     * 设置首页分类
     * @author yize<yize@iyenei.com>
     */
    public function set_shop_cate()
    {
        $rules = [
            ['field' => 'cate_ids', 'label' => '多个分类id', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $sUser=$this->s_user;
        try {
            $data = (new HomePageShopCateBll())->setShopCate($sUser,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }

    /**
     * 首页分类列表
     * @author yize<yize@iyenei.com>
     */
    public function cate_list()
    {
        $sUser=$this->s_user;
        $data = (new HomePageShopCateBll())->shopCateList($sUser);
        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }


}
<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/11
 * Time: 下午12:00
 */
use Service\Bll\Hcity\ShopBll;

class Category extends xhcity_controller
{
    /**
     * 获取门店全部分类
     * @author ahe<ahe@iyenei.com>
     */
    public function get_list()
    {
        $data = (new ShopBll())->getAllShopCategory();
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
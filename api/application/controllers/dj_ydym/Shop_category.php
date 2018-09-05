<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/12
 * Time: 下午3:41
 */
use Service\Bll\Hcity\ShopBll;

class Shop_category extends dj_ydym_controller
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
}
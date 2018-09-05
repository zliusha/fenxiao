<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/10
 * Time: 上午11:33
 */
use Service\Cache\Main\AreaCache;
use Service\Cache\Main\AreaCityCache;
class Area extends auth_controller
{
    /**
     * 得到所有省市区
     * @return [type] [description]
     */
    public function get_all()
    {
        $rows = (new AreaCache())->getDataASNX();
        $data['items'] = $rows;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 城市列表
     * @return [type] [description]
     * @author binghe 2018-08-14
     */
    public function get_city_all()
    {
        $rows = (new AreaCityCache())->getDataASNX();
        $data['items'] = $rows;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
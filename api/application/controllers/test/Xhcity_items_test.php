<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/18
 * Time: 11:29
 */
class Xhcity_items_test extends Xhcity_test_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->login();
    }

    public function index()
    {
        $params['lat'] = 30.28;
        $params['long'] = 120.15;
        $params['city_code'] = 330106;

        $url = API_URL . 'xhcity/items/index';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function detail()
    {
        $params['aid'] = 1226;
        $params['goods_id'] = 12;

        $url = API_URL . 'xhcity/items/detail';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function shop_goods_list()
    {
        $params['aid'] = 1226;
        $params['shop_id'] = 40;

        $url = API_URL . 'xhcity/items/shop_goods_list';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function welfare_list()
    {
        $params['lat'] = 30.28;
        $params['long'] = 120.15;

        $url = API_URL . 'xhcity/items/welfare_list';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function get_popular_goods()
    {
        $params['city_code'] = 330106;

        $url = API_URL . 'xhcity/items/get_popular_goods';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function get_ydym_shop_goods_list()
    {
        $params['aid'] = 1226;
        $params['shop_id'] = 1;

        $url = API_URL . 'xhcity/items/get_ydym_shop_goods_list';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }
}
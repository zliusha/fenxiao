<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/18
 * Time: 11:29
 */
class Xhcity_order_test extends Xhcity_test_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->login();
    }

    public function pre_order()
    {
        $params['aid'] = 1226;
        $params['goods_id'] = 12;
        $params['source_type'] = 1;
        $params['shop_id'] = 1;
        $params['items'] = '[{"goods_id":12,"sku_id":7,"num":1}]';

        $url = API_URL . 'xhcity/order/pre_order';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function detail()
    {
        $params['aid'] = 1226;
        $params['tid'] = 18072519588917;

        $url = API_URL . 'xhcity/order/detail';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function create()
    {
        $params['aid'] = 1226;
        $params['goods_id'] = 12;
        $params['source_type'] = 1;
        $params['shop_id'] = 1;
        $params['pay_type'] = 2;
        $params['items'] = '[{"goods_id":12,"sku_id":7,"num":1}]';

        $url = API_URL . 'xhcity/Order/create';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function cancel()
    {
        $params['aid'] = 1226;
        $params['tid'] = 18072519451953;


        $url = API_URL . 'xhcity/Order/cancel';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function get_list()
    {
        $params['aid'] = 1226;
        $params['tid'] = 18072519451953;


        $url = API_URL . 'xhcity/Order/get_list';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function hx_code_list()
    {
        $params['aid'] = 1226;
        $params['tid'] = 18072519588917;

        $url = API_URL . 'xhcity/order/hx_code_list';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

}
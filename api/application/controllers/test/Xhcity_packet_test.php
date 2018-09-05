<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/18
 * Time: 11:29
 */
class Xhcity_packet_test extends Xhcity_test_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->login();
    }

    public function detail()
    {
        $params['aid'] = 1226;
        $params['shop_id'] = 1;

        $url = API_URL . 'xhcity/packet/detail';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }


    public function get_list()
    {
        $params['aid'] = 1226;
        $params['tid'] = 18072519451953;
        $url = API_URL . 'xhcity/packet/get_list';
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
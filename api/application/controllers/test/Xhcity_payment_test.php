<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/25
 * Time: 19:56
 */
class Xhcity_payment_test extends Xhcity_test_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->login();
    }

    public function pay_order()
    {
        $params['aid'] = 1226;
        $params['tid'] = 18072519575693;

        $url = API_URL . 'xhcity/payment/pay_order';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function test()
    {
        $params['aid'] = 1226;
        $params['tid'] = 18072519575693;

        $url = API_URL . 'xhcity/payment/test';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function nativePay()
    {
        $shop_id = 1;
        $tid = '18072814360494';

        $payBll = new \Service\Bll\Hcity\PayBll();
        $result = $payBll->wxNativePay($shop_id, $tid);

        var_dump($result);
    }
}
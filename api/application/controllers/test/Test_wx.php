<?php
/**
 * @Author: binghe
 * @Date:   2018-05-08 11:30:40
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-05-09 14:10:59
 */
/**
* test wx
*/
class Test_wx extends base_controller
{
    
    public function micropay()
    {
        $auth_code = $this->input->get('code');
        if(empty($auth_code))
            exit('please input the user auth code ');
        ci_wxpay::load('micro',1226);
        $input = new WxPayMicroPay();
        $input->SetAuth_code($auth_code);
        $input->SetBody("刷卡测试样例-支付");
        $input->SetTotal_fee("1");
        // $input->SetOut_trade_no(date("YmdHis").rand(10000,99999));
        $input->SetOut_trade_no('T05091234567891');
        $microPay = new MicroPay();
        var_dump($microPay->pay($input));
    }
}
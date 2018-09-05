<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/18
 * Time: 11:29
 */
class Xhcity_company_test extends Xhcity_test_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->login();
    }

    public function test()
    {
        //erp注册公司，并生成主账号
        $erp_sdk = new erp_sdk();
        $params[]=15155115010;
        $params[]=12345678;
        $ip=get_ip();
        $params[]=['ip'=>$ip,'user_name'=>'测试号码'];
        $res=$erp_sdk->register($params);
        var_dump($res);echo '<br>';

        $_params[]=15155115010;
        $res = $erp_sdk->getUserByPhone($_params);
        var_dump($res);
    }
    public function get_shop_list()
    {
        $params['aid'] = 1226;
        $params['shop_id'] = 40;

        $url = API_URL . 'xhcity/company/get_shop_list';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

}
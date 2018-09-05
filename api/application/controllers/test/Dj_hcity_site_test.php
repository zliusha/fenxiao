<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/1
 * Time: 16:57
 */
use Service\Traits\HttpTrait;

class Dj_hcity_site_test extends dj_hcity_site_controller
{
    public function send_code()
    {
        $params['mobile'] = 15155115089;
        $params['type'] = 'normal';

        $url = API_URL . 'dj_hcity_site/mobile/send_code';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function company_register()
    {
        $params['mobile'] = 14055115095;
        $params['username'] = '测试';
        $params['password'] = 12345678;
        $params['repassword'] = 12345678;
        $params['mobile_code'] = 123456;
        $params['address'] = '测试地址';
        $params['region'] = '3331000-333106';

        $url = API_URL . 'dj_hcity_site/passport/company_register';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }

    public function proxy_apply()
    {
        $params['mobile'] = 15155115089;
        $params['username'] = '测试';
        $params['address'] = '测试地址';
        $params['region'] = '3331000-333106';

        $url = API_URL . 'dj_hcity_site/passport/proxy_apply';
        $result = $this->request($url, $params);
        print_r(json_encode($result));
    }


    use HttpTrait;

    /*
    模拟请求 返回array
     */
    public function request($url, $params = [], $method = 'POST')
    {

        $options = ['form_params' => $params];
        $http = $this->getHttp();
        $response = $http->request($url, $method, $options);
        return $http->parseJSON($response->getBody());
    }

}
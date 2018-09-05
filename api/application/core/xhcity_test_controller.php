<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/18
 * Time: 11:22
 */
class xhcity_test_controller extends auth_test_controller
{

    public $app_id = 'wxd10ca7d2f97d7a12';
    public $access_token = '';
    function __construct()
    {
        parent::__construct('ydb_hcity_xcx');
        $headers['Appid'] = $this->app_id;
        $this->setExtHeaders($headers);
    }
    /**
     * 小程序登录
     * @return [type] [description]
     */
    public function login()
    {
        $params['t'] = time();
        $url = API_URL.'xhcity/passport/test_login';
        $result = $this->request($url,$params);
        $this->access_token = $result['data']['access_token']['value'];
        $this->addExtHeaders(['Access-Token'=>$this->access_token]);
    }
}
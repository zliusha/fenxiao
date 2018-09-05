<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 16:14:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-17 15:01:36
 */
use Service\Traits\HttpTrait;

/**
 * auth testing
 */
class auth_test_controller extends base_controller
{
    use HttpTrait;
    public $source;
    public $extHeaders = [];
    public function __construct($source)
    {
        parent::__construct();
        $this->source = $source;
    }
    public function setExtHeaders($headers = [])
    {
        $this->extHeaders = $headers;
    }
    public function addExtHeaders($headers = [])
    {
        $this->extHeaders = array_merge($this->extHeaders,$headers);
    }
    /*
    模拟请求 返回array
     */
    public function request($url, $params = [], $method = 'POST')
    {
        $time = time();
        $headers = $this->extHeaders;
        $headers['Source'] = $this->source;
        $headers['Sign-Time'] = $time;
        $secretKey = $this->source . $time;
        $headers['Sign'] = simple_auth::getSign($params, $secretKey);
        // log_message('error',__METHOD__.'-'.json_encode($headers));
        $options = ['form_params' => $params, 'headers' => $headers];
        $http = $this->getHttp();
        $response = $http->request($url, $method, $options);
        return $http->parseJSON($response->getBody());
    }
}

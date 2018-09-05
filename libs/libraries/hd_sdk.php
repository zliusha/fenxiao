<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/4
 * Time: 14:23
 */
use Service\Traits\HttpTrait;

/**
 * hd_sdk
 */
class hd_sdk
{
    public $inc;
    use HttpTrait;
    public function __construct()
    {
        $this->inc = &inc_config('hd');
    }

    /**
     * 获取订阅列表
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getHdList($params=[])
    {
        $url = $this->inc['url'].'hd/admin/Api/get_game_list';

        return $this->request($params, $url, 'POST');
    }

    /**
     * 错误时有异常输出
     * @param array $params
     * @return [type] [description]
     */
    public function request($data = [] ,$url, $method='POST')
    {
        $time = time();
        $data['appid'] = $this->inc['appid'];
        $data['timestamp'] = $time;
        $data['keysecret'] = $this->inc['keysecret'];
        $data['access_key'] = $this->inc['access_key'];
        $data['sign'] = $this->_sign($data, $this->inc['access_key']);
        $options  =['query' => $data];
        $http = $this->getHttp();
        $http->setDefaultOptions();
        $response = $http->request($url, $method, $options);

        $json_result = $http->parseJSON($response);

        $this->_valid_result($json_result);
        return $json_result['data'];

    }

    /**
     * @param $data
     * @return string
     */
    private function _sign($data, $access_key='a431adb20dbe7469ffec899854240250')
    {
        unset($data['sign']);
        //排序所有参数
        ksort($data);
        $signStr = $access_key;

        //组织签名规则
        foreach($data as $temp => $val)
        {
            $signStr .= $temp.$val;
        }
        $signStr = $signStr.$data['keysecret'];
        $md5signStr = strtoupper(md5($signStr));
        return $md5signStr;
    }
    /**
     * 验证结果,中断
     */
    private function _valid_result($json_result)
    {

        $err_msg = 'hdyx接口请求异常';
        if (isset($json_result['result']) && $json_result['result'] == true) {
            return true;
        } else {
            if (isset($json_result['message'])) {
                $err_msg = $json_result['message'];
            }

            throw new Exception($err_msg);

        }
    }

}
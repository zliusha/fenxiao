<?php
/**
 * @Author: binghe
 * @Date:   2017-08-23 16:56:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-08-25 17:02:56
 */
/**
* 验证
*/
class auth 
{
    private $accessKey;
    private $secretKey;

    public function __construct($accessKey, $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }
    /**
     * 数据签名
     * @param  array $params post请求参数集
     * @return string         [description]
     */
    public function sign($params=[])
    {   
        if(isset($params['sign']))
            unset($params['sign']);

        ksort($params, SORT_STRING);
        $kvs = [];
        foreach ($params as $key => $value) {
            $kvs[] = $key . '=' . $value;
        }
        $data = implode('', $kvs);
        $sign = md5($data . $this->secretKey);
        return $this->accessKey.':'.$sign;
    }
    /**
     * 验证签名
     * @param  string $sign   [description]
     * @param  array $params [description]
     * @return bool
     */
    public function validSign($sign,$params)
    {
        return $sign===self::sign($params);
    }
}
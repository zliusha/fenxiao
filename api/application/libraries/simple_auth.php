<?php
/**
 * @Author: binghe
 * @Date:   2017-12-15 17:32:16
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-12-15 17:40:22
 */ 
/**
* simple auth 简易加密(此简易非彼简易,你能破解我请你吃饭)
*/
class simple_auth
{
    
    /**
     * 得到签名, 参数名不能为sign
     * @param  [type] $params    [description]
     * @param  [type] $secretKey [description]
     * @return [type]            [description]
     */
    public static function getSign($params,$secretKey)
    {
        if(isset($params['sign']))
            unset($params['sign']);

        ksort($params, SORT_STRING);
        $kvs = [];
        foreach ($params as $key => $value) {
            $kvs[] = $key . '=' . $value;
        }
        $data = implode('', $kvs);
        $sign = md5($data . $secretKey);
        return $sign;
    }
    /**
     * 验证签名
     * @param  [type] $sign   [description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public static function validSign($sign,$params)
    {
         return $sign===self::sign($params);
    }
}
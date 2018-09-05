<?php
/**
 * @Author: binghe
 * @Date:   2018-01-11 11:19:09
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-08 17:37:09
 */

use Service\Traits\HttpTrait;

/**
 * erp_sdk
 */
class scrm_sdk
{
    public $inc;
    use HttpTrait;
    public function __construct($incName = null)
    {
        if(empty($incName))
            $this->inc = get_scrm_config();
        else
            $this->inc = inc_config($incName);
    }
    /**
     * 获取小程充授权信息 $input visit_id,appid || visit_id,type
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getXcxInfo($input)
    {
        $url = 'openapi/weChatAPP/appInfo';
        // $params['visit_id']=$input['visit_id'];
        // $params['appid'] = $input['appid'];
        return $this->request($url,$input);

    }
    /**
     * 获取公众号信息 $input [visit_id]
     */
    public function getGzhInfo($input)
    {
        $url = 'openapi/weChatAPP/gzhInfo';
        return $this->request($url,$input);
    }
    /**
     * 通过
     * @return [type] [description]
     */
    public function getVisitIdByAppid($input)
    {
        $url = 'openapi/weChat/getGzhUser';
        return $this->request($url,$input);
    }
    /**
     * 获取云店宝Menu input 必填token
     * @param  [type] $input [description]
     * @return [type]        [description]
     */
    public function getMenu($input)
    {
        $url = $this->inc['url'].'unapi/Menu/ydb';
        $params['token'] = $input['token'];
        $http = $this->getHttp();
        $response = $http->get($url,$params);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 错误时有异常输出
     * @param array $params
     * @return [type] [description]
     */
    public function request($url, $params = [])
    {
        $url = $this->inc['url'] . $url;
        $params['appkey']=$this->inc['appkey'];
        $params['timestamp']=date('Y-m-d H:i:s');
        $params['sign'] = $this->_generate_sign($params);
        $http = $this->getHttp();
        $response = $http->post($url, $params);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;

    }
    /**
     * 生成数据的签名
     *
     * @param $params
     * @return string
     * @author jiaozi<jiaozi@iyenei.com>
     *
     */
    private function _generate_sign($params)
    {
        unset($params['sign']);
        ksort($params);
        $sign = '';
        foreach($params as $key => $val)
        {
            $sign .= $key.$val;
        }
        $sign .= 'keysecret'.$this->inc['keysecret'];
        $sign = md5($sign);
        return $sign;
    }
    /**
     * 验证结果,中断
     */
    private function _valid_result($json_result)
    {
        $err_msg = 'scrm接口请求异常';
        if (isset($json_result['code']) && $json_result['code'] == 0) {
            return true;
        } else {
            if (isset($json_result['code']) && isset($json_result['data'])) {
                $err_msg = $json_result['code'] . '-' . $json_result['data'];
            }
            log_message('error',__METHOD__.'-'.@json_encode($json_result));
            throw new Exception($err_msg);            

        }
    }
}
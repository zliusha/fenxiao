<?php

/**
 * @Author: binghe
 * @Date:   2018-08-07 14:09:29
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-20 15:21:49
 */
namespace Service\Sdk;
use Service\Exceptions\Exception;
use Service\Support\Http;
/**
 * xcx sdk
 */
class XcxSdk
{
	private $http;
	private $config =[];
	public function __construct(array $config)
	{
		$this->config['APPID'] = $config['appid'];
		$this->config['APPSECRET'] = $config['app_secret'];
		$this->http = new Http();
	}
	/**
	 * 获取access token
	 * @return [type] [description]
	 */
	public function getAccessToken()
	{
		$url = "https://api.weixin.qq.com/cgi-bin/token";
		$options = [
			'grant_type'=>'client_credential',
			'appid'=>$this->config['APPID'],
			'secret'=>$this->config['APPSECRET']
		];
		$jsonResult = $this->http->parseJSON($this->http->get($url,$options));
		return $this->_validResult($jsonResult);
	}
	/**
	 * 发送模板消息
	 * @return [type] [description]
	 */
	public function sendTemplateMessage(array $options,string $accessToken)
	{
		$url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$accessToken}";
		$response = $this->http->post($url,json_encode($options,JSON_UNESCAPED_UNICODE));
		$jsonResult = $this->http->parseJSON($response);
		return $this->_validResult($jsonResult);
	}
	/**
	 * 获取小程序码
	 * @param  array  $options     选填:scene,page,width
	 * @param  string $accessToken 
	 * @return stream
	 * @author binghe 2018-08-20
	 */
	public function getQrcodeStreamContents(array $options,string $accessToken)
    {
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
        $contents = $this->http->post($url,json_encode($options))->getBody()->getContents();
        $json_result = @json_decode($contents,true);
        if($json_result)
        {
            $err_msg = 'xcx-'.$json_result['errcode'] . '-' . $json_result['errmsg'];
            throw new Exception($err_msg);
        }
        else
            return $contents;
    }
	/**
     * 验证结果,中断
     */
    private function _validResult($jsonResult)
    {
        if (isset($jsonResult['errcode']) && $jsonResult['errcode'] != 0) {
            $errMsg = '微信接口请求异常';
            if (isset($jsonResult['errmsg'])) {
                $errMsg = 'XcxSdk-'.$jsonResult['errcode'] . '-' . $jsonResult['errmsg'];
            }
            throw new Exception($errMsg);       
        } else {
            return $jsonResult;
        }
    }
}
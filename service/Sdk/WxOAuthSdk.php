<?php
/**
 * @Author: binghe
 * @Date:   2018-04-09 14:50:05
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-06 18:47:55
 */
namespace Service\Sdk;
/**
* 微信登录
*/
class WxOAuthSdk
{
    private $data;
    private $config = [];
    /**
     * @param array $config 必需:appid,app_secret 选填 component_appid,component_access_token
     */
    public function __construct($config)
    {
        $this->config['APPID'] = $config['appid'];
        $this->config['APPSECRET'] = $config['app_secret'];
        //登录方式,0开发者模式,1第三方模式
        $this->config['LOGINTYPE'] = isset($config['login_type'])?$config['login_type']:0;
        //第三方app_id
        $this->config['COMPONENTAPPID'] = isset($config['component_appid'])?$config['component_appid']:'';
        //第三方accesstoken
        $this->config['COMPONENTACCESSTOKEN'] = isset($config['component_access_token'])?$config['component_access_token']:'';
        $this->config['CURL_PROXY_HOST'] = '0.0.0.0';
        $this->config['CURL_PROXY_PORT'] = 0;
    }
    /**
     * 获取原始登录地址
     * @param  [type] $redirectUrl [description]
     * @return [type]              [description]
     */
    public function getOauthUrlForCode($redirectUrl,$state = 'state')
    {
        return $this->_createOauthUrlForCode($redirectUrl,$state);
    }
    /**
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     * @return openid
     */
    public function getOpenidFromMp($code)
    {
        $url = $this->_createOauthUrlForOpenid($code);
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->config['CURL_PROXY_HOST'] != "0.0.0.0"
            && $this->config['CURL_PROXY_PORT'] != 0) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['CURL_PROXY_HOST']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->config['CURL_PROXY_PORT']);
        }
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $json_result = json_decode($res, true);
        $this->_valid_result($json_result);
        $this->data = $json_result;
        $openid = $json_result['openid'];
        return $openid;
    }
    /**
     * 得到用户信息
     */
    public function GetUserInfo()
    {
        $url = $this->_createUserinfoUrlForOpenid();
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->config['CURL_PROXY_HOST'] != "0.0.0.0"
            && $this->config['CURL_PROXY_PORT'] != 0) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['CURL_PROXY_HOST']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->config['CURL_PROXY_PORT']);
        }
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $json_result = json_decode($res, true);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 获取小程序用户openid, session_key 等信息
     * @param $code
     * @return mixed
     */
    public function getXcxUserInfo($code)
    {

        $url = $this->_createXcxUserinfoUrl($code);
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->config['CURL_PROXY_HOST'] != "0.0.0.0"
            && $this->config['CURL_PROXY_PORT'] != 0) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['CURL_PROXY_HOST']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->config['CURL_PROXY_PORT']);
        }
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $json_result = json_decode($res, true);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 拼接签名字符串
     * @param array $urlObj
     * @return 返回已经拼接好的字符串
     */
    public function toUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
     /**
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * @return 返回构造好的url
     */
    private function _createOauthUrlForCode($redirectUrl,$state)
    {
        $urlObj["appid"] = $this->config['APPID'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = $state;
        if($this->config['LOGINTYPE'])
            $urlObj['component_appid'] = $this->config['COMPONENTAPPID'];
        $bizString = $this->toUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }
    /**
     * 构造通过code换取access_token地址
     * @param string $code，微信跳转带回的code
     * @return 请求的url
     */
    private function _createOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->config['APPID'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        if($this->config['LOGINTYPE'] == 1)
        {
            //第三方获取token
            $urlObj['component_appid'] = $this->config['COMPONENTAPPID'];
            $urlObj['component_access_token'] = $this->config['COMPONENTACCESSTOKEN'];
            $bizString = $this->toUrlParams($urlObj);
            return "https://api.weixin.qq.com/sns/oauth2/component/access_token?" . $bizString;
        }
        else
        {
            $urlObj["secret"] = $this->config['APPSECRET'];
            $bizString = $this->toUrlParams($urlObj);
            return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
        }
    }
    /**
     * [_createAuthOauthUrlForOpenid description]
     * @param  [type] $code [description]
     * @return [type]       [description]
     */
    private function _createAuthOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->config['APPID'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        
        $bizString = $this->toUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/component/access_token?" . $bizString;
    }
    /**
     * 构造获取用户信息的链接
     * @return 请求的url
     */
    private function _createUserinfoUrlForOpenid()
    {
        $getData = $this->data;
        $urlObj["access_token"] = $getData["access_token"];
        $urlObj["openid"] = $getData["openid"];
        $bizString = $this->toUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?" . $bizString;
    }
    /**
     *
     * 构造获取小程序用户信息的链接
     * @return 请求的url
     */
    private function _createXcxUserinfoUrl($code)
    {
        $urlObj["appid"] = $this->config['APPID'];
        $urlObj["secret"] = $this->config['APPSECRET'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->toUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/jscode2session?" . $bizString;
    }
    /**
     * 验证结果,中断
     */
    private function _valid_result($json_result)
    {
        if (isset($json_result['errcode']) && $json_result['errcode'] != 0) {
            $err_msg = '微信接口请求异常';
            if (isset($json_result['errmsg'])) {
                $err_msg = 'WxOAuthSdk-'.$json_result['errcode'] . '-' . $json_result['errmsg'];
            }
            log_message('error',__METHOD__.'-'.json_encode($this->config));
            log_message('error',$err_msg);
            throw new \Exception($err_msg);       
        } else {
            return true;
        }
    }
}
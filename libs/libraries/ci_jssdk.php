<?php

use Service\Cache\JsApiAccessTokenCache;
use Service\Cache\JsApiTicketCache;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/25
 * Time: 15:39
 */
class ci_jssdk
{
    private $appId = '';
    private $appSecret = '';
    private $loginType = 0;
    private $authorizerAccessToken = '';
    private $aid = 0;

    /**
     * WxJsSdk constructor.必须 app_id, app_secret, url
     * @param null $config
     */
    public function __construct($config = null) {
        // 账号配置
        $this->_init($config);
    }

    /**
     * 初始化数据
     * @param null $config
     */
    private function _init($config = null)
    {
        if($config)
        {
            $this->aid = isset($config['aid']) ? $config['aid'] : 0;
            $this->appId = $config['app_id'];
            $this->appSecret = $config['app_secret'];
            $this->loginType = $config['login_type'];
            $this->authorizerAccessToken = isset($config['authorizer_access_token']) ? $config['authorizer_access_token'] : '';
        }
    }

    /**
     * 获取分享参数
     * @return array
     */
    public function getSignPackage($share_url='') {


        $jsapiTicket = $this->getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $_url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $url = $share_url ? $share_url : $_url;

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    /**
     * 随机字符串
     * @param int $length
     * @return string
     */
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取ticket
     * @return bool|mixed
     */
    private function getJsApiTicket() {

        $jsApiTicketCache = new JsApiTicketCache(['app_id' => $this->appId,'login_type' => $this->loginType]);
        $ticket = $jsApiTicketCache->get();

        if(!$ticket || empty($ticket)){

            //判断是否三方授权
            if($this->loginType == 1)
            {
                $accessToken = $this->authorizerAccessToken;
            }
            else
            {
                $accessToken = $this->getAccessToken();
            }


            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            if(empty($res)  || !isset($res->ticket))
            {
                log_message('error', "jssdk--ticket--{$this->aid}--".json_encode($res));
                return false;
            }
            $ticket = $res->ticket;

            $jsApiTicketCache->save($ticket, 7000);
        }
        return $ticket;
    }

    /**
     * 获取accesstoken
     * @return bool|mixed
     */
    public function getAccessToken() {

        $jsApiAccessTokenCache = new JsApiAccessTokenCache(['app_id' => $this->appId]);
        $access_token = $jsApiAccessTokenCache->get();


        if(!$access_token || empty($access_token)){

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url));
            if(empty($res) || !isset($res->access_token))
            {
                log_message('error', "jssdk--token--[appID:{$this->appId}]----{$this->aid}--".json_encode($res));
                return false;
            }

            $access_token = $res->access_token;

            $jsApiAccessTokenCache->save($access_token, 7000);
        }

        return $access_token;
    }

    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }
}
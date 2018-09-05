<?php
/**
 * @Author: binghe
 * @Date:   2018-01-12 14:30:14
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-09 10:43:27
 */
require_once ROOT . 'vendor/autoload.php';
use Service\Traits\HttpTrait;
/**
* 小程序sdk
*/
class xcx_sdk
{
    use HttpTrait;
    /**
     * code登录
     * @param  array $input [app_id,js_code,component_appid,authorizer_access_token]
     * @return array        [openid,session_key]
     */
    public function codeLogin($input)
    {
        $url = "https://api.weixin.qq.com/sns/component/jscode2session";
        $params['appid']=$input['app_id'];
        $params['js_code']=$input['code'];
        $params['grant_type']='authorization_code';
        $params['component_appid']=$input['component_appid'];
        $params['component_access_token']=$input['component_access_token'];
        // log_message('error',__METHOD__.'-'.$url);
        $http = $this->getHttp();
        $response = $http->get($url,$params);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 用app_secret code登录[测试环境使用]
     * @param  array $input [app_id,secret,js_code]
     * @return array        [openid,session_key]
     */
    public function secretCodeLogin($input)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session";
        $params['appid']=$input['app_id'];
        $params['secret']=$input['secret'];
        $params['js_code']=$input['code'];
        $params['grant_type']='authorization_code';
        // log_message('error',__METHOD__.'-'.$url);
        $http = $this->getHttp();
        $response = $http->get($url,$params);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 设置小程序服务器域名
     * 授权给第三方的小程序，其服务器域名只可以为第三方的服务器，当小程序通过第三方发布代码上线后，小程序原先自己配置的服务器域名将被删除，只保留第三方平台的域名，所以第三方平台在代替小程序发布代码之前，需要调用接口为小程序添加第三方自身的域名
     * @param  array $input        上传参数
     * @param  string $access_token authorizer_access_token
     * @return [type]               [description]
     */
    public function wxaModifyDomain($input,$access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/modify_domain?access_token={$access_token}";
        $http = $this->getHttp();
        $response = $http->post($url,json_encode($input,JSON_UNESCAPED_UNICODE));
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 配置业务域名
     * @param  [type] $input        [description]
     * @param  [type] $access_token [description]
     * @return [type]               [description]
     */
    public function wxaSetWebviewDomain($input,$access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/setwebviewdomain?access_token={$access_token}";
        $http = $this->getHttp();
        $response = $http->post($url,json_encode($input,JSON_UNESCAPED_UNICODE));
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 1为授权的小程序帐号上传小程序代码
     * @param  array $input        上传参数
     * @param  string $access_token authorizer_access_token
     * @return [type]               [description]
     */
    public function wxaCommit($input,$access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/commit?access_token={$access_token}";
        $http = $this->getHttp();
        $response = $http->post($url,json_encode($input,JSON_UNESCAPED_UNICODE));
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 2.获取体验二维码地址
     * @param  [type] $access_token [description]
     * @return [type]               [description]
     */
    public function getQrCode($access_token)
    {
        return "https://api.weixin.qq.com/wxa/get_qrcode?access_token={$access_token}";
    }
    /**
     * 3获取授权小程序帐号的可选类目
     * @param  [type] $access_token [description]
     * @return [type]               [description]
     */
    public function getCategory($access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/get_category";
        $http = $this->getHttp();
        $response = $http->get($url,['access_token'=>$access_token]);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 4获取小程序的第三方提交代码的页面配置
     * @param  [type] $access_token [description]
     * @return [type]               [description]
     */
    public function getPage($access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/get_page";
        $http = $this->getHttp();
        $response = $http->get($url,['access_token'=>$access_token]);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 5将第三方提交的代码包提交审核
     * @param  array $input        上传参数
     * @param  string $access_token authorizer_access_token
     * @return [type]               [description]
     */
    public function submitAudit($input,$access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/submit_audit?access_token={$access_token}";
        $http = $this->getHttp();
        $response = $http->post($url,json_encode($input,JSON_UNESCAPED_UNICODE));
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 7查询某个指定版本的审核状态
     * @param  array $input        上传参数
     * @param  string $access_token authorizer_access_token
     * @return [type]               [description]
     */
    public function getAuditstatus($input,$access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/get_auditstatus?access_token={$access_token}";
        $http = $this->getHttp();
        $response = $http->post($url,json_encode($input,JSON_UNESCAPED_UNICODE));
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 8查询最新一次提交的审核状态
     * @param  [type] $access_token [description]
     * @return [type]               [description]
     */
    public function getLatestAuditstatus($access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/get_latest_auditstatus";
        $http = $this->getHttp();
        $response = $http->get($url,['access_token'=>$access_token]);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 9发布已通过审核的小程序
     * @param  array $input        上传参数
     * @param  string $access_token authorizer_access_token
     * @return [type]               [description]
     */
    public function release($access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/release?access_token={$access_token}";
        $http = $this->getHttp();
        $response = $http->post($url,'{}');
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 16小程序审核撤回
     * @param  [type] $access_token [description]
     * @return [type]               [description]
     */
    public function undoCodeAudit($access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/undocodeaudit";
        $http = $this->getHttp();
        $response = $http->get($url,['access_token'=>$access_token]);
        $json_result = $http->parseJSON($response);
        $this->_valid_result($json_result);
        return $json_result;
    }
    /**
     * 获取小程序二维码 注意此时返回的是流,需要判断是否正常
     * @param  [type] $access_token [description]
     * @param  string 文件路径
     * @param  [type] $input        [description]
     * @return [type]               [description]
     */
    public function getQrcodeStreamContents($input,$access_token)
    {
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";
        $http = $this->getHttp();
        $contents = $http->post($url,json_encode($input))->getBody()->getContents();
        $json_result = @json_decode($contents,true);
        if($json_result)
        {
            log_message('error',__METHOD__.'-'.$contents);
            $err_msg = 'xcx-'.$json_result['errcode'] . '-' . $json_result['errmsg'];
            throw new Exception($err_msg);
            
        }
        else
            return $contents;
    }
    /**
     * 验证结果,中断
     */
    private function _valid_result($json_result)
    {

        
        if (isset($json_result['errcode']) && $json_result['errcode'] != 0) {
            $err_msg = 'xcx接口请求异常';
            if (isset($json_result['errmsg'])) {
                $err_msg = 'xcx-'.$json_result['errcode'] . '-' . $json_result['errmsg'];
            }
            log_message('error',$err_msg);
            throw new Exception($err_msg);       
        } else {
            return true;
        }
    }
}
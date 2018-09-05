<?php
/**
 * @Author: binghe
 * @Date:   2018-04-04 14:12:36
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-21 13:38:25
 */
use Service\Traits\HttpTrait;
use Service\Sdk\WxOAuthSdk;
use Service\Cache\JsApiTicketCache;
use Service\Cache\JsApiAccessTokenCache;
use Service\Support\Redis;
/**
* 
*/
class Test_func extends base_controller
{
    /**
     * [fe description]
     * @param  [type] $a [description]
     * @return [type]    [description]
     * @author binghe 2018-08-08
     */
   public function fe()
   {
        // ob_start(); //打开缓冲区
        echo "Hellon"; //输出
        header("location:fe1"); //把浏览器重定向到index.php
        // ob_end_flush();//输出全部内容到浏览器

   }
   public function fe1()
   {
    echo 1;
   }
    public function getSign()
    {
        //默认签名
        $time='';
        $secretKey='ydb_manage';
        $secretKey.=$time;
        $params=['account_id'=>1227];
        $sign = simple_auth::getSign($params,$secretKey);
        echo $sign;
    }
    public function getPassword()
    {
        //8c18005894d65adcfa70eb6e42be1ee9
        $password = '12345678';
        echo md5($password.SECRET_KEY);
    }
    public function testError1()
    {
        throw new Exception("this is a error");
        
    }
    public function testCache()
    {
        $jsApiTicketCache = new JsApiTicketCache(['app_id'=>'testappid']);
        $jsApiTicketCache->save('testaaaaa');
        $data1 = $jsApiTicketCache->get();
        var_dump($data1);
        $JsApiAccessTokenCache = new JsApiAccessTokenCache(['app_id'=>'testappid']);
        $JsApiAccessTokenCache->save('testaaaaa');
        $data2 = $JsApiAccessTokenCache->get();
        var_dump($data2);
    }
    public function testError()
    {

        set_exception_handler(function($e){
           echo 'set_exception_handler<br/>';
           echo 'code:'.$e->getCode().'-msg:'.$e->getMessage();
        });
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            echo "set_error_handler<br/> code:$errno msg:$errstr";
            if (!(error_reporting() & $errno)) {
                // This error code is not included in error_reporting, so let it fall
                // through to the standard PHP error handler
                return false;
            }

            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        }, E_ALL);
        // footer();
        // echo $foot['aa'];
        throw new Exception("Error Processing Request");
        echo 111;
    }
    public function testCodeUrl()
    {
        $config = ['appid'=>'appid','app_secret'=>'appsecret'];
        $wxOAuthSdk = new WxOAuthSdk($config);
        $redirectUrl = 'https://adidas.m.waimaishop.com/meal/#/';
        $state='wx';
        $url = $wxOAuthSdk->getOauthUrlForCode( urlencode($redirectUrl),$state);
        echo $url;
    }
    use HttpTrait;
    public function testhttp()
    {
        $http = $this->getHttp();
        var_dump($this->http);
    }
    public function test_msg()
    {
        $method = __METHOD__.'-';
        log_message('error',$method.'error');
        log_message('debug',$method.'debug');
        log_message('info',$method.'info');
    }
    
}
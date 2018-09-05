<?php
/**
 * CI集成微信支付API
 * @author dadi
 */
use Service\Cache\AidToVisitIdCache;
use Service\Cache\WmGzhConfigCache;

ini_set('date.timezone', 'Asia/Shanghai');
class ci_wxpay
{
    /**
     * 微信支付SDK调用
     * @param  string  $packet 加载包名
     * @param  integer $aid    aid
     * @param  boolean $xcxAppid  是否是小程序
     * @return [type]          [description]
     */
    public static function load($packet = '', $aid = 0, $xcxAppid = '')
    {
        if ($aid) {
            $wmGzhConfigCache = new WmGzhConfigCache(['aid' => $aid]);
            $config = $wmGzhConfigCache->getDataASNX();

            // 商户ID
            define('WXPAY_MCHID', $config->mch_id);
            // 商户支付秘钥
            define('WXPAY_KEY', $config->key);

            if ($xcxAppid) {
                // 获取小程序第三方授权信息
                try {
                    $scrm_sdk = new scrm_sdk;
                    $aidToVisitIdCache = new AidToVisitIdCache(['aid' => $aid]);
                    $visit_id = $aidToVisitIdCache->getDataASNX();
                    $params['visit_id'] = $visit_id;
                    $params['appid'] = $xcxAppid;
                    $result = $scrm_sdk->getXcxInfo($params);
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . ' scrm_sdk返回错误:' . $e->getMessage());
                    throw new Exception('小程序未授权');
                }
                define('WXPAY_LOGIN_TYPE', 1);
                define('WXPAY_APPID', $xcxAppid);
                define('WXPAY_APPSECRET', '');
                define('WXPAY_COMPONENT_APPID', isset($result['data']['component_appid']) ? $result['data']['component_appid'] : '');
                define('WXPAY_COMPONENT_ACCESS_TOKEN', isset($result['data']['component_access_token']) ? $result['data']['component_access_token'] : '');
            } else {
                // 开启第三方授权登录
                if ($config->login_type == 1) {
                    $scrm_sdk = new scrm_sdk;
                    $aidToVisitIdCache = new AidToVisitIdCache(['aid' => $aid]);
                    $visit_id = $aidToVisitIdCache->getDataASNX();
                    $result = $scrm_sdk->getGzhInfo(['visit_id' => $visit_id]);
                    //APP_ID 重新赋值
                    $config->app_id = $result['data']['authorizer_appid'];
                }

                define('WXPAY_LOGIN_TYPE', $config->login_type ? $config->login_type : 0);
                define('WXPAY_APPID', $config->app_id);
                define('WXPAY_APPSECRET', $config->app_secret ? $config->app_secret : '');
                define('WXPAY_COMPONENT_APPID', isset($result['data']['component_appid']) ? $result['data']['component_appid'] : '');
                define('WXPAY_COMPONENT_ACCESS_TOKEN', isset($result['data']['component_access_token']) ? $result['data']['component_access_token'] : '');
            }

            define('WXPAY_SSLCERT_PATH', UPLOAD_PATH . $config->apiclient_cert_path);
            define('WXPAY_SSLKEY_PATH', UPLOAD_PATH . $config->apiclient_key_path);

            // log_message('error', WXPAY_APPID . ' = ' . WXPAY_APPSECRET . ' = ' . WXPAY_SSLCERT_PATH . ' = ' . WXPAY_SSLKEY_PATH . ' = ' . WXPAY_COMPONENT_APPID . ' = ' . WXPAY_COMPONENT_ACCESS_TOKEN);
        }

        define('WXPAY_CURL_PROXY_HOST', "0.0.0.0");
        define('WXPAY_CURL_PROXY_PORT', 0);

        define('WXPAY_REPORT_LEVENL', 1);

        switch ($packet) {
            case 'jsapi':
                require_once "wxpay/lib/WxPay.JsApiPay.php";
                break;
            case 'notify':
                require_once "wxpay/lib/WxPay.PayNotifyCallBack.php";
                break;
            case 'micro':
                require_once "wxpay/lib/WxPay.MicroPay.php";
                break;
            default:
                throw new Exception('CI_WXPAY:不支持的模式调用');
                break;
        }
    }

    /**
     * 读取小程序设置
     * @return [type] [description]
     */
    public function _loadXcxConfig()
    {
        //
    }

    /**
     * 读取嘿卡小程序设置
     * @param string $packet
     * @throws Exception
     */
    public static function loadHcity($packet='jsapi')
    {
        $xcx_inc = &inc_config('xcx_hcity');

        // 商户ID
        define('WXPAY_MCHID', $xcx_inc['mch_id']);
        // 商户支付秘钥
        define('WXPAY_KEY', $xcx_inc['key']);

        define('WXPAY_LOGIN_TYPE', 1);
        define('WXPAY_APPID', $xcx_inc['app_id']);
        define('WXPAY_APPSECRET', $xcx_inc['app_secret']);
        define('WXPAY_CURL_PROXY_HOST', "0.0.0.0");
        define('WXPAY_CURL_PROXY_PORT', 0);
        define('WXPAY_REPORT_LEVENL', 1);

        switch ($packet) {
            case 'jsapi':
                require_once "wxpay/lib/WxPay.JsApiPay.php";
                break;
            case 'notify':
                require_once "wxpay/lib/WxPay.PayNotifyCallBack.php";
                break;
            case 'micro':
                require_once "wxpay/lib/WxPay.MicroPay.php";
                break;
            case 'native':
                require_once "wxpay/lib/WxPay.NativePay.php";
                break;
            default:
                throw new Exception('CI_WXPAY:不支持的模式调用');
                break;
        }
    }

    /**
     * 格式化参数格式化成url参数
     */
    public static function ToUrlParams($data = [])
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @return 签名
     */
    public static function wxpayMakeSign($data = [], $wxpay_key = '')
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = self::ToUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $wxpay_key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
}

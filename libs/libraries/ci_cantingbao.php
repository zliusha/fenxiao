<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/13
 * Time: 9:58
 */
use Service\Traits\HttpTrait;
use Service\Cache\Wm\WmCantingbaoConfigCache;

class ci_cantingbao
{
    use HttpTrait;

    //接口基础地址
    public $api_url = '';
    public $inc = null;

    // 推送订单
    const SEND_ORDER = 'SendOrderData';
    // 关闭订单
    const CLOSE_ORDER = 'CloseOrder';
    // 订单退款
    const REFUND_ORDER = 'OrderRefund';
    // 锁定订单
    const LOCK_ORDER = 'OrderLock';
    // 更新订单
    const UPDATE_ORDER = 'UpdateOrderData';
    // 修改商家秘钥
    const UPDATE_KEY = 'ReplaceKey';
    // 修改推送地址
    const UPDATE_PUSH_URL = 'UpdateOrderStateUrl';

    /**
     *
     * ci_cantingbao constructor.
     * @param array $input aid 必须
     */
    public function __construct(array $input)
    {
        // 获取配置
        $wmCantingbaoConfigCache = new WmCantingbaoConfigCache(['aid'=>$input['aid']]);
        $inc = $wmCantingbaoConfigCache->getDataASNX();
        $this->inc = $inc;

        if (in_array(ENVIRONMENT, ['production'])) {
            $this->api_url = 'http://sdk.canqu.com.cn/Shop/V1/';
        } else {
            $this->api_url = 'http://sdk.test.canqu.com.cn/Shop/V1/';
        }

    }

    /**
     * 错误时有异常输出
     * @param array $params
     * @return [type] [description]
     */
    public function post($params ,$action)
    {
        // ClientOrder.ashx ClientAccount.ashx
        $url = $this->api_url.'ClientOrder.ashx';
        if($action == self::UPDATE_KEY || $action == self::UPDATE_PUSH_URL)
        {
            $url = $this->api_url.'ClientAccount.ashx';
        }

//        $url = 'http://fw.waimai.com/Notify_cantingbao/index/1226';

        $data['action'] = $action;
        $data['key'] = $this->inc->app_key;
        $data['content'] = base64_encode(json_encode($params));
        $data['ts'] = self::getMillisecond();
        $data['sign'] = $this->_getSign($data);

        $url_data = http_build_query($data);
        $post_url = $url.'?'.$url_data;
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //设置请求头
        $headers = ['Content-Type: application/x-www-form-urlencoded; charset=utf-8'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //设置请求接口URL
        curl_setopt($ch,CURLOPT_URL, $post_url);

//        if(stripos($post_url,"https://")!==FALSE){
//            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
//        }    else    {
//            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
//            // curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
//            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
//        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //设置header
//        curl_setopt($ch, CURLOPT_HEADER, FALSE);
//        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //运行curl
        $response = curl_exec($ch);
        //返回结果
        if($response){
            curl_close($ch);
            log_message('error',__METHOD__.'-action-'.$action.'-data12-'.$response);
            $json_result = json_decode($response, true);
            $this->_validResult($json_result);
            return $json_result;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new \Exception("curl出错，错误码:$error");
        }

    }

    // 生成sign
    private function _getSign($data)
    {
        $signStr = $data['key'].$data['content'].$this->inc->app_secret.$data['ts'];
        return strtolower(sha1($signStr));
    }

    /**
     * 验证结果,中断
     */
    private function _validResult($json_result)
    {

        $err_msg = '餐厅宝接口请求异常';
        if (isset($json_result['StateCode']) && $json_result['StateCode'] == 0) {
            return true;
        } else {
            if (isset($json_result['StateCode']) && isset($json_result['StateMsg'])) {
                $err_msg =  $json_result['StateMsg'];
                log_message('error',__METHOD__.'-'.$err_msg);
            }else{
                log_message('error',__METHOD__.'-'.json_encode($json_result));
            }

            throw new Exception($err_msg);
        }
    }

    /**
     * 获取毫秒级别的时间戳
     */
    private static function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode ( " ", microtime () );
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }

}
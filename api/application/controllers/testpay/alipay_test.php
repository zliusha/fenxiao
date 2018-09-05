<?php
/**
 * @Author: binghe
 * @Date:   2018-05-17 15:11:48
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-05-21 17:08:36
 */
require_once LIBS_PATH.'libraries/alipay/f2fpay/model/builder/AlipayTradePayContentBuilder.php';
require_once LIBS_PATH.'libraries/alipay/f2fpay/service/AlipayTradeService.php';
/**
* alipay
*/
class alipay_test extends base_controller
{
    /**
     * 当面付
     */
    public function f2f()
    {
        require_once LIBS_PATH.'libraries/alipay/f2fpay/config/config.php';
        $authCode = $this->input->get('code');
        $outTradeNo = 'T'.time().rand(1000,9999);
        $totalAmount = '0.01';
        // 支付超时，线下扫码交易定义为5分钟
        $timeExpress = "5m";
        $subject = '云店宝扫码支付subject';
        $body = '扫码支付测试body';
        // 创建请求builder，设置请求参数
        $barPayRequestBuilder = new AlipayTradePayContentBuilder();
        $barPayRequestBuilder->setOutTradeNo($outTradeNo);
        $barPayRequestBuilder->setTotalAmount($totalAmount);
        $barPayRequestBuilder->setAuthCode($authCode);
        $barPayRequestBuilder->setTimeExpress($timeExpress);
        $barPayRequestBuilder->setSubject($subject);
        $barPayRequestBuilder->setBody($body);

        // 调用barPay方法获取当面付应答
        $barPay = new AlipayTradeService($config);
        $barPayResult = $barPay->barPay($barPayRequestBuilder);

        switch ($barPayResult->getTradeStatus()) {
            case "SUCCESS":
                echo "支付宝支付成功:" . "<br>--------------------------<br>";
                echo $barPayResult->getResponse()->trade_no . '<br>--------------------------<br>';
                print_r($barPayResult->getResponse());
                break;
            case "FAILED":
                echo "支付宝支付失败!!!" . "<br>--------------------------<br>";
                if (!empty($barPayResult->getResponse())) {
                    print_r($barPayResult->getResponse());
                }
                break;
            case "UNKNOWN":
                echo "系统异常，订单状态未知!!!" . "<br>--------------------------<br>";
                if (!empty($barPayResult->getResponse())) {
                    print_r($barPayResult->getResponse());
                }
                break;
            default:
        }
    }
}
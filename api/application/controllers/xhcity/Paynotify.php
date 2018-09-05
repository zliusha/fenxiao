<?php
/**
 * 第三方支付回调方法
 * @author dadi
 */
use Service\Bll\Hcity\PayBll;
class Paynotify extends CI_Controller
{
    // 微信支付回调
    public function wxpay()
    {
        ci_wxpay::loadHcity('notify');

        // log_message('error', __METHOD__ . ' begin notify');

        $raw_xml = file_get_contents("php://input");
        libxml_disable_entity_loader(true);
        $ret = json_decode(json_encode(simplexml_load_string($raw_xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        //验签
        $xcx_inc = &inc_config('xcx_hcity');
        $ret_sign = $ret['sign'];
        unset($ret['sign']);
        $sign = ci_wxpay::wxpayMakeSign($ret, $xcx_inc['key']);

        // log_message('error', __METHOD__ . ' ' . json_encode($ret));

        if ($ret_sign == $sign && $ret['return_code'] === 'SUCCESS' && $ret['return_code'] === 'SUCCESS') {
            $payBll = new PayBll();
            try{
                $payBll->notifyPaySuccess($ret["out_trade_no"], $ret["transaction_id"]);
                echo array_to_xml(['return_code' => 'SUCCESS']);
            }catch(Exception $e){
                log_message('error', __METHOD__.':'.$e->getMessage());
                log_message('error', __METHOD__.':'.json_encode($ret));
                echo array_to_xml(['return_code' => 'FAIL']);
            }
        }else{
            log_message('error', __METHOD__.':'.json_encode($ret));
            echo array_to_xml(['return_code' => 'FAIL']);
        }
    }
}

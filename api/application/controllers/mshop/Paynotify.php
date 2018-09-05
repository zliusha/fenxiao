<?php
/**
 * 第三方支付回调方法
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmPaymentRecordDao;
class Paynotify extends CI_Controller
{
    // 微信支付回调
    public function wxpay($aid)
    {
        $this->aid = $aid;
        ci_wxpay::load('notify');

        // log_message('error', __METHOD__ . ' begin notify');

        $raw_xml = file_get_contents("php://input");
        libxml_disable_entity_loader(true);
        $ret = json_decode(json_encode(simplexml_load_string($raw_xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        // $notify = new PayNotifyCallBack();
        // $notify->Handle(false);
        // $ret = $notify->GetValues();

        // log_message('error', __METHOD__ . ' ' . json_encode($ret));

        if ($ret['return_code'] === 'SUCCESS' && $ret['return_code'] === 'SUCCESS') {
            $wmPaymentRecordDao = WmPaymentRecordDao::i($this->aid);
            if ($wmPaymentRecordDao->weixinOrderCallback($ret["out_trade_no"], $ret["transaction_id"])) {
                echo array_to_xml(['return_code' => 'SUCCESS']);
            }
        }
    }
}

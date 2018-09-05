<?php
use Service\DbFrame\DataBase\WmShardDbModels\WmPaymentRecordDao;
/**
 * 操作工具
 */
class Tools extends CI_Controller
{
    public $aid = 0;

    // 便捷支付退款（仅支持1226测试账号记录）
    public function refund()
    {
        $code = $this->input->get('code');

        $this->aid = 1226;
        $wmPaymentRecordDao = WmPaymentRecordDao::i($this->aid);

        // 查询付款信息
        $pay_order = $wmPaymentRecordDao->getOneArray(['code' => $code, 'gateway' => 'weixin', 'aid' => $this->aid, 'status' => 1]);

        if (!$pay_order) {
            echo '不存在付款记录';
            exit;
        }

        try {
            if ($pay_order['source'] == 'xcx' && $pay_order['appid']) {
                ci_wxpay::load('jsapi', $pay_order['aid'], $xcxAppid = $pay_order['appid']);
            } else {
                ci_wxpay::load('jsapi', $pay_order['aid']);
            }

            $total_fee = bcmul($pay_order['money'], 100);
            $refund_fee = bcmul($pay_order['money'], 100);
            $wx_input = new WxPayRefund();
            $wx_input->SetTransaction_id($pay_order['trade_no']);
            $wx_input->SetTotal_fee($total_fee);
            $wx_input->SetRefund_fee($refund_fee);
            $wx_input->SetOut_refund_no(WXPAY_MCHID . date("YmdHis"));
            $wx_input->SetOp_user_id(WXPAY_MCHID);
            $result = WxPayApi::refund($wx_input);

            echo json_encode($result);
            exit;
        } catch (Exception $e) {
            echo json_encode('exception:' . $e->getMessage());
            exit;
        }
    }
}

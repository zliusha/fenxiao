<?php
/**
 * 第三方支付回调方法
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealPaymentRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
class Paynotify extends CI_Controller
{
    // 微信订单支付回调
    public function wxpay_order($aid)
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
            $mealPaymentRecordDao = MealPaymentRecordDao::i($this->aid);
            $return = $mealPaymentRecordDao->weixinOrderCallback($ret["out_trade_no"], $ret["transaction_id"]);

            if ($return) //支付通知
            {
                //获取支付流水信息
                $mealStatementsDao = MealStatementsDao::i($this->aid);
                $m_meal_statements = $mealStatementsDao->getOne(['order_number' => $ret["transaction_id"], 'type' => 1], 'id,order_table_id,aid,shop_id,type,order_number,amount,gateway,time');
                if ($m_meal_statements) {
                    $mealOrderTableDao = MealOrderTableDao::i($m_meal_statements->aid);
                    $m_meal_order_table = $mealOrderTableDao->getOne(['id' => $m_meal_statements->order_table_id, 'aid' => $m_meal_statements->aid]);
                    if ($m_meal_order_table) {
                        $input = [
                            'aid' => $m_meal_order_table->aid,
                            'shop_id' => $m_meal_order_table->shop_id,
                            'table_id' => $m_meal_order_table->table_id,
                            'area_name' => $m_meal_order_table->area_name,
                            'table_name' => $m_meal_order_table->table_name,
                            'total_money' => $m_meal_statements->amount,
                            'time' => date('Y-m-d H:i:s', $m_meal_statements->time),
                            'pay_type' => $m_meal_statements->gateway,
                        ];
                        //通知T1支付成功
                        $worker_bll = new worker_bll();
                        $worker_bll->mealOrderPay($input);
                    }
                }
                echo array_to_xml(['return_code' => 'SUCCESS']);
            }

        }
    }
}

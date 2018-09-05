<?php
/**
 * 订单支付操作
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealPaymentRecordDao;
class Payment extends xmeal_table_controller
{
    // 获取结算金额
    public function checkout()
    {
        $rule = [
            ['field' => 'order_table_id', 'label' => '桌位订单号', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 检查当前桌位是否存在待审核的订单
        $mealOrderDao = MealOrderDao::i($this->aid);
        if ($mealOrderDao->getCount(['status' => 1010, 'order_table_id' => $fdata['order_table_id']]) > 0) {
            $this->json_do->set_error('004', '当前存在待审核订单，请跟服务员确认后再结算');
        }

        // 查询待支付金额总和
        $sum_pay_money = $mealOrderDao->getSum('pay_money', ['status' => 2020, 'order_table_id' => $fdata['order_table_id']]);

        if ($sum_pay_money <= 0) {
            $this->json_do->set_error('004', '当前没有待支付订单');
        }

        // 查询桌位记录
        $mealOrderTableDao = MealOrderTableDao::i($this->aid);
        $order_table = $mealOrderTableDao->getOne(['id' => $fdata['order_table_id']]);

        $this->json_do->set_data(['amount' => $sum_pay_money, 'order_table' => $order_table]);
        $this->json_do->out_put();
    }

    // 微信支付
    public function weixin()
    {
        $rule = [
            ['field' => 'order_table_id', 'label' => '桌位订单号', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 查询桌位订单记录表
        $mealOrderTableDao = MealOrderTableDao::i($this->aid);
        $order_table = $mealOrderTableDao->getOne(['id' => $fdata['order_table_id']]);

        if (!$order_table) {
            $this->json_do->set_error('004', '无效桌位订单号');
        }

        // 查询待支付金额总和
        $mealOrderDao = MealOrderDao::i($this->aid);
        $sum_pay_money = $mealOrderDao->getSum('pay_money', ['status' => 2020, 'order_table_id' => $order_table->id]);

        if ($sum_pay_money <= 0) {
            $this->json_do->set_error('004', '当前没有待支付订单');
        }

        // fakepay模式（危险，全局订单只需支付0.01即可完成订单支付）
        $meal_inc = &inc_config('meal');
        if ($meal_inc['fakepay'] === true && $order_table->aid == 1226) {
            $sum_pay_money = 0.01;
        }
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->aid);

        $data['open_id'] = $this->s_user->openid;
        $data['nickname'] = $this->s_user->nickname;
        $data['headimg'] = $this->s_user->headimg;
        $data['aid'] = $order_table->aid;
        $data['shop_id'] = $order_table->shop_id;
        $data['gateway'] = 'weixin';
        $data['source'] = 'xcx';
        $data['appid'] = $this->app_id;
        $data['money'] = $sum_pay_money;
        $data['code'] = create_order_number();
        $data['order_table_id'] = $order_table->id;
        $mealPaymentRecordDao->create($data);

        $subject = '订单支付';
        $body = '';

        // 测试环境直接支付成功
        if (in_array(ENVIRONMENT, ['development'])) {
            $out_trade_no = create_order_number();
            $result = $mealPaymentRecordDao->weixin_order_callback($data['code'], $out_trade_no);

            if ($result) {

                //获取支付流水信息
                $mealStatementsDao = MealStatementsDao::i($this->aid);
                $m_meal_statements = $mealStatementsDao->getOne(['order_number' => $out_trade_no, 'type' => 1], 'id,order_table_id,aid,shop_id,type,order_number,amount,gateway,time');
                if($m_meal_statements)
                {
                    $input = [
                        'aid' => $order_table->aid,
                        'shop_id' => $order_table->shop_id,
                        'table_id' => $order_table->table_id,
                        'area_name' => $order_table->area_name,
                        'table_name' => $order_table->table_name,
                        'total_money' => $m_meal_statements->amount,
                        'time' => date('Y-m-d H:i:s', $m_meal_statements->time),
                        'pay_type' => $m_meal_statements->gateway
                    ];
                    //通知T1支付成功
                    $worker_bll = new worker_bll();
                    $worker_bll->mealOrderPay($input);

                }

                $this->json_do->set_msg('支付成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('004', '支付失败');
            }

        }

        try {
            ci_wxpay::load('jsapi', $this->aid, $xcxAppid = $this->app_id);

            $tools = new JsApiPay();

            // 调用统一下单接口
            $input = new WxPayUnifiedOrder();
            $input->SetBody($subject);
            $input->SetAttach($body);
            $input->SetOut_trade_no($data['code']);
            $input->SetTotal_fee($data['money'] * 100);
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            $input->SetGoods_tag($subject);
            $input->SetNotify_url(API_URL . 'meal/paynotify/wxpay_order/'.$this->aid);
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($this->s_user->openid);
            $payorder = WxPayApi::unifiedOrder($input);
        } catch (Exception $e) {
            log_message('error', '点餐小程序支付错误：' . $e->getMessage());
            $this->json_do->set_error('004', $e->getMessage());
        }

        if ($payorder['return_code'] == 'FAIL') {
            $this->json_do->set_error('004', $payorder['return_msg']);
        }

        if ($payorder['result_code'] == 'FAIL' && $payorder['err_code'] == 'ORDERPAID') {
            $this->json_do->set_error('004', '该订单已经支付，请勿重复支付');
        }

        if ($payorder['return_code'] == 'FAIL') {
            $this->json_do->set_error('004', '支付订单创建失败');
        }

        $ret['param'] = $tools->GetJsApiParameters($payorder);
        // 支付订单号
        $ret['code'] = $data['code'];

        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    // 微信公众号支付结果查询接口
    // 给前端查询支付结果
    public function weixin_result()
    {
        $rule = [
            ['field' => 'code', 'label' => '订单号', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->aid);
        $result = $mealPaymentRecordDao->getOne(['code' => $fdata['code'], 'gateway' => 'weixin'], 'status', 'id desc');

        if (!isset($result)) {
            $this->json_do->set_error('004', '支付订单不存在');
        } else {
            if ($result->status == 1) {
                $this->json_do->set_msg('已支付成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('003', '订单尚未支付成功');
            }
        }
    }
}

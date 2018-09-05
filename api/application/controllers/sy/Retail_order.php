<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/24
 * Time: 9:35
 * 零售订单
 */
require_once LIBS_PATH.'libraries/alipay/f2fpay/model/builder/AlipayTradePayContentBuilder.php';
require_once LIBS_PATH.'libraries/alipay/f2fpay/service/AlipayTradeService.php';
use Service\Cache\WmFubeiConfigCache;

use Service\Cache\WmAlipayConfigCache;
use Service\Cache\WmGzhConfigCache;

use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealPaymentRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmFubeiRefshopDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderDao;
use Service\Cache\WmShopCache;

class Retail_order extends sy_controller
{
    /**
     * 创建订单 pro_attr可选
     * items JSON格式 [{"goods_id":1, "sku_id":1,"quantity":1,["pro_attr":"xx/xx/xx"]}]
     */
    public function create()
    {
        $rule = [
            ['field' => 'items', 'label' => '订单JSON', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $input = $this->form_data($rule);

        $input['aid'] = $this->s_user->aid;
        $input['shop_id'] = $this->s_user->shop_id;
        $input['items'] = @json_decode($input['items'], true);

        $retail_order_event_bll = new retail_order_event_bll();
        $retail_order_event_bll->orderCreate($input, true);
    }

    /**
     * 结算订单 pay_type 1现金,2支付宝,3微信，4会员储值消费 5 付呗微信 6付呗支付宝 7 刷卡消费(会员卡，礼品卡)
     */
    public function pay_order()
    {

        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required|numeric'],
            ['field' => 'pay_type', 'label' => '支付类型', 'rules' => 'trim|required|in_list[1,2,3,4,5,6,7]'],
            ['field' => 'sum_pay_money', 'label' => '实际支付金额', 'rules' => 'trim|required|numeric'],
            ['field' => 'auth_code', 'label' => '支付授权码', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|preg_key[MOBILE]'],
            ['field' => 'card_code', 'label' => '卡号', 'rules' => 'trim']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $fdata['aid'] = $this->s_user->aid;
        $fdata['source_type'] = 3;//零售
        if(in_array($fdata['pay_type'],[2,3,5,6]) && empty($fdata['auth_code']))
            $this->json_do->set_error('001','请扫描支付授权码');

        if(in_array($fdata['pay_type'],[4]) && empty($fdata['mobile']))
            $this->json_do->set_error('001','请输入会员手机号');

        if(in_array($fdata['pay_type'],[7]) && empty($fdata['card_code']))
            $this->json_do->set_error('001','请刷卡');

        //验证金额
        if($fdata['sum_pay_money'] < 0)
        {
            $this->json_do->set_error('004', '支付金额不能小于0');
        }

        // 检查金额是否匹配
        $retailOrderDao = RetailOrderDao::i($this->s_user->aid);
        $sum_pay_money = $retailOrderDao->getSum('pay_money', ['status' => 1010, 'aid' => $this->s_user->aid, 'tid'=>$fdata['tid']]);
        if ($sum_pay_money < $fdata['sum_pay_money']) {
            log_message('error', __METHOD__ . '支付异常:待支付金额小于实际支付金额 sum_pay_money=>' . $sum_pay_money . ' input=>' . json_encode($fdata));
            $this->json_do->set_error('004', '支付异常，待支付金额小于实际支付金额');
        }

        switch ($fdata['pay_type']) {
            case 1:     //现金支付
                $this->_cashPay($fdata);
                break;
            case 2:
                $this->_alipayFFPay($fdata);
                break;
            case 3: //微信支付
                $this->_wexinMciroPay($fdata);
                break;
            case 4: //余额支付（会员储值）
                $this->_balancePay($fdata);
                break;
            case 5: //付呗微信
                $this->_fubeiPay($fdata);
                break;
            case 6: //付呗支付宝
                $this->_fubeiPay($fdata);
                break;
            case 7: //刷卡消费
                $this->_cardPay($fdata);
                break;
            default:
                $this->json_do->set_error('004','不支持的支付类型');
                break;
        }

    }
    /**
     * 现金支付
     * @param  [type] $fdata       aid必填
     * @param  [type] $m_meal_table [description]
     * @return [type]               [description]
     */
    private function _cashPay($fdata)
    {
        $trade_no = create_serial_number('T1ZF');
        $input = [
            'pay_trade_no' => $trade_no,
            'order_table_id' => 0,
            'pay_type' => $fdata['pay_type'],
            'sum_pay_money' => $fdata['sum_pay_money'],
            'payment_record_id' => 0,
            'pay_source' => 2,
            'source_type' => $fdata['source_type'],//3零售
            'tid' => $fdata['tid'],//3零售
            'aid' => $fdata['aid']
        ];

        $meal_statements_bll = new meal_statements_bll();
        $return = $meal_statements_bll->checkout($input, true);

        $serial_number = '';
        $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
        $m_statement = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_number'=>$trade_no, 'type'=>1]);
        if($m_statement)
            $serial_number = $m_statement->serial_number;

        if($return)
        {
            $this->json_do->set_data(['trade_no'=>$trade_no, 'serial_number'=>$serial_number]);
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005', '失败2');
    }
    /**
     * 微信支付
     * @param  [type] $fdata        [description]
     * @param  [type] $m_meal_table [description]
     * @return [type]               [description]
     */
    private function _alipayFFPay($fdata)
    {
        //校验公众号配置
        $wmAlipayConfigCache = new wmAlipayConfigCache(['aid'=>$fdata['aid']]);
        $config = $wmAlipayConfigCache->getDataASNX();
        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $fdata['aid'];
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $this->s_user->shop_id;
        $data['gateway'] = 'alipay';
        $data['source'] = 'sy';
        $data['appid'] = $config->app_id;
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = 0;
        $data['tid'] = $fdata['tid'];
        $mealPaymentRecordDao->create($data);

        try{
            //2.调取支付宝当面付
            $conf['charset'] = 'UTF-8';
            $conf['gatewayUrl'] = 'https://openapi.alipay.com/gateway.do';
            $conf['notify_url'] = '';
            $conf['MaxQueryRetry'] = 10;
            $conf['QueryDuration'] = 3;

            $conf['sign_type'] = $config->sign_type == 1 ? 'RSA':'RSA2';
            $conf['app_id'] = $config->app_id;
            $conf['alipay_public_key'] = $config->alipay_public_key;
            $conf['merchant_private_key'] = $config->merchant_private_key;

            // 创建请求builder，设置请求参数
            $barPayRequestBuilder = new AlipayTradePayContentBuilder();
            $barPayRequestBuilder->setOutTradeNo($data['code']);
            $barPayRequestBuilder->setTotalAmount($fdata['sum_pay_money']);
            $barPayRequestBuilder->setAuthCode($fdata['auth_code']);
            $barPayRequestBuilder->setTimeExpress('5m');
            $barPayRequestBuilder->setSubject('堂食买单');
            $barPayRequestBuilder->setBody('条码支付-堂食订单');

            // 调用barPay方法获取当面付应答
            $barPay = new AlipayTradeService($conf);
            $barPayResult = $barPay->barPay($barPayRequestBuilder);

            if($barPayResult->getTradeStatus()!='SUCCESS')
            {
                log_message('error',__METHOD__.'-'.json_encode($barPayResult->getResponse()));
                $this->json_do->set_error('004', '支付失败alipay');
            }
        } catch(Exception $e) {
            log_message('error', __METHOD__."-{$this->s_user->aid}-".$e->getMessage());
            $this->json_do->set_error('004', '支付失败alipay');
        }
        //淘宝支付订单号
        $trade_no = $barPayResult->getResponse()->trade_no;
        //3.收付款支付成功
        $result = $mealPaymentRecordDao->alipayOrderCallback($data['code'], $trade_no);

        if ($result) {
            //流水信息
            $serial_number = '';
            $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
            $m_statement = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_number'=>$trade_no, 'type'=>1]);
            if($m_statement)
                $serial_number = $m_statement->serial_number;

            $this->json_do->set_data(['trade_no'=>$trade_no, 'serial_number'=>$serial_number]);
            $this->json_do->set_msg('支付成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '支付失败');
        }

    }
    /**
     * 微信支付
     * @param  [type] $fdata        [description]
     * @param  [type] $m_meal_table [description]
     * @return [type]               [description]
     */
    private function _wexinMciroPay($fdata)
    {
        //校验公众号配置

        $wmGzhConfigCache = new WmGzhConfigCache(['aid'=>$fdata['aid']]);
        $config = $wmGzhConfigCache->getDataASNX();
        if(empty($config->app_id) || empty($config->app_secret))
        {
            $this->json_do->set_error('004', '公众号未配置');
        }
        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $fdata['aid'];
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $this->s_user->shop_id;
        $data['gateway'] = 'wx_micro';
        $data['source'] = 'sy';
        $data['appid'] = $config->app_id;
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = 0;
        $data['tid'] = $fdata['tid'];
        $mealPaymentRecordDao->create($data);

        try{
            //2.调取微信收付款支付
            ci_wxpay::load('micro',$this->s_user->aid);
            $input = new WxPayMicroPay();
            $input->SetAuth_code($fdata['auth_code']);      //支付授权码
            $input->SetBody('订单支付-收付款');
            $input->SetTotal_fee($fdata['sum_pay_money'] * 100); //支付金额
            $input->SetOut_trade_no($data['code']);     //支付单号
            $microPay = new MicroPay();
            $res = $microPay->pay($input);
            if(empty($res))
                $this->json_do->set_error('004', '支付失败wx');
        } catch(Exception $e) {
            log_message('error', __METHOD__."-{$this->s_user->aid}-".$e->getMessage());
            $this->json_do->set_error('004', '支付失败wx');
        }


        //3.收付款支付成功
        $result = $mealPaymentRecordDao->wxMicroOrderCallback($data['code'], $res['transaction_id']);

        if ($result) {
            //流水信息
            $serial_number = '';
            $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
            $m_statement = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_number'=>$res['transaction_id'], 'type'=>1]);
            if($m_statement)
                $serial_number = $m_statement->serial_number;

            $this->json_do->set_data(['trade_no'=>$res['transaction_id'], 'serial_number'=>$serial_number]);
            $this->json_do->set_msg('支付成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('004', '支付失败');
        }
        //$res['transaction_id'] //微信支付单号

    }

    /**
     * 余额支付（会员储值）
     * @param $fdata
     * @param $m_meal_table
     */
    private function _balancePay($fdata)
    {
        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $fdata['aid'];
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $this->s_user->shop_id;
        $data['gateway'] = 'inner';
        $data['source'] = 'sy';
        $data['appid'] = '';
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = 0;
        $data['tid'] = $fdata['tid'];
        $data['mobile'] = $fdata['mobile'];
        $record_id = $mealPaymentRecordDao->create($data);

        // 获取visit_id
        $mainCompanyDao = MainCompanyDao::i();
        $m_main_company = $mainCompanyDao->getOne(['id' => $this->s_user->aid], 'visit_id');

        //店铺信息
        $wmShopCache = new WmShopCache(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id]);
        $wmShopData = $wmShopCache->getDataASNX();

        // 获取手机号
        $scrmParams['visit_id'] = $m_main_company->visit_id;
        $scrmParams['phone'] = $fdata['mobile'];
        $scrmParams['money'] = $fdata['sum_pay_money'];
        $scrmParams['openid'] = '';
        $scrmParams['shop_name'] = $wmShopData->shop_name;

        $result = ci_scrm::call(ci_scrm::USE_FANS_ACCOUNT, $scrmParams, $source = 't1');
        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }
        if ($result['code'] != 0) {
            log_message('error', __METHOD__.json_encode($result));
            $this->json_do->set_error('004', $result['data']);
        }


        $trade_no = create_serial_number('T1');
        //更新记录状态
        $mealPaymentRecordDao->update(['status' => 1, 'trade_no' => $trade_no], ['id' => $record_id, 'aid' => $this->s_user->aid]);

        $input = [
            'pay_trade_no' => $trade_no,
            'order_table_id' => 0,
            'pay_type' => $fdata['pay_type'],
            'sum_pay_money' => $fdata['sum_pay_money'],
            'payment_record_id' => 0,
            'pay_source' => 2,
            'source_type' => $fdata['source_type'],//1点餐
            'tid' => $fdata['tid'],
            'aid'=>$fdata['aid']

        ];

        $meal_statements_bll = new meal_statements_bll();
        $return = $meal_statements_bll->checkout($input, true);

        $serial_number = '';
        $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
        $m_statement = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_number'=>$trade_no, 'type'=>1]);
        if($m_statement)
            $serial_number = $m_statement->serial_number;

        if($return)
        {
            $this->json_do->set_data(['tid'=>$trade_no, 'serial_number'=>$serial_number]);
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005', '失败');
    }

    /**
     * 银行通道支付
     * @param $fdata
     */
    private function _fubeiPay($fdata)
    {

        $wmFubeiRefshopDao = WmFubeiRefshopDao::i($this->s_user->aid);
        $m_wm_fubei_refshop = $wmFubeiRefshopDao->getOne(['aid'=>$this->s_user->aid,'shop_id'=>$this->s_user->shop_id]);
        if(!$m_wm_fubei_refshop)
            $this->json_do->set_error('004', '门店银行通道未配置');
        $wmFubeiConfigCache = new WmFubeiConfigCache(['aid'=>$this->s_user->aid,'fubei_id'=>$m_wm_fubei_refshop->fubei_id]);
        $m_pay_fubei = $wmFubeiConfigCache->getDataASNX();

        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $fdata['aid'];
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $this->s_user->shop_id;
        $data['source'] = 'sy';
        $data['appid'] = $m_pay_fubei->app_id;
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = 0;
        $data['tid'] = $fdata['tid'];

        if($fdata['pay_type'] == 5)
        {
            $type = 1;
            $data['gateway'] = 'fb_wxmicro';

        }
        elseif($fdata['pay_type'] == 6)
        {
            $type = 2;
            $data['gateway'] = 'fb_alipay';
        }
        else
            $this->json_do->set_error('004', '支付类型错误');



        $record_id = $mealPaymentRecordDao->create($data);

        try{
            $config['app_id'] = $m_pay_fubei->app_id;
            $config['app_secret'] = $m_pay_fubei->app_secret;
            $config['store_id'] = (int)$m_pay_fubei->store_id;

            //支付方式[微信1/支付宝2]
            $input['type'] = $type;
            $input['merchant_order_sn'] = $data['code'];
            $input['auth_code'] = $fdata['auth_code'];
            $input['total_fee'] = $fdata['sum_pay_money'];
            $fubei_sdk = new fubei_sdk($config);
            $res = $fubei_sdk->loopSwip($input);

            $trade_no = $res->data->order_sn;

            //更新记录状态
            $mealPaymentRecordDao->update(['status' => 1, 'trade_no' => $trade_no], ['id' => $record_id, 'aid' => $this->s_user->aid]);

            $input = [
                'pay_trade_no' => $trade_no,
                'order_table_id' => 0,
                'pay_type' => $fdata['pay_type'],
                'sum_pay_money' => $fdata['sum_pay_money'],
                'payment_record_id' => 0,
                'pay_source' => 2,
                'source_type' => $fdata['source_type'],//1点餐
                'tid' => $fdata['tid'],
                'aid'=>$fdata['aid']
            ];

            $meal_statements_bll = new meal_statements_bll();
            $return = $meal_statements_bll->checkout($input, true);

            $serial_number = '';
            $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
            $m_statement = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_number'=>$trade_no, 'type'=>1]);
            if($m_statement)
                $serial_number = $m_statement->serial_number;

            if($return)
            {
                $this->json_do->set_data(['tid'=>$trade_no, 'serial_number'=>$serial_number]);
                $this->json_do->set_msg('成功');
                $this->json_do->out_put();
            }
            $this->json_do->set_error('005', '失败');

        }catch(Exception $e){
            log_message('error', __METHOD__."-{$this->s_user->aid}-".$e->getMessage());
            $this->json_do->set_error('004', '支付失败wx');
        }
    }

    /**
     * 刷卡消费
     * @param $fdata
     */
    private function _cardPay($fdata)
    {
        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $fdata['aid'];
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $this->s_user->shop_id;
        $data['gateway'] = 'card';
        $data['source'] = 'sy';
        $data['appid'] = '';
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = 0;
        $data['tid'] = $fdata['tid'];
        $data['ext'] = $fdata['card_code'];
        $record_id = $mealPaymentRecordDao->create($data);

        //店铺信息
        $wmShopCache = new WmShopCache(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id]);
        $wmShopData = $wmShopCache->getDataASNX();

        // 获取手机号
        $scrmParams['visit_id'] = $this->s_user->visit_id;
        $scrmParams['card_code'] = $fdata['card_code'];
        $scrmParams['money'] =  $fdata['sum_pay_money'];
        $scrmParams['shop_name'] = $wmShopData->shop_name;


        $result = ci_scrm::call(ci_scrm::CONSUME, $scrmParams, $source = 't1');
        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }
        if ($result['code'] != 0) {
            log_message('error', __METHOD__.json_encode($result));
            $this->json_do->set_error('004', $result['data']);
        }


        $trade_no = create_serial_number('T1');
        //更新记录状态
        $mealPaymentRecordDao->update(['status' => 1, 'trade_no' => $trade_no], ['id' => $record_id, 'aid' => $this->s_user->aid]);

        $input = [
            'pay_trade_no' => $trade_no,
            'order_table_id' => 0,
            'pay_type' => $fdata['pay_type'],
            'sum_pay_money' => $fdata['sum_pay_money'],
            'payment_record_id' => 0,
            'pay_source' => 2,
            'source_type' => $fdata['source_type'],//1点餐
            'tid' => $fdata['tid'],
            'aid' => $fdata['aid']
        ];

        $meal_statements_bll = new meal_statements_bll();
        $return = $meal_statements_bll->checkout($input, true);

        $serial_number = '';
        $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
        $m_statement = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_number'=>$trade_no, 'type'=>1]);
        if($m_statement)
            $serial_number = $m_statement->serial_number;

        if($return)
        {
            $this->json_do->set_data(['tid'=>$trade_no, 'serial_number'=>$serial_number]);
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005', '失败');
    }
}
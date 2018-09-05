<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 支付处理接口（小程序用户端）
 * @author dadi
 */

use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPaymentRecordDao;

use Service\Cache\WmShopCache;

class Payment extends xcx_user_controller
{
    // SCRM接口请求参数
    public $scrmParams = [];

    // 微信支付
    public function weixin()
    {
        if (!$this->s_user->ext['open_id']) {
            $this->json_do->set_error('004', '未登录，下单失败');
        }

        $rule = [
            ['field' => 'type', 'label' => '类型', 'rules' => 'trim|required'],
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim'],
            ['field' => 'amount', 'label' => '充值金额', 'rules' => 'trim|numeric'],
        ];

        $wmPaymentRecordDao = WmPaymentRecordDao::i($this->aid);

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 查询订单数据
        if ($fdata['type'] == 'order') {
            if (!$fdata['tid']) {
                $this->json_do->set_error('004', '参数错误，订单号必填');
            }

            $wmOrderDao = WmOrderDao::i($this->aid);
            $order = $wmOrderDao->getOne(['tid' => $fdata['tid'], 'aid' => $this->aid]);
            if (!$order) {
                $this->json_do->set_error('004', '订单不存在');
            }
            // 判断订单是否处于未支付状态
            if ($order->status != '1010') {
                $this->json_do->set_error('004', '当前订单状态不支持支付');
            }

            // 支付订单记录
            $record = $wmPaymentRecordDao->getOne(['code' => $order->tid, 'gateway' => 'weixin', 'uid' => $this->s_user->uid,'aid' => $this->aid]);

            $data['uid'] = $this->s_user->uid;
            $data['aid'] = $order->aid;
            $data['shop_id'] = $order->shop_id;
            $data['type'] = $fdata['type'];
            $data['gateway'] = 'weixin';
            $data['source'] = 'xcx';
            $data['appid'] = $this->app_id;
            $data['money'] = $order->pay_money;
            $data['code'] = $order->tid;

            if ($record) {
                if ($record->status > 0) {
                    $this->json_do->set_error('004', '该订单已经支付，请勿重复支付');
                }
                if ($record->money != $order->pay_money) {
                    $this->json_do->set_error('004', '订单金额异常，请勿支付');
                }
            } else {
                $wmPaymentRecordDao->create($data);
            }

            $subject = '订单支付';
            $body = '';
        } else if ($fdata['type'] == 'deposit') {
            if (!$fdata['amount']) {
                $this->json_do->set_error('004', '参数错误，金额必填');
            }

            $data['uid'] = $this->s_user->uid;
            $data['aid'] = $this->aid;
            $data['shop_id'] = 0;
            $data['type'] = 'deposit';
            $data['gateway'] = 'weixin';
            $data['source'] = 'xcx';
            $data['appid'] = $this->app_id;
            $data['money'] = $fdata['amount'];
            $data['code'] = create_order_number();
            $wmPaymentRecordDao->create($data);

            $subject = '储值卡充值';
            $body = '';
        } else {
            $this->json_do->set_error('004', '不支持的类型');
        }

        // 本地环境直接支付成功
        if (in_array(ENVIRONMENT, ['development'])) {
            $result = $wmPaymentRecordDao->weixinOrderCallback($data['code'], 1111);

            if ($result) {
                $this->json_do->set_msg('支付成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('004', '支付失败');
            }

        }

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
        $input->SetNotify_url(API_URL . 'mshop/paynotify/wxpay/'.$this->aid);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($this->s_user->ext['open_id']);
        $payorder = WxPayApi::unifiedOrder($input);

        if ($payorder['return_code'] == 'FAIL') {
            log_message('error',__METHOD__.json_encode($payorder));
            $this->json_do->set_error('004', $payorder['return_msg']);
        }

        if ($payorder['result_code'] == 'FAIL' && $payorder['err_code'] == 'ORDERPAID') {
            log_message('error',__METHOD__.json_encode($payorder));
            $this->json_do->set_error('004', '该订单已经支付，请勿重复支付');
        }

        if ($payorder['return_code'] == 'FAIL') {
            log_message('error',__METHOD__.json_encode($payorder));
            $this->json_do->set_error('004', '支付订单创建失败');
        }

        $ret['param'] = $tools->GetJsApiParameters($payorder);

        $this->json_do->set_data($ret['param']);
        $this->json_do->out_put();
    }

    // 微信公众号支付结果查询接口
    // 给前端查询支付结果
    public function weixin_result()
    {
        if (!$this->s_user->uid) {
            $this->json_do->set_error('004', '未登录，下单失败');
        }

        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmPaymentRecordDao = WmPaymentRecordDao::i($this->aid);
        $result = $wmPaymentRecordDao->getOne(['code' => $fdata['tid'], 'gateway' => 'weixin', 'uid' => $this->s_user->uid,'aid' => $this->aid], 'status', 'id desc');

        if (!isset($result->status)) {
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

    // 余额支付
    public function balancepay()
    {
        if (!$this->s_user->ext['open_id']) {
            $this->json_do->set_error('004', '未登录，下单失败');
        }

        $rule = [
            ['field' => 'type', 'label' => '类型', 'rules' => 'trim|required'],
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim'],
        ];

        $wmPaymentRecordDao = WmPaymentRecordDao::i($this->aid);

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 查询订单数据
        if ($fdata['type'] == 'order') {
            if (!$fdata['tid']) {
                $this->json_do->set_error('004', '参数错误，订单号必填');
            }

            $wmOrderDao = WmOrderDao::i($this->aid);
            $order = $wmOrderDao->getOne(['tid' => $fdata['tid'],'aid' => $this->aid]);
            if (!$order) {
                $this->json_do->set_error('004', '订单不存在');
            }
            // 判断订单是否处于未支付状态
            if ($order->status != '1010') {
                $this->json_do->set_error('004', '当前订单状态不支持支付');
            }

            // 支付订单记录
            $record = $wmPaymentRecordDao->getOne(['code' => $order->tid, 'gateway' => 'balance', 'uid' => $this->s_user->uid,'aid' => $this->aid]);

            if ($record) {
                if ($record->status > 0) {
                    $this->json_do->set_error('004', '该订单已经支付，请勿重复支付');
                }
                if ($record->money != $order->pay_money) {
                    $this->json_do->set_error('004', '订单金额异常，请勿支付');
                }
            } else {
                $data['uid'] = $this->s_user->uid;
                $data['aid'] = $order->aid;
                $data['shop_id'] = $order->shop_id;
                $data['type'] = $fdata['type'];
                $data['gateway'] = 'balance';
                $data['money'] = $order->pay_money;
                $data['code'] = $order->tid;
                $wmPaymentRecordDao->create($data);
            }

            $subject = '订单支付';
            $body = '';
        } else {
            $this->json_do->set_error('004', '不支持的类型');
        }

        if (in_array(ENVIRONMENT, ['development'])) {
            $this->scrmParams['openid'] = 'ovPui0Vs-iKyricaa40m-FY84WBs';
            $this->scrmParams['visit_id'] = '9169744';
            $this->scrmParams['phone'] = '15505885184';
        } else {
            // 获取openid
            $this->scrmParams['openid'] = $this->s_user->ext['open_id'];
            // 获取visit_id
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id' => $this->aid], 'visit_id');

            $this->scrmParams['visit_id'] = $m_main_company->visit_id;
            // 获取手机号
            $this->scrmParams['phone'] = $this->s_user->mobile;
        }

        //店铺信息
        $wmShopCache = new WmShopCache(['aid' => $this->aid, 'shop_id' => $order->shop_id]);
        $wmShopData = $wmShopCache->getDataASNX();
        $this->scrmParams['shop_name'] = $wmShopData->shop_name;

        $api = ci_scrm::USE_FANS_ACCOUNT;
        $this->scrmParams['money'] = $order->pay_money;
        $result = ci_scrm::call($api, $this->scrmParams, $source = 'xcx');

        if (!$result) {
            $this->json_do->set_error('004', '接口错误，请稍候重试');
        }

        if ($result['code'] != 0) {
            $this->json_do->set_error('004', $result['data']);
        }

        $wmPaymentRecordDao->balanceOrderCallback($data['code'], 1111);

        $this->json_do->set_msg('支付成功');
        $this->json_do->out_put();
    }

}

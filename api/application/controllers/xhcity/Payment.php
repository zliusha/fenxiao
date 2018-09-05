<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/21
 * Time: 14:49
 */

use Service\Support\FLock;
use Service\Bll\Hcity\PayBll;
use Service\Bll\Hcity\OrderEventBll;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityPaymentRecordDao;

class Payment extends xhcity_user_controller
{
    /**
     * 订单支付
     */
    public function pay_order()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单编号', 'rules' => 'trim|required|numeric'],
            ['field' => 'aid', 'label' => '商户ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'pay_type', 'label' => '支付类型', 'rules' => 'trim|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        //校验用户状态
        $this->check_user_status();

        $shcityOrderDao = ShcityOrderDao::i(['aid'=>$fdata['aid']]);
        $order = $shcityOrderDao->getOne(['tid' => $fdata['tid'],'aid'=>$fdata['aid'], 'uid'=>$this->s_user->uid]);
        if (!$order) {
            $this->json_do->set_error('004', '订单不存在');
        }

        // 判断订单是否处于未支付状态
        if ($order->status != 0) {
            $this->json_do->set_error('004', '当前订单状态不支持支付');
        }
        // 是否更换支付方式
        if(!empty($fdata['pay_type']))
        {
            //嘿卡币支付，不支持更换支付方式，额外补钱 支付默认是余额
            if($order->pay_type == 4 && $fdata['pay_type']!=$order->pay_type)
            {
                $this->json_do->set_error('004', '支付方式无效');
            }
            else
            {
                if(!in_array($fdata['pay_type'], [1, 2, 3]))
                {
                    $this->json_do->set_error('004', '支付方式不存在');
                }

                $shcityOrderDao->update(['pay_type'=>$fdata['pay_type']], ['aid'=>$order->aid, 'tid'=>$order->tid]);

                $order->pay_type = $fdata['pay_type'];
            }
        }


        $params = '';
        switch($order->pay_type)
        {
            case 1://会员储值支付
                $this->json_do->set_error('005', '暂不支持会员储值支付');
                break;
            case 2://余额支付
                $this->_balancePay($order);
                break;
            case 3://微信支付
                $params = $this->_wxPay($order);
                break;
            case 4://嘿卡支付
                $this->_heyCoinPay($order);
                break;
            default:
                $this->json_do->set_error('005', '支付方式异常');
                break;
        }

        $ret['params'] = $params;
        $ret['tid'] = $order->tid; // 支付订单号
        $ret['pay_type'] = $order->pay_type;
        $ret['server_time'] = date('Y-m-d H:i:s'); // 服务器时间

        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }

    /**
     * 微信支付
     * @param $order 主订单对象
     */
    private function _wxPay($order)
    {
        //加锁
        if(!FLock::getInstance()->lock('PayBll:wxJsapiPay:' . $order->uid))
        {
            $this->json_do->set_error('005', '系统繁忙,请稍后再试。');
        }
        // 支付订单记录
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $record = $hcityPaymentRecordDao->getOne(['inner_trade_no' => $order->tid, 'uid' => $this->s_user->uid,'aid'=>$order->aid,'status'=>1]);
        $data['uid'] = $order->uid;
        $data['aid'] = $order->aid;
        $data['shop_id'] = $order->shop_id;
        $data['type'] = 1;
        $data['pay_type'] = 3;
        $data['pay_source'] = 'xcx';
        $data['source_type'] = $order->source_type;;
        $data['money'] = $order->payment;
        $data['pay_tid'] = create_order_number();
        $data['inner_trade_no'] = $order->tid;

        if ($record) {
            throw new Exception('该订单已经支付，请勿重复支付');
        } else {
            $hcityPaymentRecordDao->create($data);
        }
        $payBll = new PayBll();
        $params = $payBll->wxJsapiPay($data['uid'], $this->s_user->openid, $data['pay_tid']);

        return $params;
    }
    /**
     * 余额支付
     * @param $order 主订单对象
     */
    private function _balancePay($order)
    {
        //加锁
        if(!FLock::getInstance()->lock('PayBll:balancePay:' . $order->uid))
        {
            $this->json_do->set_error('005', '系统繁忙,请稍后再试。');
        }

        // 支付订单记录
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $record = $hcityPaymentRecordDao->getOne(['inner_trade_no' => $order->tid, 'uid' => $this->s_user->uid,'aid'=>$order->aid,'status'=>1]);
        $data['uid'] = $order->uid;
        $data['aid'] = $order->aid;
        $data['shop_id'] = $order->shop_id;
        $data['type'] = 1;
        $data['pay_type'] = 2;
        $data['pay_source'] = 'xcx';
        $data['source_type'] = $order->source_type;;
        $data['money'] = $order->payment;
        $data['pay_tid'] = create_order_number();
        $data['inner_trade_no'] = $order->tid;

        if ($record) {
            throw new Exception('该订单已经支付，请勿重复支付');
        } else {
            $hcityPaymentRecordDao->create($data);
        }
        //调取支付接口
        $payBll = new PayBll();
        $return = $payBll->balancePay($data['uid'],  $data['pay_tid']);
        //通知支付成功
        $payBll->notifyPaySuccess($data['pay_tid']);
    }

    /**
     * 嘿卡币不足，余额支付
     * @param $order
     */
    public function _heyCoinPay($order)
    {
        // 支付订单记录
        $hcityPaymentRecordDao = HcityPaymentRecordDao::i();
        $record = $hcityPaymentRecordDao->getOne(['inner_trade_no' => $order->tid, 'uid' => $order->uid,'aid'=>$order->aid, 'pay_type'=>4,'status'=>1]);

        $data['uid'] = $order->uid;
        $data['aid'] = $order->aid;
        $data['shop_id'] = $order->shop_id;
        $data['type'] = 1;
        $data['pay_type'] = 4;
        $data['pay_source'] = 'xcx';
        $data['source_type'] = $order->source_type;
        $data['money'] = $order->pay_hey_coin;
        $data['pay_tid'] = create_order_number();
        $data['inner_trade_no'] = $order->tid;

        if ($record) {
            throw new Exception('该订单已经支付，请勿重复支付');
        } else {
            //是否需要余额支付
            if($order->payment>0)
            {
                $money_data['uid'] = $order->uid;
                $money_data['aid'] = $order->aid;
                $money_data['shop_id'] = $order->shop_id;
                $money_data['type'] = 1;
                $money_data['pay_type'] = 2;
                $money_data['pay_source'] = 'xcx';
                $money_data['source_type'] = $order->source_type;
                $money_data['money'] = $order->payment;
                $money_data['pay_tid'] = create_order_number();
                $money_data['inner_trade_no'] = $order->tid;
                $hcityPaymentRecordDao->create($money_data);
            }
            $hcityPaymentRecordDao->create($data);
        }

        $payBll = new PayBll();
        //==开启支付==
        $hcityMainDb = HcityMainDb::i();
        try{

            $hcityMainDb->trans_start();
            //加锁
            if(!FLock::getInstance()->lock('PayBll:heyCoinPay:' . $order->uid))
            {
                throw new Exception('系统繁忙,请稍后再试');
            }
            $return = $payBll->heyCoinPay($data['uid'],  $data['pay_tid'], false);
            //释放锁
            FLock::getInstance()->unlock();
            //是否需要余额支付
            if($order->payment>0)
            {
                //加锁
                if(!FLock::getInstance()->lock('PayBll:balancePay:' . $order->uid))
                {
                    throw new Exception('系统繁忙,请稍后再试');
                }
                $payBll->balancePay($data['uid'],  $money_data['pay_tid'], false);
            }

            if($hcityMainDb->trans_status() === FALSE)
            {
                log_message('error',__METHOD__."订单支付失败:".json_encode($order));
                $hcityMainDb->trans_rollback();
                throw new Exception('订单支付失败');
            }
            $hcityMainDb->trans_commit();
            //通知支付成功
            $payBll->notifyPaySuccess($data['pay_tid']);
        }catch(Exception $e){
            log_message('error',__METHOD__."订单支付失败msg:".$e->getMessage());
            log_message('error',__METHOD__."订单支付失败2:".json_encode($order));
            $hcityMainDb->trans_rollback();

            //福利池商品支付失败取消订单
            $input = ['aid' => $order->aid, 'tid' => $order->tid];
            try {
                $orderEventBll = new OrderEventBll();
                $orderEventBll->cancelOrder($input, 'NOTIFY_UNPAID_TIMEOUT');
            } catch (\Exception $e) {
                log_message('error', __METHOD__ . '订单过期通知失败:' . json_encode($input));
                log_message('error', __METHOD__ . $e->getMessage());
            }

            throw new Exception($e->getMessage());
        }

    }
}
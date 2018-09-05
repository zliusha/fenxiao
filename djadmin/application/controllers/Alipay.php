<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaMoneyRecordDao;
/**
 * 支付宝支付网关
 */
class Alipay extends base_controller
{
    // 支付宝商家充值请求页面
    public function dianwoda()
    {
        require_once APPPATH . "pay/alipay/alipay.config.php";
        require_once APPPATH . "pay/alipay/lib/alipay_submit.class.php";

        if (!$this->s_user->id) {
            redirect(SITE_URL . 'passport/login');
        }

        // 商户订单号，商户网站订单系统中唯一订单号，必填
        // $out_trade_no = '20161009163312321';
        // // 订单名称，必填
        // $subject = '充值测试';
        // // 付款金额，必填
        // $total_fee = '0.01';
        // // 商品描述，可空
        // $body = '描述';
        /**************************请求参数**************************/
        // 商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = create_order_number();
        // 订单名称，必填
        $subject = '云店宝充值';
        // 付款金额，必填
        $total_fee = $this->input->post_get('amount');
        // $total_fee = '0.01';
        // 商品描述，可空
        $body = '点我达商户充值';

        // 记录入库
        $wmDianwodaMoneyRecordDao = WmDianwodaMoneyRecordDao::i($this->s_user->aid);

        $data['uid'] = $this->s_user->id;
        $data['aid'] = $this->s_user->aid;
        $data['shop_id'] = $this->currentShopId;
        $data['type'] = 1;
        $data['gateway'] = 'alipay';
        $data['money'] = $total_fee;
        $data['code'] = $out_trade_no;
        $wmDianwodaMoneyRecordDao->create($data);

        /************************************************************/

        // 测试环境模拟支付
        if (in_array(ENVIRONMENT, ['development'])) {
            $trade_no = '1111';
            $wmDianwodaMoneyRecordDao->deposit($out_trade_no, $trade_no);

            redirect(DJADMIN_URL . 'mshop/pay/success');
        }

        // 构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => $alipay_config['service'],
            "partner" => $alipay_config['partner'],
            "seller_id" => $alipay_config['seller_id'],
            "payment_type" => $alipay_config['payment_type'],
            "notify_url" => $alipay_config['notify_url'].'/'.$this->s_user->aid,
            "return_url" => $alipay_config['return_url'].'/'.$this->s_user->aid,

            "anti_phishing_key" => $alipay_config['anti_phishing_key'],
            "exter_invoke_ip" => $alipay_config['exter_invoke_ip'],
            "out_trade_no" => $out_trade_no,
            "subject" => $subject,
            "total_fee" => $total_fee,
            "body" => $body,
            "_input_charset" => trim(strtolower($alipay_config['input_charset'])),
            // 其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
            // 如"参数名"=>"参数值"

        );

        // 建立请求
        $alipaySubmit = new AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        echo $html_text;
    }

    // 异步支付结果通知 达到率99.999%
    public function notify_dianwoda($aid)
    {
        if (!is_numeric($aid)) {
            $this->_error();
        }

        require_once APPPATH . "pay/alipay/alipay.config.php";
        require_once APPPATH . "pay/alipay/lib/alipay_notify.class.php";

        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if ($verify_result) {
            //验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号
            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];
            $wmDianwodaMoneyRecordDao = WmDianwodaMoneyRecordDao::i($aid);

            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                $wmDianwodaMoneyRecordDao->deposit($out_trade_no, $trade_no);
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                $wmDianwodaMoneyRecordDao->deposit($out_trade_no, $trade_no);
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success"; //请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    // 同步支付结果通知
    public function return_dianwoda($aid)
    {
        if (!is_numeric($aid)) {
            $this->_error();
        }

        require_once APPPATH . "pay/alipay/alipay.config.php";
        require_once APPPATH . "pay/alipay/lib/alipay_notify.class.php";

        //计算得出通知验证结果
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        if ($verify_result) {
            //验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];

            //支付宝交易号
            $trade_no = $_GET['trade_no'];

            //交易状态
            $trade_status = $_GET['trade_status'];
            $wmDianwodaMoneyRecordDao = WmDianwodaMoneyRecordDao::i($this->s_user->aid);

            if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                $wmDianwodaMoneyRecordDao->deposit($out_trade_no, $trade_no);
            } else {
                // echo "trade_status=" . $_GET['trade_status'];
            }

            // echo "验证成功";
            redirect(DJADMIN_URL . 'mshop/pay/success');
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            //如要调试，请看alipay_notify.php页面的verifyReturn函数
            echo "验证失败";
        }
    }


}

<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/6/11
 * Time: 16:43
 */
use Service\Cache\WmGzhConfigCache;

require_once LIBS_PATH.'libraries/alipay/f2fpay/model/builder/AlipayTradePayContentBuilder.php';
require_once LIBS_PATH.'libraries/alipay/f2fpay/service/AlipayTradeService.php';
use Service\Cache\WmFubeiConfigCache;

use Service\DbFrame\DataBase\WmShardDbModels\MealPaymentRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmAlipayConfigDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmFubeiRefshopDao;
class Member extends sy_controller
{
    /**
     * 会员搜索
     */
    public function search()
    {
        $rule = [
            ['field' => 'code', 'label' => '手机号/会员卡号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::SEARCH_MEMBER;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['code'] = $fdata['code'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $data['code'] = $result['code'];
                    if(empty($result['data']))
                    {
                        $data['code'] = 2002;//手机号不存在
                        $data['info'] = null;
                    }
                    else
                    {
                        $data['info'] = $result['data'];
                    }
                    $this->json_do->set_data($data);
                    $this->json_do->out_put();
                }
                else
                {
                    $data = [];
                    if($result['code'] == 2001)//无效卡号，code为2001，data为错误提示
                    {
                        $data['code'] = $result['code'];
                    }
                    $this->json_do->set_data($data);
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 创建用户
     */
    public function create_user()
    {
        $rule = [
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::CREATE_MEMBER;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['phone'] = $fdata['mobile'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $data['info'] = $result['data'];
                    $this->json_do->set_data($data);
                    $this->json_do->out_put();
                }
                else
                {
                    $data = [];
                    if($result['code'] == 2001)//无效卡号，code为2001，data为错误提示
                    {
                        $data['code'] = $result['code'];
                    }
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 绑定会员卡
     */
    public function bind_card()
    {
        $rule = [
            ['field' => 'm_id', 'label' => '会员ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'is_change', 'label' => '是否更改', 'rules' => 'trim|required|numeric|in_list[0,1]']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::BIND_MASTER_CARD;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['m_id'] = $fdata['m_id'];
            $params['is_change'] = $fdata['is_change'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $data['info'] = $result['data'];
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $data = [];
                    if($result['code'] == 2001)//无效卡号，code为2001，data为错误提示
                    {
                        $data['code'] = $result['code'];
                    }
                    $this->json_do->set_data($data);
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }


    /**
     * 创建礼品卡
     */
    public function create_giftcard()
    {
        $rule = [
            ['field' => 'money', 'label' => '金额', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::CREATE_GIFT_CARD;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['money'] = $fdata['money'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 获取礼品卡开卡列表接口
     */
    public function giftcard_list()
    {
        $rule = [
            ['field' => 'limit', 'label' => '记录条数', 'rules' => 'trim|numeric'],
            ['field' => 'page', 'label' => '页码', 'rules' => 'trim|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::GIFT_CARD_LIST;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['limit'] = $fdata['limit'];
            $params['page'] = $fdata['page'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }
    /**
     * 添加副卡
     */
    public function add_vicecard()
    {
        $rule = [
            ['field' => 'm_id', 'label' => '会员ID', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::BIND_VICE_CARD;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['m_id'] = $fdata['m_id'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 副卡列表
     */
    public  function vicecard_list()
    {
        $rule = [
            ['field' => 'm_id', 'label' => '会员ID', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::VICE_CARD_LIST;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['m_id'] = $fdata['m_id'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $this->json_do->set_error('005', $result['data']);
                }
            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 注销副卡
     */
    public  function cancel_vicecard()
    {
        $rule = [
            ['field' => 'card_code', 'label' => '卡号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::CANCEL_VICE_CARD;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['card_code'] = $fdata['card_code'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 余额明细
     */
    public function balance_list()
    {
        $rule = [
            ['field' => 'm_id', 'label' => '会员ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'limit', 'label' => '记录条数', 'rules' => 'trim|numeric'],
            ['field' => 'page', 'label' => '页码', 'rules' => 'trim|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::DETAIL_LIST;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['m_id'] = $fdata['m_id'];
            $params['limit'] = (int)$fdata['limit'];
            $params['page'] = (int)$fdata['page'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 礼品卡充值
     */
    public function giftcard_recharge()
    {
        $rule = [
            ['field' => 'm_id', 'label' => '会员ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'card_code', 'label' => '卡号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::GIFT_CARD_RECHARGE;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['card_code'] = $fdata['card_code'];
            $params['m_id'] = $fdata['m_id'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $data = [];
                    if($result['code'] == 2001)//无效卡号，code为2001，data为错误提示
                    {
//                        $data['code'] = $result['code'];
                    }
                    $this->json_do->set_data($data);
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 礼品卡额明细
     */
    public function gift_card_balance_list()
    {
        $rule = [
            ['field' => 'card_code', 'label' => '卡号', 'rules' => 'trim|required'],
            ['field' => 'limit', 'label' => '记录条数', 'rules' => 'trim|numeric'],
            ['field' => 'page', 'label' => '页码', 'rules' => 'trim|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        try{

            $api_url = ci_scrm::GIFT_CARD_DETAIL_LIST;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['card_code'] = $fdata['card_code'];
            $params['limit'] = (int)$fdata['limit'];
            $params['page'] = (int)$fdata['page'];
            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
    }

    /**
     * 充值
     * pay_type 1现金,2支付宝,3微信 5 付呗微信 6付呗支付宝
     */
    public function recharge()
    {
        $rule = [
            ['field' => 'm_id', 'label' => '会员ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'money', 'label' => '金额', 'rules' => 'trim|required|numeric'],
            ['field' => 'pay_type', 'label' => '支付类型', 'rules' => 'trim|required|in_list[1,2,3,5,6]'],
            ['field' => 'auth_code', 'label' => '支付授权码', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        if(in_array($fdata['pay_type'],[2,3,5,6]) && empty($fdata['auth_code']))
            $this->json_do->set_error('001','请扫描支付授权码');

        switch ($fdata['pay_type']) {
            case 1:     //现金支付
//                $this->_cashPay($fdata);
                break;
            case 2:
                $this->_alipayFFPay($fdata);
                break;
            case 3: //微信支付
                $this->_wexinMciroPay($fdata);
                break;
            case 5: //付呗微信
                $this->_fubeiPay($fdata);
                break;
            case 6: //付呗支付宝
                $this->_fubeiPay($fdata);
                break;
            default:
                $this->json_do->set_error('004','不支持的支付类型');
                break;
        }

        try{

            $api_url = ci_scrm::CASH_RECHARGE;
            $params['visit_id'] = $this->s_user->visit_id;
            $params['m_id'] = $fdata['m_id'];
            $params['money'] = $fdata['money'];
            //充值方式
            if(in_array($fdata['pay_type'],[2,6]))//支付宝
                $params['method'] = '支付宝';
            elseif(in_array($fdata['pay_type'],[3,5]))//微信
                $params['method'] = '微信';
            else
                $params['method'] = '现金';

            //调用scrm接口
            $result = ci_scrm::call($api_url, $params, 't1');
            if($result)
            {
                if($result['code'] == 0)
                {
                    $this->json_do->set_data($result['data']);
                    $this->json_do->out_put();
                }
                else
                {
                    $this->json_do->set_error('005', $result['data']);
                }

            }
            else
            {
                $this->json_do->set_error('005','请求失败');
            }

        }catch(Exception $e){
            log_message('error', __METHOD__.$e->getMessage());
            $this->json_do->set_error('005','请求失败');
        }
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
        $wmAlipayConfigDao = WmAlipayConfigDao::i($this->s_user->aid);
        $config = $wmAlipayConfigDao->getOne(['aid' => $this->s_user->aid]);
        if(!$config)
        {
            $this->json_do->set_error('004', '支付宝当面付未配置');
        }
        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $this->s_user->aid;
        $data['source_type'] = 4;
        $data['shop_id'] = $this->s_user->shop_id;
        $data['gateway'] = 'alipay';
        $data['source'] = 'sy';
        $data['appid'] = $config->app_id;
        $data['money'] =  $fdata['money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = 0;
        $data['tid'] = '';
        $record_id = $mealPaymentRecordDao->create($data);

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
            $barPayRequestBuilder->setTotalAmount($fdata['money']);
            $barPayRequestBuilder->setAuthCode($fdata['auth_code']);
            $barPayRequestBuilder->setTimeExpress('5m');
            $barPayRequestBuilder->setSubject('会员充值');
            $barPayRequestBuilder->setBody('条码支付-会员充值');

            // 调用barPay方法获取当面付应答
            $barPay = new AlipayTradeService($conf);
            $barPayResult = $barPay->barPay($barPayRequestBuilder);

            if($barPayResult->getTradeStatus()!='SUCCESS')
            {
                log_message('error',__METHOD__.'-'.json_encode($barPayResult->getResponse()));
                $this->json_do->set_error('004', '支付失败alipay');
            }
        } catch(Exception $e) {
            log_message('error', __METHOD__.'-'.$e->getMessage());
            $this->json_do->set_error('004', '支付失败alipay');
        }
        //淘宝支付订单号
        $trade_no = $barPayResult->getResponse()->trade_no;
        // 更新记录状态
        $mealPaymentRecordDao->update(['status' => 1, 'trade_no' => $trade_no], ['id' => $record_id, 'aid' => $this->s_user->aid]);

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
        $wmGzhConfigCache = new WmGzhConfigCache(['aid' => $this->s_user->aid]);
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
        $data['aid'] = $this->s_user->aid;
        $data['source_type'] = 4;
        $data['shop_id'] = $this->s_user->shop_id;
        $data['gateway'] = 'wx_micro';
        $data['source'] = 'sy';
        $data['appid'] = $config->app_id;
        $data['money'] =  $fdata['money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = 0;
        $data['tid'] = '';
        $record_id = $mealPaymentRecordDao->create($data);

        try{
            //2.调取微信收付款支付
            ci_wxpay::load('micro',$this->s_user->aid);
            $input = new WxPayMicroPay();
            $input->SetAuth_code($fdata['auth_code']);      //支付授权码
            $input->SetBody('会员充值-收付款');
            $input->SetTotal_fee($fdata['money'] * 100); //支付金额
            $input->SetOut_trade_no($data['code']);     //支付单号
            $microPay = new MicroPay();
            $res = $microPay->pay($input);
            if(empty($res))
                $this->json_do->set_error('004', '支付失败wx');
        } catch(Exception $e) {
            log_message('error', __METHOD__.'-'.$e->getMessage());
            $this->json_do->set_error('004', '支付失败wx');
        }

        //3.收付款支付成功
        // 更新记录状态
        $mealPaymentRecordDao->update(['status' => 1, 'trade_no' => $res['transaction_id']], ['id' => $record_id, 'aid' => $this->s_user->aid]);

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
        $data['aid'] = $this->s_user->aid;
        $data['source_type'] = 4;
        $data['shop_id'] = $this->s_user->shop_id;
        $data['source'] = 'sy';
        $data['appid'] = $m_pay_fubei->app_id;
        $data['money'] =  $fdata['money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = 0;
        $data['tid'] = '';

        if($fdata['pay_type'] == 5)//微信1
        {
            $type = 1;
            $data['gateway'] = 'fb_wxmicro';

        }
        elseif($fdata['pay_type'] == 6)//支付宝2
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
            $input['total_fee'] = $fdata['money'];
            $fubei_sdk = new fubei_sdk($config);
            $res = $fubei_sdk->loopSwip($input);

            $trade_no = $res->data->order_sn;
            //更新记录状态
            $mealPaymentRecordDao->update(['status' => 1, 'trade_no' => $trade_no], ['id' => $record_id, 'aid' => $this->s_user->aid]);

        }catch(Exception $e){
            log_message('error', __METHOD__.'-'.$e->getMessage());
            $this->json_do->set_error('004', '支付失败');
        }
    }
}
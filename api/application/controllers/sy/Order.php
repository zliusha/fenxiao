<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/8
 * Time: 18:49
 */
require_once LIBS_PATH.'libraries/alipay/f2fpay/model/builder/AlipayTradePayContentBuilder.php';
require_once LIBS_PATH.'libraries/alipay/f2fpay/service/AlipayTradeService.php';
use Service\Cache\WmAlipayConfigCache;
use Service\Cache\WmGzhConfigCache;
use Service\Cache\WmFubeiConfigCache;


use Service\DbFrame\DataBase\WmShardDbModels\MealPaymentRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmFubeiRefshopDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;

use Service\Cache\WmShopCache;

class Order extends sy_controller
{
    /**
     * 创建订单
     * items JSON格式 [{"table_id":1, "goods_id":1, "sku_id":1,"quantity":1}]
     */
    public function create()
    {
        $rule = [
             ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
             ['field' => 'items', 'label' => '订单JSON', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $input['aid'] = $this->s_user->aid;
        $input['open_id'] = '';
        $input['nickname'] = '';
        $input['headimg'] = '';
        $input['table_id'] = $f_data['table_id'];
        $input['items'] = @json_decode($f_data['items'], true);

        $meal_order_event_bll = new meal_order_event_bll();
        $meal_order_event_bll->orderCreate($input, true);
    }

    /**
     * 审核订单
     */
    public function audit_order()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $m_meal_order = $mealOrderDao->getOne(['aid'=>$this->s_user->aid, 'shop_id'=>$this->s_user->shop_id, 'tid'=>$f_data['tid']], 'id,api_status,order_table_id');

        if(!$m_meal_order || $m_meal_order->api_status != 1)
        {
            $this->json_do->set_error('001', '当前状态记录不存在');
        }

        $m_order_table = $mealOrderTableDao->getOne(['aid'=>$this->s_user->aid, 'shop_id'=>$this->s_user->shop_id, 'id'=>$m_meal_order->order_table_id]);

        $input = ['tradeno' => $f_data['tid'], 'aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'table_id' => $m_order_table->table_id];
        $meal_order_event_bll = new meal_order_event_bll();
        $meal_order_event_bll->sellerAgreeOrder($input, true);

        //更新桌位状态信息
        $mealShopAreaTableDao->updateStatus($m_order_table->id, $m_order_table->aid);

        $this->json_do->set_msg('审核成功');
        $this->json_do->out_put();
    }

    /**
     * 拒单
     */
    public function refuse_order()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $m_meal_order = $mealOrderDao->getOne(['aid'=>$this->s_user->aid, 'shop_id'=>$this->s_user->shop_id, 'tid'=>$f_data['tid']], 'id,api_status,order_table_id');
        if(!$m_meal_order || $m_meal_order->api_status != 1)
        {
            $this->json_do->set_error('001', '当前状态记录不存在');
        }

        $m_order_table = $mealOrderTableDao->getOne(['aid'=>$this->s_user->aid, 'shop_id'=>$this->s_user->shop_id, 'id'=>$m_meal_order->order_table_id]);

        $input = ['tradeno' => $f_data['tid'], 'aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'table_id' => $m_order_table->table_id];
        $meal_order_event_bll = new meal_order_event_bll();
        $meal_order_event_bll->sellerRefuseOrder($input, true);

        //更新桌位状态信息
        $mealShopAreaTableDao->updateStatus($m_order_table->id, $m_order_table->aid);

        $this->json_do->set_msg('取消成功');
        $this->json_do->out_put();
    }

    /**
     * 退菜
     */
    public function retreat_goods()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required'],
            ['field' => 'sku_id', 'label' => 'SKU ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'num', 'label' => '退菜数量', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderExtDao = MealOrderEXTDao::i($this->s_user->aid);

        $m_meal_order = $mealOrderDao->getOne(["aid" => $this->s_user->aid, "shop_id" => $this->s_user->shop_id, 'tid' => $f_data['tid'], 'api_status <= '=>2], 'id,tid,aid,shop_id');
        $m_meal_order_ext = $mealOrderExtDao->getOne(["aid" => $this->s_user->aid, "shop_id" => $this->s_user->shop_id, 'tid' => $f_data['tid'], 'sku_id'=>$f_data['sku_id'],'api_status <= '=>2], 'id,num,price,order_money,retreat_num');
        //检测订单状态
        if(!$m_meal_order || !$m_meal_order_ext)
        {
            $this->json_do->set_error('001', '当前订单状态禁止退菜');
        }
        //检测退菜数量
        if($f_data['num'] <=0 || $f_data['num'] > $m_meal_order_ext->num)
        {
            $this->json_do->set_error('001', '退菜数量错误');
        }

        $order_money = bcsub($m_meal_order_ext->order_money, $m_meal_order_ext->price*$f_data['num'], 2);
        $num = $m_meal_order_ext->num-$f_data['num'];

        //如果商品数量为0 ，则删除
        if($num == 0)
        {
            $result = $mealOrderExtDao->delete(['id' => $m_meal_order_ext->id, 'aid' => $this->s_user->aid]);
        }
        else
        {
            $retreat_num = $m_meal_order_ext->retreat_num+$f_data['num'];
            $result = $mealOrderExtDao->update(['order_money' => $order_money, 'pay_money' => $order_money, 'num' => $num, 'retreat_num' => $retreat_num], ['id' => $m_meal_order_ext->id, 'aid' => $this->s_user->aid]);
        }

        if($result !== false)
        {
            $count = $mealOrderExtDao->getCount(['aid' => $this->s_user->aid, 'tid' => $f_data['tid']]);
            if($count > 0)
            {
                $total_money = $mealOrderExtDao->getSum('order_money', ['aid' => $this->s_user->aid, 'tid' => $f_data['tid']]);
                $total_num = $mealOrderExtDao->getSum('num', ['aid' => $this->s_user->aid, 'tid' => $f_data['tid']]);

                $oder_result = $mealOrderDao->update(['total_money' => $total_money, 'pay_money' => $total_money, 'total_num' => $total_num], ['id' => $m_meal_order->id, 'aid' => $this->s_user->aid]);
                if($oder_result === false)
                    log_message('error', __METHOD__ . ' 退菜更新主订单失败' . ' input=>' . json_encode($f_data));
            }
            else//如无子订单则删除主订单
            {
                $oder_result = $mealOrderDao->delete(['id' => $m_meal_order->id, 'aid' => $this->s_user->aid]);
                if($oder_result === false)
                    log_message('error', __METHOD__ . ' 退菜更新主订单失败' . ' input=>' . json_encode($f_data));
            }
            
            $this->json_do->set_msg('退菜成功');
            $this->json_do->out_put();
        }

        $this->json_do->set_error('005', '退菜失败');
    }

    /**
     * 订单列表（待审核）
     */
    public function order_list()
    {

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        //待审核订单列表
        $order_list = $mealOrderDao->getAllArray(["aid" => $this->s_user->aid, "shop_id" => $this->s_user->shop_id, 'api_status' => 1], '*', 'id desc');

        //订单关联桌位记录列表
        $order_table_ids = array_unique(array_column($order_list, 'order_table_id'));
        $order_table_ids = empty($order_table_ids) ? [0] : $order_table_ids;
        $table_record_list = $mealOrderTableDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id}", 'where_in' => ['id' => $order_table_ids]], true);

        //关联桌位列表
        $table_ids = array_unique(array_column($table_record_list, 'table_id'));
        $table_ids = empty($table_ids) ? [0] : $table_ids;
        $table_list = $mealShopAreaTableDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id}", 'where_in' => ['id' => $table_ids]], true);

        //关联区域列表
        $area_ids = array_unique(array_column($table_list, 'shop_area_id'));
        $area_ids = empty($area_ids) ? [0] : $area_ids;
        $area_list = $mealShopAreaDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id}", 'where_in' => ['id' => $area_ids]], true);

        foreach($order_list as $key => $order)
        {
            $order_list[$key]['time'] = date('Y-m-d H:i:s', $order['time']);

            $area_table_name = '';

            //获取订单桌位信息
            $table_record = array_find($table_record_list, 'id', $order['order_table_id']);
            if(isset($table_record['table_id']))
                $table = array_find($table_list, 'id', $table_record['table_id']);
            if(isset($table['shop_area_id']))
            {
                $area = array_find($area_list, 'id', $table['shop_area_id']);
            }
            if(isset($area['name']))
                $area_table_name = $area['name'].$table['name'];

            $order_list[$key]['area_table_name'] = $area_table_name;
        }

        $this->json_do->set_data($order_list);
        $this->json_do->out_put();
    }

    /**
     * 订单商品详情
     */
    public function table_order_goods_info()
    {
        $rule = [
             ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderExtDao = MealOrderExtDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $m_meal_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $f_data['table_id'], 'aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id]);

        if(!$m_meal_shop_area_table)
        {
            $this->json_do->set_error('001', '桌位记录不存在');
        }

        $order_list = $mealOrderDao->getAllArray(["aid" => $this->s_user->aid, "shop_id" => $this->s_user->shop_id, 'order_table_id' => $m_meal_shop_area_table->cur_order_table_id, 'api_status'=>2], 'id,tid,aid,shop_id');
        $tids = array_unique(array_column($order_list, 'tid'));
        $tids = empty($tids) ? [0] : $tids;

        $fields = 'id,order_table_id,tid,ext_tid,goods_id,goods_title,goods_pic,sku_id,sku_str,api_status,price,pay_money,order_money,num,retreat_num,pro_attr';
        $order_ext_list = $mealOrderExtDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id} AND api_status=2", 'where_in' => ['tid' => $tids], 'field'=>$fields], true);;

        $rules = [
            ['type' => 'img', 'field' => 'goods_pic']
        ];
        $order_ext_list = convert_client_list($order_ext_list, $rules);

        foreach ($order_list as $key => $order) {
            $order_list[$key]['order_ext'] = array_find_list($order_ext_list, 'tid', $order['tid']);
        }

        $this->json_do->set_data($order_list);
        $this->json_do->out_put();

    }

    /**
     * 订单商品列表
     */
    public function order_goods_list()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required']

        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealOrderExtDao = MealOrderExtDao::i($this->s_user->aid);
        $fields = 'id,order_table_id,tid,ext_tid,goods_id,goods_title,goods_pic,sku_id,sku_str,api_status,price,pay_money,order_money,num,retreat_num,pro_attr';
        $order_ext_list = $mealOrderExtDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id} AND tid='{$f_data['tid']}'", 'field'=>$fields], true);;

        $rules = [
            ['type' => 'img', 'field' => 'goods_pic']
        ];
        $order_ext_list = convert_client_list($order_ext_list, $rules);

        $this->json_do->set_data($order_ext_list);
        $this->json_do->out_put();
    }

    /**
     * 结算订单 pay_type 1现金,2支付宝当面付,3微信当面付,4余额支付,5付呗微信,6付呗支付宝,7刷卡消费(会员卡，礼品卡)
     */
    public function pay_order()
    {
        $rule = [
            ['field' => 'table_id', 'label' => '桌位ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'pay_type', 'label' => '支付类型', 'rules' => 'trim|required|in_list[1,2,3,4,5,6,7]'],
            ['field' => 'sum_pay_money', 'label' => '实际支付金额', 'rules' => 'trim|required|numeric'],
            ['field' => 'auth_code', 'label' => '支付授权码', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '手机号码', 'rules' => 'trim|preg_key[MOBILE]'],
            ['field' => 'card_code', 'label' => '卡号', 'rules' => 'trim'],

        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $fdata['source_type'] = 1;

        if(in_array($fdata['pay_type'],[2,3,5,6]) && empty($fdata['auth_code']))
            $this->json_do->set_error('001','请扫描支付授权码');

        if(in_array($fdata['pay_type'],[4]) && empty($fdata['mobile']))
            $this->json_do->set_error('001','请输入会员手机号');

        if(in_array($fdata['pay_type'],[7]) && empty($fdata['card_code']))
            $this->json_do->set_error('001','请刷卡');

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $m_meal_table = $mealShopAreaTableDao->getOne(['id' => $fdata['table_id'], 'aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id]);
        if(!$m_meal_table)
            $this->json_do->set_error('001', '桌位记录不存在');

        //验证金额
        $meal_inc = &inc_config('meal');

        // 检查金额是否匹配
        $sum_pay_money = $mealOrderDao->getSum('pay_money', ['status' => 2020, 'order_table_id' => $m_meal_table->cur_order_table_id, 'aid' => $this->s_user->aid]);
        if ($sum_pay_money < $fdata['sum_pay_money']) {
            log_message('error', __METHOD__ . '支付异常:待支付金额小于实际支付金额 sum_pay_money=>' . $sum_pay_money . ' input=>' . json_encode($fdata));
            $this->json_do->set_error('004', '支付异常，待支付金额小于实际支付金额');
        }
        if ($sum_pay_money > $fdata['sum_pay_money'] && $meal_inc['fakepay'] === false) {
            log_message('error', __METHOD__ . '支付异常:待支付金额大于实际支付金额（可能存在支付后马上下单审核的情况） sum_pay_money=>' . $sum_pay_money . ' input=>' . json_encode($fdata));
            $this->json_do->set_error('004', '支付异常，待支付金额小于实际支付金额');
        }


        switch ($fdata['pay_type']) {
            case 1:     //现金支付
                    $this->_cashPay($fdata,$m_meal_table);
                break;
            case 2:
                    $this->_alipayFFPay($fdata,$m_meal_table);
                break;
            case 3: //微信支付
                    $this->_wexinMciroPay($fdata,$m_meal_table);
                break;
            case 4: //余额支付（会员储值）
                    $this->_balancePay($fdata, $m_meal_table);
                break;
            case 5: //付呗微信
                $this->_fubeiPay($fdata, $m_meal_table);
                break;
            case 6: //付呗支付宝
                $this->_fubeiPay($fdata, $m_meal_table);
                break;
            case 7: //刷卡消费
                $this->_cardPay($fdata, $m_meal_table);
                break;
            default:
                $this->json_do->set_error('004','不支持的支付类型');
                break;
        }
        
    }
    /**
     * 现金支付
     * @param  [type] $fdata        [description]
     * @param  [type] $m_meal_table [description]
     * @return [type]               [description]
     */
    private function _cashPay($fdata,$m_meal_table)
    {
        $tid = create_serial_number('T1ZF');
        $input = [
            'pay_trade_no' => $tid,
            'order_table_id' => $m_meal_table->cur_order_table_id,
            'pay_type' => $fdata['pay_type'],
            'sum_pay_money' => $fdata['sum_pay_money'],
            'payment_record_id' => 0,
            'pay_source' => 2,
            'source_type' => $fdata['source_type'],//1点餐
            'aid'=>$m_meal_table->aid
        ];

        $meal_statements_bll = new meal_statements_bll();
        $return = $meal_statements_bll->checkout($input, true);

        $serial_number = '';
        $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
        $m_statement = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_number'=>$tid, 'type'=>1]);
        if($m_statement)
            $serial_number = $m_statement->serial_number;

        if($return)
        {
            $this->json_do->set_data(['tid'=>$tid, 'serial_number'=>$serial_number]);
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005', '失败');
    }
    /**
     * 微信支付
     * @param  [type] $fdata        [description]
     * @param  [type] $m_meal_table [description]
     * @return [type]               [description]
     */
    private function _alipayFFPay($fdata,$m_meal_table)
    {
        //校验公众号配置
        $wmAlipayConfigCache = new wmAlipayConfigCache(['aid'=>$m_meal_table->aid]);
        $config = $wmAlipayConfigCache->getDataASNX();
        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $m_meal_table->aid;
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $m_meal_table->shop_id;
        $data['gateway'] = 'alipay';
        $data['source'] = 'sy';
        $data['appid'] = $config->app_id;
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = $m_meal_table->cur_order_table_id;
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

            $this->json_do->set_data(['tid'=>$trade_no, 'serial_number'=>$serial_number]);
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
    private function _wexinMciroPay($fdata,$m_meal_table)
    {
        //校验公众号配置
        $wmGzhConfigCache = new WmGzhConfigCache(['aid'=>$m_meal_table->aid]);
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
        $data['aid'] = $m_meal_table->aid;
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $m_meal_table->shop_id;
        $data['gateway'] = 'wx_micro';
        $data['source'] = 'sy';
        $data['appid'] = $config->app_id;
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = $m_meal_table->cur_order_table_id;
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

            $this->json_do->set_data(['tid'=>$res['transaction_id'], 'serial_number'=>$serial_number]);
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
    private function _balancePay($fdata, $m_meal_table)
    {
        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $m_meal_table->aid;
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $m_meal_table->shop_id;
        $data['gateway'] = 'inner';
        $data['source'] = 'sy';
        $data['appid'] = '';
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = $m_meal_table->cur_order_table_id;
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
        $tid = create_serial_number('T1');
        //更新记录状态
        $mealPaymentRecordDao->update(['status' => 1, 'trade_no' => $tid], ['id' => $record_id, 'aid' => $this->s_user->aid]);

        $input = [
            'pay_trade_no' => $tid,
            'order_table_id' => $m_meal_table->cur_order_table_id,
            'pay_type' => $fdata['pay_type'],
            'sum_pay_money' => $fdata['sum_pay_money'],
            'payment_record_id' => 0,
            'pay_source' => 2,
            'source_type' => $fdata['source_type'],//1点餐
            'aid'=>$m_meal_table->aid
        ];

        $meal_statements_bll = new meal_statements_bll();
        $return = $meal_statements_bll->checkout($input, true);

        $serial_number = '';
        $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
        $m_statement = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'order_number'=>$tid, 'type'=>1]);
        if($m_statement)
            $serial_number = $m_statement->serial_number;

        if($return)
        {
            $this->json_do->set_data(['tid'=>$tid, 'serial_number'=>$serial_number]);
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005', '失败');
    }
    /**
     * ##################扫码二期##################
     * 2018/05/08
     */
    /**
     * 目前针对同一桌位
     * 审核订单
     */
    public function audit_order_v128()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required|preg_key[IDS]'],
            ['field' => 'order_table_id', 'label' => '桌位记录ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $tids = trim($f_data['tid'], ',');
        $order_table_id = $f_data['order_table_id'];

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        //获取有效的待审核订单
        $meal_order_list = $mealOrderDao->getAllArray("aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id} AND order_table_id={$order_table_id} AND api_status=1 AND tid in ({$tids})", "id,api_status,order_table_id,tid");

        if(empty($meal_order_list))
        {
            $this->json_do->set_error('001', '当前状态记录不存在');
        }
        //获取桌位ID
        $m_order_table = $mealOrderTableDao->getOne(['aid'=>$this->s_user->aid, 'shop_id'=>$this->s_user->shop_id, 'id'=>$meal_order_list[0]['order_table_id']]);

        //审核
        $meal_order_event_bll = new meal_order_event_bll();
        foreach($meal_order_list as $meal_order)
        {
            $input = ['tradeno' => $meal_order['tid'], 'aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'table_id' => $m_order_table->table_id];
            $meal_order_event_bll->sellerAgreeOrder($input, true);
        }

        //更新桌位状态信息
        $mealShopAreaTableDao->updateStatus($m_order_table->id, $m_order_table->aid);

        $this->json_do->set_msg('审核成功');
        $this->json_do->out_put();
    }

    /**
     * 目前针对同一桌位
     * 拒单
     */
    public function refuse_order_v128()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required|preg_key[IDS]'],
            ['field' => 'order_table_id', 'label' => '桌位记录ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $tids = trim($f_data['tid'], ',');
        $order_table_id = $f_data['order_table_id'];

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        //获取有效的待审核订单
        $meal_order_list = $mealOrderDao->getAllArray("aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id} AND order_table_id={$order_table_id} AND api_status=1 AND tid in ({$tids})", "id,api_status,order_table_id,tid");

        if(empty($meal_order_list))
        {
            $this->json_do->set_error('001', '当前状态记录不存在');
        }
        //获取桌位ID
        $m_order_table = $mealOrderTableDao->getOne(['aid'=>$this->s_user->aid, 'shop_id'=>$this->s_user->shop_id, 'id'=>$meal_order_list[0]['order_table_id']]);

        //取消
        $meal_order_event_bll = new meal_order_event_bll();
        foreach($meal_order_list as $meal_order)
        {
            $input = ['tradeno' => $meal_order['tid'], 'aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'table_id' => $m_order_table->table_id];
            $meal_order_event_bll->sellerRefuseOrder($input, true);
        }

        //更新桌位状态信息
        $mealShopAreaTableDao->updateStatus($m_order_table->id, $m_order_table->aid);

        $this->json_do->set_msg('取消成功');
        $this->json_do->out_put();
    }

    /**
     * 订单商品详情
     */
    public function table_order_goods_info_v128()
    {
        $rule = [
            ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
            ['field' => 'status', 'label' => '订单状态', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderExtDao = MealOrderExtDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);

        $m_meal_shop_area_table = $mealShopAreaTableDao->getOne(['id' => $f_data['table_id'], 'aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id]);

        if(!$m_meal_shop_area_table)
        {
            $this->json_do->set_error('001', '桌位记录不存在');
        }

        $order_list = $mealOrderDao->getAllArray(["aid" => $this->s_user->aid, "shop_id" => $this->s_user->shop_id, 'order_table_id' => $m_meal_shop_area_table->cur_order_table_id, 'api_status'=>$f_data['status']], 'id,tid,aid,shop_id');
        $tids = array_unique(array_column($order_list, 'tid'));
        $tids = empty($tids) ? [0] : $tids;

        $fields = 'id,order_table_id,tid,ext_tid,goods_id,goods_title,goods_pic,sku_id,sku_str,api_status,price,pay_money,order_money,num';
        $order_ext_list = $mealOrderExtDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id} AND api_status={$f_data['status']}", 'where_in' => ['tid' => $tids], 'field'=>$fields], true);;

        $rules = [
            ['type' => 'img', 'field' => 'goods_pic']
        ];
        $order_ext_list = convert_client_list($order_ext_list, $rules);

        foreach ($order_list as $key => $order) {
            $order_list[$key]['order_ext'] = array_find_list($order_ext_list, 'tid', $order['tid']);
        }
        $data['order_list'] = $order_list;

        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }

    /**
     * 银行通道支付
     * @param $fdata
     */
    private function _fubeiPay($fdata,$m_meal_table)
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
        $data['aid'] = $m_meal_table->aid;
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $m_meal_table->shop_id;
        $data['source'] = 'sy';
        $data['appid'] = '';
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = $m_meal_table->cur_order_table_id;
        $data['mobile'] = $fdata['mobile'];

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
                'order_table_id' => $m_meal_table->cur_order_table_id,
                'pay_type' => $fdata['pay_type'],
                'sum_pay_money' => $fdata['sum_pay_money'],
                'payment_record_id' => 0,
                'pay_source' => 2,
                'source_type' => $fdata['source_type'],//1点餐
                'aid'=>$m_meal_table->aid
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
    private function _cardPay($fdata, $m_meal_table)
    {
        //1.在线支付记录
        $mealPaymentRecordDao = MealPaymentRecordDao::i($this->s_user->aid);
        $data['open_id'] = '';
        $data['nickname'] = '';
        $data['headimg'] = '';
        $data['aid'] = $m_meal_table->aid;
        $data['source_type'] = $fdata['source_type'];
        $data['shop_id'] = $m_meal_table->shop_id;
        $data['gateway'] = 'inner';
        $data['source'] = 'sy';
        $data['appid'] = '';
        $data['money'] =  $fdata['sum_pay_money'];
        $data['code'] = create_order_number();
        $data['order_table_id'] = $m_meal_table->cur_order_table_id;
        $data['mobile'] = $fdata['mobile'];
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
            'order_table_id' => $m_meal_table->cur_order_table_id,
            'pay_type' => $fdata['pay_type'],
            'sum_pay_money' => $fdata['sum_pay_money'],
            'payment_record_id' => 0,
            'pay_source' => 2,
            'source_type' => $fdata['source_type'],//1点餐
            'aid'=>$m_meal_table->aid
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
<?php
/**
 * 云店宝（扫码点餐版）流水模块
 * @author dadi
 *
 */
use Service\Cache\WmFubeiConfigCache;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmAlipayConfigDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealPaymentRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmFubeiRefshopDao;
class meal_statements_bll extends base_bll
{
    private $enum = [];

    public function __construct()
    {
        parent::__construct();
        $this->enum = &inc_config('meal');
    }

    // 零售流水查询 必选：aid
    public function retailQuery($input = [])
    {
        $wmShardDb = WmShardDb::i($input['aid']);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->fields = 'a.*, b.shop_name';
        $p_conf->table = "{$wmShardDb->tables['meal_statements']} a left join {$wmShardDb->tables['wm_shop']} b on a.shop_id=b.id";

        $p_conf->where .= " AND a.`aid` = {$page->filter($input['aid'])} AND a.source_type=3 ";

        #====================================
        # 指定查询条件处理
        // 按门店查询
        if (!empty($input['shop_id']) && $input['shop_id'] > 0) {
            $p_conf->where .= " AND a.`shop_id` = {$input['shop_id']} ";
        }

        // 流水渠道
        if (!empty($this->input->post_get('gateway')) && $this->input->post_get('gateway') > 0) {
            $p_conf->where .= " AND a.gateway = {$page->filter($this->input->post_get['gateway'])} ";
        }

        // 流水类型
        if (!empty($this->input->post_get('type')) && $this->input->post_get('type') > 0) {
            $p_conf->where .= " AND a.type = {$page->filter($this->input->post_get['type'])} ";
        }

        // 按退款类型
        if (!empty($this->input->post_get('refund_type')) && $this->input->post_get('refund_type') > 0) {
            $p_conf->where .= " AND a.refund_type = {$page->filter($this->input->post_get('refund_type'))} ";
        }

        // 按状态查询
        if (!empty($this->input->post_get('status'))) {
            $p_conf->where .= " AND a.status = {$page->filter($this->input->post_get('status'))} ";
        }

        // 按支付方式查询
        if (!empty($this->input->post_get('pay_type'))) {
            $p_conf->where .= " AND a.`pay_type` = {$page->filter($this->input->post_get('pay_type'))} ";
        }
        // 流水号
        if (!empty($this->input->post_get('serial_number'))) {
            $p_conf->where .= " AND a.serial_number like '%{$page->filterLike($this->input->post_get('serial_number'))}%' ";
        }
        // 按创建时间范围查询
        if (!empty($this->input->post_get('time'))) {
            $range = explode(' - ', $this->input->post_get('time'));
            $range = array_map('strtotime', $range);
            if (count($range) == 2) {
                $range[1] += 86400;
                $p_conf->where .= " AND a.`time` >= '{$range[0]}' AND a.`time` <= '{$range[1]}' ";
            }
        }

        #====================================
        # 排序
        $p_conf->order = 'a.id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        // 格式化数据
        foreach ($list as $key => $row) {
            #====================================
            # 处理记录枚举字段
            // 创建时间
            $list[$key]['time'] = $this->time($row['time']);
            //流水类型
            $list[$key]['type'] = $this->enum('statements_type', $row['type']);
            //退款类型
            $list[$key]['refund_type'] = $this->enum('statements_refund_type', $row['refund_type']);
            //流水渠道
            $list[$key]['gateway'] = $this->enum('statements_gateway', $row['gateway']);
            //操作渠道
            $list[$key]['pay_source'] = $this->enum('statements_pay_source', $row['pay_source']);
            //流水状态
            $list[$key]['status'] = $this->enum('statements_status', $row['status']);

        }

        $data['total'] = $count;
        $data['rows'] = $list;

        return $data;
    }
    // 点餐流水查询
    public function mealQuery($input = [])
    {
        $wmShardDb = WmShardDb::i($input['aid']);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->fields = 'a.*, b.shop_name,b.table_name,b.area_name';
        $p_conf->table = "{$wmShardDb->tables['meal_statements']} a left join {$wmShardDb->tables['meal_order_table']} b on a.order_table_id=b.id";

         $p_conf->where .= " AND a.`aid` = {$page->filter($input['aid'])} AND a.source_type=1";

        #====================================
        # 指定查询条件处理
        // 按门店查询
        if (!empty($input['shop_id']) && $input['shop_id'] > 0) {
            $p_conf->where .= " AND a.`shop_id` = {$input['shop_id']} ";
        }

        // 流水渠道
        if (!empty($this->input->post_get('gateway')) && $this->input->post_get('gateway') > 0) {
            $p_conf->where .= " AND a.gateway = {$page->filter($this->input->post_get['gateway'])} ";
        }

        // 流水类型
        if (!empty($this->input->post_get('type')) && $this->input->post_get('type') > 0) {
            $p_conf->where .= " AND a.type = {$page->filter($this->input->post_get['type'])} ";
        }

        // 按退款类型
        if (!empty($this->input->post_get('refund_type')) && $this->input->post_get('refund_type') > 0) {
            $p_conf->where .= " AND a.refund_type = {$page->filter($this->input->post_get('refund_type'))} ";
        }

        // 按状态查询
        if (!empty($this->input->post_get('status'))) {
            $p_conf->where .= " AND a.status = {$page->filter($this->input->post_get('status'))} ";
        }

        // 按支付方式查询
        if (!empty($this->input->post_get('pay_type'))) {
            $p_conf->where .= " AND a.`pay_type` = {$page->filter($this->input->post_get('pay_type'))} ";
        }

        // 流水号
        if (!empty($this->input->post_get('serial_number'))) {
            $p_conf->where .= " AND a.serial_number like '%{$page->filterLike($this->input->post_get('serial_number'))}%' ";
        }
        // 流水号
        if (!empty($this->input->post_get('table_name'))) {
            $p_conf->where .= " AND b.table_name like '%{$page->filterLike($this->input->post_get('table_name'))}%' ";
        }

        // 按创建时间范围查询
        if (!empty($this->input->post_get('time'))) {
            $range = explode(' - ', $this->input->post_get('time'));
            $range = array_map('strtotime', $range);
            if (count($range) == 2) {
                $range[1] += 86400;
                $p_conf->where .= " AND a.`time` >= '{$range[0]}' AND a.`time` <= '{$range[1]}' ";
            }
        }

         #====================================
         # 排序
         $p_conf->order = 'a.id desc';

         $count = 0;
         $list = $page->getList($p_conf, $count);

         // 格式化数据
         foreach ($list as $key => $row) {
             #====================================
             # 处理记录枚举字段
             // 创建时间
             $list[$key]['time'] = $this->time($row['time']);
             //流水类型
             $list[$key]['type'] = $this->enum('statements_type', $row['type']);
             //退款类型
             $list[$key]['refund_type'] = $this->enum('statements_refund_type', $row['refund_type']);
             //流水渠道
             $list[$key]['gateway'] = $this->enum('statements_gateway', $row['gateway']);
             //操作渠道
             $list[$key]['pay_source'] = $this->enum('statements_pay_source', $row['pay_source']);
             //流水状态
             $list[$key]['status'] = $this->enum('statements_status', $row['status']);

         }

        $data['total'] = $count;
        $data['rows'] = $list;

        return $data;
    }


    // 枚举标准格式化
    public function enum($key_name = '', $code = '', $unkown = '')
    {
        if (!isset($this->enum[$key_name])) {
            return [
                'type' => 'enum',
                'value' => 0,
                'alias' => '未知枚举类型',
            ];
        }
        return [
            'type' => 'enum',
            'value' => $code,
            'alias' => isset($this->enum[$key_name][$code]) ? $this->enum[$key_name][$code] : $unkown,
        ];
    }

    // 时间标准格式化
    public function time($timestamp = 0, $format = 'Y-m-d H:i:s')
    {
        if (!$timestamp || !is_numeric($timestamp)) {
            return [
                'type' => 'unix_timestamp',
                'value' => 0,
                'alias' => '-',
            ];
        }
        return [
            'type' => 'unix_timestamp',
            'value' => $timestamp,
            'alias' => date($format, $timestamp),
        ];
    }

    /**
     * 流水详情查询(只取一条记录)
     * @param array $where 必填：aid
     */
    public function detail($where=[])
    {
        $aid = $where['aid'];
        $mealStatementsDao = MealStatementsDao::i($aid);


        $m_meal_statement = $mealStatementsDao->getOne($where);
        if(!$m_meal_statement)
        {
            return false;
        }
        $order_ext_list = [];
        if($m_meal_statement->type == 1)//如果是流水，查询相关商品记录
        {
            $where = ['aid'=>$m_meal_statement->aid, 'shop_id'=>$m_meal_statement->shop_id, 'pay_trade_no'=>$m_meal_statement->order_number];

            if($m_meal_statement->source_type == 1)//点餐
            {
                $mealOrderExtDao = MealOrderExtDao::i($aid);
                $order_ext_list = $mealOrderExtDao->getAllArray($where, 'id,aid,order_table_id,tid,goods_id,sku_id,goods_title,goods_pic,price,pay_money,order_money,num,sku_str,pro_attr');
            }
            elseif($m_meal_statement->source_type == 3)//零售
            {

                $retailOrderExtDao = RetailOrderExtDao::i($aid);
                $order_ext_list = $retailOrderExtDao->getAllArray($where, 'id,aid,tid,goods_id,sku_id,goods_title,goods_pic,price,pay_money,order_money,num,sku_str,pro_attr,measure_type,unit_type,unit_type_name');

            }
            else//其他
            {
                $order_ext_list = [];
            }



            //保留处理
            foreach($order_ext_list as $ok => $order_ext)
            {
                $order_ext_list[$ok]['goods_pic'] = conver_picurl($order_ext['goods_pic']);
            }

            $m_meal_statement->goods_number = array_sum(array_map(function($val){return $val['num'];}, $order_ext_list));
            $m_meal_statement->pay_money = array_sum(array_map(function($val){return $val['pay_money'];}, $order_ext_list));
            $m_meal_statement->order_money = array_sum(array_map(function($val){return $val['order_money'];}, $order_ext_list));

        }

        $m_meal_statement->time = $this->time($m_meal_statement->time);
        //流水类型
        $m_meal_statement->type = $this->enum('statements_type', $m_meal_statement->type);
        //退款类型
        $m_meal_statement->refund_type = $this->enum('statements_refund_type', $m_meal_statement->refund_type);
        //流水渠道
        $m_meal_statement->gateway = $this->enum('statements_gateway', $m_meal_statement->gateway);
        //操作渠道
        $m_meal_statement->pay_source = $this->enum('statements_pay_source', $m_meal_statement->pay_source);
        //流水状态
        $m_meal_statement->status = $this->enum('statements_status', $m_meal_statement->status);

        $m_meal_statement->order_ext = $order_ext_list;


        return $m_meal_statement;
    }

    /**
     * 创建收款流水记录
     * @param $input 必含参数[ 'pay_trade_no', 'pay_type', 'pay_source', 'payment_record_id','order_table_id','sum_pay_money', 'source_type',aid]
     * @return bool
     * 备注：source_type 3 需增加参数 tid
     */
    public function checkout($input=[], $is_t1=false)
    {
        $keys = ['pay_trade_no', 'pay_type', 'pay_source', 'payment_record_id', 'order_table_id', 'sum_pay_money','source_type'];
        if(!valid_keys_exists($keys, $input))
        {
            log_message('error', __METHOD__.'参数不足:' .@json_encode($input));
            return false;
        }

        switch($input['source_type']) {
            case 1://点餐流水
                return   $this->_mealCheckout($input);
                break;
            case 2://外卖流水
                break;
            case 3://零售流水
                return   $this->_retailCheckout($input);
                break;
            default:
                break;
        }
    }

    /**
     * 创建点餐收款流水记录
     * @param $input 必含参数[ 'pay_trade_no', 'pay_type', 'pay_source', 'payment_record_id','order_table_id','sum_pay_money','aid']
     * @return bool
     */
    private function _mealCheckout($input=[])
    {
        //通知支付成功
        $meal_order_event_bll = new meal_order_event_bll();
        $success = $meal_order_event_bll->notifyPaidSuccess($input, true);

        if($success !== true)
        {
            log_message('error', __METHOD__.'通知支付错误:' .@json_encode($input));
            return false;
        }

        // source 来源 在线支付 & t1
        // pay_type 支付类型
        // money 金额
        // out_pay_no 外部支付单号
        $mealOrderDao = MealOrderDao::i($input['aid']);
        $mealStatementsDao = MealStatementsDao::i($input['aid']);

        $m_order = $mealOrderDao->getOne(['pay_trade_no' => $input['pay_trade_no']]);
        if(!$m_order)
        {
            log_message('error', __METHOD__.'相关订单记录不存在:' .@json_encode($input));
            return false;
        }

        $total_money = $mealOrderDao->getSum('pay_money',['aid'=>$input['aid'],'pay_trade_no' => $input['pay_trade_no']]);

        $statement['amount'] = $m_order->sum_pay_money;
        $statement['total_money'] = $total_money;//订单总金额
        $statement['serial_number'] = $input['order_table_id'].create_serial_number('SKLS');
        $statement['order_number'] = $input['pay_trade_no'];
        $statement['order_table_id'] = $input['order_table_id'];
        $statement['shop_id'] = $m_order->shop_id;
        $statement['status'] = 2;


        //构建数据
        $statement['aid'] = $m_order->aid;
        $statement['source_type'] = $input['source_type'];
        $statement['type'] = 1;
        $statement['refund_type'] = 0;
        $statement['gateway'] = $input['pay_type'];
        $statement['pay_source'] = $input['pay_source'];
        $statement['operator'] = '';
        $statement['discount_type'] = 0;
        $statement['discount_money'] = 0;
        $statement['reason'] = '';
        $statement['is_print'] = 0;
        $statement['print_time'] = 0;
        $statement['print_sn'] = '';
        $statement['ext_data'] = '';
        $statement['time'] = time();

        $id = $mealStatementsDao->create($statement);
        if($id > 0)
        {
            return true;
        }

        log_message('error', __METHOD__.'创建流水失败:' .@json_encode($input));
        return false;
    }

    /**
     * 创建零售收款流水记录
     * @param $input 必含参数[ 'pay_trade_no', 'pay_type', 'pay_source', 'payment_record_id','order_table_id','sum_pay_money','aid']
     * @return bool
     */
    private function _retailCheckout($input=[])
    {
        //通知支付成功
        $retail_order_event_bll = new retail_order_event_bll();
        $success = $retail_order_event_bll->notifyPaidSuccess($input, true);

        if($success !== true)
        {
            log_message('error', __METHOD__.'通知支付错误:' .@json_encode($input));
            return false;
        }

        // source 来源 在线支付 & t1
        // pay_type 支付类型
        // money 金额
        // out_pay_no 外部支付单号
        $retailOrderDao = RetailOrderDao::i($input['aid']);
        $mealStatementsDao = MealStatementsDao::i($input['aid']);

        $m_order = $retailOrderDao->getOne(['pay_trade_no' => $input['pay_trade_no']]);
        if(!$m_order)
        {
            log_message('error', __METHOD__.'相关订单记录不存在:' .@json_encode($input));
            return false;
        }
        $total_money = $retailOrderDao->getSum('pay_money',['aid'=>$input['aid'],'pay_trade_no' => $input['pay_trade_no']]);

        $statement['amount'] = $m_order->sum_pay_money;
        $statement['total_money'] = $total_money;
        $statement['serial_number'] = create_serial_number('SKLS');
        $statement['order_number'] = $input['pay_trade_no'];
        $statement['order_table_id'] = $input['order_table_id'];
        $statement['shop_id'] = $m_order->shop_id;
        $statement['status'] = 2;


        //构建数据
        $statement['aid'] = $m_order->aid;
        $statement['source_type'] = $input['source_type'];
        $statement['type'] = 1;
        $statement['refund_type'] = 0;
        $statement['gateway'] = $input['pay_type'];
        $statement['pay_source'] = $input['pay_source'];
        $statement['operator'] = '';
        $statement['discount_type'] = 0;
        $statement['discount_money'] = 0;
        $statement['reason'] = '';
        $statement['is_print'] = 0;
        $statement['print_time'] = 0;
        $statement['print_sn'] = '';
        $statement['ext_data'] = '';
        $statement['time'] = time();

        $id = $mealStatementsDao->create($statement);
        if($id > 0)
        {
            return true;
        }

        log_message('error', __METHOD__.'创建流水失败:' .@json_encode($input));
        return false;
    }
    /**
     * 创建退款流水记录
     * @param $input 必含参数[ 'serial_number', 'gateway', 'pay_source','money','ext_data',aid];
     * @param int $aid
     * @return bool
     *  ext_data = ['tid';'','goods_id':'','sku_id':'','num':'','price':'']
     */
    public function refund($input=[])
    {
        $keys = ['serial_number', 'gateway', 'pay_source', 'refund_type', 'money', 'ext_data', 'aid'];
        if (!valid_keys_exists($keys, $input)) {
            log_message('error', __METHOD__ . '参数不足:' . @json_encode($input));
            return false;
        }
        $mealStatementsDao = MealStatementsDao::i($input['aid']);
        //
        $m_meal_statements = $mealStatementsDao->getOne(['aid' => $input['aid'], 'serial_number' => $input['serial_number']]);
        if (!$m_meal_statements) {
            log_message('error', __METHOD__ . '相关流水记录不存在:' . @json_encode($input));
            return false;
        }


        //构建数据
        $statement['refund_type'] = $input['refund_type'];
        $statement['gateway'] = $input['gateway'];
        $statement['amount'] = $input['money'];
        $statement['serial_number'] = create_serial_number('TKLS');
        $statement['order_number'] = $m_meal_statements->serial_number;
        $statement['order_table_id'] = $m_meal_statements->order_table_id;
        $statement['shop_id'] = $m_meal_statements->shop_id;
        $statement['status'] = 10;
        $statement['aid'] = $input['aid'];
        $statement['refund_type'] = $input['refund_type'];
        $statement['type'] = 2;
        $statement['source_type'] = $m_meal_statements->source_type;
        $statement['operator'] = '';
        $statement['discount_type'] = 0;
        $statement['discount_money'] = 0;
        $statement['reason'] = '';
        $statement['is_print'] = 0;
        $statement['print_time'] = 0;
        $statement['print_sn'] = '';
        $statement['ext_data'] = !empty($input['ext_data']) ? $input['ext_data'] : '';
        $statement['time'] = time();

        //更新付款流水数据
        $refund_money = $input['money'] + $m_meal_statements->refund_money;
        //判断退款金额
        if($refund_money > $m_meal_statements->amount  )
        {
            log_message('error', __METHOD__ . '退款金额错误:' . @json_encode($input));
            return false;
        }

        $update_data['status'] = 3;
        $update_data['refund_money'] = $refund_money;
        //判断是否全额退款
        if($m_meal_statements->amount == $refund_money)
        {
            $update_data['status'] = 4;
        }
        if(!($m_meal_statements->refund_type > 0))
        {
            $update_data['refund_type'] = $input['refund_type'];

            $ext_data = $this->_getRefundGoods($m_meal_statements->ext_data,$m_meal_statements->serial_number,$input['aid']);
            $update_data['ext_data'] = @json_encode($ext_data);
        }

        try
        {
            //原路返回
            if($input['gateway'] == 2)//支付宝
            {
                //支付宝对款接口
                $this->_alipayRefund($m_meal_statements,$input);
                // log_message('error', __METHOD__ . '暂无支付宝退款:' . @json_encode($input));
            }
            elseif($input['gateway'] == 3)//微信
            {
                //微信退款
                $this->_wxRefund($m_meal_statements, $input);

            }
            elseif(in_array($input['gateway'], [5,6]))
            {
                //付呗退款（银行通道)
                $this->_fbRefund($m_meal_statements, $input);

            }
        }
        catch (Exception $e)
        {
            log_message('error', __METHOD__ . '原路退款异常:' . @json_encode($input).'--'.$e->getMessage());
            return false;
        }

        //创建记录
        $id = $mealStatementsDao->create($statement);
        if($id > 0)
        {
            //更新付款流水
            $mealStatementsDao->update($update_data, ['id'=>$m_meal_statements->id, 'aid'=>$m_meal_statements->aid]);
            return true;
        }

        log_message('error', __METHOD__.'创建流水失败:' .@json_encode($input));
        return false;
    }

    /**
     * 获取当前流水所有退款的商品数据
     */
    private function _getRefundGoods($ext_data='', $serial_number='', $aid=0)
    {
        $mealStatementsDao = MealStatementsDao::i($aid);
        $refund_statements = $mealStatementsDao->getAllArray(['aid' => $aid, 'order_number' => $serial_number], 'id,ext_data');

        $_ext_data = @json_decode($ext_data, true);
        $ext_data = is_array($_ext_data) ? $_ext_data : [];

        foreach($refund_statements as $statement)
        {
            $_ext_data = @json_decode($statement['ext_data'], true);
            if(!is_array($_ext_data))
                continue;
            $ext_data = array_merge($ext_data, $_ext_data);
        }

        return $this->_mergeExt($ext_data);
    }

    /**
     * 组合重复商品
     * @param array $ext_arr
     * @return array
     */
    private function _mergeExt($ext_arr=[])
    {
        $arr = [];
        foreach ($ext_arr as $v) {
            $is_exit =false;
            foreach ($arr as $ak=>$av) {
                if($av['tid'] == $v['tid'] && $av['sku_id'] == $v['sku_id'])
                {
                    $is_exit = true;
                    $arr[$ak]['num'] += $v['num'];
                }
            }
            if(!$is_exit)
                array_push($arr, $v);
        }

        return $arr;
    }
    /**
     *
     * 微信退款
     * $input 必须字段
     * money 退款金额
     */
    private function _wxRefund($m_meal_statements, $input=[])
    {
        $aid = $m_meal_statements->aid;
        // 查询付款信息
        $mealPaymentRecordDao = MealPaymentRecordDao::i($aid);
        $pay_order = $mealPaymentRecordDao->getOneArray(['aid' => $aid, 'trade_no' => $m_meal_statements->order_number, 'status' => 1]);

        if (!$pay_order) {
            $this->json_do->set_error('004', '支付信息异常');
        }

        //判断支付类型
        if($pay_order['source'] == 'xcx' && $pay_order['appid']) {
            ci_wxpay::load('jsapi', $aid, $xcxAppid = $pay_order['appid']);
        } else {
            ci_wxpay::load('jsapi', $aid);
        }

        //微信退款接口

        $total_fee = bcmul($m_meal_statements->amount, 100);
        $refund_fee = bcmul($input['money'], 100);
        $wx_input = new WxPayRefund();
        $wx_input->SetTransaction_id($m_meal_statements->order_number);
        $wx_input->SetTotal_fee($total_fee);
        $wx_input->SetRefund_fee($refund_fee);
        $wx_input->SetOut_refund_no(WXPAY_MCHID . date("YmdHis"));
        $wx_input->SetOp_user_id(WXPAY_MCHID);
        $result = WxPayApi::refund($wx_input);

        if (!(isset($result['return_code']) && $result['return_code'] == 'SUCCESS')) {
            log_message('error', __METHOD__ . '微信退款：' . json_encode($result));
            $this->json_do->set_error('004', '微信退款失败');
        }
        log_message('error', __METHOD__ . '微信退款success：' . json_encode($result));
    }

    /**
     * 支付宝退款
     */
    private function _alipayRefund($m_meal_statements, $input=[])
    {
        $aid = $m_meal_statements->aid;
        // 查询付款信息
        $mealPaymentRecordDao = MealPaymentRecordDao::i($aid);
        $pay_order = $mealPaymentRecordDao->getOneArray(['aid' => $aid, 'trade_no' => $m_meal_statements->order_number, 'status' => 1]);

        if (!$pay_order) {
            $this->json_do->set_error('004', '支付信息异常');
        }
        //校验公众号配置
        $wmAlipayConfigDao = WmAlipayConfigDao::i($aid);
        $config = $wmAlipayConfigDao->getOne(['aid' => $aid]);

        if(!$config)
        {
            $this->json_do->set_error('004', '支付宝当面付未配置');
        }
        require_once LIBS_PATH.'libraries/alipay/f2fpay/model/builder/AlipayTradeRefundContentBuilder.php';
        require_once LIBS_PATH.'libraries/alipay/f2fpay/service/AlipayTradeService.php';

         
        $out_trade_no = create_order_number();
        $trade_no = $pay_order['trade_no'];
        $refund_amount = $input['money'];
        $out_request_no = $pay_order['trade_no'];
        
        //创建退款请求builder,设置参数
        $refundRequestBuilder = new AlipayTradeRefundContentBuilder();
        $refundRequestBuilder->setOutTradeNo($out_trade_no);
        $refundRequestBuilder->setTradeNo($trade_no);
        $refundRequestBuilder->setRefundAmount($refund_amount);
//        $refundRequestBuilder->setOutRequestNo($out_request_no);

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
        //初始化类对象,调用refund获取退款应答
        $refundResponse = new AlipayTradeService($conf);
        $refundResult = $refundResponse->refund($refundRequestBuilder);
        if($refundResult->getTradeStatus()!='SUCCESS')
        {
            log_message('error',__METHOD__.'-'.json_encode($refundResult->getResponse()));
            $this->json_do->set_error('004', '失败alipay');
        }
        log_message('error',__METHOD__.'--支付宝refund success'.json_encode($refundResult->getResponse()));
    }

    /**
     * 付呗退款（银行通道)
     * @param $m_meal_statements
     * @param array $input
     */
    private function _fbRefund($m_meal_statements, $input=[])
    {
        $aid = $m_meal_statements->aid;
        // 查询付款信息
        $mealPaymentRecordDao = MealPaymentRecordDao::i($aid);
        $pay_order = $mealPaymentRecordDao->getOneArray(['aid' => $aid, 'trade_no' => $m_meal_statements->order_number, 'status' => 1]);

        if (!$pay_order) {
            $this->json_do->set_error('004', '支付信息异常');
        }
        $wmFubeiRefshopDao = WmFubeiRefshopDao::i($aid);
        $m_wm_fubei_refshop = $wmFubeiRefshopDao->getOne(['aid'=>$aid,'shop_id'=>$m_meal_statements->shop_id]);
        if(!$m_wm_fubei_refshop)
            $this->json_do->set_error('004', '门店银行通道未配置');
        $wmFubeiConfigCache = new WmFubeiConfigCache(['aid'=>$aid,'fubei_id'=>$m_wm_fubei_refshop->fubei_id]);
        $m_pay_fubei = $wmFubeiConfigCache->getDataASNX();
        
        $config['app_id'] = $m_pay_fubei->app_id;
        $config['app_secret'] = $m_pay_fubei->app_secret;
        $config['store_id'] = (int)$m_pay_fubei->store_id;

        //支付方式[微信1/支付宝2]

        $fbinput['merchant_order_sn'] = $pay_order['code'];
        $fbinput['merchant_refund_sn'] = create_order_number();
        $fbinput['refund_money'] = $input['money'];
        $fubei_sdk = new fubei_sdk($config);
        $res = $fubei_sdk->refund($fbinput);

        if($res->result_code != 200)
        {
            log_message('error', __METHOD__.json_encode($res));
            $this->json_do->set_error('004', '失败');
        }
        log_message('error', __METHOD__.'--fubei refund success:'.json_encode($res));
    }

    // 打印小票
    public function println()
    {
        //
    }

}

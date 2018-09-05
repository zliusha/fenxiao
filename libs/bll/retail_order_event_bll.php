<?php
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
/**
 * 云店宝（零售版）订单处理机
 * @author dadi
 *
 */
class retail_order_event_bll extends base_bll
{
    // 订单对象
    public $order = null;
    // 状态机对象
    public $fsm = null;
    // json_do对象
    public $json_do = null;
    // 当前锁键
    public $lockkey = '';
    // 使用return返回结果
    public $return = false;

    public function __construct()
    {
        parent::__construct();
        $this->fsm = new retail_order_fsm_bll();
        $this->json_do = new json_do();
    }

    // 上锁
    private function lock($key)
    {
        $locked = false;
        if ($locked) {
            $this->json_do->set_error('004', '系统繁忙，请稍候重试');
        }

        $this->lockkey = $key;
        return true;
    }

    // 释放锁
    private function unlock()
    {
        // 释放锁
    }

    // 订单日志记录
    private function eventLogger($event, $tradeno)
    {
        //
    }

    // 输出结果 带解锁功能
    public function respond($unlock = true)
    {
        $this->unlock();
        return $this->respond;
    }
    //必填:aid
    private function loadOrder($input = [])
    {
        // 自动加载订单
        $retailOrderDao = RetailOrderDao::i($input['aid']);
        $this->order = $retailOrderDao->getOne($input);

        if (!$this->order) {
            $this->json_do->set_error('004', '订单读取失败');
        } else {
            return $this->order;
        }
    }

    // 运行FSM状态机 必填:aid
    public function runFsm($event = '', $input = [])
    {
        // 不需要加载订单的事件
        $except_event = ['ORDER_CREATE'];

        if (!$input && !in_array($event, $except_event)) {
            $this->json_do->set_error('004', '程序问题，请联系管理员');
        }

        if (!in_array($event, $except_event)) {
            // 加载订单信息
            $order = $this->loadOrder($input);
            if (!$order) {
                return false;
            }

            $tradeno = $order->tid;
            $state = $order->status;
        } else {
            $tradeno = '0';
            $state = '0000';
        }

        // 调用状态机获取新状态
        try {
            if ($this->fsm->setSpaceState($state)->process($event)) {
                return $this->fsm->state;
            } else {
                if ($this->return === true) {
                    return false;
                } else {
                    $this->json_do->set_error('004', $this->fsm->msg);
                }
            }
        } catch (Exception $e) {
            log_message('error', '订单编号 => ' . $tradeno . ' 状态机执行失败 => ' . $e->getMessage());
            $this->json_do->set_error('004', '操作失败');
        }
    }

    /**
     * 创建订单
     * $input 数组结构
     * aid => 商家ID
     * shop_id => 店铺ID
     * items => 订单JSON
     */
    public function orderCreate(&$input = [], $is_t1 = false)
    {
        $waimai_inc = inc_config('waimai');
        $unit_type_arr = @$waimai_inc['goods_unit_type'];
        $this->lock($key = 1);
        $retailOrderDao = RetailOrderDao::i($input['aid']);
        $retailOrderExtDao = RetailOrderExtDao::i($input['aid']);

        #=============================================================================
        // 批量获取商品相关数据开始
        $wmGoodsDao = WmGoodsDao::i($input['aid']);
        $WmGoodsSkuDao = WmGoodsSkuDao::i($input['aid']);

        // 原始下单数据
        $input_items = $input['items'];

        // 锁定库存
        $is_lock_store = $WmGoodsSkuDao->lockStock($input_items, $input['shop_id']);
        if ($is_lock_store !== true) {
            $this->unlock();
            $this->json_do->set_error('004', '库存不足');
        }

        // 获取商品数据列表
        $goods_ids = array_unique(array_column($input['items'], 'goods_id'));
        $goods_list = $wmGoodsDao->getEntitysByAR([
            'where_in' => ['id' => $goods_ids],
        ], true);
        $goods_list = array_column($goods_list, null, 'id');

        // 获取SKU数据列表
        $sku_ids = array_unique(array_column($input['items'], 'sku_id'));
        $sku_list = $WmGoodsSkuDao->getEntitysByAR([
            'where_in' => ['id' => $sku_ids],
        ], true);
        $sku_list = array_column($sku_list, null, 'id');
        // 批量获取商品相关数据结束
        #=============================================================================

        $ext_total_money = 0; // 商品总额
        $ext_total_num = 0; // 商品总数
        $ext_pay_money = 0; // 支付总金额

        // 计算订单数据
        foreach ($input['items'] as $key => $item) {
            // 商品信息
            $item['goods'] = $goods_list[$item['goods_id']];
            // sku信息
            $item['sku'] = $sku_list[$item['sku_id']];

            // 订单金额 (sku售价*商品数量)
//            $item['order_money'] = bcmul($item['sku']['sale_price'], $item['quantity'], 2);
            //四舍五入2位小数
            $item['order_money'] = sprintf("%.2f", $item['sku']['sale_price']*$item['quantity']);
            // 待支付金额
            $item['pay_money'] = $item['order_money'];

            // 商品总额
            $ext_total_money = bcadd($ext_total_money, $item['order_money'], 2);
            // 商品总数 计重商品当成1个数量
            $ext_total_num += $item['goods']['measure_type'] ==1 ? $item['quantity'] : 1;
            // 支付总金额（不含运费、不包含餐盒费）
            $ext_pay_money = bcadd($ext_pay_money, $item['pay_money'], 2);
            // 保存数据
            $input['items'][$key] = $item;
        }

        #=============================================================================
        // 标记是否是t1下单
        $this->fsm->is_t1 = $is_t1;
        #=============================================================================
        // 调用状态机获取新状态
        $_state = $this->runFsm('ORDER_CREATE',['aid'=>$input['aid']]);
        #=============================================================================

        #=============================================================================
        // 构建基础父订单开始
        $order['tid'] = create_order_number();
        $order['aid'] = $input['aid'];
        $order['shop_id'] = $input['shop_id'];
        $order['total_money'] = $ext_total_money;
        $order['total_num'] = $ext_total_num;
        $order['pay_money'] = $order['total_money'];
        $order['status'] = $_state->code;
        $order['api_status'] = $_state->api_code;
        $order['update_time'] = time();
        // 构建基础父订单结束
        #=============================================================================

        // 增加下单金额判断
        if ($order['pay_money'] <= 0) {
            $this->unlock();
            $WmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
            log_message('error', __METHOD__ . '下单失败 ' . json_encode($order));
            $this->json_do->set_error('004', '下单失败，支付金额必须大于0');
        }
        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();
        $order_id = $retailOrderDao->create($order);

        foreach ($input['items'] as $key => $item) {
            $ext_tid = $key + 1;
            $order_ext = [];

            $order_ext['aid'] = $input['aid'];
            $order_ext['shop_id'] = $input['shop_id'];
            $order_ext['tid'] = $order['tid'];
            $order_ext['ext_tid'] = $ext_tid;
            $order_ext['order_id'] = $order_id;
            $order_ext['goods_id'] = $item['goods_id'];
            $order_ext['goods_title'] = $item['goods']['title'];
            $order_ext['goods_pic'] = $item['goods']['pict_url'];
            $order_ext['sku_id'] = $item['sku_id'];
            $order_ext['sku_str'] = implode(' ', explode(',', $item['sku']['attr_names'])); // SKU属性名
            $order_ext['status'] = $_state->code;
            $order_ext['api_status'] = $_state->api_code;
            $order_ext['ori_price'] = isset($item['sku']['price']) ? $item['sku']['price'] : 0; // 原价
            $order_ext['price'] = isset($item['sku']['sale_price']) ? $item['sku']['sale_price'] : 0; // 售价
            $order_ext['pay_money'] = $item['pay_money'];
            $order_ext['order_money'] = $item['order_money'];
            $order_ext['num'] = $item['quantity'];
            $order_ext['update_time'] = time();
            $order_ext['measure_type'] = $item['goods']['measure_type'];
            $order_ext['unit_type'] = $item['goods']['unit_type'];
            $order_ext['unit_type_name'] = $unit_type_arr[$order_ext['unit_type']];
            if(isset($item['pro_attr']))
                $order_ext['pro_attr'] = $item['pro_attr'];

            $order_ext_id[$key] = $retailOrderExtDao->create($order_ext);
        }

        $wmShardDb->trans_complete();

        if (!$order_id) {
            $this->unlock();
            $WmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
            log_message('error', __METHOD__ . '下单失败 ' . json_encode($order));
            $this->json_do->set_error('004', '下单失败');
        }

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $order_id);

        $this->unlock();
        $this->json_do->set_data([
            'tradeno' => $order['tid'],
            'server_time' => date('Y-m-d H:i:s'),
        ]);
        $this->json_do->set_msg('下单成功');
        $this->json_do->out_put();
    }

    /**
     * 通知订单已经支付
     * @param  array   $input   [description]
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // order_table_id => 桌位订单ID
    // pay_source => 支付渠道
    // pay_type => 支付方式
    // payment_record_id => 在线支付记录ID（收银台支付时为0）
    // pay_trade_no => 支付网关返回的支付单号
    // sum_pay_money => 合并支付的总金额
    // aid => aid
    public function notifyPaidSuccess($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 全局设置return
        $this->return = $return;

        $retail_inc = &inc_config('meal');

        $retailOrderDao = RetailOrderDao::i($input['aid']);
        $retailOrderExtDao = RetailOrderExtDao::i($input['aid']);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($input['aid']);

        $this->fsm->setSpaceState(1010)->process('NOTIFY_PAID_SUCCESS');

        $_state = $this->fsm->state;

        // 支付记录ID
        $input['payment_record_id'] = !isset($input['payment_record_id']) ? 0 : $input['payment_record_id'];

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $retailOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time(), 'pay_source' => $input['pay_source'], 'pay_type' => $input['pay_type'], 'payment_record_id' => $input['payment_record_id'], 'pay_trade_no' => $input['pay_trade_no'], 'pay_time' => time(), 'sum_pay_money' => $input['sum_pay_money']], ['tid' => $input['tid'], 'status' => 1010]);

        // 更新子订单状态
        $retailOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time(), 'pay_source' => $input['pay_source'], 'pay_type' => $input['pay_type'], 'payment_record_id' => $input['payment_record_id'], 'pay_trade_no' => $input['pay_trade_no'], 'pay_time' => time(), 'sum_pay_money' => $input['sum_pay_money']], ['tid' => $input['tid'], 'status' => 1010]);

        // 扣除库存
        $order_ext = $retailOrderExtDao->getAllArray(['tid' => $input['tid']], 'sku_id, goods_id, num as quantity');
        $wmGoodsSkuDao->reduceStock($order_ext, $this->order->shop_id);

        $wmShardDb->trans_complete();

        // 计算订单商品最后的计算单价
        $this->_calculateOrderGoodsFinalPrice(['aid'=>$input['aid'], 'pay_trade_no' => $input['pay_trade_no'], 'sum_pay_money' => $input['sum_pay_money']]);

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['pay_trade_no']);

        $this->unlock();

        if ($this->return) {
            return true;
        } else {
            $this->json_do->set_msg('支付成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 计算订单商品最后的计算单价
     * @param array $input 必须aid pay_trade_no sum_pay_money
     */
    private function _calculateOrderGoodsFinalPrice($input=[])
    {
        $retailOrderExtDao = RetailOrderExtDao::i($input['aid']);

        $total_money = $retailOrderExtDao->getSum('pay_money',['aid'=>$input['aid'], 'pay_trade_no'=>$input['pay_trade_no'], 'pay_time >'=>0]);
        $order_exts = $retailOrderExtDao->getAllArray(['aid'=>$input['aid'], 'pay_trade_no'=>$input['pay_trade_no'], 'pay_time >'=>0]);
        $order_exts_count = $retailOrderExtDao->getCount(['aid'=>$input['aid'], 'pay_trade_no'=>$input['pay_trade_no'], 'pay_time >'=>0]);
        $update_data = [];
        $i = 0;
        $order_total_money = 0;
        foreach($order_exts as $order_ext)
        {
            $i++;
            //踢出单独商品折扣金额后 商品按照金额比例分配的优惠金额
            //如果最后一个则保留剩余金额
            if($i == $order_exts_count)
            {
                $final_price =  bcsub($input['sum_pay_money'], $order_total_money, 2);
            }
            else
            {
                $final_price = bcmul($order_ext['pay_money']/$total_money, $input['sum_pay_money'], 2);
                $order_total_money = bcadd($order_total_money, $final_price, 2);
            }

            $tmp['id'] = $order_ext['id'];
            $tmp['aid'] = $input['aid'];
            $tmp['final_price'] = $final_price;

            $update_data[] = $tmp;
        }
        if(!empty($update_data))
            $retailOrderExtDao->updateBatch($update_data, 'id');

    }
}

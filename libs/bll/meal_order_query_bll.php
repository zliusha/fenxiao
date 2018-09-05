<?php
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderExtDao;

/**
 * 云店宝（扫码点餐版）订单查询机
 * @author dadi
 *
 */
class meal_order_query_bll extends base_bll
{

    // 订单数据
    private $order = [];
    // 枚举数据
    private $enum = [];

    public function __construct()
    {
        parent::__construct();
        $this->fsm = new meal_order_fsm_bll();
        $this->enum = &inc_config('meal');
    }

    // 订单数据查询（H5&&T1简单查询） 必选：aid
    public function simple_query($input = [])
    {
        $wmShardDb = WmShardDb::i($input['aid']);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->table = $wmShardDb->tables['meal_order'];

        $p_conf->where .= " AND `order_table_id` = {$page->filter($input['order_table_id'])} ";

        #====================================
        # 排序
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        // 查询子订单
        if ($list) {
            $order_ids = array_column($list, 'id');
            $mealOrderExtDao = MealOrderExtDao::i($input['aid']);
            $result = $mealOrderExtDao->getEntitysByAR(['where_in' => ['order_id' => $order_ids]], true);

            foreach ($result as $key => $row) {
                #====================================
                # 处理子订单时间戳字段
                // 创建时间
                $row['time'] = $this->time($row['time']);
                // 更新时间
                $row['update_time'] = $this->time($row['update_time']);
                #====================================
                # 处理子订单状态字段
                $row['status'] = $this->fsm->parseState($row['status']);
                #====================================
                $order_ext[$row['order_id']][] = $row;
            }
            foreach ($list as $key => $row) {
                #====================================
                # 处理父订单枚举字段
                # 处理父订单时间戳字段
                // 创建时间
                $list[$key]['time'] = $this->time($row['time']);
                // 更新时间
                $list[$key]['update_time'] = $this->time($row['update_time']);

                #====================================
                # 处理父订单状态字段
                $list[$key]['status'] = $this->fsm->parseState($row['status']);
                #====================================

                #====================================
                if ($order_ext[$row['id']]) {
                    $list[$key]['order_ext'] = $order_ext[$row['id']];
                } else {
                    $list[$key]['order_ext'] = [];
                    log_message('error', '存在损毁订单 order_id=>' . $row['id'] . ' tid=>' . $row['tid']);
                }
            }
        }

        $this->order['total'] = $count;
        $this->order['rows'] = $list;

        return $this;
    }

    // 获取订单数据
    public function get()
    {
        return is_array($this->order) ? $this->order : [];
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
     * 查询满足条件所有订单
     * @param array $input 必含 aid
     * @return bool
     */
    public function query_all($input=[])
    {
        if(empty($input)) return false;
        $mealOrderDao = MealOrderDao::i($input['aid']);
        $list = $mealOrderDao->getAllArray($input);

        // 查询子订单
        if($list) {
            $order_ids = array_column($list, 'id');
            $mealOrderExtDao = MealOrderExtDao::i($input['aid']);
            $result = $mealOrderExtDao->getEntitysByAR(['where_in' => ['order_id' => $order_ids]], true);

            foreach ($result as $key => $row) {
                #====================================

                # 处理子订单时间戳字段
                // 创建时间
                $row['time'] = $this->time($row['time']);
                // 更新时间
                $row['update_time'] = $this->time($row['update_time']);
                #====================================
                # 处理子订单状态字段
                $row['status'] = $this->fsm->parseState($row['status']);
                #====================================
                //处理商品图片
                $rwo['goods_pic'] = conver_picurl($row['goods_pic']);

                $order_ext[$row['order_id']][] = $row;
            }
            foreach ($list as $key => $row) {
                #====================================
                # 处理父订单枚举字段
                # 处理父订单时间戳字段
                // 创建时间
                $list[$key]['time'] = $this->time($row['time']);
                // 更新时间
                $list[$key]['update_time'] = $this->time($row['update_time']);

                #====================================
                # 处理父订单状态字段
                $list[$key]['status'] = $this->fsm->parseState($row['status']);
                #====================================

                #====================================
                if ($order_ext[$row['id']]) {
                    $list[$key]['order_ext'] = $order_ext[$row['id']];
                } else {
                    $list[$key]['order_ext'] = [];
                    log_message('error', '存在损毁订单 order_id=>' . $row['id'] . ' tid=>' . $row['tid']);
                }
            }
        }

        return $list;
    }
}

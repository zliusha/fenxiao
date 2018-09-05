<?php
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDadaDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDianwodaDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFengdaDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderCantingbaoDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFreightDao;
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
/**
 * 云店宝（外卖版）订单查询机
 * @author dadi
 *
 */
class wm_order_query_bll extends base_bll
{

    // 订单数据
    private $order = [];
    // 枚举数据
    private $enum = [];

    public function __construct()
    {
        parent::__construct();
        $this->fsm = new wm_order_fsm_bll();
        $this->enum = &inc_config('waimai');
    }

    // 订单数据查询 aid必填
    public function query($input = [])
    {
        $inc = &inc_config('waimai');
        $wmShardDb = WmShardDb::i($input['aid']);
        $page = new pageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = $wmShardDb->tables['wm_order'];

        #====================================
        # 订单指定查询条件处理
        if ($input) {
            // 特定用户订单查询
            if (isset($input['uid'])) {
                $p_conf->where .= " AND uid = {$page->filter($input['uid'])} ";
            }

            // 特定用户订单查询
            if (isset($input['aid'])) {
                $p_conf->where .= " AND aid = {$page->filter($input['aid'])} ";
            }

            // 按订单号查询
            if (isset($input['tradeno'])) {
                $p_conf->where .= " AND tid = {$page->filter($input['tradeno'])} ";
            }

            // 按订单类别查询
            if (isset($input['type'])) {
                $p_conf->where .= " AND `type` IN ({$input['type']}) ";
            }
        }
        #====================================

        #====================================
        # 订单POST查询条件处理
        // 按门店查询
        // 优先门店处理
        if(isset($input['shop_id']) && is_numeric($input['shop_id']))
        {
            $p_conf->where .= " AND `shop_id` = {$input['shop_id']} ";
        }
        else
        {
            if (!empty($this->input->post_get('shop_id'))) {
                $p_conf->where .= " AND `shop_id` = {$page->filter($this->input->post_get('shop_id'))} ";
            }
        }


        // 按订单状态查询
        if (!empty($this->input->post_get('status'))) {
            // 未付款的订单必须大于订单截止时间
            if ($this->input->post_get('status') == '1010') {
                $cut_time = order_pay_expire(0, true);
                $p_conf->where .= " AND `status` = 1010 AND `time` > {$cut_time}";
            } else {
                $_status = implode(',', explode(',', $this->input->post_get('status')));
                $p_conf->where .= " AND `status` IN ({$_status}) ";
            }
        }

        // 按支付方式查询
        if (!empty($this->input->post_get('pay_type'))) {
            $p_conf->where .= " AND `pay_type` = {$page->filter($this->input->post_get('pay_type'))} ";
        }

        // 按派送方式查询
        if (!empty($this->input->post_get('logistics_type'))) {
            $p_conf->where .= " AND `logistics_type` = {$page->filter($this->input->post_get('logistics_type'))} ";
        }

        // 按运单号查询
        if (!empty($this->input->post_get('logistics_code'))) {
            $p_conf->where .= " AND `logistics_code` = {$page->filter($this->input->post_get('logistics_code'))} ";
        }

        // 按收货手机号查询
        if (!empty($this->input->post_get('phone'))) {
            $p_conf->where .= " AND `receiver_phone` = {$page->filter($this->input->post_get('phone'))} ";
        }

        // 按收货手机号查询
        if (!empty($this->input->post_get('name'))) {
            $p_conf->where .= " AND `receiver_name` = {$page->filter($this->input->post_get('name'))} ";
        }

        // 按订单号查询
        if (!empty($this->input->post_get('tradeno'))) {
            $p_conf->where .= " AND `tid` = {$page->filter($this->input->post_get('tradeno'))} ";
        }

        // 按支付时间范围查询
        if (!empty($this->input->post_get('pay_time'))) {
            $range = explode(' - ', $this->input->post_get('pay_time'));
            $range = array_map('strtotime', $range);
            if (count($range) == 2) {
                $range[1] += 86400;
                $p_conf->where .= " AND `pay_time` >= '{$range[0]}' AND `pay_time` <= '{$range[1]}' ";
            }
        }

        // 按创建时间范围查询
        if (!empty($this->input->post_get('create_time'))) {
            $range = explode(' - ', $this->input->post_get('create_time'));
            $range = array_map('strtotime', $range);
            if (count($range) == 2) {
                $range[1] += 86400;
                $p_conf->where .= " AND `time` >= '{$range[0]}' AND `time` <= '{$range[1]}' ";
            }
        }

        // 按发货时间方位查询
        if (!empty($this->input->post_get('fh_time'))) {
            $range = explode(' - ', $this->input->post_get('fh_time'));
            $range = array_map('strtotime', $range);
            if (count($range) == 2) {
                $p_conf->where .= " AND `fh_time` >= '{$range[0]}' AND `fh_time` <= '{$range[1]}' ";
            }
        }
        #====================================
        # 排序
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        // 查询子订单
        if ($list) {
            $order_ids = array_column($list, 'id');
            $wmOrderExtDao = WmOrderExtDao::i();
            if (isset($input['ext_tid'])) {
                $result = $wmOrderExtDao->getEntitysByAR(['where_in' => ['order_id' => $order_ids], 'where' => ['ext_tid' => $input['ext_tid']]], true);
            } else {
                $result = $wmOrderExtDao->getEntitysByAR(['where_in' => ['order_id' => $order_ids], 'order_by' => 'puid asc, id asc'], true);
            }

            // 获取达达物流信息
            $dada_nos = [];
            $dada_infos = [];
            $dianwoda_nos = [];
            $dianwoda_infos = [];
            $fengda_nos = [];
            $fengda_infos = [];
            $cantingbao_nos = [];
            $cantingbao_infos = [];
            foreach ($list as $key => $row) {
                if ($row['logistics_type'] == 2 && !empty($row['logistics_code'])) {
                    $dada_nos[] = $row['logistics_code'];
                }
                if ($row['logistics_type'] == 3 && !empty($row['logistics_code'])) {
                    $dianwoda_nos[] = $row['logistics_code'];
                }

                //风达配送
                if ($row['logistics_type'] == 5 ) {
                    $fengda_nos[] = $row['tid'];
                }
                //餐厅宝
                if ($row['logistics_type'] == 6) {
                    $cantingbao_nos[] = $row['tid'];
                }
            }

            if ($dada_nos) {
                $wmOrderDadaDao = WmOrderDadaDao::i($input['aid']);
                $dada_infos = $wmOrderDadaDao->getEntitysByAR(['where_in' => ['client_id' => $dada_nos]], true);

                $dada_infos = array_column($dada_infos, null, 'client_id');
            }

            if ($dianwoda_nos) {
                $wmOrderDianwodaDao = WmOrderDianwodaDao::i($input['aid']);
                $dianwoda_infos = $wmOrderDianwodaDao->getEntitysByAR(['where_in' => ['order_original_id' => $dianwoda_nos]], true);

                $dianwoda_infos = array_column($dianwoda_infos, null, 'order_original_id');
            }
            //风达配送
            if ($fengda_nos) {
                $wmOrderFengdaDao = WmOrderFengdaDao::i($input['aid']);
                $fengda_infos = $wmOrderFengdaDao->getEntitysByAR(['where_in' => ['order_no' => $fengda_nos]], true);

                $fengda_infos = array_column($fengda_infos, null, 'order_no');
            }

            //餐厅宝配送
            if ($cantingbao_nos) {
                $wmOrderCantingbaoDao = WmOrderCantingbaoDao::i($input['aid']);
                $cantingbao_infos = $wmOrderCantingbaoDao->getEntitysByAR(['where_in' => ['order_no' => $cantingbao_infos]], true);

                $cantingbao_infos = array_column($cantingbao_infos, null, 'order_no');
            }

            foreach ($result as $key => $row) {
                #====================================
                # 处理子订单时间戳字段
                // 子订单小计金额
                $row['subtotal_money'] = $row['order_money'] - $row['discount_money'];
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
                // 支付方式
                $list[$key]['pay_type'] = $this->enum('pay_type', $row['pay_type']);
                // 物流方式
                $list[$key]['logistics_type'] = $this->enum('logistics_type', $row['logistics_type']);
                #====================================
                # 处理父订单时间戳字段
                // 创建时间
                $list[$key]['time'] = $this->time($row['time']);
                // 订单支付过期时间
                $list[$key]['pay_expire'] = $this->time(order_pay_expire($row['time']));
                // 更新时间
                $list[$key]['update_time'] = $this->time($row['update_time']);
                // 支付时间
                $list[$key]['pay_time'] = $this->time($row['pay_time']);
                // 发货时间
                $list[$key]['fh_time'] = $this->time($row['fh_time']);
                // 优惠详情
                $list[$key]['discount_detail'] = json_decode($row['discount_detail'], true);

                $list[$key]['logistics_status'] = '';
                $list[$key]['logistics_rider_name'] = '';
                $list[$key]['logistics_rider_mobile'] = '';
                // 物流信息
                if ($row['logistics_type'] == 2 && !empty($row['logistics_code'])) {
                    $list[$key]['logistics_detail'] = isset($dada_infos[$row['logistics_code']]) ? $dada_infos[$row['logistics_code']] : null;

                    if ($list[$key]['logistics_detail']) {
                        $list[$key]['logistics_status'] = isset($inc['dada_status'][$list[$key]['logistics_detail']['order_status']]) ? $inc['dada_status'][$list[$key]['logistics_detail']['order_status']] : '未知状态';
                        $list[$key]['logistics_rider_name'] = $list[$key]['logistics_detail']['dm_name'] ? $list[$key]['logistics_detail']['dm_name'] : '';
                        $list[$key]['logistics_rider_mobile'] = $list[$key]['logistics_detail']['dm_mobile'] ? $list[$key]['logistics_detail']['dm_mobile'] : '';
                    } else {
                        $list[$key]['logistics_status'] = "";
                    }
                } else if ($row['logistics_type'] == 3 && !empty($row['logistics_code'])) {
                    $list[$key]['logistics_detail'] = isset($dianwoda_infos[$row['logistics_code']]) ? $dianwoda_infos[$row['logistics_code']] : null;

                    if ($list[$key]['logistics_detail']) {
                        $list[$key]['logistics_status'] = isset($inc['dianwoda_status'][$list[$key]['logistics_detail']['order_status']]) ? $inc['dianwoda_status'][$list[$key]['logistics_detail']['order_status']] : '未知状态';
                        $list[$key]['logistics_rider_name'] = $list[$key]['logistics_detail']['rider_name'] ? $list[$key]['logistics_detail']['rider_name'] : '';
                        $list[$key]['logistics_rider_mobile'] = $list[$key]['logistics_detail']['rider_mobile'] ? $list[$key]['logistics_detail']['rider_mobile'] : '';
                    } else {
                        $list[$key]['logistics_status'] = "";
                    }
                } else if ($row['logistics_type'] == 4) {
                    // 到店自提信息
                    $list[$key]['logistics_detail'] = json_decode($list[$key]['logistics_status'], true);
                    $list[$key]['logistics_status'] = '';
                    $list[$key]['logistics_rider_name'] = '';
                    $list[$key]['logistics_rider_mobile'] = '';
                } else if ($row['logistics_type'] == 5) {
                    //风达
                    $list[$key]['logistics_detail'] = isset($fengda_infos[$row['tid']]) ? $fengda_infos[$row['tid']] : null;

                    if ($list[$key]['logistics_detail']) {
                        $list[$key]['logistics_status'] = isset($inc['fengda_status'][$list[$key]['logistics_detail']['order_status']]) ? $inc['fengda_status'][$list[$key]['logistics_detail']['order_status']] : '未知状态';
                        $list[$key]['logistics_rider_name'] = $list[$key]['logistics_detail']['rider_name'] ? $list[$key]['logistics_detail']['rider_name'] : '';
                        $list[$key]['logistics_rider_mobile'] = $list[$key]['logistics_detail']['rider_phone'] ? $list[$key]['logistics_detail']['rider_phone'] : '';
                    } else {
                        $list[$key]['logistics_status'] = "";
                    }
                } else if ($row['logistics_type'] == 6 ) {
                    //风达
                    $list[$key]['logistics_detail'] = isset($cantingbao_infos[$row['tid']]) ? $cantingbao_infos[$row['tid']] : null;

                    if ($list[$key]['logistics_detail']) {
                        $list[$key]['logistics_status'] = isset($inc['cantingbao_status'][$list[$key]['logistics_detail']['order_status']]) ? $inc['cantingbao_status'][$list[$key]['logistics_detail']['order_status']] : '未知状态';
                        $list[$key]['logistics_rider_name'] = $list[$key]['logistics_detail']['rider_name'] ? $list[$key]['logistics_detail']['rider_name'] : '';
                        $list[$key]['logistics_rider_mobile'] = $list[$key]['logistics_detail']['rider_phone'] ? $list[$key]['logistics_detail']['rider_phone'] : '';
                    } else {
                        $list[$key]['logistics_status'] = "";
                    }
                } else {
                    $list[$key]['logistics_detail'] = null;
                    $list[$key]['logistics_status'] = '';
                    $list[$key]['logistics_rider_name'] = '';
                    $list[$key]['logistics_rider_mobile'] = '';
                }
                #====================================
                # 处理父订单状态字段
                $list[$key]['status'] = $this->fsm->parseState($row['status']);
                #====================================
                # 判断是否可以申请售后
                if (in_array($row['status'], ['2020', '2030', '2035', '2040', '2050', '2060', '6060']) && time() < order_refund_expire($row['pay_time']) && empty($row['afsno'])) {
                    $list[$key]['is_can_refund'] = 1;
                } else {
                    $list[$key]['is_can_refund'] = 0;
                }
                # 判断是否允许评论
                if (in_array($row['status'], ['6060', '6061'])) {
                    if ($row['is_comment']) {
                        $list[$key]['is_able_comment'] = 0;
                    } else {
                        if ($row['pay_time'] > 0) {
                            $list[$key]['is_able_comment'] = (time() - $row['pay_time']) > 86400 ? 0 : 1;
                        } else {
                            $list[$key]['is_able_comment'] = 0;
                        }
                    }
                } else {
                    $list[$key]['is_able_comment'] = 0;
                }

                #====================================
                if ($order_ext[$row['id']]) {
                    $list[$key]['order_ext'] = $order_ext[$row['id']];
                } else {
                    $list[$key]['order_ext'] = [];
                    log_message('error', '存在损毁订单 order_id=>' . $row['id'] . ' tid=>' . $row['tid']);
                }
                // 计算子订单合计金额
                $_ext_total_money = 0;
                foreach ($order_ext[$row['id']] as $_ext_row) {
                    $_ext_total_money += $_ext_row['subtotal_money'];
                }
                $list[$key]['ext_total_money'] = $_ext_total_money;
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

}

<?php
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDb;
/**
 * 微商城优惠券查询机
 * @author dadi
 *
 */
class wm_coupon_query_bll extends base_bll
{

    // 列表数据
    private $list = [];
    // 枚举数据
    private $enum = [];

    public function __construct()
    {
        parent::__construct();
        $this->enum = &inc_config('waimai');
    }

    /**
     * 领取时间显示优化
     * @param  integer $time     领取时间
     * @param  integer $cur_time 当前时间
     * @return string        优化后显示
     */
    public function pickup_time_formatter($time, $cur_time = 0)
    {
        if (empty($cur_time)) {
            $cur_time = time();
        }

        $time_diff = $cur_time - $time;

        if ($time_diff <= 60) {
            return '刚刚领取';
        } else if ($time_diff > 60 && $time_diff < 3600) {
            return floor($time_diff / 60) . '分钟前领取';
        } else if ($time_diff >= 3600 && $time_diff < 18000) {
            return floor($time_diff / 3600) . '小时前领取';
        } else {
            return date('Y-m-d H:i:s', $time);
        }
    }

    /**
     * 过期时间优化展示
     * @param  integer $start    开始时间
     * @param  integer $end      过期时间
     * @param  integer $cur_time 当前时间
     * @return string            优化后显示
     */
    public function expire_time_formatter($start, $end, $cur_time = 0)
    {
        if (empty($cur_time)) {
            $cur_time = time();
        }

        if ($start > $cur_time) {
            return '未到可用时段';
        } else if ($end < $cur_time) {
            return '已过期';
        } else {
            $time_diff = $end - $cur_time;
            if ($time_diff <= 86400) {
                return '即将过期';
            } else {
                return floor($time_diff / 86400) . '天后过期';
            }
        }
    }

    // 订单数据查询 必填：aid
    public function query($input = [])
    {
        $wmShardDb = WmShardDb::i($input['aid']);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->table = $wmShardDb->tables['wm_coupon'];

        #====================================
        # 指定查询条件处理
        if ($input) {
            // 按ID查询
            if (isset($input['id'])) {
                $p_conf->where .= " AND `id` = {$page->filter($input['id'])} ";
            }

            // 特定用户订单查询
            if (isset($input['mobile'])) {
                $p_conf->where .= " AND `mobile` = {$page->filter($input['mobile'])} ";
            } else {
                $p_conf->where .= " AND `mobile` = -1 ";
            }

            // 特定用户订单查询
            if (isset($input['aid'])) {
                $p_conf->where .= " AND `aid` = {$page->filter($input['aid'])} ";
            }

            // 按类型查询
            if (isset($input['type'])) {
                $p_conf->where .= " AND `type` = {$page->filter($input['type'])} ";
            }

            // 按状态查询
            if (isset($input['status'])) {
                $p_conf->where .= " AND `status` IN ({$input['status']}) ";
            }
        }
        #====================================
        # 排序
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        // 查询子订单
        if ($list) {
            foreach ($list as $key => $row) {
                #====================================
                // 状态
                $list[$key]['status'] = $this->enum('coupon_status', $row['status']);
                #====================================
                # 处理父订单时间戳字段
                // 领取时间
                $list[$key]['time'] = $this->time($row['time']);
                // 领取时间显示
                $list[$key]['time_show'] = $this->pickup_time_formatter($row['time']);
                // 更新时间
                $list[$key]['use_start_time'] = $this->time($row['use_start_time']);
                // 支付时间
                $list[$key]['use_end_time'] = $this->time($row['use_end_time']);
                // 过期时间显示
                $list[$key]['expire_time_show'] = $this->expire_time_formatter($row['use_start_time'], $row['use_end_time']);
                // 发货时间
                $list[$key]['update_time'] = $this->time($row['update_time']);
                #====================================
            }
        }

        $this->list['total'] = $count;
        $this->list['rows'] = $list;

        return $this;
    }

    // 获取订单数据
    public function get()
    {
        return is_array($this->list) ? $this->list : [];
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

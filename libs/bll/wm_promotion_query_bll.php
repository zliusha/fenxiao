<?php
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
/**
 * 微商城促销查询机
 * @author dadi
 *
 */
class wm_promotion_query_bll extends base_bll
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

    // 订单数据查询 aid 必选
    public function query($input = [])
    {
        $wmShardDb = WmShardDb::i($input['aid']);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->table = $wmShardDb->tables['wm_promotion'];

        #====================================
        # 指定查询条件处理
        if ($input) {
            // 按ID查询
            if (isset($input['id'])) {
                $p_conf->where .= " AND `id` = {$page->filter($input['id'])} ";
            }

            // 特定用户订单查询
            if (isset($input['uid'])) {
                $p_conf->where .= " AND `uid` = {$page->filter($input['uid'])} ";
            }

            // 特定用户订单查询
            if (isset($input['aid'])) {
                $p_conf->where .= " AND `aid` = {$page->filter($input['aid'])} ";
            }

            // 按类型查询
            if (isset($input['type'])) {
                $p_conf->where .= " AND `type` = {$page->filter($input['type'])} ";
            }
        }
        #====================================

        #====================================
        # POST查询条件处理
        // 按门店查询
        if (!empty($this->input->post_get('shop_id'))) {
            $p_conf->where .= " AND `shop_id` IN ({$page->filter($this->input->post_get('shop_id'))},0)  ";
        }

        // 按状态查询
        if (!empty($this->input->post_get('status'))) {
            $_status = implode(',', explode(',', $this->input->post_get('status')));
            $p_conf->where .= " AND `status` IN ({$_status}) ";
        }

        // 按订单号查询
        if (!empty($this->input->post_get('title'))) {
            $p_conf->where .= " AND `title` LIKE '%{$this->input->post_get('title')}%' ";
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
        #====================================
        # 排序
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);

        // 查询子订单
        if ($list) {
            $shop_ids = array_unique(array_column($list, 'shop_id'));
            $WmShopDao = WmShopDao::i($input['aid']);
            $result = $WmShopDao->getEntitysByAR(['field' => 'id,shop_name', 'where_in' => ['id' => $shop_ids]], true);
            $shop_info = array_column($result, null, 'id');
            foreach ($list as $key => $row) {
                #====================================
                // 折扣方式
                $list[$key]['discount_type'] = $this->enum('discount_type', $row['discount_type']);
                // 状态
                $list[$key]['status'] = $this->enum('promotion_status', $row['status']);
                // 店铺信息
                if ($row['shop_id'] == 0) {
                    $list[$key]['shop'] = ['id' => 0, 'shop_name' => '全部门店'];
                } else {
                    $list[$key]['shop'] = isset($shop_info[$row['shop_id']]) ? $shop_info[$row['shop_id']] : ['id' => 0, 'shop_name' => '-'];
                }
                #====================================
                # 处理父订单时间戳字段
                // 创建时间
                $list[$key]['time'] = $this->time($row['time']);
                // 更新时间
                $list[$key]['start_time'] = $this->time($row['start_time']);
                // 支付时间
                $list[$key]['end_time'] = $this->time($row['end_time']);
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

<?php
use Service\Cache\WmDianwodaShopCache;
/**
 * 点我达商户后台接口
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmDianwodaCityDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaBalanceDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopReportDetailDao;
class Dianwoda_api extends wm_service_controller
{
    /**
     * 商户信息
     */
    public function info()
    {
        $wmDianwodaBalanceDao = WmDianwodaBalanceDao::i($this->s_user->aid);
        $m_wm_dianwoda_balance = $wmDianwodaBalanceDao->getOne(['aid' => $this->s_user->aid]);
        $data['balance'] = isset($m_wm_dianwoda_balance->balance) ? $m_wm_dianwoda_balance->balance : 0;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 充值记录
     */
    public function deposit_history()
    {
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->fields = '*';
        $p_conf->table = "{$wmShardDb->tables['wm_dianwoda_balance_record']}";
        $p_conf->where = " aid={$this->s_user->aid} AND type = 1 ";
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);
        $c_rules = [
            ['type' => 'time', 'field' => 'time', 'format' => 'Y-m-d H:i:s'],
        ];
        $list = convert_client_list($list, $c_rules);
        $data['total'] = $count;
        $data['rows'] = $list;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 订单报表查询
     */
    public function report_list()
    {
        $shop_id = intval($this->input->get('shop_id'));
        $start_date = trim($this->input->get('start_date'));
        $end_date = trim($this->input->get('end_date'));

        $query = '';

        if ($shop_id > 0) {
            $query .= ' AND `shop_id` = ' . $shop_id . ' ';
        }

        if ($start_date) {
            $query .= ' AND `date` >= ' . strtotime($start_date) . ' ';
        }

        if ($end_date) {
            $query .= ' AND `date` <= ' . strtotime($end_date) . ' ';
        }

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->fields = '*';
        $p_conf->table = "{$wmShardDb->tables['wm_dianwoda_shop_report']}";
        $p_conf->where = " aid={$this->s_user->aid} {$query}";
        $p_conf->order = 'id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);
        $c_rules = [
            ['type' => 'time', 'field' => 'time', 'format' => 'Y-m-d H:i:s'],
        ];
        $list = convert_client_list($list, $c_rules);

        foreach ($list as $key => $row) {
            $list[$key]['date'] = date('Y-m-d', $list[$key]['date']);
        }

        $data['total'] = $count;
        $data['rows'] = $list;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /*
     * 所属区域
     */
    public function city_all_list()
    {
        $wmDianwodaCityDao = WmDianwodaCityDao::i();
        $list = $wmDianwodaCityDao->getAllArray();
        $data['rows'] = $list;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /*
     * 添加（编辑）门店配置
     */
    public function shop_edit()
    {
        $rules = [
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required|numeric'],
            ['field' => 'city_code', 'label' => '订单所属区域编号', 'rules' => 'trim|required'],
            ['field' => 'city_name', 'label' => '订单所属区域名称', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $wmDianwodaShopDao = WmDianwodaShopDao::i($this->s_user->aid);
        $m_wm_dianwoda_shop = $wmDianwodaShopDao->getOne(['shop_id' => $fdata['shop_id']]);
        if ($m_wm_dianwoda_shop) {
            if ($wmDianwodaShopDao->update($fdata, ['id' => $m_wm_dianwoda_shop->id]) !== false) {
                //清除缓存
                (new WmDianwodaShopCache(['aid'=>$this->s_user->aid,'shop_id'=>$m_wm_dianwoda_shop->shop_id]))->delete();
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '保存失败');
            }

        } else {
            $fdata['aid'] = $this->s_user->aid;
            if ($wmDianwodaShopDao->create($fdata)) {
                $this->json_do->set_msg('保存成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '保存失败');
            }
        }
    }

    /**
     * 门店配置列表
     */
    public function shop_list()
    {
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->fields = '*';
        $p_conf->table = "{$wmShardDb->tables['wm_dianwoda_shop_report']}";

        $p_conf->fields = 'a.*,b.shop_name';
        $p_conf->table = "{$wmShardDb->tables['wm_dianwoda_shop']} a left join {$wmShardDb->tables['wm_shop']} b on a.shop_id=b.id ";
        $p_conf->where = " a.aid={$this->s_user->aid} ";
        $p_conf->order = 'a.id desc';

        $count = 0;
        $list = $page->getList($p_conf, $count);
        $c_rules = [
            ['type' => 'time', 'field' => 'time', 'format' => 'Y-m-d H:i:s'],
        ];
        $list = convert_client_list($list, $c_rules);
        $data['total'] = $count;
        $data['rows'] = $list;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    // 临时导出功能
    public function daily_statement()
    {
        $date = $this->input->get('date');
        $shop_id = $this->input->get('shop_id');

        $starttime = strtotime($date);
        $endtime = $starttime + 86400;

        $wmDianwodaShopReportDetailDao = WmDianwodaShopReportDetailDao::i($this->s_user->aid);
        $where = ['aid' => $this->s_user->aid, 'time >=' => $starttime, 'time <=' => $endtime];
        if ($shop_id) {
            $where['shop_id'] = $shop_id;
        }
        $list = $wmDianwodaShopReportDetailDao->getAllArray($where, '*', 'id desc');

        $result = [];

        $result[] = ['发单时间', '门店名称', '商家ID', '云店宝订单号', '点我达订单号', '收货人姓名', '收货人电话', '收货地址', '配送距离', '配送费', '账户余额消耗', '接单时间', '取货时间', '完成时间', '取消时间'];

        foreach ($list as $key => $row) {
            $_row = [];
            $_row['send_time'] = date('Y-m-d H:i:s', $row['send_time']);
            $_row['shop_name'] = $row['shop_name'];
            $_row['shop_id'] = $row['shop_id'];
            $_row['tid'] = $row['tid'];
            $_row['dwd_order_id'] = $row['dwd_order_id'];
            $_row['receiver_name'] = $row['receiver_name'];
            $_row['receiver_phone'] = $row['receiver_phone'];
            $_row['receiver_address'] = $row['receiver_address'];
            $_row['distance'] = $row['distance'];
            $_row['freight'] = $row['freight'];
            $_row['amount'] = $row['amount'];
            $_row['accept_time'] = $row['accept_time'] ? date('Y-m-d H:i:s', $row['accept_time']) : '-';
            $_row['take_time'] = $row['take_time'] ? date('Y-m-d H:i:s', $row['take_time']) : '-';
            $_row['finish_time'] = $row['finish_time'] ? date('Y-m-d H:i:s', $row['finish_time']) : '-';
            $_row['cancel_time'] = $row['cancel_time'] ? date('Y-m-d H:i:s', $row['cancel_time']) : '-';

            $result[] = $_row;
        }

        export_csv($result, '点我达对账单_');
    }
}

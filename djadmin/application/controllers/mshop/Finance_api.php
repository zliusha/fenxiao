<?php
/**
 * @Author: binghe
 * @Date:   2017-10-28 14:25:41
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:27:21
 */
/**
* 财务api
*/
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmWithdrawDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmMoneyRecordDao;
class Finance_api extends wm_service_controller
{
    /**
     * 提现列表
     * @return [type] [description]
     */
    public function withdraw_list()
    {
        $shop_id = intval($this->input->get('shop_id'));
        $status = $this->input->get('status');

        if(!$this->is_zongbu)
        {
            $shop_id = $this->currentShopId;
        }

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();

        $p_conf->fields='a.*,b.shop_name';
        $p_conf->table = "{$wmShardDb->tables['wm_withdraw']} a left join {$wmShardDb->tables['wm_shop']} b on a.shop_id=b.id";
        $p_conf->where = " a.aid={$this->s_user->aid} AND b.is_delete=0";
        $p_conf->order = 'a.id desc';

        if($shop_id > 0)
            $p_conf->where .= "  AND a.shop_id={$shop_id}";
        if(is_numeric($status))
            $p_conf->where .= "  AND a.status={$status}";

        $count = 0;
        $list = $page->getList($p_conf, $count);

        $v_rules = array(
          array('type' => 'time', 'field' => 'time', 'format'=>'Y-m-d H:i:s')
        );
        $list = convert_client_list($list, $v_rules);

        $data['total'] = $count;
        $data['rows'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 修改提现状态
     * @return [type] [description]
     */
    public function update_status()
    {
 
        $rules = [
          ['field' => 'id', 'label' => '提现id', 'rules' => 'trim|required|numeric'],
          ['field' => 'status', 'label' => '门店状态', 'rules' => 'trim|required|in_list[1]']
        ];

        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmWithdrawDao = WmWithdrawDao::i($this->s_user->aid);

        $m_wm_withdraw = $wmWithdrawDao->getOne(['id'=>$fdata['id'],'aid'=>$this->s_user->aid]);
        if(!$m_wm_withdraw)
            $this->json_do->set_error('004','提现记录不存在');

        if($wmWithdrawDao->update(['status'=>$fdata['status']],['id'=>$m_wm_withdraw->id])===false)
            $this->json_do->set_error('005');
        else
        {
            //减少门店金额，添加提现金额，并记录
            $wmShopDao = WmShopDao::i($this->s_user->aid);
            $wmShopDao->addWithdrawMoney($m_wm_withdraw->money,$m_wm_withdraw->aid,$m_wm_withdraw->shop_id);
            //修改门店余额和提现金额
            $this->json_do->set_msg('修改提现状态成功');
            $this->json_do->out_put();
        }
    }
    /**
     * 结算账户流水
     * @return [type] [description]
     */
    public function moeny_record_list()
    {

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf = $page->getConfig();
        $p_conf->fields='a.*,b.shop_name';
        $p_conf->table = "{$wmShardDb->tables['wm_money_record']} a left join {$wmShardDb->tables['wm_shop']} b on a.shop_id=b.id";
        $p_conf->where = " a.aid={$this->s_user->aid} AND b.is_delete=0";
        $p_conf->order = 'a.id desc';

        //0为支出,1为收入
        $pay_type=$this->input->get('pay_type');
        if(in_array($pay_type,['0','1']))
            $p_conf->where.=" AND a.pay_type={$pay_type} ";
        if($this->is_zongbu)
        {
            //门店
            $shop_id=$this->input->get('shop_id');
            if($shop_id && is_numeric($shop_id))
                $p_conf->where.=" AND a.shop_id={$shop_id}";
        }
        else
            $p_conf->where .= " AND a.shop_id={$this->currentShopId} ";


        //时间
        if (!empty($this->input->post_get('time'))) {
            $range = explode(' - ', $this->input->get('time'));
            $range = array_map('strtotime', $range);
            if (count($range) == 2) {
                $range[1] += 86400;
                $p_conf->where .= " AND a.time >= {$range[0]} AND a.time < {$range[1]} ";
            }
        }

        $count = 0;
        $list = $page->getList($p_conf, $count);

        $v_rules = array(
          array('type' => 'time', 'field' => 'time', 'format'=>'Y-m-d H:i:s')
        );
        $list = convert_client_list($list, $v_rules);

        $data['total'] = $count;
        $data['rows'] = $list;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 提现
     * @return [type] [description]
     */
    public function withdraw()
    {
        if($this->is_zongbu)
            $this->json_do->set_error('004','只有门店账号才可以提现');
        $rules=[
            ['field' => 'money', 'label' => '提现金额', 'rules' => 'trim|required|preg_key[PRICE]']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        if($fdata['money'] <=0)
            $this->json_do->set_error('004','提现金额必须0');

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $m_shop=$wmShopDao->getOne(['id'=>$this->currentShopId]);

        $wmWithdrawDao = WmWithdrawDao::i($this->s_user->aid);
        //提现中的金额
        $on_money=$wmWithdrawDao->getWithdrawMoeny(['shop_id'=>$this->currentShopId,'status'=>0]);

        if($fdata['money']>$m_shop->money  - $on_money)
            $this->json_do->set_error('004','余额不足');
        $data['shop_id']=$this->currentShopId;
        $data['aid']=$this->s_user->aid;
        $data['money']=$fdata['money'];

        
        if($wmWithdrawDao->create($data))
        {
            $this->json_do->set_msg('提现成功');
            $this->json_do->out_put();
        }
        else
        {
            $this->json_do->set_error('005','提现失败');
        }
    }

    /*
    总账号财务信息
     */
    public function info()
    {
        $shop_id = $this->input->get('shop_id');

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $wmWithdrawDao = WmWithdrawDao::i($this->s_user->aid);
        $shop_where['aid'] = $this->s_user->aid;
        $shop_where['is_delete'] = 0;
        $withdraw_where = ['aid'=>$this->s_user->aid, 'status'=>0];
        if(is_numeric($shop_id) && $shop_id>0)
        {
            $shop_where['id']=$shop_id;
            $withdraw_where['shop_id']=$shop_id;
        }
        //账户余额
        $money=$wmShopDao->getSum('money',$shop_where);

        // 获取所有有效店铺
        $shop_list = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0], 'id');
        $shop_id_arr = empty($shop_list) ? [0] : array_column($shop_list, 'id');
        $input['where_in'] = ['shop_id'=>$shop_id_arr];

        //提现中的金额
        $on_money = $wmWithdrawDao->getWithdrawMoeny($withdraw_where, $input);
        $data['on_money']=sprintf("%.2f", $on_money);
        $data['money']=$money;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 导出流水列表
     */
    public function money_record_export()
    {
        $where = "a.aid={$this->s_user->aid} ";
        //0为支出,1为收入
        $pay_type = $this->input->get('pay_type');
        if(in_array($pay_type,  ['0', '1']))
            $where .= "AND a.pay_type = {$pay_type} ";
        if($this->is_zongbu)
        {
          //门店
          $shop_id=$this->input->get('shop_id');
          if($shop_id && is_numeric($shop_id))
              $where .= " AND a.shop_id = {$shop_id}";
        }
        else
            $where .=  " AND a.shop_id={$this->currentShopId} ";


        //时间
        if (!empty($this->input->post_get('time')))
        {
            $range = explode(' - ', $this->input->get('time'));
            $range = array_map('strtotime',  $range);
            if (count($range) == 2) {
              $range[1] += 86400;
              $where .= " AND a.time >= {$range[0]} AND a.time < {$range[1]} ";
            }
        }

        $wmShardDb = WmShardDb::i($this->s_user->aid);
        //获取店铺所有商品
        $config_arr = array(
          'field' => 'a.*,b.shop_name',
          'table' => "{$wmShardDb->tables['wm_money_record']} a",
          'join' => array(
            array("{$wmShardDb->tables['wm_shop']} b", "a.shop_id=b.id", 'left'),
          ),
          'where' => $where,
        );

        //获取当前时间段所有流水列表
        $data = [];
        $wmMoneyRecordDao = WmMoneyRecordDao::i($this->s_user->aid);
        $money_record_list = $wmMoneyRecordDao->getEntitysByAR($config_arr, true);
        foreach ($money_record_list as $key => $row) {
            $_row = [];

            $_row['time'] = date('Y-m-d H:i:s', $row['time']);
            $_row['pay_type'] = $row['pay_type'] == 1 ? '收入' : '支出';
            $_row['shop_name'] = $row['shop_name'];
            $_row['desc'] = $row['des'];
            $_row['money'] = $row['money'];

            $data[] = $_row;
        }

        // 字段，一定要以$data字段顺序
        $fields = [
          'time' => '时间',
          'pay_type' => '类型',
          'shop_name' => '所属门店',
          'desc' => '描述',
          'money' => '金额'
        ];
        // 文件名尽量使用英文
        $filename = 'money_record_' . mt_rand(100, 999) . '_' . date('Y-m-d');

        ci_phpexcel::down($fields, $data, $filename);
    }
}
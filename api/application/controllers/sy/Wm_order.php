<?php
/**
 * @Author: binghe
 * @Date:   2018-05-23 14:45:01
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:51:29
 */
/**
* 外卖订单
*/
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
class Wm_order extends sy_controller
{
    
    //订单简易列表
    public function simple_list()
    {

        $rule = [
            ['field'=> 'type','label'=>'订单类型','rules'=>'trim|required|numeric|in_list[0,1,2,3,4]'],
            ['field' => 'code', 'label' => '关键词', 'rules' => 'trim']

        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        //分页数据
        $wmShardDb = WmShardDb::i($this->s_user->aid);
        $page = new PageList($wmShardDb);
        $p_conf=$page->getBtConfig();
        $p_conf->table = "{$wmShardDb->tables['wm_order']}";
        $p_conf->fields = 'id,tid,aid,shop_id,shop_name,buyer_username,total_money,total_num,receiver_name,receiver_phone,receiver_site,receiver_address,sex,pay_money,pay_time,update_time,time,status,api_status,logistics_code';
        //支付后以支付时间排排序
        $p_conf->order_by = ' pay_time desc ';
        // 查询条件
        $p_conf->where .= " AND aid = {$this->s_user->aid} AND shop_id = {$this->s_user->shop_id} ";
        //订单号查询
        if(!empty($fdata['code']))
            $p_conf->where .= " AND (logistics_code like '%{$fdata['code']}%' or receiver_phone like '%{$fdata['code']}%' )";
        // type 1 =>待商家接单,2配送中,3拒单 其它状态为全部
        switch ($fdata['type']) {
            case 1:
                $p_conf->where .= ' AND status = 2020 ';
                break;
            case 2:
                $p_conf->where .= ' AND status >= 2030 AND status <= 2060 AND status != 2035';
                break;
            case 3:
                $p_conf->where .= ' AND status = 5011 ';
                break;
            case 4:
                $p_conf->where .= ' AND status = 2035 ';
                break;
            default:
                //其它状态以下单时间排序
                $p_conf->order_by = ' time desc ';
                break;  
        }
        $count = 0;
        $rows=$page->getBtList($p_conf,$count);
        //订单状态别名转换
        $wm_order_fsm_bll = new wm_order_fsm_bll;
        foreach ($rows as $k=>$v) {
            $state = $wm_order_fsm_bll->parseState($v['status']);
            $rows[$k]['status_alias'] = $state['alias'];

            $rows[$k]['pay_time'] = date('Y-m-d H:i:s', $v['pay_time']);
            $rows[$k]['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
            $rows[$k]['time'] = date('Y-m-d H:i:s', $v['time']);
        }
        $data['total']=$count;
        $data['rows']=$rows;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    //订单详情
    public function detail()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $wmOrderExtDao = WmOrderExtDao::i($this->s_user->aid);

        $fields='id,tid,aid,shop_id,shop_name,buyer_username,total_money,total_num,receiver_name,receiver_phone,receiver_site,receiver_address,sex,pay_type,pay_money,pay_time,logistics_type,logistics_code,logistics_status,package_money,freight_money,discount_money,update_time,time,afsno,is_afs_finished,tk_money,tk_quantity,status,api_status,remark';
        $where=['aid'=>$this->s_user->aid,'tid'=>$fdata['tradeno']];
        $m_wm_order = $wmOrderDao->getOne($where,$fields);
        if(!$m_wm_order)
            $this->json_do->set_error('004','订单不存在');
        //订单状态别名转换
        $wm_order_fsm_bll = new wm_order_fsm_bll;
        $state = $wm_order_fsm_bll->parseState($m_wm_order->status);
        $m_wm_order->status_alias = $state['alias'];

        $m_wm_order->pay_time = date('Y-m-d H:i:s', $m_wm_order->pay_time);
        $m_wm_order->update_time = date('Y-m-d H:i:s', $m_wm_order->update_time);
        $m_wm_order->time = date('Y-m-d H:i:s', $m_wm_order->time);
        //自取订单信息
        $logistics_status = !empty($m_wm_order->logistics_status) ? @json_decode($m_wm_order->logistics_status, true) : null;
        if(isset($logistics_status['time']))
            $logistics_status['time'] = date('Y-m-d H:i:s', $logistics_status['time']);
        $m_wm_order->logistics_status = $logistics_status;

        //子订单信息
        $ext_fields = 'id,tid,ext_tid,order_id,goods_id,goods_title,sku_id,sku_str,discount_price,price,ori_price,pay_money,num,time';
        $m_wm_order->ext_orders=$wmOrderExtDao->getAll(['order_id'=>$m_wm_order->id,'aid'=>$this->s_user->aid],$ext_fields);
        
        $data['info']=$m_wm_order;
        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }
    //商家接单
    public function receiver()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $m_wm_shop = $wmShopDao->getOne(['aid'=>$this->s_user->aid,'id'=>$this->s_user->shop_id],'id,auto_printer');
        if(!$m_wm_shop)
            $this->json_do->set_error('门店不存在');
        $input['auto_printer']=$m_wm_shop->auto_printer;
        $input['aid']=$this->s_user->aid;
        $input['tradeno']=$fdata['tradeno'];
        $input['shop_id']=$m_wm_shop->id;
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->sellerAgreeOrder($input);
    }
    //商家拒单
    public function refuse()
    {
        $rule = [
            ['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $input['aid']=$this->s_user->aid;
        $input['tradeno']=$fdata['tradeno'];
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->sellerRefuseOrder($input);
    }
    //时间段 订单统计
    public function statistics()
    {
        $rule = [
            ['field' => 'ranges', 'label' => '时间范围', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        //验证时间格式 json格式 ['key1'=>['stime':'06:30','etime'=>'12:00'],...]
        $ranges_arr = @json_decode($fdata['ranges'],true);
        $preg = '/^[0-9]{2}:[0-9]{2}$/';
        if(empty($ranges_arr))
            $this->json_do->set_error('001','数组格式错误');
        $t_keys=['t1','t2','t3','t4','t5'];
        $today = date('Y-m-d');
        $t_arr=[];
        foreach ($ranges_arr as $k => $v) {
            if(isset($v['stime']) && isset($v['etime']) && in_array($k,$t_keys))
            {
                if(preg_match($preg, $v['stime']) && preg_match($preg, $v['etime']))
                {

                    $t_arr[$k]=['stime'=>strtotime($today." {$v['stime']}"),'etime'=> strtotime($today." {$v['etime']}")];
                }
                else
                    $this->json_do->set_error('001','时间格式错误');
            }
            else
                $this->json_do->set_error('001','数组字段错误');
        }
        $wmOrderDao = WmOrderDao::i($this->s_user->aid);

        $time=strtotime($today);
        $where = " aid = {$this->s_user->aid} AND shop_id ={$this->s_user->shop_id} AND pay_time >={$time} ";
        $info = $wmOrderDao->statistics($where,$t_arr);
        $data['info']=$info;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
        
    }

    /**
     * 确认送达
     */
    public function confirm_delivered()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单编号', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $fdata['aid'] = $this->s_user->aid;
        $fdata['tradeno'] = $fdata['tid'];

        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->sellerOrderDelivered($fdata);
    }
}
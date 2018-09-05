<?php
/**
 * @Author: binghe
 * @Date:   2017-08-24 15:27:35
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:51:30
 */
/**
* order
*/
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDadaDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDianwodaDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderFengdaDao;
class Order extends open_controller
{
    /**
     * shop_id 订单列表
     * @return [type] [description]
     */
    public function list()
    {
        $rules=[
            ['field'=>'update_time','label'=>'增量更新','rules'=>'trim|required|numeric'],
            ['field'=>'shop_id','label'=>'店铺ID','rules'=>'trim|required|numeric'],
            ['field'=>'aid','label'=>'AID','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmShopDao = WmShopDao::i($fdata['aid']);
        $m_wsc_shop = $wmShopDao->getOne(['id'=>$fdata['shop_id'], 'aid' => $fdata['aid']]);
        if(!$m_wsc_shop)
            $this->json_do->set_error('004','店铺不存在');


        $wmShardDb = WmShardDb::i($fdata['aid']);
        $page = new PageList($wmShardDb);
        $p_conf=$page->getBtConfig();
        $p_conf->table="{$wmShardDb->tables['wm_order']}";
        $p_conf->where .= " and aid={$m_wsc_shop->aid} and shop_id={$fdata['shop_id']} and update_time >={$fdata['update_time']} ";
        $p_conf->order_by='update_time asc';
        $count = 0;
        $rows=$page->getBtList($p_conf,$count);

        $wmOrderExtDao = WmOrderExtDao::i($fdata['aid']);


        //获取配送信息
        //达达运单
        $dada_infos = [];
        $wmOrderDadaDao = WmOrderDadaDao::i($fdata['aid']);
        $dada_order_list = array_find_list($rows, 'logistics_type', 2);
        $logistics_code_arr = array_unique(array_column($dada_order_list, 'logistics_code'));
        if($logistics_code_arr) $dada_infos = $wmOrderDadaDao->getEntitysByAR(['where_in' => ['client_id' => $logistics_code_arr]], true);
        $dada_infos = array_column($dada_infos, null, 'client_id');

        //点我达
        $dianwoda_infos = [];
        $wmOrderDianwodaDao = WmOrderDianwodaDao::i($fdata['aid']);
        $dianwoda_order_list = array_find_list($rows, 'logistics_type', 3);
        $logistics_code_arr = array_unique(array_column($dianwoda_order_list, 'logistics_code'));
        if($logistics_code_arr) $dianwoda_infos = $wmOrderDianwodaDao->getEntitysByAR(['where_in' => ['order_original_id' => $logistics_code_arr]], true);
        $dianwoda_infos = array_column($dianwoda_infos, null, 'order_original_id');

        //风达
        $fengda_infos = [];
        $wmOrderFengdaDao = WmOrderFengdaDao::i($fdata['aid']);
        $fengda_order_list = array_find_list($rows, 'logistics_type', 5);
        $logistics_code_arr = array_unique(array_column($fengda_order_list, 'logistics_code'));
        if($logistics_code_arr) $fengda_infos = $wmOrderFengdaDao->getEntitysByAR(['where_in' => ['tracking_no' => $logistics_code_arr]], true);
        $fengda_infos = array_column($fengda_infos, null, 'tracking_no');


        foreach ($rows as $k => $v) {
            $ext_orders=$wmOrderExtDao->getAllArray(['order_id'=>$v['id'],'aid'=>$m_wsc_shop->aid]);
            foreach ($ext_orders as $k1 => $v1) {
                $ext_orders[$k1]['goods_pic']=UPLOAD_URL.$v1['goods_pic'];
            }
            $rows[$k]['ext_orders']=$ext_orders;

            //添加配送信息 骑手姓名 手机号码 取货码
            $rows[$k]['ride_name']='';
            $rows[$k]['ride_mobile']='';
            $rows[$k]['pick_code']='';
            //达达运单
            if($v['logistics_type'] == 2 && !empty($v['logistics_code']))
            {
                $logistics_detail = isset($dada_infos[$v['logistics_code']]) ? $dada_infos[$v['logistics_code']] : null;
                $rows[$k]['ride_name'] = isset($logistics_detail['dm_name']) ? $logistics_detail['dm_name'] : '';
                $rows[$k]['ride_mobile'] = isset($logistics_detail['dm_mobile']) ? $logistics_detail['dm_mobile'] : '';
            }

            //点我达
            if($v['logistics_type'] == 3 && !empty($v['logistics_code']))
            {
                $logistics_detail = isset($dianwoda_infos[$v['logistics_code']]) ? $dianwoda_infos[$v['logistics_code']] : null;
                $rows[$k]['ride_name'] = isset($logistics_detail['rider_name']) ? $logistics_detail['rider_name'] : '';
                $rows[$k]['ride_mobile'] = isset($logistics_detail['rider_mobile']) ? $logistics_detail['rider_mobile'] : '';
            }

            //自提
            if($v['logistics_type'] == 4 && !empty($v['logistics_code']))
            {
                $rows[$k]['pick_code'] = $v['logistics_code'];
            }

            //风达
            if($v['logistics_type'] == 5 && !empty($v['logistics_code']))
            {
                $logistics_detail = isset($fengda_infos[$v['logistics_code']]) ? $fengda_infos[$v['logistics_code']] : null;
                $rows[$k]['ride_name'] = isset($logistics_detail['rider_name']) ? $logistics_detail['rider_name'] : '';
                $rows[$k]['ride_mobile'] = isset($logistics_detail['rider_phone']) ? $logistics_detail['rider_phone'] : '';
            }

        }
        // $data['rows']=$rows;
        $this->json_do->set_data($rows);
        $this->json_do->out_put();
    }
    //订单简易列表
    public function simple_list()
    {

        $rule = [
            ['field' => 'aid','label'=>'店铺所数id','rules'=>'trim|required|numeric']
            ,['field' => 'shop_id','label'=>'店铺id','rules'=>'trim|required|numeric']
            ,['field'=> 'type','label'=>'订单类型','rules'=>'trim|required|numeric|in_list[0,1,2,3]']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        //分页数据
        $wmShardDb = WmShardDb::i($fdata['aid']);
        $page = new PageList($wmShardDb);
        $p_conf=$page->getBtConfig();
        $p_conf->table="{$wmShardDb->tables['wm_order']}";
        $p_conf->fields = 'id,tid,aid,shop_id,shop_name,buyer_username,total_money,total_num,receiver_name,receiver_phone,receiver_site,receiver_address,sex,pay_money,pay_time,update_time,time,status,api_status';
        //支付后以支付时间排排序
        $p_conf->order_by = ' pay_time desc ';
        // 查询条件
        $p_conf->where .= " AND aid = {$fdata['aid']} AND shop_id = {$fdata['shop_id']} ";
        // type 1 =>待商家接单,2配送中,3拒单 其它状态为全部
        switch ($fdata['type']) {
            case 1:
                $p_conf->where .= ' AND status = 2020 ';
                break;
            case 2:
                $p_conf->where .= ' AND status >= 2040 AND status <= 2060 ';
                break;
            case 3:
                $p_conf->where .= ' AND status = 5011 ';
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
            ['field' => 'aid','label'=>'订单所属id','rules'=>'trim|required|numeric']
            ,['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);


        $wmOrderDao = WmOrderDao::i($fdata['aid']);
        $fields='id,tid,aid,shop_id,shop_name,buyer_username,total_money,total_num,receiver_name,receiver_phone,receiver_site,receiver_address,sex,pay_type,pay_money,pay_time,logistics_code,logistics_type,package_money,freight_money,discount_money,update_time,time,afsno,is_afs_finished,tk_money,tk_quantity,status,api_status,remark';

        $where=['aid'=>$fdata['aid'],'tid'=>$fdata['tradeno']];
        $m_wm_order = $wmOrderDao->getOne($where,$fields);
        if(!$m_wm_order)
            $this->json_do->set_error('004','订单不存在');
        //订单状态别名转换
        $wm_order_fsm_bll = new wm_order_fsm_bll;
        $state = $wm_order_fsm_bll->parseState($m_wm_order->status);
        $m_wm_order->status_alias = $state['alias'];
        //子订单信息
        $wmOrderExtDao = WmOrderExtDao::i($fdata['aid']);
        $ext_fields = 'id,tid,ext_tid,order_id,goods_id,goods_title,sku_id,sku_str,discount_price,price,ori_price,pay_money,num,time';

        $m_wm_order->ext_orders=$wmOrderExtDao->getAll(['order_id'=>$m_wm_order->id],$ext_fields);

        //添加配送信息 骑手姓名 手机号码 取货码
        $m_wm_order->ride_name='';
        $m_wm_order->ride_mobile='';
        $m_wm_order->pick_code='';
        //达达运单
        if($m_wm_order->logistics_type == 2 && !empty($m_wm_order->logistics_code))
        {
            $wmOrderDadaDao = WmOrderDadaDao::i($fdata['aid']);
            $m_order_dada =  $wmOrderDadaDao->getOne(['aid' => $fdata['aid'], 'client_id' => $m_wm_order->logistics_code]);
            $m_wm_order->ride_name = isset($m_order_dada->dm_name) ? $m_order_dada->dm_name : '';
            $m_wm_order->ride_mobile = isset($m_order_dada->dm_mobile) ? $m_order_dada->dm_mobile : '';
        }

        //点我达
        if($m_wm_order->logistics_type == 3 && !empty($m_wm_order->logistics_code))
        {
            $wmOrderDianwodaDao = WmOrderDianwodaDao::i($fdata['aid']);
            $m_order_dianwoda = $wmOrderDianwodaDao->getOne(['aid' => $fdata['aid'], 'order_original_id' => $m_wm_order->logistics_code]);
            $m_wm_order->ride_name = isset($m_order_dianwoda->rider_name) ? $m_order_dianwoda->rider_name : '';
            $m_wm_order->ride_mobile = isset($m_order_dianwoda->rider_mobile) ? $m_order_dianwoda->rider_mobile : '';
        }

        //自提
        if($m_wm_order->logistics_type == 4 && !empty($m_wm_order->logistics_code))
        {
            $m_wm_order->pick_code = $m_wm_order->logistics_code;
        }

        //风达
        if($m_wm_order->logistics_type == 5 && !empty($m_wm_order->logistics_code))
        {
            $wmOrderFengdaDao = WmOrderFengdaDao::i($fdata['aid']);
            $m_order_fengda = $wmOrderFengdaDao->getOne(['aid' => $fdata['aid'], 'tracking_no' => $m_wm_order->logistics_code]);
            $m_wm_order->ride_name = isset($m_order_fengda->rider_name) ? $m_order_fengda->rider_name : '';
            $m_wm_order->ride_mobile = isset($m_order_fengda->rider_phone) ? $m_order_fengda->rider_phone : '';
        }

        
        $data['info']=$m_wm_order;
        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }
    //商家接单
    public function receiver()
    {
        $rule = [
            ['field' => 'aid','label'=>'订单所属id','rules'=>'trim|required|numeric']
            ,['field' => 'shop_id','label'=>'店铺id','rules'=>'trim|required|numeric']
            ,['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $wmShopDao = WmShopDao::i($fdata['aid']);
        $m_wm_shop = $wmShopDao->getOne(['aid'=>$fdata['aid'],'id'=>$fdata['shop_id']],'id,auto_printer');
        if(!$m_wm_shop)
            $this->json_do->set_error('门店不存在');
        $input['auto_printer']=$m_wm_shop->auto_printer;
        $input['aid']=$fdata['aid'];
        $input['tradeno']=$fdata['tradeno'];
        $input['shop_id']=$m_wm_shop->id;
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->sellerAgreeOrder($input);
    }
    //商家拒单
    public function refuse()
    {
        $rule = [
            ['field' => 'aid','label'=>'订单所属id','rules'=>'trim|required|numeric']
            ,['field' => 'shop_id','label'=>'店铺id','rules'=>'trim|required|numeric']
            ,['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $input['aid']=$fdata['aid'];
        $input['tradeno']=$fdata['tradeno'];
        $wm_order_event_bll = new wm_order_event_bll();
        $wm_order_event_bll->sellerRefuseOrder($input);
    }
    //时间段 订单统计
    public function statistics()
    {
        $rule = [
            ['field' => 'aid','label'=>'店铺所属id','rules'=>'trim|required|numeric']
            ,['field' => 'shop_id','label'=>'店铺id','rules'=>'trim|required|numeric']
            ,['field' => 'ranges', 'label' => '时间范围', 'rules' => 'trim|required']
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
        $time=strtotime($today);
        $where = " aid = {$fdata['aid']} AND shop_id ={$fdata['shop_id']} AND pay_time >={$time} ";
        $wmOrderDao = WmOrderDao::i($fdata['aid']);
        $info = $wmOrderDao->statistics($where,$t_arr);
        $data['info']=$info;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
        
    }

}
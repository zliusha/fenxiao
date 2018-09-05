<?php
/**
 * @Author: binghe
 * @Date:   2017-09-22 10:11:22
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 15:51:29
 */
/**
* 售后
*/
use Service\DbFrame\DataBase\WmShardDb;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\WmShardDbModels\WmAfsDao;
class Afs extends open_controller
{
    //售后增量查询
    public function list()
    {
        $rule = [
            ['field' => 'aid','label'=>'订单所属id','rules'=>'trim|required|numeric']
            ,['field' => 'shop_id','label'=>'门店id','rules'=>'trim|required|numeric']
            ,['field' => 'update_time', 'label' => '更新时间', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmShardDb = WmShardDb::i($fdata['aid']);
        $page = new PageList($wmShardDb);
        $p_conf=$page->getBtConfig();
        $p_conf->fields='id,afsno,type,shop_id,aid,tid,order_money,pay_money,freight_money,reason,remark,refuse_reason,tk_money,final_tk_money,tk_quantity,final_tk_quantity,afs_detail,status,update_time,time';
        $p_conf->table="{$wmShardDb->tables['wm_afs']}";
        $p_conf->where .= " AND aid={$fdata['aid']} AND shop_id={$fdata['shop_id']} AND update_time >={$fdata['update_time']} ";
        $p_conf->order_by='update_time asc';
        $count = 0;
        $rows=$page->getBtList($p_conf,$count);

        $data['rows'] = $rows;
        $data['count'] = $count;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 售后单个订单查询
     * @return [type] [description]
     */
    public function detail()
    {
        $rule = [
            ['field' => 'aid','label'=>'订单所属id','rules'=>'trim|required|numeric']
            ,['field' => 'shop_id','label'=>'门店id','rules'=>'trim|required|numeric']
            ,['field' => 'tradeno', 'label' => '订单编号', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $fields = 'id,afsno,type,shop_id,aid,tid,order_money,pay_money,freight_money,reason,remark,refuse_reason,tk_money,final_tk_money,tk_quantity,final_tk_quantity,afs_detail,status,update_time,time';
        $where = " aid={$fdata['aid']} AND shop_id={$fdata['shop_id']} AND tid={$fdata['tradeno']}";

        $wmAfsDao = WmAfsDao::i($fdata['aid']);
        $rows = $wmAfsDao->getAllArray($where,$fields);
        $data['rows'] = $rows;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
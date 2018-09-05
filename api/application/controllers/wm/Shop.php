<?php
/**
 * @Author: binghe
 * @Date:   2017-08-23 19:14:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:47
 */
/**
* 门店
*/
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
class Shop extends open_controller
{
    
    /**
     * 获取所有门店列表
     * @return [type] [description]
     */
    public function list()
    {
        $rules=[
            ['field'=>'visit_id','label'=>'visit_id','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        //自动将aid转换为visit_id
        $aid=$this->convert_visit_id($fdata['visit_id']);

        $wmShopDao = WmShopDao::i($aid);
        $rows=$wmShopDao->getAllArray(['aid'=>$aid,'is_delete'=>0],'id,aid,shop_name,shop_logo,contact,shop_state,shop_district,region,shop_address');
        foreach ($rows as $k => $v) {
            $rows[$k]['shop_logo']=UPLOAD_URL.$v['shop_logo'];
        }
        $data['rows']=$rows;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 门店信息
     * @return [type] [description]
     */
    public function info()
    {
        $rules = [
            ['field'=>'aid','label'=>'门店所属id','rules'=>'trim|required|numeric']
            ,['field'=>'shop_id','label'=>'门店id','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmShopDao = WmShopDao::i($fdata['aid']);
        $where=['aid'=>$fdata['aid'],'id'=>$fdata['shop_id'],'is_delete'=>0];
        $fields='id,shop_name,auto_receiver';
        $m_wm_shop = $wmShopDao->getOne($where,$fields);
        if(!$m_wm_shop)
            $this->json_do->set_error('004','门店不存在');
        $data['info']=$m_wm_shop;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 修改自动接单状态
     * @return [type] [description]
     */
    public function update_autoreceive()
    {
        $rules = [
            ['field'=>'aid','label'=>'门店所属id','rules'=>'trim|required|numeric']
            ,['field'=>'shop_id','label'=>'门店id','rules'=>'trim|required|numeric']
            ,['field' => 'auto_receiver', 'label' => '自动接单', 'rules' => 'trim|required|in_list[0,1]']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmShopDao = WmShopDao::i($fdata['aid']);
        if($wmShopDao->update(['auto_receiver'=>(int)$fdata['auto_receiver']],['aid'=>$fdata['aid'],'id'=>$fdata['shop_id']])!==false)
        {
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005');
    }
}
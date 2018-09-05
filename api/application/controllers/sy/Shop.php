<?php
/**
 * @Author: binghe
 * @Date:   2018-05-24 15:30:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:57:46
 */
/**
* 门店
*/
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Shop extends sy_controller
{
    
    /**
     * 门店信息
     * @return [type] [description]
     */
    public function info()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);

        $where=['aid'=>$this->s_user->aid,'id'=>$this->s_user->shop_id,'is_delete'=>0];
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
            ['field' => 'auto_receiver', 'label' => '自动接单', 'rules' => 'trim|required|in_list[0,1]']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $where = ['aid'=>$this->s_user->aid,'id'=>$this->s_user->shop_id];
        if($wmShopDao->update(['auto_receiver'=>(int)$fdata['auto_receiver']],$where)!==false)
        {
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005');
    }
}
<?php
/**
 * @Author: binghe
 * @Date:   2018-04-03 14:20:52
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-03 14:54:21
 */
/**
* 供第一次接入调试使用
*/
class Test extends auth_controller
{
    
    public function index()
    {
        $rules = [['field'=>'name','label'=>'名称','rules'=>'trim|required']];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $this->json_do->set_msg($fdata['name'].' connection success');
        $this->json_do->set_data($fdata);
        $this->json_do->out_put();
    }
}
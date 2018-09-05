<?php

/**
 * @Author: binghe
 * @Date:   2018-08-07 17:00:56
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-07 17:53:07
 */
use Service\Bll\Hcity\Xcx\XcxFormIdBll;
/**
 * 跟踪
 */
class Trace extends xhcity_controller
{
	/**
	 * 收集form_id
	 */
	public function add_from_id()
	{
		$rules = [
            ['field' => 'form_id', 'label' => '表单id', 'rules' => 'trim|required']
        ];

        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $params=[
        	'form_id'=>$fdata['form_id'],
        	'open_id'=>$this->s_user->openid
        ];
        //做了加锁处理，防止重复收集
        try {
        	(new XcxFormIdBll())->add($params);
        } catch (Exception $e) {
        	log_message('error',__METHOD__.'-'.$e->getMessage());
        }
        
        $this->json_do->out_put();
	}

}
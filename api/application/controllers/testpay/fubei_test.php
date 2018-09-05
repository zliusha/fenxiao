<?php

/**
 * @Author: binghe
 * @Date:   2018-06-07 09:28:20
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-03 21:06:26
 */
/**
 * 付呗
 */
class fubei_test extends base_controller
{
	
	public function swip()
	{
		$code = $this->input->get('code');
		if(empty($code))
			exit('no code');
		$config['app_id']='20180411103115428001';
		$config['app_secret']='996329d8225e87a6495419edf9291e3d';
		$config['store_id']=330954;

		//支付方式[微信1/支付宝2]
		$input['type'] = 1;
		$input['merchant_order_sn'] = 'T'.time().rand(1000,9999);
		$input['auth_code'] = $code;
		$input['total_fee'] = 0.01;
		$fubei_sdk = new fubei_sdk($config);
		$res = $fubei_sdk->swip($input);
		var_dump($res);
	}
	public function query()
	{
		$config['app_id']='20180411103115428001';
		$config['app_secret']='996329d8225e87a6495419edf9291e3d';
		$config['store_id']=330954;

		//支付方式[微信1/支付宝2]
		$input['merchant_order_sn'] = 'T15283386176692';
		$fubei_sdk = new fubei_sdk($config);
		$res = $fubei_sdk->query($input);
		var_dump($res);
	}
}
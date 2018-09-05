<?php

/**
 * @Author: binghe
 * @Date:   2018-07-24 11:04:39
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-07 11:43:30
 */
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainSaasDao;
use Service\Enum\SaasEnum;
use Service\Bll\Main\MainSaasAccountBll;
/**
 * Saas
 */
class Saas extends dj_controller
{
	//授权,带免费，试用　　可直接跳转
	public function auth()
	{
		$rules = [
            ['field' => 'saas_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $saasId = $fdata['saas_id'];

		$mainSaasDao = MainSaasDao::i();
		$mMainSaas = $mainSaasDao->getOne(['saas_id'=>$saasId]);
		if(!$mMainSaas)
			$this->json_do->set_error('001','服务不存在');

		//判断是否开通了服务，未开通则开通试用版
		$mainSaasAccountDao = MainSaasAccountDao::i();
		$mMianSaasAccount = $mainSaasAccountDao->getOne(['aid'=>$this->s_user->aid,'saas_id'=>$mMainSaas->saas_id]);
		$companySaasInfo = null;
		//未开通服务器，则免费开通
		if(!$mMianSaasAccount)
		{
			if($mMainSaas->is_free)
			{

				$data['aid'] = $this->s_user->aid;
				$data['saas_id'] = $mMainSaas->saas_id;
				$time = time();
				$data['time'] = $time;
				$data['update_time'] = $time;
				if($id =(new MainSaasAccountBll())->register($data))
				{
					$companySaasInfo = [];
					$companySaasInfo['id'] = $id;
					$companySaasInfo['saas_item_id'] = null;
					$companySaasInfo['saas_item_name'] = null;
					$companySaasInfo['expire_time'] = null;
					
				}
				else
					$this->json_do->set_error('005','自动开通免费服务失败');
			}
			elseif($mMainSaas->is_has_trial)
			{
				//免费版本，直接进入，目前由具体服务控制
				
			}
			else
				$this->json_do->set_error('004','此服务需要先支付开通');
			
		}
		else
		{
			$companySaasInfo = [];
			$companySaasInfo['id'] = $mMianSaasAccount->id;
			$companySaasInfo['saas_item_id'] = $mMianSaasAccount->saas_item_id;
			$companySaasInfo['saas_item_name'] = $mMianSaasAccount->saas_item_name;
			$companySaasInfo['expire_time'] = $mMianSaasAccount->expire_time;
		}
		$info['saas_id'] = $mMainSaas->saas_id;
		$info['saas_name'] = $mMainSaas->saas_name;
		$info['is_free'] = $mMainSaas->is_free;
		$info['is_has_trial'] = $mMainSaas->is_has_trial;
		$info['redirect_url'] = $mMainSaas->redirect_url;
		$info['company_saas_info'] = $companySaasInfo;
		$data['info'] = $info;
		$this->json_do->set_data($data);
		$this->json_do->set_msg('授权成功');
		$this->json_do->out_put();
	}
}
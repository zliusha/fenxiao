<?php
/**
 * @Author: binghe
 * @Date:   2017-12-01 13:44:41
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-20 15:49:47
 */
/**
* 公司信息
*/
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\Enum\SaasEnum;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmServiceDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmServiceAccountDao;
class Company extends open_controller
{
    
    /**
     * 版本信息
     * @return [type] [description]
     */
    public function service_info()
    {
        $rules=[
        ['field'=>'visit_id','label'=>'visit_id','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);
        $aid = $this->convert_visit_id($fdata['visit_id']);
        $mainSaasAccountDao = MainSaasAccountDao::i();
        $mMainSaasAccount = $mainSaasAccountDao->getOne(['aid'=>$aid,'saas_id'=>SaasEnum::YD]);
        $info = null;
        if($mMainSaasAccount)
        {
            $info = new stdClass();
            $info->visit_id = $fdata['visit_id'];
            $info->service_id = $mMainSaasAccount->saas_item_id;
            $info->service_day_limit = $mMainSaasAccount->expire_time;
            $info->service_type_name = $mMainSaasAccount->saas_item_name;
            $info->is_trial = 0 ;//新版永为false
            $info->shop_limit = 1;//默认等于１
            $mWmService = WmServiceDao::i()->getOne(['item_id'=>$mMainSaasAccount->saas_item_id]);
            if($mWmService)
                $info->shop_limit = $mWmService->shop_limit;
            $mWmServiceAccount = WmServiceAccountDao::i()->getOne(['aid'=>$input['aid']]);
            if($mWmServiceAccount)
                $info->shop_limit = $mWmServiceAccount->shop_limit;
        }
        $data['info']=$info;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 获取aid账户信息 (目前风达使用)
     */
    public function account_info()
    {
        $rules=[
            ['field'=>'aid','label'=>'aid','rules'=>'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rules);
        $fdata=$this->form_data($rules);

        $mainCompanyAccountDao = MainCompanyAccountDao::i();;
        $m_main_company_account = $mainCompanyAccountDao->getOne(['aid' => $fdata['aid'], 'shop_id' => 0]);
        if(!$m_main_company_account)
            $this->json_do->set_error('004','暂无信息');

        $m_main_company_account->img = conver_picurl($m_main_company_account->img);
        $m_main_company_account->mobile = '';
        $m_main_company_account->address = '';
        try{
            $erp_sdk = new erp_sdk();
            $params[] = (int)$m_main_company_account->visit_id;
            $params[] = (int)$m_main_company_account->user_id;
            $res = $erp_sdk->getUserById($params);
            $m_main_company_account->mobile = $res['phone'];
        }catch(Exception $e) {
            log_message('error', __METHOD__.':'.$e->getMessage());
        }

        $data['info']=$m_main_company_account;
        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }
}
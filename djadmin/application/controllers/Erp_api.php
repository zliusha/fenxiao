<?php
/**
 * @Author: binghe
 * @Date:   2017-11-27 13:24:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-03 17:21:18
 */
use Service\Cache\ErpLoginTokenCache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
/**
* erp_api
*/
class Erp_api extends base_controller
{
    
    /**
     * 获取用户erp token
     * @return [type] [description]
     */
    public function get_login_url()
    {
        $erpGetTokenCache = new ErpLoginTokenCache(['visit_id'=>$this->s_user->visit_id,'user_id'=>$this->s_user->user_id]);
        $token = $erpGetTokenCache->getDataASNX();
        $inc = &inc_config('erp');
        $data['url']=$inc['login_url'].'?token='.$token.'&redirectUri=/oanew/#/new/power/1';
        $this->json_do->set_data($data);
        $this->json_do->out_put();
        
    }
    /**
     * 获取全部云店宝员工,并自动过滤已绑定店铺
     * @return [type] [description]
     */
    public function get_nobind_users()
    {
        try {
            $erp_sdk = new erp_sdk;
            $params[]=$this->s_user->visit_id;
            $res=$erp_sdk->getUsers($params);
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $mca_arr=$mainCompanyAccountDao->getAllArray(['visit_id'=>$this->s_user->visit_id,'is_admin <>'=>1],'id,user_id');
        $last_arr =[];
        foreach ($res as $item) {
           $one=array_find($mca_arr,'user_id',$item['user_id']);
           if(!$one)
            array_push($last_arr, $item);
        }
        $data['users']=$last_arr;
        $this->json_do->set_data($last_arr);
        $this->json_do->out_put();
    }
    
    
}
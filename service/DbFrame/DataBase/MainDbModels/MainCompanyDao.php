<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 16:29:03
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-20 15:12:08
 */
namespace Service\DbFrame\DataBase\MainDbModels;
/**
 * 公司
 */
class MainCompanyDao extends BaseDao
{
	/**
    * 注册公司
    * 兼容微信绑定
    * res_info 必须 值 ['visit_id'=>123,'user_id':123,'user_nature'=>1,'user_name'=>'user_name']
    * res_info['user_nature'] 1总账号,2子账号
    * 
    * wx_info 可选 值 ['open_id','union_id']存在则关联微信登录
    * @return [type] [description]
    */
   public function register(array $params)
   {
        if(empty($params['res_info']))
            return false;
        $res_info=$params['res_info'];
        //只有主账号才可以注册公司
        if($res_info['user_nature'] != 1)
            return false;
        $this->db->trans_start();
        
        //1.注册公司
        $data['visit_id']=$res_info['visit_id'];
        $aid=$this->create($data);
        //2.注册账号
        $mainCompanyAccountDao = MainCompanyAccountDao::i($this->shardNode);
        $a_data['user_id']=$res_info['user_id'];
        $a_data['visit_id']=$res_info['visit_id'];
        $a_data['aid']=$aid;
        $a_data['is_admin']=$res_info['user_nature'];
        $a_data['username']=$res_info['user_name'];
        $account_id = $mainCompanyAccountDao->create($a_data);
        //3.如果传入微信信息，则自动关联
        if(isset($params['wx_info']))
        {   
        	$mainCompanyAccountWxbindDao = MainCompanyAccountWxbindDao::i($this->shardNode);
            $wx_info = $params['wx_info'];
            $b_data['open_id']=$wx_info['open_id'];
            $b_data['union_id']=$wx_info['union_id'];
            $b_data['type']='site';
            $b_data['aid']=$aid;
            $b_data['account_id'] = $account_id;
            $wxbind_id=$mainCompanyAccountWxbindDao->create($b_data);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            return false;
        }
        else
            return true;

   }
	
}
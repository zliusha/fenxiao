<?php
/**
 * @Author: binghe
 * @Date:   2017-08-07 17:28:20
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-02 15:12:52
 */
/**
* passport api
*/
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
class Passport extends site_controller
{
    //注册
    public function register()
    {
        $rule=[
            ['field'=>'mobile','label'=>'账号','rules'=>'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|preg_key[PWD]'],
            ['field' => 'repassword', 'label' => '确认密码', 'rules' => 'trim|required|preg_key[PWD]|matches[password]'],
            ['field'=>'code','label'=>'手机验证码','rules'=>'trim|required|numeric|min_length[4]|max_length[6]']
        ];
        $this->check->check_form($rule);
        $fdata=$this->form_data($rule);
        //开放140注册
        if(substr($fdata['mobile'], 0,3)=='140')
        {
            if('123456'!=$fdata['code'])
                $this->json_do->set_error('004','验证码错误');
        }
        else
        {
            //验证手机号验证码
            $mainMobileCodeDao = MainMobileCodeDao::i();
            $m_main_mobile_code=$mainMobileCodeDao->getRegCode($fdata['mobile']);
            if(!$m_main_mobile_code || $m_main_mobile_code->code!=$fdata['code'])
                $this->json_do->set_error('004','验证码错误');
        }

        try {
            //erp注册公司，并生成主账号
            $erp_sdk = new erp_sdk();
            $params[]=$fdata['mobile'];
            $params[]=$fdata['password'];
            $ip=get_ip();
            $params[]=['ip'=>$ip];
            $res=$erp_sdk->register($params);
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
        $r_params['res_info']=$res;
        //处理自己业务
        //1.注册公司，添加主账号，开通试用
        $mainCompanyDao = MainCompanyDao::i();
        if($mainCompanyDao->register($r_params))
        {
            //重新获取用户信息,并自动登录
            $mainCompanyAccountDao = MainCompanyAccountDao::i();
            $m_main_company_account = $mainCompanyAccountDao->getOne(['visit_id'=>$res['visit_id'],'user_id'=>$res['user_id']]);
            if(!$m_main_company_account)
                    $this->json_do->set_error('004','账号不存在,请重新登录');

            $data['user_id']=$res['user_id'];
            $data['visit_id']=$res['visit_id'];
            $this->json_do->set_data($data);
            $this->json_do->set_msg('注册成功');
            $this->json_do->out_put(); 
        }
        else
            $this->json_do->set_error('005','注册失败,请重新注册');
        
    }
}
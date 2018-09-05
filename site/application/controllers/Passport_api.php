<?php
/**
 * @Author: binghe
 * @Date:   2017-08-07 17:28:20
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-20 10:27:52
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountWxbindDao;
use Service\DbFrame\DataBase\MainDbModels\MainKvDao;
use Service\Bll\Main\MainCompanyAccountBll;
use Service\Bll\Basic\CaptchaBll;
/**
* passport api
*/
class Passport_api extends base_api_controller
{
    
    //找回密码
    public function findpassword()
    {
        //验证
        $rule = [
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'code', 'label' => '手机验证码', 'rules' => 'trim|required|numeric|min_length[6]|max_length[6]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|preg_key[PWD]'],
            ['field' => 'repassword', 'label' => '确认密码', 'rules' => 'trim|required|preg_key[PWD]|matches[password]']
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        //验证手机验证码
         $mainMobileCodeDao = MainMobileCodeDao::i();
        $m_main_mobile_code = $mainMobileCodeDao->getUpwdCode($fdata['mobile']);
        if (!$m_main_mobile_code || $m_main_mobile_code->code != $fdata['code']) 
            $this->json_do->set_error('004','手机验证码不正确');

        try {
            $erp_sdk = new erp_sdk;
            $params[]=$fdata['mobile'];
            $params[]=$fdata['password'];
            $info = $erp_sdk->updatePwdByPhone($params);
            $this->json_do->set_msg('修改密码成功');
            $this->json_do->out_put();

        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
    }
    
    /***********************以下为新方法**********************/
    /**
     * 登录 获取token
     */
    public function login()
    {
        //验证
        $rule = [
            ['field' => 'account', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|min_length[6]|max_length[25]'],
            ['field'=>'phrase','label'=>'验证码','rules'=>'trim|required'],
            ['field'=>'token','label'=>'token','rules'=>'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        //验证码
        $equal = (new CaptchaBll())->assertEqual($fdata['token'],$fdata['phrase']);
        if(!$equal)
            $this->json_do->set_error('004','验证码错误');

        $sUser = (new MainCompanyAccountBll())->getSaasSUser($fdata['account'],$fdata['password']);
        //cookie保存登录信息保存7天
        $c_value=$this->encryption->encrypt(serialize($sUser));
        set_cookie('saas_user',$c_value,3600*24*7);
        $this->json_do->set_msg('登录成功');
        $this->json_do->out_put();
    }
    /**
     * 注册，不会自动登录
     * @return [type] [description]
     */
    public function register()
    {
        $rule=[
            ['field'=>'mobile','label'=>'账号','rules'=>'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|preg_key[PWD]'],
            ['field' => 'repassword', 'label' => '确认密码', 'rules' => 'trim|required|preg_key[PWD]|matches[password]'],
            ['field'=>'code','label'=>'手机验证码','rules'=>'required|numeric']
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
        //1.注册公司，添加主账号
        $mainCompanyDao = MainCompanyDao::i();
        if($mainCompanyDao->register($r_params))
        {
            //重新获取用户信息,并自动登录
            $mainCompanyAccountDao = MainCompanyAccountDao::i();
            $m_main_company_account = $mainCompanyAccountDao->getOne(['visit_id'=>$res['visit_id'],'user_id'=>$res['user_id']]);
            if(!$m_main_company_account)
                    $this->json_do->set_error('004','账号不存在,请重新登录');
            $main_company_account_bll = new main_company_account_bll;
            $sUser = (new MainCompanyAccountBll())->getSaasSUserByModel($m_main_company_account);
            $c_value=$this->encryption->encrypt(serialize($sUser));
            set_cookie('saas_user',$c_value,3600*24*7);

            $data['user_id']=$res['user_id'];
            $data['visit_id']=$res['visit_id'];
            $this->json_do->set_data($data);
            $this->json_do->set_msg('注册成功');
            $this->json_do->out_put(); 
        }
        else
            $this->json_do->set_error('005','注册失败,请重新注册');
    }
    /**
     * 微信绑定
     * @return [type] [description]
     */
    public function wx_bind()
    {
        //验证
        $rules = [
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'code', 'label' => '手机验证码', 'rules' => 'trim|required|numeric|min_length[6]|max_length[6]'],
            ['field' => 'open_id', 'label' => '微信open_id', 'rules' => 'trim|required'],
            ['field' => 'union_id', 'label' => '微信union_id', 'rules' => 'trim|required'],
            ['field' => 'sign', 'label' => '签名', 'rules' => 'trim|required']
        ];
        $this->check->check_form($rules);
        $fdata=$this->form_data($rules);

        $sign=md5($fdata['open_id'].$fdata['union_id'].SECRET_KEY);
        if($sign!=$fdata['sign'])
            $this->json_do->set_error('004','签名验证错误');

        //验证手机验证码
        $mainMobileCodeDao = MainMobileCodeDao::i();
        $m_main_mobile_code = $mainMobileCodeDao->getNormalCode($fdata['mobile']);
        if(!$m_main_mobile_code || $m_main_mobile_code->code!=$fdata['code'])
            $this->json_do->set_error('004','验证码错误');

        //二次验证open_id
        $mainCompanyAccountWxbindDao = MainCompanyAccountWxbindDao::i();
        $m_main_company_account_wxbind = $mainCompanyAccountWxbindDao->getOne(['open_id'=>$fdata['open_id'],'type'=>'site']);
        if($m_main_company_account_wxbind)
            $this->json_do->set_error('004','当前微信号已绑定账号');

        //erp根据手机号得到用户信息
        $erp_sdk = new erp_sdk;
        try {
            $params[]=$fdata['mobile'];
            $res = $erp_sdk->getUserByPhone($params);
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $mainCompanyAccountBll = new MainCompanyAccountBll();
        if($res)
        {   
            //判断是否主账号
            $m_main_company_account = $mainCompanyAccountDao->getOne(['user_id'=>$res['user_id'],'visit_id'=>$res['visit_id']]);
            //存在账号直接登录
            if($m_main_company_account)
            {
                //1.关联账号
                $data['open_id']=$fdata['open_id'];
                $data['union_id']=$fdata['union_id'];
                $data['type']='site';
                $data['aid']=$m_main_company_account->aid;
                $data['account_id']=$m_main_company_account->id;
                $mainCompanyAccountWxbindDao->create($data);
                //2.登陆
                $sUser = $mainCompanyAccountBll->getSaasSUser($m_main_company_account);
                //cookie保存登录信息,保存7天
                $c_value=$this->encryption->encrypt(serialize($sUser));
                set_cookie('saas_user',$c_value,3600*24*7);

                $this->json_do->set_msg('关联账号并登录成功');
                $this->json_do->out_put();
            }
            else
            {
                //子账号不能自动开通
                if($res['user_nature'] != 1)
                    $this->json_do->set_error('004','员工账号未关联云店宝,请先在主账号开通');
                //1.注册公司，添加主账号，开通试用
                $r_params['res_info']=$res;
                $r_params['wx_info']=['open_id'=>$fdata['open_id'],'union_id'=>$fdata['union_id']];
                $mainCompanyDao = MainCompanyDao::i();
                if(!$mainCompanyDao->register($r_params))
                    $this->json_do->set_error('005','微信自动注册失败,请重新注册');
                $m_main_company_account = $mainCompanyAccountDao->getOne(['visit_id'=>$res['visit_id'],'user_id'=>$res['user_id']]);
                if(!$m_main_company_account)
                    $this->json_do->set_error('004','账号不存在,请重新登录');
                //cookie保存登录信息保存7天
                $sUser = $mainCompanyAccountBll->getSaasSUser($m_main_company_account);
                $c_value=$this->encryption->encrypt(serialize($sUser));
                set_cookie('saas_user',$c_value,3600*24*7);
                
                $this->json_do->set_msg('登录成功');
                $this->json_do->out_put();
            }
            
        }
        else //注册账号
        {
            //1.erp注册账号
            $init_pwd=rand(100000,999999);
            try {
                $params1[]=$fdata['mobile'];
                $params1[]=$init_pwd;
                $ip=get_ip();
                $params1[]=['ip'=>$ip];
                $reg_res=$erp_sdk->register($params1);
            } catch (Exception $e) {
                $this->json_do->set_error('005',$e->getMessage());
            }
            //处理自己业务
            //1.注册公司，添加主账号，开通试用
            $r_params['res_info']=$reg_res;
            $r_params['wx_info']=['open_id'=>$fdata['open_id'],'union_id'=>$fdata['union_id']];
            $mainCompanyDao = MainCompanyDao::i();
            if(!$mainCompanyDao->register($r_params))
                $this->json_do->set_error('005','微信登录注册失败,请重新登录');
  
            //3.登录
            $m_main_company_account_new = $mainCompanyAccountDao->getOne(['visit_id'=>$reg_res['visit_id'],'user_id'=>$reg_res['user_id']]);
            if(!$m_main_company_account)
                $this->json_do->set_error('005','微信登录账号不存在,请重新登录');

            //3.1cookie保存登录信息，保存7天
            $sUser = $mainCompanyAccountBll->getSaasSUser($m_main_company_account);
            $c_value=$this->encryption->encrypt(serialize($sUser));
            set_cookie('saas_user',$c_value,3600*24*7);

            //4.短信发送初始密码
            $mainKvDao = MainKvDao::i();
            $m_main_kv = $mainKvDao->getOne(['key'=>'template_mobile_initpwd']);
            if($m_main_kv)
            {
                $mobile_api=new mobile_code();
                $msg_v=str_replace(['{mobile}','{code}'],[$fdata['mobile'],$init_pwd],$m_main_kv->value);
                $mobile_api->send($fdata['mobile'],$msg_v);
            }
            $data['user_id']=$reg_res['user_id'];
            $data['visit_id']=$reg_res['visit_id'];
            $this->json_do->set_data($data);
            $this->json_do->set_msg('注册关联账号并登录成功');
            $this->json_do->out_put();
        }
    }
    
}
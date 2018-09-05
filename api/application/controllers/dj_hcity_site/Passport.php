<?php

/**
 * @Author: binghe
 * @Date:   2018-08-01 15:24:08
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-01 15:25:37
 */
/**
 * passport
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCustomerApplyRecordDao;

class Passport extends dj_hcity_site_controller
{
    /**
     * 商家申请注册
     */
	public function company_register()
	{
        $rule=[
            ['field'=>'username','label'=>'用户名','rules'=>'trim|required'],
            ['field'=>'mobile','label'=>'账号','rules'=>'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|preg_key[PWD]'],
            ['field' => 'repassword', 'label' => '确认密码', 'rules' => 'trim|required|preg_key[PWD]|matches[password]'],
            ['field'=>'mobile_code','label'=>'手机验证码','rules'=>'trim|required|numeric|min_length[4]|max_length[6]'],
            ['field' => 'address', 'label' => '地址', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地址码', 'rules' => 'trim']
        ];
        $this->check->check_form($rule);
        $fdata=$this->form_data($rule);
        //开放140注册
        if(substr($fdata['mobile'], 0,3)=='140')
        {
            if('123456'!=$fdata['mobile_code'])
                $this->json_do->set_error('004','验证码错误');
        }
        else
        {
            //验证手机号验证码
            $mainMobileCodeDao = MainMobileCodeDao::i();
            $m_main_mobile_code=$mainMobileCodeDao->getNormalCode($fdata['mobile']);
            if(!$m_main_mobile_code || $m_main_mobile_code->code!=$fdata['mobile_code'])
                $this->json_do->set_error('004','验证码错误');
        }

        $erp_sdk = new erp_sdk();
        try {
            //查询是否已注册
            $_params[] = $fdata['mobile'];
            $res = $erp_sdk->getUserByPhone($_params);
            if (empty($res)) {
                try {
                    //未注册->注册商家
                    $params[] = $fdata['mobile'];
                    $params[] = $fdata['password'];
                    $ip = get_ip();
                    $params[] = ['ip' => $ip, 'user_name' => $fdata['username']];
                    $res = $erp_sdk->register($params);
                } catch (Exception $e) {
                    $this->json_do->set_error('005', $e->getMessage());
                }
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
        $r_params['res_info']=$res;

        if($r_params['res_info']['user_nature'] !=1)
        {
            $this->json_do->set_error('004','账号已存在，请使用主账号分配店铺');
        }

        $mainCompanyDao = MainCompanyDao::i();
        //判断是否已注册商家
        $mainCompany =$mainCompanyDao->getOne(['visit_id'=>$res['visit_id']]);
        if($mainCompany)
        {
            $this->json_do->set_error('004','账号已注册');
        }
        //处理自己业务
        //1.注册公司，添加主账号
        if($mainCompanyDao->register($r_params))
        {
            //添加申请记录
            $applyRecord['mobile'] = $fdata['mobile'];
            $applyRecord['username'] = $fdata['username'];
            $applyRecord['type'] = 0;
            $applyRecord['status'] = 1;
            $applyRecord['address'] = $fdata['address'];
            $applyRecord['region'] = $fdata['region'];
            $ret = HcityCustomerApplyRecordDao::i()->create($applyRecord);

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
     * 申请城市合伙人
     */
	public function customer_apply()
	{
        $rule=[
            ['field'=>'username','label'=>'用户名','rules'=>'trim|required'],
            ['field'=>'mobile','label'=>'账号','rules'=>'trim|required|preg_key[MOBILE]'],
            ['field' => 'address', 'label' => '地址', 'rules' => 'trim'],
            ['field' => 'region', 'label' => '地址码', 'rules' => 'trim'],
            ['field' => 'type', 'label' => '类型', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_form($rule);
        $fdata=$this->form_data($rule);

        $hcityCustomerApplyRecordDao = HcityCustomerApplyRecordDao::i();
        $mApplyRecord = $hcityCustomerApplyRecordDao->getOne(['mobile'=>$fdata['mobile']]);
        if($mApplyRecord)
        {
            $this->json_do->set_error('005','已申请');
        }
        $fdata['status'] = 0;

        $ret = $hcityCustomerApplyRecordDao->create($fdata);

        if ($ret) {
            $this->json_do->set_msg('申请成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '收藏失败');
        }
	}
}
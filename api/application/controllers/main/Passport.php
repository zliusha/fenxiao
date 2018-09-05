<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/3/12
 * Time: 17:34
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
class Passport extends open_controller
{
    /**
     *  检查体验账号
     */
    public function check_account()
    {
        $rule=[
            ['field'=>'mobile','label'=>'账号','rules'=>'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|preg_key[PWD]']
        ];
        $this->check->check_form($rule);
        $f_data=$this->form_data($rule);


        $erp_sdk = new erp_sdk();

        try {
            //判断手机号码是否已注册
            $params[] = $f_data['mobile'];
            $phone_res = $erp_sdk->getUserByPhone($params);

            //已注册，返回账号信息
            if(!empty($phone_res))
            {
                $this->json_do->set_error('004', '此账号已注册');
//              $this->json_do->set_data(['mobile' => $phone_res['phone']]);
//              $this->json_do->set_msg('此账号已注册');
//              $this->json_do->out_put();
            }

        } catch (Exception $e) {
    //          $this->json_do->set_error('005',$e->getMessage());

          $phone_res = null;
        }

        //手机号码未注册，注册信息
        try {
            //erp注册公司，并生成主账号

            $_r_params[] = $f_data['mobile'];
            $_r_params[] = $f_data['password'];
            $ip = get_ip();
            $_r_params[] = ['ip'=>$ip];
            $reg_res = $erp_sdk->register($_r_params);

            $r_params['res_info']=$reg_res;

            $mainCompanyDao = MainCompanyDao::i();
            if($mainCompanyDao->register($r_params))
            {
                $this->json_do->set_data($f_data);
                $this->json_do->out_put();
            }
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
    }
}
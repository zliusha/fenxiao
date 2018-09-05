<?php
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\MainDbModels\MainKvDao;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2016/8/30
 * Time: 14:52
 */
class Mobile_api extends base_controller {
    function __construct()
    {
        parent::__construct();

    }

    //发送手机验证码
    public function send_code()
    {

        $rule=array(
            array('field'=>'mobile','label'=>'手机号码','rules'=>'trim|required|preg_key[MOBILE]'),
            array('field'=>'type','label'=>'验证类类型','rules'=>'trim|required')
            );
        $this->check->check_ajax_form($rule);
        $fdata=$this->form_data($rule);

        // $types=['update_pwd','register','normal'];   //找回密码，注册已移至官网
        $types=['normal'];
        if(!in_array($fdata['type'], $types)){
            $this->json_do->set_error('004','发送失败,params error');
        }

        $type='';//模板key值
        $type_int=0;//插入手机验证码数据表类型

        switch ($fdata['type']) {
            case 'normal':$type_int=2;$type="template_mobile_normal";
            break;
        }

        $mainKvDao = MainKvDao::i();
        $msg=$mainKvDao->getOne(['key'=>$type]);
        if(!$msg || empty($msg->value))
            $this->json_do->set_error('004','500!短信模板不存在');
        $code=rand(100000,999999);
        $msg_v=str_replace('{code}', $code, $msg->value);
        
        $mobile_api=new mobile_code();
        $res=$mobile_api->send($fdata['mobile'],$msg_v);
        if(!$res)
        {
            $data['mobile']=$fdata['mobile'];
            $data['code']=$code;
            $data['type']=$type_int;
            $data['time']=time();
            $mainMobileCodeDao = MainMobileCodeDao::i();
            $mainMobileCodeDao->create($data);

            $this->json_do->set_msg('短信发送成功');
            $this->json_do->out_put();
        }
        else 
            $this->json_do->set_error('006');

    }
    
}
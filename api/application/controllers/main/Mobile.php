<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/3/12
 * Time: 16:54
 */
use Service\DbFrame\DataBase\MainDbModels\MainKvDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
class Mobile extends open_controller
{
  //发送体验码
    public function send_exp_code()
    {

        $rule = array(
          array('field'=>'mobile','label'=>'手机号码','rules'=>'trim|required|preg_key[MOBILE]'),
          array('field'=>'code','label'=>'体验码','rules'=>'trim|required')
        );
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $type = 'template_mobile_exp';//模板key值
        $type_int = 4;//插入手机验证码数据表类型


        $mainKvDao = MainKvDao::i();
        $msg=$mainKvDao->getOne(['key'=>$type]);
        if(!$msg || empty($msg->value))
          $this->json_do->set_error('004','500!短信模板不存在');

        $msg_v = str_replace('{code}', $f_data['code'], $msg->value);



        $mobile_api = new mobile_code();
        $res = $mobile_api->send($f_data['mobile'],$msg_v);
//        log_message('error', $f_data['mobile']);
        if(!$res)
        {
            $data['mobile']=$f_data['mobile'];
            $data['code']=$f_data['code'];
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

    //发送手机验证码
    public function send_code()
    {

        $rule=array(
            array('field'=>'mobile','label'=>'手机号码','rules'=>'trim|required|preg_key[MOBILE]'),
            array('field'=>'code','label'=>'验证码','rules'=>'trim|required'),
            array('field'=>'type','label'=>'验证类类型','rules'=>'trim|required')//normal
        );
        $this->check->check_ajax_form($rule);
        $fdata=$this->form_data($rule);

        if(!in_array($fdata['type'], array('update_pwd','register','normal'))){
            $this->json_do->set_error('004','type not in list');
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
//        $code=rand(100000,999999);

        $code = $fdata['code'];
        $msg_v=str_replace('{code}', $code, $msg->value);

        $mobile_api=new mobile_code();
        $res=$mobile_api->send($fdata['mobile'],$msg_v);
        if(!$res)
        {
            $data['mobile']=$fdata['mobile'];
            $data['code']=$code;
            $data['type']=$type_int;
            $data['time']=time();
            $id = MainMobileCodeDao::i()->create($data);

            $this->json_do->set_msg('短信发送成功');
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('006');

    }
}
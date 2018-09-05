<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 14:27:50
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-10 11:52:07
 */
use Service\DbFrame\DataBase\MainDbModels\MainKvDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\Enum\MessageTagEnum;
use Service\Enum\MessageTemplateEnum;
class Mobile extends auth_controller
{
    //发送手机验证码
    public function send_code()
    {

        $rule=array(
            array('field'=>'mobile','label'=>'手机号码','rules'=>'trim|required|preg_key[MOBILE]'),
            array('field'=>'type','label'=>'验证类类型','rules'=>'trim|required'),
            array('field'=>'tag_type','label'=>'签名','rules'=>'trim|numeric')
            );
        $this->check->check_ajax_form($rule);
        $fdata=$this->form_data($rule);

        //选择短信签名
        $tag = MessageTagEnum::YDB;
        switch ($fdata['tag_type']) {
            case '1':
                $tag = MessageTagEnum::HCITY;
                break;
        }

        if(!in_array($fdata['type'], array('update_pwd','register','normal'))){
            $this->json_do->set_error('004','type not in list');
        }

        $type='';//模板key值
        $type_int=0;//插入手机验证码数据表类型

        switch ($fdata['type']) {
            case 'register':$type_int=0;$type=MessageTemplateEnum::REGISTER;
                break;
            case 'update_pwd':$type_int=1;$type=MessageTemplateEnum::UPDATE_PWD;
                break;
            case 'normal':$type_int=2;$type=MessageTemplateEnum::NORMAL;
                break;
        }


        $mainKvDao = MainKvDao::i();
        $msg=$mainKvDao->getOne(['key'=>$type]);
        if(!$msg || empty($msg->value))
            $this->json_do->set_error('004','500!短信模板不存在');
        $code=rand(100000,999999);
        $msg_v=str_replace('{code}', $code, $msg->value);
        
        $mobile_api=new mobile_code();
        $res=$mobile_api->send($fdata['mobile'],$msg_v,$tag);
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
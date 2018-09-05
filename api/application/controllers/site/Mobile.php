<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2016/8/30
 * Time: 14:52
 */
use Service\DbFrame\DataBase\MainDbModels\MainKvDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
class Mobile extends site_controller {

    //发送手机验证码
    public function send_code()
    {

        $rule=array(
            array('field'=>'mobile','label'=>'手机号码','rules'=>'trim|required|preg_key[MOBILE]'),
            array('field'=>'type','label'=>'验证类类型','rules'=>'trim|required')
            );
        $this->check->check_ajax_form($rule);
        $fdata=$this->form_data($rule);

        if(!in_array($fdata['type'], array('update_pwd','register','normal'))){
            $this->json_do->set_error('004','发送失败,params error');
        }

        $type='';//模板key值
        $type_int=0;//插入手机验证码数据表类型

        switch ($fdata['type']) {
            case 'update_pwd':
                $type='template_mobile_pwd';$type_int=1;$this->_valid_mobile_exist($fdata['mobile']);
                break;
            case 'register':
                $type='template_mobile_reg';$this->_valid_mobile_not_exist($fdata['mobile']);
                break;
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
    private function _valid_mobile_exist($mobile)
    {
        $erp_sdk = new erp_sdk;
        $params[]=$mobile;
        $res = $erp_sdk->getUserByPhone($params);
        if(!isset($res['user_id']))
        {
            $this->json_do->set_error('004','手机号码不存在');
            $this->json_do->out_put();
        }


    }
    /**
     * [_valid_mobile_not_exist description]
     * @param  [type] $mobile [description]
     * @return [type]         [description]
     */
    private function _valid_mobile_not_exist($mobile)
    {
        $erp_sdk = new erp_sdk;
        $params[]=$mobile;
        $res = $erp_sdk->getUserByPhone($params);
        if(isset($res['user_id']))
        {
            $this->json_do->set_error('004','手机号码已存在');
            $this->json_do->out_put();
        }
    }
}
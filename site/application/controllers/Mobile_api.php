<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2016/8/30
 * Time: 14:52
 */
use Service\DbFrame\DataBase\MainDbModels\MainKvDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\Bll\Basic\CaptchaBll;
class Mobile_api extends base_api_controller {
    
    public function token()
    {
        if(!is_post())
            $this->json_do->set_error('001');
        $token = (new CaptchaBll())->getToken();
        $data['captcha_img'] = SITE_URL.'captcha?t='.$token;
        $data['token'] = $token;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * [send_code description]
     * @return [type] [description]
     */
    public function send_code()
    {
       
        //表单验证
        $rule=[
            ['field'=>'mobile','label'=>'手机号码','rules'=>'trim|required|preg_key[MOBILE]'],
            ['field'=>'type','label'=>'验证类类型','rules'=>'trim|required'],
            ['field'=>'phrase','label'=>'图片验证码','rules'=>'trim'],
            ['field'=>'token','label'=>'图片验证码token','rules'=>'trim'],
            ];
        $this->check->check_ajax_form($rule);
        $fdata=$this->form_data($rule);

        if(!in_array($fdata['type'], array('update_pwd','register','normal'))){
            $this->json_do->set_error('004','发送失败,params error');
        }
        //获取客户端ip
        $ip = get_ip();
        if(empty($ip))
            $this->json_do->set_error('004','非法环境请求');
        

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
        log_message('error',__METHOD__.'-ip:'.$ip.'-'.json_encode($fdata));
        $mainMobileCodeDao = MainMobileCodeDao::i();
        //验证防刷验证,1个小时内同ip超过3条需要验证码
        // $limitTime = time()-3600;
        // $ipSendCount = $mainMobileCodeDao->getCount(['type'=>$type_int,'ip'=>$ip,'time >='=>$limitTime]);
        // if($ipSendCount>=3)
        // {
        //     if(empty($fdata['phrase']) || empty($fdata['token']))
        //         $this->json_do->set_error('004-3','请输入图片验证码');
        //     //图片验证码
        //     $equal = (new CaptchaBll())->assertEqual($fdata['token'],$fdata['phrase']);
        //     if(!$equal)
        //     {
        //         $this->json_do->set_error('004','图片验证码错误');
        //     }
        // }
        // 
        
        if(empty($fdata['phrase']) || empty($fdata['token']))
            $this->json_do->set_error('004-3','请输入图片验证码');
        //图片验证码
        $equal = (new CaptchaBll())->assertEqual($fdata['token'],$fdata['phrase']);
        if(!$equal)
        {
            $this->json_do->set_error('004','图片验证码错误');
        }

        //1小时内同一个ip同种短信行为大于500受限
        if($ipSendCount>=500)
        {
            log_message('error',"短信发送限制-{$fdata['mobile']}-{$ip}-{$type_int}");
            $this->json_do->set_error('004','短信发送失败-limit');
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
            $data['ip'] = $ip;
            
            $mainMobileCodeDao->create($data);

            $this->json_do->set_msg('短信发送成功');
            $this->json_do->out_put();
        }
        else 
            $this->json_do->set_error('006', '发送验证码失败');

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
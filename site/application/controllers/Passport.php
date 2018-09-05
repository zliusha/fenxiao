<?php
/**
 * @Author: binghe
 * @Date:   2017-08-07 17:28:20
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-09-04 17:21:00
 */
use Service\Traits\HttpTrait;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountWxbindDao;
use Service\Bll\Basic\CaptchaBll;
class Passport extends base_controller {

    use HttpTrait;
    public function login()
    {
        if($this->s_user)
        {
            redirect(SAAS_URL);
        }
        else
        {
            $wx_open_ydb = &ini_config('wx_open_ydb');
            $data['app_id']=$wx_open_ydb['app_id'];
            $data['redirect_uri']=SITE_URL.'passport/wx_back';

            $token = (new CaptchaBll())->getToken();
            $data['captcha_img'] = SITE_URL.'captcha?t='.$token;
            $data['token'] = $token;

            $time=time();
            $data['state']=$time.'_'.md5($time.SECRET_KEY);
            $this->load->view('passport/login',$data);
        }

    }
    //微信登录
    public function wx_back()
    {
        $code=$this->input->get('code');
        $state=$this->input->get('state');
        //参数演出验证
        if(empty($code) || empty($state))
        {
            echo 'params error';exit;
        }
        //签名验证
        $valids=explode('_',$state);
        if(count($valids)!=2 || $valids[1]!=md5($valids[0].SECRET_KEY) )
        {
            echo 'validate error';
            exit;
        }

        $wx_ydb_ini = &ini_config('wx_open_ydb');
        $url="https://api.weixin.qq.com/sns/oauth2/access_token";
        $params['appid']=$wx_ydb_ini['app_id'];
        $params['secret']=$wx_ydb_ini['app_secret'];
        $params['code']=$code;
        $params['grant_type']='authorization_code';
        $http = $this->getHttp();
        $result = $http->get($url,$params);
        $json = $http->parseJSON($result);
        if(isset($json['errcode']))
        {
            //此处应有过度界面
            echo $json['errcode'].'-'.$json['errmsg'];
            exit;
        }
        $mainCompanyAccountWxbindDao =  MainCompanyAccountWxbindDao::i();
        $m_main_company_account_wxbind = $mainCompanyAccountWxbindDao->getOne(['open_id'=>$json['openid'],'type'=>'site']);
        //直接登录
        if($m_main_company_account_wxbind)
        {
            $mainCompanyAccountDao = MainCompanyAccountDao::i();
            $m_main_company_account = $mainCompanyAccountDao->getOne(['id'=>$m_main_company_account_wxbind->account_id]);
            if(!$m_main_company_account)
                show_error('关联用户不存在,请联系工作人员');
            //判断登录权限
            try {
                $erp_sdk = new erp_sdk;
                $params1[]=$m_main_company_account->visit_id;
                $params1[]=$m_main_company_account->user_id;
                $res = $erp_sdk->loginById($params1);
            } catch (Exception $e) {
                show_error($e->getMessage());
            }
            

            $main_company_account_bll = new main_company_account_bll();
            $s_main_company_account_do = $main_company_account_bll->get_s_user($m_main_company_account);

            //cookie保存登录信息.保存7天
            $c_value=$this->encryption->encrypt(serialize($s_main_company_account_do));
            set_cookie('c_user',$c_value,3600*24*7);

            redirect(SAAS_URL);
        }
        else    //跳转到手机号绑定界面
        {
            
            $sign=md5($json['openid'].$json['unionid'].SECRET_KEY);
            $url=SITE_URL."passport/bindphone?open_id={$json['openid']}&union_id={$json['unionid']}&sign={$sign}";
            redirect($url);
        }
    }
    /**
     * 手机号绑定
     * @return [type] [description]
     */
    public function bindphone()
    {
        $data['open_id']=$this->input->get('open_id');
        $data['union_id']=$this->input->get('union_id');
        $data['sign']=$this->input->get('sign');
        $this->load->view('passport/bindphone',$data);
    }
    public function logout()
    {
        delete_cookie('saas_user');
        redirect(SITE_URL);
    }
}

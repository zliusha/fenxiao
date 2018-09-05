<?php
/**
 * @Author: binghe
 * @Date:   2017-08-05 16:35:01
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-25 16:15:51
 */
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\Enum\SaasEnum;
use Service\Bll\Main\MainCompanyAccountBll;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
/**
* 登录，注册，找回
*/
class Passport extends base_controller
{
    
    function __construct()
    {
        parent::__construct();
    }
    
    //安全退出
    public function logout()
    {
        
        //删除登录cookie
        delete_cookie('c_wm_shop_id');
        delete_cookie('saas_user');
        redirect(SITE_URL.'passport/login');
    }
    /**
     * 产品中心免密登录
     * @return [type] [description]
     */
    public function ajuc_login()
    {

        $token=$this->input->get('token');
        $data['url'] = SITE_URL.'passport/login';
        //未带token则返回到登录界面
        if(empty($token))
        {
            $this->_load_redirect($data);
        }

        try {
            $erp_sdk = new erp_sdk;
            $params[]=$token;
            $res=$erp_sdk->tokenLogin($params);
        } catch (Exception $e) {
            $data['msg']='登录信息失效,3秒后自动跳转至登录页面...';
            $this->_load_redirect($data);
        }
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        //获取账号信息
        $m_main_company_account = $mainCompanyAccountDao->getOne(['visit_id'=>$res['visit_id'],'user_id'=>$res['user_id']]);
        if($m_main_company_account)
        {
            $mainCompanyAccountBll = new MainCompanyAccountBll();
            $sUser = $mainCompanyAccountBll->getSaasSUserByModel($m_main_company_account); 
            //3.1cookie保存登录信息，保存7天
            $c_value=$this->encryption->encrypt(serialize($sUser));
            set_cookie('saas_user',$c_value,3600*24*7);
            redirect(DJADMIN_URL);
        }
        else
        {
            $action = $this->input->get('action');
            if($action == 'ktsy')
            {
                if($res['user_nature'] != 1)
                {
                    $data['msg']='开通试用版失败,请通过主账号开通,3秒后自动跳转至登录页面...';
                    $data['is_redirect']=false;
                    $this->_load_redirect($data);
                }

                //注册公司
                //2.1 添加公司,注册账号，开通试用
                $mainCompanyDao = MainCompanyDao::i();
                $r_params['res_info']=$res;
                if(!$mainCompanyDao->register($r_params))
                {
                    $data['msg']='开通试用失败,请联系工作人员';
                    $data['is_redirect']=false;
                    $this->_load_redirect($data);
                }
                
                //自动登录
                $m_main_company_account = $mainCompanyAccountDao->getOne(['visit_id'=>$res['visit_id'],'user_id'=>$res['user_id']]);
                //3.1cookie保存登录信息，保存7天
                $mainCompanyAccountBll = new MainCompanyAccountBll();
                $sUser = $mainCompanyAccountBll->getSaasSUserByModel($m_main_company_account); 
                $c_value=$this->encryption->encrypt(serialize($sUser));
                set_cookie('saas_user',$c_value,3600*24*7);
                redirect(DJADMIN_URL);
            }
            else
            {
                $data['msg']='该账号未开通云店宝,3秒后自动跳转至登录页面...';
                $this->_load_redirect($data);
            }           
            
        }
    }
    /*
    加载跳转过渡页
     */
    private function _load_redirect($data)
    {
        $this->load->view('common/redirect',$data);
        $this->output->_display();
        exit;
    }
}
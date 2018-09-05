<?php
/**
 * @Author: binghe
 * @Date:   2018-01-22 15:23:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 11:20:31
 */
use Service\DbFrame\DataBase\WmMainDbModels\XcxAppDao;
use Service\DbFrame\DataBase\MainDbModels\MainXcxVersionDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
/**
* wm小程序
*/
class Xcx_config_api extends wm_service_controller
{
    public function __construct()
    {
        parent::__construct(module_enum::XCX_WM_MODULE);
    }
    //小程序类型 0外卖小程序
    const TYPE = 0;
    /**
     * 获取用户小程序版本信息
     * @return [type] [description]
     */
    public function info()
    {  
        //$m_xcx_app->audit_status 1,//审核状态，其中0为审核成功，1为审核失败，2为审核中
        //1.获取acess_token，获取不到代表未授权
        $authInfo = $this->_getAuthInfo();
        $authorizer_appid = $authInfo['data']['authorizer_appid'];
        $data['app_nick_name'] = $authInfo['data']['nick_name'];
        $xcxAppdao = XcxAppDao::i();
        $m_xcx_app = $xcxAppdao->getOne(['aid'=>$this->s_user->aid,'app_id'=>$authorizer_appid]);
        $data['info']=$m_xcx_app;
        //0代表无需操作,1代表首次注册,2代表需要升级
        $mainXcxVersionDao = MainXcxVersionDao::i();
        $vWhere['type'] = self::TYPE;
        if($this->s_user->aid != 1226)
            $vWhere['status'] = 1;
        $m_main_xcx_version = $mainXcxVersionDao->getOne($vWhere,'*','time desc');
        if(!$m_main_xcx_version)
            $this->json_do->set_error('004','该类型小程序版本未发布');
        $xcx_info['user_version'] = $m_main_xcx_version->user_version;
        $xcx_info['user_desc']=$m_main_xcx_version->user_desc;

        if($m_xcx_app)
        {
            //-1小于,0等于,1大于,可带v
            $lt = compare_version($m_xcx_app->user_version,$m_main_xcx_version->user_version);
            if($lt === -1 && $m_xcx_app->audit_status != 2)
                $data['use_status'] = 2; //升级小程序
            elseif($m_xcx_app->audit_status == 1) //审核失败
                $data['use_status'] = 1; //生成小程序
            else 
                $data['use_status'] = 0; //无需操作

        }
        else
            $data['use_status'] = 1;    //生成小程序
        $data['xcx_server_info'] =$xcx_info;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 1为授权的小程序帐号上传小程序代码
     * @return [type] [description]
     */
    public function generate()
    {
        $mainXcxVersionDao = MainXcxVersionDao::i();
        $vWhere['type'] = self::TYPE;
        if($this->s_user->aid != 1226)
            $vWhere['status'] = 1;
        $m_main_xcx_version = $mainXcxVersionDao->getOne($vWhere,'*','time desc');
        if(!$m_main_xcx_version)
            $this->json_do->set_error('004','该类型小程序版本未发布');
        //1.获取acess_token，获取不到代表未授权
        $authInfo = $this->_getAuthInfo();
        try {
            
            //授权小程序的access_token
            $access_token = $authInfo['data']['authorizer_access_token'];
            //授权小程序的appid
            $authorizer_appid = $authInfo['data']['authorizer_appid'];
            $xcx_sdk = new xcx_sdk;

            //2设置域名
            //2.1设置小程序服务器域名
            $xcx_inc = inc_config('xcx');
            $domainParams=[
                'action'=>'set'
                ,'requestdomain'=>$xcx_inc['requestdomain']
                ,'wsrequestdomain'=>$xcx_inc['wsrequestdomain']
                ,'uploaddomain'=>$xcx_inc['uploaddomain']
                ,'downloaddomain'=>$xcx_inc['downloaddomain']
            ];
            $xcx_sdk->wxaModifyDomain($domainParams,$access_token);
            //2.2设置小程序业务域名
            $webDomainParams = [
                'action'=>'set'
                ,'webviewdomain'=>$xcx_inc['webviewdomain']
            ];
            $xcx_sdk->wxaSetWebviewDomain($webDomainParams,$access_token);
            //3.提交小程序模板待审核
            $json_arr['extEnable']=true;
            $json_arr['extAppid']=$authorizer_appid;
            $json_arr['ext']=[
            'extAppid'=>$authorizer_appid
            ,'base_url'=>$xcx_inc['base_url']
            ,'upload_url'=>$xcx_inc['upload_url']
            ,'environment'=>ENVIRONMENT
            ,'version'=> $m_main_xcx_version->user_version
            ];
            $ext_json = json_encode($json_arr,JSON_UNESCAPED_UNICODE);
            //直接上传配置文件中小程序版本
            $commitParams=[
                'template_id'=>$m_main_xcx_version->template_id
                ,'user_version'=>$m_main_xcx_version->user_version
                ,'user_desc'=>$m_main_xcx_version->user_desc
                ,'ext_json'=>$ext_json
            ];

            $xcx_sdk->wxaCommit($commitParams,$access_token);

            //4.获取小程序分类
            $categoryRes = $xcx_sdk->getCategory($access_token);
            if(!isset($categoryRes['category_list'][0])){
                log_message('error',__METHOD__.'-'.json_encode($res,JSON_UNESCAPED_UNICODE));
                $this->json_do->set_error('005','系统未获取到小程序类目');
            }
            $item = $categoryRes['category_list'][0];
            $item['title'] = '首页';
            $item['address'] = 'pages/home/index/index';
            $item_list = [];
            array_push($item_list, $item);
            // $item_list=[
            //         ["address"=>'pages/home/home/home',
            //         "tag"=>"美食 外卖",
            //         "first_class"=>"餐饮",
            //         "second_class"=>"菜谱",
            //         "title"=>"首页"
            //         ]
            //     ];
            //5.发布审核
            $submitAuditParams=[
            "item_list"=>
                $item_list
            ];
            $submitAuditRes=$xcx_sdk->submitAudit($submitAuditParams,$access_token);

            //5保存审核结果
            $this->_saveXcxApp($authInfo,$m_main_xcx_version,$submitAuditRes);
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
            
        } catch (Exception $e) {
            log_message('error',__METHOD__.'-'.json_encode($authInfo));
            log_message('error',__METHOD__.'-'.$e->getMessage());
            $this->json_do->set_error('005',$e->getMessage());
        }
        
    }
    /**
     * 2获取小程序的体验二维码
     * @return [type] [description]
     */
    public function getQrCode()
    {
        //1.获取acess_token，获取不到代表未授权
        $authInfo = $this->_getAuthInfo();
        $xcx_sdk = new xcx_sdk;
        $access_token = $authInfo['data']['authorizer_access_token'];
        $data['qrurl']= $xcx_sdk->getQrCode($access_token);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 发布小程序接口
     * @return [type] [description]
     */
    public function release()
    {
        $authInfo = $this->_getAuthInfo();
        $xcx_sdk = new xcx_sdk;
        $access_token = $authInfo['data']['authorizer_access_token'];
        $res=$xcx_sdk->release($access_token);
    }
    /**
     * 保存小程序密钥
     * @return [type] [description]
     */
    public function save_secreat()
    {
        $rule = [
          ['field' => 'app_id', 'label' => '小程序app_id', 'rules' => 'trim|required'],
          ['field' => 'app_secreat', 'label' => '小程序密钥', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $xcxAppDao = XcxAppDao::i();
        if($xcxAppDao->update(['app_secreat'=>$fdata['app_secreat'], 'verify_file_name'=>$fdata['verify_file_name'],  'verify_file_path'=>$fdata['verify_file_path']],['aid'=>$this->s_user->aid,'app_id'=>$fdata['app_id']]) !== false)
        {
            $this->json_do->out_put();
        }
        else
            $this->json_do->set_error('005');
    }


    /**
     * 保存申请的app信息
     * @return [type] [description]
     */
    public function _saveXcxApp($authInfo,$m_main_xcx_version,$submitAuditRes)
    {
        $data['aid']=$this->s_user->aid;
        $data['app_id']=$authInfo['data']['authorizer_appid'];
        $data['template_id']=$m_main_xcx_version->template_id;
        $data['user_version']=$m_main_xcx_version->user_version;
        $data['user_desc']=$m_main_xcx_version->user_desc;
        $data['update_time'] = time();
        $data['auditid']=$submitAuditRes['auditid'];
        $data['audit_status']=2;//审核中
        $data['audit_reason']='';//审核结果清空
        $data['nick_name'] = $authInfo['data']['nick_name'];
        $data['user_name'] = $authInfo['data']['user_name'];
        $data['head_img'] = $authInfo['data']['head_img'];
        $data['type'] = self::TYPE;
        $xcxAppDao = XcxAppDao::i();
        $m_xcx_app = $xcxAppDao->getOne(['aid'=>$data['aid'],'app_id'=>$data['app_id']]);
        if($m_xcx_app)
            return $xcxAppDao->update($data,['aid'=>$data['aid'],'app_id'=>$data['app_id']]);
        else
            return $xcxAppDao->create($data);
    }
    /**
     * 获取小程序授权信息
     * @return [type] [description]
     */
    private function _getAuthInfo()
    {
        try {
            $scrm_sdk = new scrm_sdk;
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id'=>$this->s_user->aid]);
            if(!$m_main_company)
                $this->json_do->set_error('004','visit_id不存在');
            $params['visit_id']=$m_main_company->visit_id;
            $params['type'] = self::TYPE; //外卖小程序
            $res = $scrm_sdk->getXcxInfo($params);
            if(isset($res['data']['authorizer_appid']))
                return $res;
            else
                throw new Exception("小程序未授权或授权过期");
        } catch (Exception $e) {
            $this->json_do->set_error('001','小程序未授权或授权过期');
        }
        
    }
}
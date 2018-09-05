<?php
/**
 * @Author: binghe
 * @Date:   2018-04-08 09:56:05
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-15 17:19:14
 */
use Service\Bll\Main\MainCompanyAccountBll;
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;

use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainMobileCodeDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopAccountDao;
/**
* passport
*/
class PassPort extends sy_controller
{
    //登录
    public function login()
    {
        //验证
        $rule = [
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|min_length[6]|max_length[25]'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        //账号中心登录
        try {
            $erp_sdk = new erp_sdk();
            $params[]=$fdata['mobile'];
            $params[]=$fdata['password'];
            $res=$erp_sdk->login($params);
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }      
        //2.本地关联
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $m_main_company_account = $mainCompanyAccountDao->getOne(['visit_id'=>$res['visit_id'],'user_id'=>$res['user_id']]);
        if(!$m_main_company_account)
            $this->json_do->set_error('004','账号未开通云店宝');

        //3．获取所有门店列表
        
        $shopList = $this->_shopList($m_main_company_account);
        if(empty($shopList))
            $this->json_do->set_error('004','账号没有门店管理权限');
        $data['shop_list'] = $shopList;
        //4．输出
        $data['account_id'] = $m_main_company_account->id;
        $time = time();
        $data['login_id'] = $time;
        $data['login_token'] = md5($data['account_id'].$time.ENCRYPTION_KEY);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 伙伴账号的门店列表
     * @param  object $m_main_company_account [description]
     * @return [type]                         [description]
     */
    private function _shopList($mainAccount)
    {
        //主账号
        $list=[];
        $where = " aid={$mainAccount->aid} and is_delete=0 ";
        if($mainAccount->is_admin != 1)
        {
            $shopAccountList = MainShopAccountDao::i()->getAllArray(['aid'=>$mainAccount->aid,'account_id'=>$mainAccount->id],'id,main_shop_id');
            if(!$shopAccountList)
                return false;
            $mainShopIds = array_column($shopAccountList, 'main_shop_id');
            $inStr = sql_where_in($mainShopIds);
            $where.= " and main_shop_id in ({$inStr})";
        }
        $wmShopDao = WmShopDao::i($mainAccount->aid);
        return $wmShopDao->getAllArray($where,'id as shop_id,shop_name');
    }
    public function shop_login()
    {
        $rule = [
            ['field' => 'account_id', 'label' => '账号id', 'rules' => 'trim|required|numeric'],
            ['field' => 'login_id', 'label' => '登录id', 'rules' => 'trim|required'],
            ['field' => 'login_token', 'label' => '登录令牌', 'rules' => 'trim|required'],
            ['field' => 'shop_id', 'label' => '门店id', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $sign = md5($fdata['account_id'].$fdata['login_id'].ENCRYPTION_KEY);
        if($sign!=$fdata['login_token'])
            $this->json_do->set_error('004','登录门店失败');

        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $mainAccount = $mainCompanyAccountDao->getOne(['id'=>$fdata['account_id']]);
        if(!$mainAccount)
            $this->json_do->set_error('004','登录账号不存在');
        $mainCompanyAccountBll = new MainCompanyAccountBll;
        $mainAccount->shop_id = $fdata['shop_id'];
        $sUser = $mainCompanyAccountBll->getSySUser($mainAccount);
        $expiredTime = 3600 * 2;
        //cookie保存登录信息保存7天
        $input['access_token'] = md5(create_guid());
        $apiAccessTokenCache = new apiAccessTokenCache($input);
        $apiAccessTokenCache->save($sUser,$expiredTime);

        //t1登录缓存为7
        $rExpiredTime = 3600 * 24 * 7;
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token'=>$refreshToken]);
        $apiRefreshTokenCache->save($sUser, $rExpiredTime);

        $data['refresh_token'] = $refreshToken;
        $data['access_token'] = ['value'=>$input['access_token'],'expire_time'=>time()+$expiredTime];
        $this->json_do->set_data($data);
        $this->json_do->out_put();

    }
    //找回密码
    public function findpassword()
    {
        //验证
        $rule = [
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'trim|required|preg_key[MOBILE]'],
            ['field' => 'code', 'label' => '手机验证码', 'rules' => 'trim|required|numeric|min_length[4]|max_length[6]'],
            ['field' => 'password', 'label' => '密码', 'rules' => 'trim|required|preg_key[PWD]'],
            ['field' => 'repassword', 'label' => '确认密码', 'rules' => 'trim|required|preg_key[PWD]|matches[password]']
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        //验证手机验证码
        $mainMobileCodeDao = MainMobileCodeDao::i();
        $m_main_mobile_code = $mainMobileCodeDao->getUpwdCode($fdata['mobile']);
        if (!$m_main_mobile_code || $m_main_mobile_code->code != $fdata['code'])
            $this->json_do->set_error('004','手机验证码不正确');

        try {
            $erp_sdk = new erp_sdk;
            $params[]=$fdata['mobile'];
            $params[]=$fdata['password'];
            $info = $erp_sdk->updatePwdByPhone($params);
            $this->json_do->set_msg('修改密码成功');
            $this->json_do->out_put();

        } catch (Exception $e) {
            $this->json_do->set_error('005', substr(strstr($e->getMessage(), "-"), 1));
        }
    }
}
<?php
/**
 * @Author: binghe
 * @Date:   2018-04-08 09:49:20
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 11:36:50
 */
namespace Service\Bll\Main;

use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountWxbindDao;
use Service\Exceptions\Exception;
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\ApiRefreshTokenCache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopAccountDao;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\MainDb;
/**
 * 公司账号
 */
class MainCompanyAccountBll extends \Service\Bll\BaseBll
{
    /**
     * 得到管理后台的登录账号
     * @param  [type] $m_main_company_account [description]
     * @return [type]                         [description]
     */
    public function getSUser($m_main_company_account)
    {
        $s_main_company_account_do           = new \s_main_company_account_do();
        $s_main_company_account_do->id       = $m_main_company_account->id;
        $s_main_company_account_do->aid      = $m_main_company_account->aid;
        $s_main_company_account_do->username = $m_main_company_account->username;
        $s_main_company_account_do->visit_id = $m_main_company_account->visit_id;
        $s_main_company_account_do->user_id  = $m_main_company_account->user_id;
        $s_main_company_account_do->shop_id  = $m_main_company_account->shop_id;
        $s_main_company_account_do->is_admin = $m_main_company_account->is_admin == 1 ? 1 : 0;
        return $s_main_company_account_do;
    }
    /**
     * 得到收银的登录账号
     * @param  [type] $m_main_company_account [description]
     * @return [type]                         [description]
     */
    public function getSySUser($m_main_company_account)
    {
        $s_sy_user_do           = new \s_sy_user_do();
        $s_sy_user_do->id       = $m_main_company_account->id;
        $s_sy_user_do->aid      = $m_main_company_account->aid;
        $s_sy_user_do->username = $m_main_company_account->username;
        $s_sy_user_do->visit_id = $m_main_company_account->visit_id;
        $s_sy_user_do->user_id  = $m_main_company_account->user_id;
        $s_sy_user_do->shop_id  = $m_main_company_account->shop_id ? $m_main_company_account->shop_id : 0;
        $s_sy_user_do->is_admin = $m_main_company_account->is_admin == 1 ? 1 : 0;
        return $s_sy_user_do;
    }
    /**
     * 得到saasSUser by modle
     * @param  object $mainCompanyAccount 账号对象
     * @return \s_saas_user                     [description]
     */
    public function getSaasSUserByModel(\stdClass $mMainCompanyAccount)
    {
        $s_saas_user_do           = new \s_saas_user_do();
        $s_saas_user_do->id       = $mMainCompanyAccount->id;
        $s_saas_user_do->aid      = $mMainCompanyAccount->aid;
        $s_saas_user_do->username = $mMainCompanyAccount->username;
        $s_saas_user_do->visit_id = $mMainCompanyAccount->visit_id;
        $s_saas_user_do->user_id  = $mMainCompanyAccount->user_id;
        $s_saas_user_do->is_admin = $mMainCompanyAccount->is_admin == 1 ? true : false;
        return $s_saas_user_do;
    }
    /**
     * 根据用户名,得到SaasSuer,如果是主账号且未注册本系统则会自动生成账号
     * @param  string $mobile   手机号
     * @param  string $password 密码
     * @return object s_saas_user
     */
    public function getSaasSUser(string $mobile, string $password)
    {
        //erp_sdk登录
        try {
            $erp_sdk  = new \erp_sdk();
            $params[] = $mobile;
            $params[] = $password;
            $res      = $erp_sdk->login($params);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $mMainCompanyAccount   = $mainCompanyAccountDao->getOne(['visit_id' => $res['visit_id'], 'user_id' => $res['user_id']]);
         //主账号
        if ($mMainCompanyAccount) {
            return $this->getSaasSUserByModel($mMainCompanyAccount);
        } 
        else {
                //子账号不能自动开通
                if ($res['user_nature'] != 1) {
                    throw new Exception("子账号没有开通saas权限");
                }
                $params['res_info'] = [ 
                                        'visit_id'=>$res['visit_id'],
                                        'user_id'=>$res['user_id'],
                                        'user_nature'=>1,
                                        'user_name'=>$mobile
                                    ];
                //主账号自动注册
                if(MainCompanyDao::i()->register($params))
                {
                    $mMainCompanyAccount   = $mainCompanyAccountDao->getOne(['visit_id' => $res['visit_id'], 'user_id' => $res['user_id']]);
                    if(!$mMainCompanyAccount)
                        throw new Exception("账号不存在");
                    return $this->getSaasSUserByModel($mMainCompanyAccount);
                }
                else
                    throw new Exception("总账号自动注册失败");
                    
        }
    }
    /**
     * 得到saas登录token
     * @param  object $mainCompanyAccount 账号对象
     * @param  int $accessTokenExpiredTime accessToken过期时间 3600*2
     * @param  int $refreshTokenExpiredTime refreshToken期时间 3600*24*7
     * @return array
     */
    public function getSaasTokenByModel(\stdClass $mMainCompanyAccount,int $accessTokenExpiredTime=7200,int $refreshTokenExpiredTime = 604800)
    {
        $sUser = $this->getSaasSUserByModel($mMainCompanyAccount);
        return $this->getTokenBySuser($sUser,$accessTokenExpiredTime,$refreshTokenExpiredTime);
    }
    /**
     * 得到saas登录token
     * @param  string $mobile   手机号
     * @param  string $password 密码
     * @param  int $accessTokenExpiredTime accessToken过期时间 3600*2
     * @param  int $refreshTokenExpiredTime refreshToken期时间 3600*24*7
     * @return array
     */
    public function getSaasToken(string $mobile,string $password,int $accessTokenExpiredTime=7200,int $refreshTokenExpiredTime = 604800)
    {
        $sUser = $this->getSaasSUser($mobile,$password);
        return $this->getTokenBySuser($sUser,$accessTokenExpiredTime,$refreshTokenExpiredTime);
    }
    /**
     * 根据sUser得到Token array
     * @param  \s_saas_user_do $sUser [description]
     * @return [type]                 [description]
     */
    public function getTokenBySuser(\s_saas_user_do $sUser,int $accessTokenExpiredTime=7200,int $refreshTokenExpiredTime = 604800)
    {
        $time = time();
        //cookie保存登录信息保存7天
        $accessToken = md5(create_guid());
        $apiAccessTokenCache = new ApiAccessTokenCache(['access_token'=>$accessToken]);
        $apiAccessTokenCache->save($sUser,$accessTokenExpiredTime);

        //t1登录缓存为7
        $refreshToken = md5(create_guid());
        $apiRefreshTokenCache = new ApiRefreshTokenCache(['refresh_token'=>$refreshToken]);
        $apiRefreshTokenCache->save($sUser, $refreshTokenExpiredTime);

        $data['access_token'] = ['value'=>$accessToken,'expire_time'=>$time+$accessTokenExpiredTime];
        $data['refresh_token'] = ['value'=>$refreshToken,'expire_time'=>$time+$refreshTokenExpiredTime];
        return $data;
    }
    /**
     * [sampleInfo 获取简易的]
     * @param  array  $params id|visit_id,user_id
     * @return null or object
     */
    public function sampleInfo(array $params)
    {
        $where=[];
        if(isset($params['id']))
            $where['id']=$params['id'];
        if(isset($params['visit_id']) && isset($params['user_id']))
        {
            $where['visit_id'] = $params['visit_id'];
            $where['user_id'] = $params['user_id'];
        }
        if(!$where)
            throw new Exception("查询用户信息失败");
        $info = MainCompanyAccountDao::i()->getOne($where,'username,img,sex');
        if($info)
            $info->img = \conver_picurl($info->img);
        return $info;
            
    }
    /**
     * 添加员工
     */
    public function addEmployee($aid,$visitId,$userId)
    {
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $where=['visit_id'=>$visitId,'user_id'=>$userId];
        $mMainCompanyAccount = $mainCompanyAccountDao->getOne($where);
        if($mMainCompanyAccount)
            throw new Exception("添加失败,账号已存在");
    
        $erp_sdk = new \erp_sdk;
        $params[]=$visitId;
        $params[]=$userId;
        $res=$erp_sdk->getUserById($params);
        if($res['user_nature']==1)
            throw new Exception("添加失败，不能添加总账号");

        $data['user_id']=$res['user_id'];
        $data['visit_id']=$res['visit_id'];
        $data['aid']=$aid;
        $data['is_admin']=$res['user_nature'];
        $data['username']=$res['user_name'];
        $id = $mainCompanyAccountDao->create($data);
        if($id)
        {
            return $id;
        }
        else
            throw new Exception("添加失败");            
    }
    /**
     * 删除员工同时删除员工关联的门店权限记录，微信绑定记录
     * @return  int　删除条数 
     */
    public function deleteEmployee(int $aid,int $accountId)
    {
        $mainCompanyAccountDao = MainCompanyAccountDao::i();
        $where =['aid'=>$aid,'id'=>$accountId,'is_admin <>'=>1];
        if($affectedRows =$mainCompanyAccountDao->delete($where))
        {
            //删除微信绑定记录
            MainCompanyAccountWxbindDao::i()->delete(['account_id'=>$accountId]);
            //删除门店权限记录
            MainShopAccountDao::i()->delete(['aid'=>$aid,'account_id'=>$accountId]);
            return $affectedRows;
        }
        else
            throw new Exception("删除失败,门店不存在");

    }
    /**
     * 员工账号列表
     */
    public function employeePageList(int $aid)
    {
        $mainDb = MainDb::i();
        $page = new PageList($mainDb);
        $pConf = $page->getConfig();
        $pConf->fields='id,visit_id,user_id,username,img,sex,time';
        $pConf->table = "{$mainDb->tables['main_company_account']}";
        $pConf->where = " aid = {$aid} and is_admin<>1";
        $count = 0;
        $list = $page->getList($pConf,$count);
        $cRules = [
            ['type' => 'time', 'field' => 'time'],
            ['type'=>'img','field'=>'img']

        ];
        $list = \convert_client_list($list,$cRules);
        $data['total'] = $count;
        $data['rows'] = $list;
        return $data;
    }
    /**
     * 员工账号列表
     */
    public function employeeAllList(int $aid)
    {
        $fields='id,visit_id,user_id,username,img,sex,time';
        $list = MainCompanyAccountDao::i()->getAllArray(['aid'=>$aid,'is_admin <>'=>1],$fields);
        $cRules = [
            ['type' => 'time', 'field' => 'time'],
            ['type'=>'img','field'=>'img']

        ];
        $list = \convert_client_list($list,$cRules);
        return $list;
    }
    /**
     * 获取所有子账号
     * @param  int    $aid         [description]
     * @param  int    $visitId     [description]
     * @param  bool   $filterExist 是否过滤已存在的账号
     * @return array
     */
    public function ajucEmployeeAllList(int $aid,int $visitId,bool $filterExist =true)
    {
        $erp_sdk = new \erp_sdk;
        $params[]=$visitId;
        $res=$erp_sdk->getUsers($params);
        $lastList=[];
        if($filterExist && $res)
        {
            $employeeList = $this->employeeAllList($aid);
            foreach ($res as $key=>$item) {
                $one = array_find($employeeList,'user_id',$item['user_id']);
                if($one)
                    continue;
                array_push($lastList, $item);
            }
        }
        return $lastList;
    }
}

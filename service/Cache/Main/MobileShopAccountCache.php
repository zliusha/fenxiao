<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/14
 * Time: 10:45
 */
namespace Service\Cache\Main;

use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;


/**
 * 子账号有权限的saas缓存
 */
class MobileShopAccountCache extends \Service\Cache\BaseCache
{

    /**
     * @param array $input 必需: mobile
     */
    public function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * @return array
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            //1.查找erp mobile 账户信息
            try{
                $erp_sdk = new \erp_sdk();
                $params[] = $input['mobile'];
                $res = $erp_sdk->getUserByPhone($params);

                if(!isset($res['user_id']))
                    throw new \Exception('未注册账户或账户异常');

                $mainCompanyAccountDao = MainCompanyAccountDao::i();
                return $mainCompanyAccount = $mainCompanyAccountDao->getOne(['user_id'=>$res['user_id']]);
            }catch(\Exception $e){
                log_message('error', __METHOD__.'msg:'.$e->getMessage());
                throw new \Exception('未注册账户或账户异常');
            }
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "Main:MobileShopAccount:{$this->input['mobile']}";
    }

}
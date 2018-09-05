<?php

/**
 * @Author: binghe
 * @Date:   2018-07-20 12:00:49
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-20 17:32:24
 */
namespace Service\Cache\Wm;
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmServiceDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmServiceAccountDao;
use Service\Exceptions\Exception;
use Service\Enum\SaasEnum;

/**
* CompanyCache
*/
class WmServiceCache extends \Service\Cache\BaseCache
{
    /**
     * @param array $input 必需:aid
     */
    public function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * data=>service_do
     * @return object $service_do
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){

            //1.查找云店saas开通记录
            $mainSaasAccountDao = MainSaasAccountDao::i();
            $mMainSaasAccount = $mainSaasAccountDao->getOne(['aid'=>$input['aid'],'saas_id'=>SaasEnum::YD]);
            if(!$mMainSaasAccount)
                throw new Exception("未开通服务");
            //2.查找云店内部关联版本
            $wmServiceDao = WmServiceDao::i();
            $mWmService = $wmServiceDao->getOne(['item_id'=>$mMainSaasAccount->saas_item_id]);
            if(!$mWmService)
                throw new Exception("不支持此版本.item_id ".$mMainSaasAccount->saas_item_id);
            //扩展服务配置
            $mWmServiceAccount = WmServiceAccountDao::i()->getOne(['aid'=>$input['aid']]);
            $wm_service_do = new \wm_service_do;
            $wm_service_do->aid = $mMainSaasAccount->aid;
            $wm_service_do->item_id = $mMainSaasAccount->saas_item_id;
            $wm_service_do->item_name = $mMainSaasAccount->saas_item_name;
            $wm_service_do->expire_time = $mMainSaasAccount->expire_time;
            $wm_service_do->shop_limit = $mWmService->shop_limit;
            if($mWmServiceAccount)
                $wm_service_do->shop_limit = $mWmServiceAccount->shop_limit;
            if(!empty($mWmService->power_key))
                $wm_service_do->power_keys = explode(',',$mWmService->power_key);
            return $wm_service_do;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "Wm:WmService:{$this->input['aid']}";
    }
    
}
<?php

/**
 * @Author: binghe
 * @Date:   2018-07-21 14:21:51
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 17:59:39
 */
namespace Service\Cache\Main;
use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;


/**
* 总账号saas缓存
*/
class AidSaasAccountCache extends \Service\Cache\BaseCache
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
     * @return array 
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            //1.查找云店saas开通记录
            $mainSaasAccountDao = MainSaasAccountDao::i();
            return $mainSaasAccountDao->getAllArray(['aid'=>$input['aid']]);
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "Main:AidSaasAccount:{$this->input['aid']}";
    }
    
}
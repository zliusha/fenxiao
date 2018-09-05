<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 11:05:11
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:13:56
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\MainDbModels\MainSecretDao;
use Service\Exceptions\Exception;
/**
 * 密钥对
 */
class MainSecretCache extends BaseCache
{
	
	/**
     * @param array $input 必需:access_key
     */
    function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * @return object $m_main_secret
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $mainSecretDao = MainSecretDao::i();
            $m_main_secret = $mainSecretDao->getOne(['access_key'=>$input['access_key'],'status'=>1]);
            if(!$m_main_secret)
                throw new Exception('记录不存在');
                
            return $m_main_secret;
        });
    }
    public function getKey()
    {
        return "MainSecret:{$this->input['access_key']}";
    }
}
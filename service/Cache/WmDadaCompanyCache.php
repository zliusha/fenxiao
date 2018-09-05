<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 12:48:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:31:31
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\WmDadaCompanyDao;
use Service\Exceptions\Exception;
/**
* 达达商户号缓存
*/
class WmDadaCompanyCache extends BaseCache
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
     * @return object 
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $wmDadaCompanyDao = WmDadaCompanyDao::i($input['aid']);
            $m_dada_company = $wmDadaCompanyDao->getOne(['aid' => $input['aid']]);
            if(!$m_dada_company)
                throw new Exception('达达信息未配置');
                
            return $m_dada_company;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "WmDadaCompany:{$this->input['aid']}";
    }
}
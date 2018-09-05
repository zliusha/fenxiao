<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 20:38:55
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 18:42:21
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\Exceptions\Exception;
/**
 * visitId to aid
 */
class AidToShardNodeCache extends BaseCache implements IAssign
{
	
	/**
     * @param array $input 必需:aid
     */
    function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * @return int aid
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id'=>$input['aid']],'shard_node');
            if(!$m_main_company)
	            throw new Exception('公司记录不存在');
            return $m_main_company->shard_node;
        });
    }
    public function getKey()
    {
        return "AidToShardNode:{$this->input['aid']}";
    }
}
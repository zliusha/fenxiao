<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 10:54:53
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 18:44:05
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\Exceptions\Exception;
/**
 * visitId to aid
 */
class AidToVisitIdCache extends BaseCache implements IAssign
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
     * data=>[aid
     * @return mixed [description]
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id'=>$input['aid']],'visit_id');
            if(!$m_main_company)
	            throw new Exception('公司记录不存在');
            return $m_main_company->visit_id;
        });
    }
    public function getKey()
    {
        return "AidToVisitId:{$this->input['aid']}";
    }
}
<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 10:36:40
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:26:30
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\Exceptions\Exception;
/**
 * visitId to aid
 */
class VisitIdToAidCache extends BaseCache implements IAssign
{
	
	/**
     * @param array $input 必需:visit_id
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
            $m_main_company = $mainCompanyDao->getOne(['visit_id'=>$input['visit_id']],'id');
            if(!$m_main_company)
	            throw new Exception('visit_id 未开通');
                
            return $m_main_company->id;
        });
    }
    public function getKey()
    {
        return "VisitIdToAid:{$this->input['visit_id']}";
    }
}
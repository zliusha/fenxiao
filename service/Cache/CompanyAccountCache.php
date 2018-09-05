<?php
/**
 * @Author: binghe
 * @Date:   2018-04-08 15:07:27
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 18:55:44
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyAccountDao;
use Service\Exceptions\Exception;
/**
 * 账户信息
 */
class CompanyAccountCache extends BaseCache
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
     * data=>[aid,visit_id]
     * @return mixed [description]
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $mainCompanyAccountDao = MainCompanyAccountDao::i();
            $model = $mainCompanyAccountDao->getOne(['aid'=>$input['aid'], 'is_admin'=>1]);
            if(!$model)
                throw new Exception("账号记录不存在");
            return $model;
        });
    }
    public function getKey()
    {
        return "CompanyAccount:{$this->input['aid']}";
    }
}
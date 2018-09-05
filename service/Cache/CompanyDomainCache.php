<?php
/**
 * @Author: binghe
 * @Date:   2018-04-08 15:07:27
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:09:15
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\WmMainDbModels\WmSettingDao;
use Service\Exceptions\Exception;
/**
* 域名映射
*/
class CompanyDomainCache extends BaseCache
{
    /**
     * @param array $input 必需:domain
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
            $where=[];
            if(is_numeric($input['domain']))
            {
                $where['id'] = $input['domain'];
            }
            elseif(strpos($input['domain'], 'wx')===0)
            {
                $scrm_sdk = new \scrm_sdk('scrm_new');
                $params['appid'] = $input['domain'];
                $res = $scrm_sdk->getVisitIdByAppid($params);
                if(!isset($res['data']['visit_id']))
                    throw new Exception('visit_id 不存在');
                $where['visit_id'] = $res['data']['visit_id'];
                
            }
            else
            {
                $WmSettingDao = WmSettingDao::i();
                $m_wm_setting = $WmSettingDao->getOne(['domain' => $input['domain']], 'aid');
                if(!$m_wm_setting)
                    throw new Exception('域名未开通');
                    
                $where['id'] = $m_wm_setting->aid;
                
            }
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne($where,'id,visit_id');
            if(!$m_main_company)
                throw new Exception('公司不存在');
            $data['aid'] = $m_main_company->id;
            $data['visit_id'] = $m_main_company->visit_id; 
            return $data;
        });
    }
    public function getKey()
    {
        return "CompanyDomain:{$this->input['domain']}";
    }
}

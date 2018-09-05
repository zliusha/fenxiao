<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 11:49:01
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 17:29:05
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmMainDbModels\XcxAppDao;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\Exceptions\Exception;
/**
* 小程序appInfo
*/
class XcxAppInfoCache extends BaseCache
{
    /**
     * @param array $input 必需:app_id
     */
    public function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * @return array [aid,visit_id]
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
           	$appInfo=[];
            $xcxAppDao = XcxAppDao::i();
            $m_xcx_app = $xcxAppDao->getOne(['app_id'=>$input['app_id']]);
            if(!$m_xcx_app)
                throw new Exception("获取小程序信息失败,记录不存在");
            $appInfo['aid'] = $m_xcx_app->aid;
            //aid得到visit_id
            $mainCompanyDao = MainCompanyDao::i();
            $m_main_company = $mainCompanyDao->getOne(['id'=>$m_xcx_app->aid]);
            if(!$m_main_company)
                throw new Exception("获取小程序信息失败,公司不存在");
            $appInfo['visit_id']=$m_main_company->visit_id;
            return $appInfo;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "XcxAppInfo:{$this->input['app_id']}";
    }
}
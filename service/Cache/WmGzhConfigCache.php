<?php
/**
 * @Author: binghe
 * @Date:   2018-04-08 19:51:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:36:35
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\WmGzhConfigDao;
use Service\Exceptions\Exception;
/**
* gzh配置缓存
*/
class WmGzhConfigCache extends BaseCache
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
     * @return object $m_wm_gzh_config
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $wmGzhConfigCache = WmGzhConfigDao::i($input['aid']);
            $m_wm_gzh_config = $wmGzhConfigCache->getOne(['aid' => $input['aid']]);
            if(!$m_wm_gzh_config)
                throw new Exception('公众号信息未配置');
            return $m_wm_gzh_config;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "WmGzhConfig:{$this->input['aid']}";
    }
}
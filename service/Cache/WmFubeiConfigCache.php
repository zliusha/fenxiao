<?php

/**
 * @Author: binghe
 * @Date:   2018-06-14 15:23:59
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:35:09
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\WmFubeiConfigDao;
use Service\Exceptions\Exception;
/**
* gzh配置缓存
*/
class WmFubeiConfigCache extends BaseCache
{
    /**
     * @param array $input 必需:aid,feubei_id
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
            $wmFubeiConfigDao = WmFubeiConfigDao::i($input['aid']);
            $m_wm_fubei_config = $wmFubeiConfigDao->getOne(['aid' => $input['aid'],'id'=>$input['fubei_id']]);
            if(!$m_wm_fubei_config)
                throw new Exception('银行通道信息未配置');
            return $m_wm_fubei_config;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "WmFubeiConfig:{$this->input['aid']}_{$this->input['fubei_id']}";
    }
}
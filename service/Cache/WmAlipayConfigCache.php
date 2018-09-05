<?php

/**
 * @Author: binghe
 * @Date:   2018-06-14 14:17:03
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:28:32
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\WmAlipayConfigDao;
use Service\Exceptions\Exception;
/**
* gzh配置缓存
*/
class WmAlipayConfigCache extends BaseCache
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
            $wmAlipayConfigDao = WmAlipayConfigDao::i($input['aid']);
            $m_alipay_config = $wmAlipayConfigDao->getOne(['aid' => $input['aid']]);
            if(!$m_alipay_config)
                throw new Exception('支付宝信息未配置');
                
            return $m_alipay_config;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "WmAlipayConfig:{$this->input['aid']}";
    }
}
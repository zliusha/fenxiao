<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 13:15:41
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 17:34:37
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\Exceptions\Exception;
/**
* dianwoda 门店缓存
*/
class WmShopCache extends BaseCache
{
    /**
     * @param array $input 必需:aid,shop_id
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
            $wmShopDao = WmShopDao::i($input['aid']);
            $m_wm_shop   = $wmShopDao->getOne(['aid' => $input['aid'], 'id' => $input['shop_id']]);
            if(!$m_wm_shop)
                throw new Exception("门店不存在");
            return $m_wm_shop;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "WmShop:{$this->input['aid']}_{$this->input['shop_id']}";
    }
}
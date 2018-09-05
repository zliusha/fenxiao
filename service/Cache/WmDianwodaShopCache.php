<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 13:06:43
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:33:50
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\WmDianwodaShopDao;
use Service\Exceptions\Exception;
/**
* dianwoda 门店缓存
*/
class WmDianwodaShopCache extends BaseCache
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
            $wmDianwodaShopDao = WmDianwodaShopDao::i($input['aid']);
            $m_wm_dianwoda_shop = $wmDianwodaShopDao->getOne(['aid' => $input['aid'], 'shop_id' => $input['shop_id']]);
            if(!$m_wm_dianwoda_shop)
                throw new Exception('点我达门店信息未配置');
            return $m_wm_dianwoda_shop;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "WmDianwodaShop:{$this->input['aid']}_{$this->input['shop_id']}";
    }
}
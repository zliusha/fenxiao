<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 12:59:19
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:33:02
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\WmDadaShopDao;
use Service\Exceptions\Exception;
/**
* dada 门店缓存
*/
class WmDadaShopCache extends BaseCache
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
            $WmDadaShopDao = WmDadaShopDao::i($input['aid']);
            $m_wm_dada_shop = $WmDadaShopDao->getOne(['aid' => $input['aid'], 'shop_id' => $input['shop_id']]);
            if(!$m_wm_dada_shop)
                throw new Exception('达达门店信息未配置');
                
            return $m_wm_dada_shop;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "WmDadaShop:{$this->input['aid']}_{$this->input['shop_id']}";
    }
}
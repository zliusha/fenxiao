<?php
/**
 * @Author: binghe
 * @Date:   2018-04-12 16:45:54
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:24:44
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaTableDao;
use Service\Exceptions\Exception;
/**
*  桌位缓存
*/
class MealShopAreaTableCache extends BaseCache
{
    /**
     * @param array $input 必需:aid,table_id,code
     */
    public function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * @return object $m_meal_shop_area_table
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $mealShopAreaTableDao = MealShopAreaTableDao::i($input['aid']);
            $m_table = $mealShopAreaTableDao->getOne(['id' => $input['table_id']]);
            if(!$m_table || $m_table->code != $input['code'])
                throw new Exception('桌位二维码已失效');
                
            return $m_table;
        });
    }
    public function getKey()
    {
        return "MealShopAreaTable:{$this->input['table_id']}";
    }
}
<?php
/**
 * @Author: binghe
 * @Date:   2018-04-25 20:24:31
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-02 17:03:57
 */
use Service\Cache\MealShopAreaTableCache;
/**
* table
*/
class Test_table extends base_controller
{
    
    public function tableCacheTest()
    {
        $mealShopAreaTableCache = new MealShopAreaTableCache(['aid'=>1226,'table_id' => 13, 'code' => '4AC5CD']);
        $m_table = $mealShopAreaTableCache->getDataASNX();
        var_dump($m_table);
        $mealShopAreaTableCache->delete();
        $m2_table = $mealShopAreaTableCache->getDataASNX();
        var_dump($m2_table);
    }
}
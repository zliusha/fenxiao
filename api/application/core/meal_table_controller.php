<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 15:14:47
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:25:17
 */
use Service\Cache\ApiAccessTokenCache;
use Service\Cache\CompanyDomainCache;
use Service\Cache\MealShopAreaTableCache;
use Service\Cache\WmGzhConfigCache;

/**
 * 点餐
 */
class meal_table_controller extends meal_controller
{

    public function __construct()
    {
        parent::__construct();
        //加载桌位
        $this->_init_table();
    }
    /**
     * 初始化桌位信息
     */
    private function _init_table()
    {
        $table_id = (int) $this->input->post_get('table_id');
        $code = trim($this->input->post_get('code'));

        if ($table_id && $code) {
            $mealShopAreaTableCache = new MealShopAreaTableCache(['aid'=>$this->aid,'table_id' => $table_id, 'code' => $code]);
            $m_table = $mealShopAreaTableCache->getDataASNX();

            $this->table = $m_table;
        }
    }

}

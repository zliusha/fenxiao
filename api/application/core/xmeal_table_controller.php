<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/17
 * Time: 10:08
 */
use Service\Cache\MealShopAreaTableCache;
class xmeal_table_controller extends xmeal_controller
{

    function __construct()
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
            // 临时处理
            if (is_object($m_table)) {
                $this->table = $m_table;
            } else {
                log_message('error', __METHOD__.'---'.json_encode($m_table));
                $this->table = unserialize($m_table);
            }
        }
    }
}
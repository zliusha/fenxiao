<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 09:55:44
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 10:55:59
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 公司
 */
class MealShopAreaTableDao extends BaseDao
{
	
    /**
     *  更新桌位状态, 待审核订单数量
     * @param int $order_table_id
     * @param int $aid
     * @return bool
     */
    public function updateStatus($order_table_id=0, $aid=0)
    {
        $m_table = $this->getOne(['cur_order_table_id' => $order_table_id, 'aid' => $aid]);
        if(!$m_table || $m_table->cur_order_table_id == 0)
        {
            log_message('error', __METHOD__.'-order_table_id-'.$order_table_id.'-aid-'.$aid);
            return false;
        }

        $mealOrderDao = MealOrderDao::i($aid);
        $mealOrderTableDao = MealOrderTableDao::i($aid);
        $order_count = $mealOrderDao->getCount(['aid' => $aid, 'order_table_id' => $m_table->cur_order_table_id, 'api_status'=>1]);

        // log_message('error', __METHOD__.'-$order_count-'.$order_count);
        //存在待审核订单 点餐中
        if($order_count > 0)
        {
            $this->update(['status' => 1], ['id' => $m_table->id, 'aid' => $aid]);
            $mealOrderTableDao->update(['status' => 1, 'order_count' => $order_count], ['id' => $m_table->cur_order_table_id, 'aid' => $aid]);
        }
        else//就餐中
        {
            $this->update(['status' => 2], ['id' => $m_table->id, 'aid' => $aid]);
            $mealOrderTableDao->update(['status' =>2, 'order_count' => $order_count], ['id' => $m_table->cur_order_table_id, 'aid' => $aid]);
        }

        return true;
    }
}
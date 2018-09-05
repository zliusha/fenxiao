<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/3
 * Time: 16:17
 */
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealPaymentRecordDao;

class Meal_statements_api extends wm_service_controller
{

    /**
     * 流水列表
     */
    public function record_list()
    {
        $shop_id = (int)$this->input->post_get('shop_id');
        if(!is_numeric($shop_id))
            $this->json_do->set_error('001', '参数错误');

        if(!$this->is_zongbu)
            $shop_id = $this->currentShopId;

        $where['aid'] = $this->s_user->aid;
        //如果shop_id 则增加刷选条件
        if($shop_id > 0)
            $where['shop_id'] = $shop_id;

        $meal_statements_bll = new meal_statements_bll();
        $_data = $meal_statements_bll->mealQuery($where);
        $list = $_data['rows'];

        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $mealShopAreaDao = MealShopAreaDao::i($this->s_user->aid);
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        //订单关联桌位记录列表
        $order_table_ids = array_unique(array_column($list, 'order_table_id'));
        $order_table_ids = empty($order_table_ids) ? [0] : $order_table_ids;
        $table_record_list = $mealOrderTableDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid}", 'where_in' => ['id' => $order_table_ids]], true);

        //关联桌位列表
        $table_ids = array_unique(array_column($table_record_list, 'table_id'));
        $table_ids = empty($table_ids) ? [0] : $table_ids;
        $table_list = $mealShopAreaTableDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid}", 'where_in' => ['id' => $table_ids]], true);

        //关联区域列表
        $area_ids = array_unique(array_column($table_list, 'shop_area_id'));
        $area_ids = empty($area_ids) ? [0] : $area_ids;
        $area_list = $mealShopAreaDao->getEntitysByAR([ "where" => "aid={$this->s_user->aid}", 'where_in' => ['id' => $area_ids]], true);


        foreach($list as $key => $meal_statement)
        {
            $area_name = '';
            $area_table_name = '';

            //获取订单桌位信息
            $table_record = array_find($table_record_list, 'id', $meal_statement['order_table_id']);
            if(isset($table_record['table_id']))
            {
                $table = array_find($table_list, 'id', $table_record['table_id']);
                $area_table_name = $table['name'];
            }

            if(isset($table['shop_area_id']))
            {
                $area = array_find($area_list, 'id', $table['shop_area_id']);
            }
            if(isset($area['name']))
            {
                $area_name = $area['name'];
            }

            //区域桌位信息
            $list[$key]['area_name'] = $area_name;
            $list[$key]['area_table_name'] = $area_table_name;
            $list[$key]['meal_order'] = $table_record;


        }
        $data['rows'] = $list;
        $data['total'] = $_data['total'];

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 流水详情
     */
    public function info()
    {
        $serial_number = $this->input->post_get('serial_number');

        $meal_statements_bll = new meal_statements_bll();
        $meal_statement = $meal_statements_bll->detail(['aid' => $this->s_user->aid,  'serial_number' => $serial_number]);
        if($meal_statement)
        {

            $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
            $m_order_table = $mealOrderTableDao->getOne(['aid'=>$this->s_user->aid, 'id' => $meal_statement->order_table_id]);
            $data['order_table'] = $m_order_table;

        }
        $data['statement_info'] = $meal_statement;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
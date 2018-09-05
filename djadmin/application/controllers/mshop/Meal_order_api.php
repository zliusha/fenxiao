<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/3
 * Time: 16:19
 */
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
class Meal_order_api extends wm_service_controller
{

    /**
     * 审核订单
     */
    public function audit_order()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required|preg_key[IDS]'],
            ['field' => 'order_table_id', 'label' => '桌位记录ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $tids = trim($f_data['tid'], ',');
        $order_table_id = $f_data['order_table_id'];

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        //获取有效的待审核订单
        $meal_order_list = $mealOrderDao->getAllArray("aid={$this->s_user->aid}  AND order_table_id={$order_table_id} AND api_status=1 AND tid in ({$tids})", "id,api_status,order_table_id,tid,shop_id");

        if(empty($meal_order_list))
        {
            $this->json_do->set_error('001', '当前状态记录不存在');
        }
        //获取桌位ID

        $m_order_table = $mealOrderTableDao->getOne(['aid'=>$this->s_user->aid, 'id'=>$meal_order_list[0]['order_table_id']]);


        //审核
        $meal_order_event_bll = new meal_order_event_bll();
        foreach($meal_order_list as $meal_order)
        {
            $input = ['tradeno' => $meal_order['tid'], 'aid' => $this->s_user->aid, 'table_id' => $m_order_table->table_id];
            $meal_order_event_bll->sellerAgreeOrder($input, true);
        }

        //更新桌位状态信息
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $mealShopAreaTableDao->updateStatus($m_order_table->id, $m_order_table->aid);

        $this->json_do->set_msg('审核成功');
        $this->json_do->out_put();
    }

    /**
     * 拒单
     */
    public function refuse_order()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required|preg_key[IDS]'],
            ['field' => 'order_table_id', 'label' => '桌位记录ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $tids = trim($f_data['tid'], ',');
        $order_table_id = $f_data['order_table_id'];

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);

        //获取有效的待审核订单
        $meal_order_list = $mealOrderDao->getAllArray("aid={$this->s_user->aid}  AND order_table_id={$order_table_id} AND api_status=1 AND tid in ({$tids})", "id,api_status,order_table_id,tid,shop_id");

        if(empty($meal_order_list))
        {
            $this->json_do->set_error('001', '当前状态记录不存在');
        }
        //获取桌位ID
        $m_order_table = $mealOrderTableDao->getOne(['aid'=>$this->s_user->aid,  'id'=>$meal_order_list[0]['order_table_id']]);

        //取消
        $meal_order_event_bll = new meal_order_event_bll();
        foreach($meal_order_list as $meal_order)
        {
            $input = ['tradeno' => $meal_order['tid'], 'aid' => $this->s_user->aid,  'table_id' => $m_order_table->table_id];
            $meal_order_event_bll->sellerRefuseOrder($input, true);
        }

        //更新桌位状态信息
        $mealShopAreaTableDao = MealShopAreaTableDao::i($this->s_user->aid);
        $mealShopAreaTableDao->updateStatus($m_order_table->id, $m_order_table->aid);

        $this->json_do->set_msg('取消成功');
        $this->json_do->out_put();
    }
}
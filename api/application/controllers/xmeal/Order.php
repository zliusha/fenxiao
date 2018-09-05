<?php
/**
 * 订单操作
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
class Order extends xmeal_table_controller
{
    // 已下单列表
    public function index()
    {
        // $rule = [
        //     // ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
        //     // ['field' => 'code', 'label' => '防刷码', 'rules' => 'trim|required|numeric'],
        // ];
        // $this->check->check_ajax_form($rule);
        // $fdata = $this->form_data($rule);

        $meal_order_query_bll = new meal_order_query_bll();
        $order = $meal_order_query_bll->simple_query(['order_table_id' => $this->table->cur_order_table_id,'aid'=>$this->aid])->get();

        // 读取当前桌位记录信息
        $mealOrderTableDao = MealOrderTableDao::i($this->aid);
        $order['order_table'] = $mealOrderTableDao->getOneArray(['id' => $this->table->cur_order_table_id]);

        // 获取最近下单时间
        $order['last_order_time'] = 0;
        foreach ($order['rows'] as $key => $row) {
            if ($row['time']['value'] > $order['last_order_time']) {
                $order['last_order_time'] = $row['time']['value'];
            }
        }
        $order['last_order_time'] = date('Y-m-d H:i:s', $order['last_order_time']);

        $this->json_do->set_data($order);
        $this->json_do->out_put();
    }

    // 点餐下单
    public function create()
    {
        // $rule = [
        //     // ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
        //     // ['field' => 'code', 'label' => '防刷码', 'rules' => 'trim|required|numeric'],
        //     // ['field' => 'items', 'label' => '订单JSON', 'rules' => 'trim|required'],
        // ];
        // $this->check->check_ajax_form($rule);
        // $input = $this->form_data($rule);

        $input['open_id'] = $this->s_user->openid;
        $input['nickname'] = $this->s_user->nickname;
        $input['headimg'] = $this->s_user->headimg;
        $input['table_id'] = $this->table->id;
        $input['aid'] = $this->aid;
        $meal_order_event_bll = new meal_order_event_bll();
        $meal_order_event_bll->orderCreate($input);
    }

}

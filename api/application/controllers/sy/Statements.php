<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/10
 * Time: 16:38
 */
use Service\DbFrame\DataBase\WmShardDbModels\MealStatementsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderDao;
class Statements extends sy_controller
{
    /**
     * t1流水列表
     */
    public function record_list()
    {
        $rule = [
            ['field' => 'source_type', 'label' => '流水类型', 'rules' => 'trim|required|numeric']

        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $meal_statements_bll = new meal_statements_bll();
        $data = [];
        switch($f_data['source_type']) {
            case 1: // 点餐
                $data = $meal_statements_bll->mealQuery(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id]);
                break;
            case 2: //外卖
                break;
            case 3: //零售
                $data = $meal_statements_bll->retailQuery(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id]);
                break;
            default;
                break;
        }

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 流水详情
     */
    public function detail()
    {
        $rule = [
            ['field' => 'serial_number', 'label' => '流水单号', 'rules' => 'trim|required']

        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $meal_statements_bll = new meal_statements_bll();

        $data = $meal_statements_bll->detail(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'serial_number' => $f_data['serial_number']]);

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


    /**
     * 退款
     */
    public function refund()
    {
        $rule = [
            ['field' => 'serial_number', 'label' => '流水单号', 'rules' => 'trim|required'],
            ['field' => 'refund_type', 'label' => '退款类型', 'rules' => 'trim|required|numeric'],
            ['field' => 'money', 'label' => '实际退款金额', 'rules' => 'trim|required|numeric'],
            ['field' => 'pay_type', 'label' => '支付类型', 'rules' => 'trim|required|numeric'],
            ['field' => 'pay_source', 'label' => '操作渠道', 'rules' => 'trim|required|numeric'],
            ['field' => 'ext_data', 'label' => '附加信息', 'rules' => 'trim']

        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $f_data['aid'] = $this->s_user->aid;
        $f_data['gateway'] = $f_data['pay_type'];

        if($f_data['pay_type'] == 0)//判断是否原路退款
        {
            $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
            $m_meal_statements = $mealStatementsDao->getOne(['aid' => $this->s_user->aid, 'serial_number' => $f_data['serial_number']]);
            if (!$m_meal_statements)
            {

                log_message('error', __METHOD__ . '相关流水记录不存在:' . @json_encode($f_data));
                $this->json_do->set_error('005', '相关流水记录不存在');
            }

            $f_data['gateway'] = $m_meal_statements->gateway;
        }


        $meal_statements_bll = new meal_statements_bll();
        $return = $meal_statements_bll->refund($f_data);

        if($return)
        {
            $this->json_do->set_msg('成功');
            $this->json_do->out_put();
        }
        $this->json_do->set_error('005', '失败');
    }

    /**
     * 打印信息
     */
    public function println()
    {
        $rule = [
            ['field' => 'serial_number', 'label' => '流水单号', 'rules' => 'trim|required']

        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $meal_statements_bll = new meal_statements_bll();
        $m_meal_statement = $meal_statements_bll->detail(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'serial_number' => $f_data['serial_number']]);

        //判断是否已经打印
        if(!$m_meal_statement->is_print)
        {
            $_data['is_print'] = 1;
            $_data['print_sn'] = create_serial_number('P');
            $_data['print_time'] = time();

            $m_meal_statement->is_print = $_data['is_print'];
            $m_meal_statement->print_sn = $_data['print_sn'];
            $m_meal_statement->print_time = $_data['print_time'];

            $mealStatementsDao = MealStatementsDao::i($this->s_user->aid);
            $mealStatementsDao->update($_data, ['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'serial_number' => $f_data['serial_number']]);
        }

        $mealOrderDao = MealOrderDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $retailOrderDao = RetailOrderDao::i($this->s_user->aid);

        switch($m_meal_statement->source_type){
            case 1://点餐
                $data['total_money'] = $mealOrderDao->getSum('pay_money', ['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'pay_trade_no' => $m_meal_statement->order_number]);
                $m_meal_order = $mealOrderTableDao->getOne(['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'id' => $m_meal_statement->order_table_id]);
                $data['table_name'] = isset($m_meal_order)?$m_meal_order->table_name:'';
                $data['area_name'] = isset($m_meal_order)?$m_meal_order->area_name:'';
                break;
            case 2:
                break;
            case 3://零售
                $data['total_money'] = $retailOrderDao->getSum('pay_money', ['aid' => $this->s_user->aid, 'shop_id' => $this->s_user->shop_id, 'pay_trade_no' => $m_meal_statement->order_number]);
                break;
            default;
                break;
        }

        $data['is_print'] = $m_meal_statement->is_print;
        $data['print_sn'] = $m_meal_statement->print_sn;
        $data['print_time'] = date('Y-m-d H:i:s', $m_meal_statement->print_time);
        $data['time'] = $m_meal_statement->time;
        $data['gateway'] = $m_meal_statement->gateway;
        $data['amount'] = $m_meal_statement->amount;
        $data['goods_list'] = $m_meal_statement->order_ext;

        //店铺信息
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $m_shop = $wmShopDao->getOne(['aid' => $this->s_user->aid, 'id' => $this->s_user->shop_id], 'id,shop_name');
        $data['shop_name'] = isset($m_shop->shop_name) ? $m_shop->shop_name : '';
        $data['username'] = $this->s_user->username;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }


}
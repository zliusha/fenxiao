<?php
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/3
 * Time: 16:17
 */
class Statements_api extends wm_service_controller
{

    /**
     * 流水列表
     */
    public function record_list()
    {
        $shop_id = (int)$this->input->post_get('shop_id');
        $source_type = (int)$this->input->post_get('source_type');
        if(!is_numeric($shop_id))
            $this->json_do->set_error('001', '参数错误');

        if(!$this->is_zongbu)
            $shop_id = $this->currentShopId;

        $where['aid'] = $this->s_user->aid;
        //如果shop_id 则增加刷选条件
        if($shop_id > 0)
            $where['shop_id'] = $shop_id;


        $meal_statements_bll = new meal_statements_bll();
        $data = [];
        switch($source_type) {
            case 1: // 点餐
                $data = $meal_statements_bll->mealQuery(['aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
                break;
            case 2: //外卖
                break;
            case 3: //零售
                $data = $meal_statements_bll->retailQuery(['aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
                break;
            default;
                break;
        }
//        $data['rows'] = $list;

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
        if($meal_statement && $meal_statement->source_type==1)
        {
            $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
            $m_order_table = $mealOrderTableDao->getOne(['aid'=>$this->s_user->aid, 'id' => $meal_statement->order_table_id]);
            $data['order_table'] = $m_order_table;

        }
        $data['statement_info'] = $meal_statement;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     *  堂食，零售流水导出
     */
    public function export()
    {

        $shop_id = (int)$this->input->post_get('shop_id');
        $source_type = (int)$this->input->post_get('source_type');
        if(!is_numeric($shop_id))
            $this->json_do->set_error('001', '参数错误');

        if(!$this->is_zongbu)
            $shop_id = $this->currentShopId;

        $where['aid'] = $this->s_user->aid;
        //如果shop_id 则增加刷选条件
        if($shop_id > 0)
            $where['shop_id'] = $shop_id;


        $meal_statements_bll = new meal_statements_bll();
        $order = [];
        $filename = 'Order';
        switch($source_type) {
            case 1: // 点餐
                $order = $meal_statements_bll->mealQuery(['aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
                $filename = 'Dc'.$filename;
                break;
            case 2: //外卖
                break;
            case 3: //零售
                $order = $meal_statements_bll->retailQuery(['aid' => $this->s_user->aid, 'shop_id' => $shop_id]);
                $filename = 'Ls'.$filename;
                break;
            default;
                break;
        }
        $data = [];
        foreach ($order['rows'] as $key => $row) {
            $_row = [];
            $_total_money = 0;
//             foreach ($row['order_ext'] as $order_ext) {
//                 $_total_money += $order_ext['order_money'];
//             }
            // $_row['goods_name'] = implode('|', $_row['goods_name']);
            $_row['serial_number'] = ' '.$row['serial_number'];
            $_row['shop_name'] = $row['shop_name'];
            $_row['time'] = $row['time']['alias'];
            //订单总金额 = 商品金额+餐盒+运费
            $_row['amount'] = ' '.$row['amount'];
//            $_row['discount_money'] = ' '.$row['discount_money'];
//            $_row['total_money'] = ' '.$_total_money;
            $_row['refund_money'] = ' '.$row['refund_money'];
            $_row['status'] = $row['status']['alias'];
            $_row['type'] = $row['type']['alias'];
            $_row['gateway']  = empty($row['gateway']['alias'])?'-':$row['gateway']['alias'];
            array_push($data, $_row);
        }



        // 字段，一定要以$data字段顺序
        $fields = [
            // 'goods_name' => '商品名称',
            'serial_number' => '流水编号',
            'shop_name' => '所属门店',
//            'total_money' => '订单总金额',
            'amount'=>'金额',
//            'discount_money'=>'优惠金额',
            'refund_money' => '退款金额',
            'gateway' => '流水渠道',
//            'status' => '状态',
            'type' => '流水类型',
            'time' => '记录时间',
        ];
        // 文件名尽量使用英文
        $filename = $filename.'_'  . date('Y-m-d') . '_' . mt_rand(100, 999);
        ob_end_clean();
        ci_phpexcel::down($fields, $data, $filename);

    }

}
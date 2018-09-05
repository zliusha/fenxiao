<?php
use Service\Cache\WmPrintConfigCache;
/**
 * 微商城（外卖版）订单打印机
 * @author dadi
 *
 */
class wm_print_order_bll extends base_bll
{
    // 订单对象
    public $order = null;
    // json_do对象
    public $json_do = null;

    public function __construct()
    {
        parent::__construct();
        $this->json_do = new json_do();
    }

    // 输出结果 带解锁功能
    public function respond()
    {
        return $this->respond;
    }
    /**
     * 打印订单
     * @param  array $input   输入信息 必填tradeno,aid
     * @param  boolean $auto_print 为false代表手工调用打印,json_do输出
     * @return [type]          [description]
     */
    public function printOrder($input = [], $auto_printer = false)
    {

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query($input)->get();
        //条件:判断订单是否重复
        if (count($order['rows']) > 1) {
            log_message('error', __METHOD__ . '--打印错误，存在重复订单号 => ' . $input['tradeno']);
        }

        $order = $order['rows'][0];
        //条件:判断订单是否存在
        if (!isset($order)) {
            if ($auto_printer) {
                log_message('error', __METHOD__ . '--打印错误，订单不存在 => ' . $input['tradeno']);
                return false;
            } else {
                $this->json_do->set_error('004', '订单不存在');
            }
        }

       

        $wm_print_bll = new wm_print_bll;
        // 打印机缓存2小时
        $wmPrintConfigCache = new WmPrintConfigCache(['aid'=>$order['aid'],'shop_id'=>$order['shop_id']]);
        $print_config = $wmPrintConfigCache->getDataASNX();

        //条件:判断门店打印机是否设置
        if (!$print_config) {
            if ($auto_printer) {
                log_message('error', __METHOD__ . '--打印机参数未设置,门店id:' . $order['shop_id']);
                return false;
            } else {
                $this->json_do->set_error('004', '打印机参数未设置');
            }
        }
        //usb打印
        if($print_config['type'] == 2)
        {
            //自动打印 调用通知
            if ($auto_printer) {
                $worker_bll = new worker_bll;
                $winput['aid'] = $order['aid'];
                $winput['shop_id'] = $order['shop_id'];
                $winput['tradeno'] = $order['tid'];
                $worker_bll->transpondAgreeOrder($winput);
                log_message('error', __METHOD__ . '--' . "usb打印通知成功,订单号:{$order['tid']},门店id:{$order['shop_id']}");
                return true;
            } else {    //手工打印,由客户端调用打印机
                $data['print_type'] = $print_config['type'];
                $data['print_times'] = $print_config['times'];
                $this->json_do->set_data($data);
                $this->json_do->out_put();
            }
        }
        $params['deviceNo'] = $print_config['print_deviceno'];
        $params['key'] = $print_config['print_key'];
        $c_params['shop_name'] = $order['shop_name'];
        $c_params['total_money'] = $order['pay_money'];
        $c_params['order_time'] = $order['time']['value'];
        $c_params['goods'] = [];
        foreach ($order['order_ext'] as $key => $row) {
            $c_params['goods'][] = [
                'name' => $row['goods_title'],
                'num' => $row['num'],
                'price' => $row['price'],
            ];
        }
        // $c_params['goods'] = [
        //     ['name' => '米饭', 'num' => 3, 'price' => 2],
        //     ['name' => '手撕鸡a', 'num' => 1, 'price' => 48],
        // ];
        $c_params['user'] = [
            'address' => $order['receiver_site'] . ' ' . $order['receiver_address'],
            'name' => $order['receiver_name'] . $order['sex'],
            'phone' => $order['receiver_phone'],
        ];
        $c_params['package_money'] = $order['package_money'];
        $c_params['freight_money'] = $order['freight_money'];
        $c_params['other_items'] = [];
        if (isset($order['discount_detail'])) {
            // 满减优惠
            if ($order['discount_detail']['manjian']['reduce_price'] > 0) {
                $c_params['other_items'][] = ['name' => '满减优惠', 'value' => '-' . $order['discount_detail']['manjian']['reduce_price']];
            }
            // 新人优惠
            if ($order['discount_detail']['xinren'] > 0) {
                $c_params['other_items'][] = ['name' => '新人优惠', 'value' => '-' . $order['discount_detail']['xinren']];
            }
            // 会员优惠
            if ($order['discount_detail']['huiyuan'] > 0) {
                $c_params['other_items'][] = ['name' => '会员优惠', 'value' => '-' . $order['discount_detail']['huiyuan']];
            }
            // 提示信息
            if (!empty($order['discount_detail']['tips'])) {
                $c_params['other_items'][] = ['name' => $order['discount_detail']['tips'], 'value' => ''];
            }
        }
        // $c_params['other_items'] = $order['freight_money'];
        $content = $wm_print_bll->get_order_print_content($c_params);
        $params['printContent'] = $content;
        $params['times'] = $print_config['times'];
        //达达发单
        $result_json = $wm_print_bll->request(wm_print_bll::ADD_ORDER_URL, $params);
        if (isset($result_json['responseCode']) && $result_json['responseCode'] == 0) {
            log_message('error', __METHOD__ . '--' . "365发送订单成功,订单号:{$order['tid']},门店id:{$order['shop_id']}");
        } else {
            log_message('error', __METHOD__ . '--' . "365发送订单失败,订单号:{$order['tid']},门店id:{$order['shop_id']}");
            log_message('error', __METHOD__ . '--' . "365发送订单失败-" . json_encode($result_json));
        }
        if ($result_json) {
            if ($auto_printer) {
                return true;
            } else {
                $data['print_type'] = $print_config->type;
                $data['print_times'] = $print_config->times;
                $this->json_do->set_data($data);
                $this->json_do->out_put();
            }
        }

    }

}

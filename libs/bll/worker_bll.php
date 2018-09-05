<?php
use Service\Cache\WmPrintConfigCache;

/**
 * @Author: binghe
 * @Date:   2018-03-14 10:03:36
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-20 17:06:18
 */
/**
 * worker bll  使用此方法,rooms为内置键,不能使用
 */
class worker_bll extends base_bll
{
    //来源
    const SOURCE_YDB_MANAGE = 'ydb_manage';
    const SOURCE_YDB_MEAL = 'ydb_meal';
    const SOURCE_YDB_SY = 'ydb_sy';
    const SOURCE_YDB_XCX = 'ydb_xcx';
    const SOURCE_YDB_MEAL_XCX = 'ydb_meal_xcx';
    const SOURCE_YDB_MSHOP = 'ydb_mshop';

    //外卖新订单通知
    const ORDER_NOTIFY = 'order_notify';
    //外卖订单打印
    const ORDER_PRINTER = 'order_printer';
    //ROOM 拼单购物车变动
    const ROOM_PINDAN_CART_CHANGE = 'room_pindan_cart_change';

    //扫码购物车变动
    const MEAL_CART_CHANGE = 'meal_cart_change';
    //扫码新订单
    const MEAL_NEW_ORDER = 'meal_new_order';
    //t1订单审核
    const MEAL_ORDER_CHANGE = 'meal_order_change';
    //扫码清桌
    const MEAL_TABLE_CLEAR = 'meal_table_clear';
    //结算支付通知
    const MEAL_ORDER_PAY = 'meal_order_pay';

    /**
     * 新订单转发通知门店
     * @param  array $input 必需:aid,shop_id,tradeno
     * @return [type]        [description]
     */
    public function transpondNewOrder($input)
    {
        //转发数据
        $content['type'] = self::ORDER_NOTIFY; //转发类型 扩展使用
        $content['aid'] = $input['aid'];
        $content['shop_id'] = $input['shop_id'];
        $content['tid'] = $input['tradeno'];

        //转发规则
        $data['rules'] = [
            ['rule' => 'in', 'field' => 'source', 'value' => [self::SOURCE_YDB_MANAGE, self::SOURCE_YDB_SY]],
            ['rule' => 'equal', 'field' => 'aid', 'value' => $input['aid']],
            ['rule' => 'equal', 'field' => 'shop_id', 'value' => $input['shop_id']],
        ];
        $data['content'] = json_encode($content);

        $worker_sdk = new worker_sdk;
        $worker_sdk->send($data);
    }
    /**
     * 商家接单转发订单
     * @param  array $input 必需:aid,shop_id,tradeno
     * @return [type]        [description]
     */
    public function transpondAgreeOrder($input)
    {
        // 打印机缓存2小时
        $wmPrintConfigCache = new WmPrintConfigCache(['aid' => $input['aid'], 'shop_id' => $input['shop_id']]);
        $print_config = $wmPrintConfigCache->getDataASNX();

        $print_type = 0; //打印类型 0未配置,1 365打印机 2usb打印机
        $print_times = 0; //打印联数
        if ($print_config) {
            $print_type = $print_config['type'];
            $print_times = $print_config['times'];
        }
        $content['type'] = self::ORDER_PRINTER; //转发类型 扩展使用
        $content['aid'] = $input['aid'];
        $content['shop_id'] = $input['shop_id'];
        $content['tid'] = $input['tradeno'];
        $content['print_type'] = $print_type;
        $content['print_times'] = $print_times;

        //转发规则
        $data['rules'] = [
            ['rule' => 'in', 'field' => 'source', 'value' => [self::SOURCE_YDB_MANAGE, self::SOURCE_YDB_SY]],
            ['rule' => 'equal', 'field' => 'aid', 'value' => $input['aid']],
            ['rule' => 'equal', 'field' => 'shop_id', 'value' => $input['shop_id']],
        ];
        $data['content'] = json_encode($content);

        $worker_sdk = new worker_sdk;
        $worker_sdk->send($data);
    }
    /**
     * 扫码点餐购物车变更 添加/删除 购物车商品
     * @param  array $input 必需:aid,shop_id,openid,nickname,table_id
     * openid nickname 当前操作用户openid和昵称
     * table_id 桌位id
     * @return [type]        [description]
     */
    public function mealCartChange($input)
    {
        $content['nickname'] = $input['nickname'];
        $content['type'] = self::MEAL_CART_CHANGE;
        //转发规则
        $data['rules'] = [
            ['rule' => 'in', 'field' => 'source', 'value' => [self::SOURCE_YDB_MEAL, self::SOURCE_YDB_MEAL_XCX]],
            ['rule' => 'equal', 'field' => 'aid', 'value' => $input['aid']],
            ['rule' => 'equal', 'field' => 'shop_id', 'value' => $input['shop_id']],
            ['rule' => 'notequal', 'field' => 'openid', 'value' => $input['openid']],
            ['rule' => 'equal', 'field' => 'table_id', 'value' => $input['table_id']],
        ];
        $data['content'] = json_encode($content);
        $worker_sdk = new worker_sdk;
        $worker_sdk->send($data);
    }
    /**
     * 扫码点餐购物车变更 下单
     * @param  array $input 必需:aid,shop_id,openid,nickname,table_id,table_name
     * 参数说明:
     * openid nickname 当前操作用户openid和昵称
     * table_id 桌位id
     * @param filterSource string or array 过滤来源
     * @return [type]        [description]
     */
    public function mealNewOrder($input, $filterSource = '')
    {
        $content['nickname'] = $input['nickname'];
        $content['type'] = self::MEAL_NEW_ORDER;
        $content['table_name'] = $input['table_name'];
        //过滤来源
        $toSources = [self::SOURCE_YDB_MEAL, self::SOURCE_YDB_SY, self::SOURCE_YDB_MEAL_XCX];
        if (!empty($filterSource)) {
            $toSources = array_filter($toSources, function ($item) use ($filterSource) {
                is_array($filterSource) ? $result = in_array($item, $filterSource) : $result = $filterSource == $item;
                return !$result;
            });

        }
        //转发至h5端规则
        $data['rules'] = [
            ['rule' => 'in', 'field' => 'source', 'value' => $toSources],
            ['rule' => 'equal', 'field' => 'aid', 'value' => $input['aid']],
            ['rule' => 'equal', 'field' => 'shop_id', 'value' => $input['shop_id']],

        ];
        $data['source_rules'] = [
            self::SOURCE_YDB_MEAL => [
                ['rule' => 'notequal', 'field' => 'openid', 'value' => $input['openid']],
                ['rule' => 'equal', 'field' => 'table_id', 'value' => $input['table_id']],
            ],
            self::SOURCE_YDB_MEAL_XCX => [
                ['rule' => 'notequal', 'field' => 'openid', 'value' => $input['openid']],
                ['rule' => 'equal', 'field' => 'table_id', 'value' => $input['table_id']],
            ],
        ];
        $data['content'] = json_encode($content);
        $worker_sdk = new worker_sdk;
        $worker_sdk->send($data);
    }
    /**
     * 后台改变扫码扫码消息
     * @param  array $input 必需:aid,shop_id,table_id
     * table_id 桌位id
     * @return [type]        [description]
     */
    public function mealOrderChange($input)
    {
        $content['type'] = self::MEAL_ORDER_CHANGE;
        //转发至h5端规则
        $data['rules'] = [
            ['rule' => 'in', 'field' => 'source', 'value' => [self::SOURCE_YDB_MEAL, self::SOURCE_YDB_MEAL_XCX]],
            ['rule' => 'equal', 'field' => 'aid', 'value' => $input['aid']],
            ['rule' => 'equal', 'field' => 'shop_id', 'value' => $input['shop_id']],
            ['rule' => 'equal', 'field' => 'table_id', 'value' => $input['table_id']],

        ];

        $data['content'] = json_encode($content);
        $worker_sdk = new worker_sdk;
        $worker_sdk->send($data);
    }
    /**
     * 扫码点餐 清桌
     * @param  array $input 必需:aid,shop_id,openid,table_id
     * table_id 桌位id
     * @return [type]        [description]
     */
    public function mealTableClear($input)
    {
        $content['table_id'] = $input['table_id'];
        $content['type'] = self::MEAL_TABLE_CLEAR;
        //转发规则
        $data['rules'] = [
            ['rule' => 'in', 'field' => 'source', 'value' => [self::SOURCE_YDB_MEAL, self::SOURCE_YDB_MEAL_XCX]],
            ['rule' => 'equal', 'field' => 'aid', 'value' => $input['aid']],
            ['rule' => 'equal', 'field' => 'shop_id', 'value' => $input['shop_id']],
            ['rule' => 'equal', 'field' => 'table_id', 'value' => $input['table_id']],
        ];
        $data['content'] = json_encode($content);
        $worker_sdk = new worker_sdk;
        $worker_sdk->send($data);
    }

    /**
     * 直接结算
     * @param  array $input 必需:aid,shop_id,area_name,table_name,table_id,total_money,pay_type
     * area_name 区域名称 table_name 桌位名称
     * total_money 结算金额
     * table_id 桌位id
     * @return [type]        [description]
     */
    public function mealOrderPay($input)
    {
        $content['area_name'] = $input['area_name'];
        $content['table_name'] = $input['table_name'];
        $content['total_money'] = $input['total_money'];
        $content['pay_type'] = $input['pay_type'];
        $content['pay_time'] = $input['time'];
        $content['type'] = self::MEAL_ORDER_PAY;
        //转发规则
        $data['rules'] = [
            ['rule' => 'in', 'field' => 'source', 'value' => [self::SOURCE_YDB_SY]],
            ['rule' => 'equal', 'field' => 'aid', 'value' => $input['aid']],
            ['rule' => 'equal', 'field' => 'shop_id', 'value' => $input['shop_id']],
        ];
        $data['content'] = json_encode($content);
        $worker_sdk = new worker_sdk;
        $worker_sdk->send($data);
    }
    /**
     * 拼单房间购物车变动 $pdid,aid,openid必填
     * @param  [type] $input [description]
     * @return [type]        [description]
     */
    public function roomPindanCartChange($input)
    {
        $content['type'] = self::ROOM_PINDAN_CART_CHANGE;
        $room_id = md5('pindan_' . $input['pdid']);
        //转发规则
        $data['rules'] = [
            ['rule' => 'in', 'field' => 'source', 'value' => [self::SOURCE_YDB_MSHOP, self::SOURCE_YDB_XCX]],
            ['rule' => 'equal', 'field' => 'aid', 'value' => $input['aid']],
            ['rule' => 'notequal', 'field' => 'openid', 'value' => $input['openid']],
            ['rule' => 'key_in', 'field' => 'rooms', 'value' => $room_id]
        ];
        $data['content'] = json_encode($content);
        $worker_sdk = new worker_sdk;
        $worker_sdk->send($data);
    }
}

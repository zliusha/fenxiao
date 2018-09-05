<?php
/**
 * 微商城（外卖版）父子订单有限状态机FSM（finite-state machine）实现
 * @author dadi
 *
 */
class wm_order_fsm_bll extends base_bll
{
    // 初始前态值
    public $spaceState = null;
    // 初始终态值
    public $state = null;
    // 当前处理的事件
    public $event = null;
    // 当前失败原因
    public $msg = '';
    // 售后是否申请全额退款
    public $afsType = 0;
    // 物流配送是否送达
    public $isDelivered = null;
    // 是否商家配送
    public $isSellerDelivery = 0;
    // 是否到店自提
    public $isSelfPickUp = 0;

    // 状态名称
    private $stateMap = [
        '1010' => [
            'name' => '待付款下单成功',
            'alias' => '待付款',
            'api_code' => 1,
        ],
        '1000' => [
            'name' => '待付款下单失败',
            'alias' => '订单失败',
            'api_code' => 0,
        ],
        '2020' => [
            'name' => '已支付待确认',
            'alias' => '待商家接单',
            'api_code' => 2,
        ],
        '2030' => [
            'name' => '已确认商家配送中',
            'alias' => '商家配送中',
            'api_code' => 3,
        ],
        '2035' => [ // 到店自提订单专用
            'name' => '已确认待自提',
            'alias' => '待自提',
            'api_code' => 3,
        ],
        '2040' => [
            'name' => '已确认待物流接单',
            'alias' => '待骑手接单',
            'api_code' => 3,
        ],
        '2050' => [
            'name' => '已接单待骑手取货',
            'alias' => '待骑手取货',
            'api_code' => 3,
        ],
        '2060' => [
            'name' => '物流配送中',
            'alias' => '配送中',
            'api_code' => 4,
        ],
        '4020' => [
            'name' => '售后处理中-商家未接单',
            'alias' => '售后处理中',
            'api_code' => 7,
        ],
        '4030' => [
            'name' => '售后处理中-商家派送中',
            'alias' => '售后处理中',
            'api_code' => 7,
        ],
        '4035' => [
            'name' => '售后处理中-到店自提中',
            'alias' => '售后处理中',
            'api_code' => 7,
        ],
        '4040' => [
            'name' => '售后处理中-骑手未接单',
            'alias' => '售后处理中',
            'api_code' => 7,
        ],
        '4050' => [
            'name' => '售后处理中-骑手已接单',
            'alias' => '售后处理中',
            'api_code' => 7,
        ],
        '4060' => [
            'name' => '售后处理中-已发货',
            'alias' => '售后处理中',
            'api_code' => 7,
        ],
        '5000' => [
            'name' => '订单关闭-付款超时',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '5001' => [
            'name' => '订单关闭-未付款取消',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '5010' => [
            'name' => '订单关闭-商家接单超时',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '5011' => [
            'name' => '订单关闭-商家拒绝接单',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '5012' => [
            'name' => '订单关闭-无物流接单',
            'alias' => '订单关闭', // 异常订单
            'api_code' => 8,
        ],
        '5020' => [
            'name' => '订单关闭-商家未接单',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '5030' => [
            'name' => '订单关闭-商家派送中',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '5035' => [
            'name' => '订单关闭-门店自提中',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '5040' => [
            'name' => '订单关闭-骑手未接单',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '5060' => [
            'name' => '订单关闭-已发货',
            'alias' => '订单关闭',
            'api_code' => 8,
        ],
        '6060' => [
            'name' => '订单完成-已发货',
            'alias' => '订单完成',
            'api_code' => 9,
        ],
        '6061' => [
            'name' => '订单完成-部分退款',
            'alias' => '订单完成',
            'api_code' => 9,
        ],
    ];
    // 外显订单状态分组
    public $aliasStateGroup = [
        '待付款' => '1010',
        '待商家接单' => '2020',
        '商家配送中' => '2030',
        '待骑手接单' => '2040',
        '待骑手取货' => '2050',
        '骑手配送中' => '2060',
        '订单完成' => '6060,6061',
        '售后处理中' => '4020,4030,4040,4050,4060',
        '订单关闭' => '5000,5001,5010,5011,5012,5020,5030,5040,5060',
    ];
    // 外显订单状态分组（到店自提专用）
    public $aliasStateGroup2 = [
        '待付款' => '1010',
        '待商家接单' => '2020',
        '待自提' => '2035',
        '订单完成' => '6060,6061',
        '售后处理中' => '4020,4030,4035,4040,4050,4060',
        '订单关闭' => '5000,5001,5010,5011,5012,5020,5030,5035,5040,5060',
    ];
    // API调用简化状态码
    public $apiStateGroup = [
        '待付款' => 1,
        '待商家接单' => 2,
        '物流配送中' => 3,
        '待骑手取货' => 4,
        '配送中' => 5,
        '订单完成' => 9,
        '售后处理中' => 7,
        '订单关闭' => 8,
    ];
    // 事件名称映射表
    private $eventMap = [
        'ORDER_CREATE' => '订单创建成功',
        'NOTIFY_ORDERFAILED' => '订单创建失败',
        'NOTIFY_UNPAID_TIMEOUT' => '超时未支付',
        'CANCEL_ORDER' => '用户取消订单',
        'NOTIFY_PAID_SUCCESS' => '用户全额支付成功',
        'SELLER_AGREE_ORDER' => '商家接受订单',
        'SELLER_REFUSE_ORDER' => '商家拒绝订单',
        'SYSTEM_REFUSE_ORDER' => '系统拒绝订单', // 商家超时未接订单
        'ORDER_EXPRESS_FAIL' => '配送系统返回异常', // 无人接单
        'ORDER_APPLY_REFUND' => '用户申请退款',
        'TURN_SELLER_DELIVERY' => '订单转换为商家配送',
        'ORDER_SEND_OUT' => '订单发货',
        'SELLER_ORDER_DELIVERED' => '商家确认送达', // 商家配送预留
        'SYSTEM_ORDER_TAKING' => '骑手接单', // 外接物流系统接单
        'SYSTEM_RIDER_TAKING' => '骑手取货', // 外接物流系统骑手取货
        'SYSTEM_RIDER_CANCEL' => '骑手取消', // 外接物流系统骑手取消配送单
        'SYSTEM_ORDER_DELIVERED' => '系统确认送达', // 物流平台返回送达信息
        'AFS_SELLER_AGREE' => '商家同意售后申请',
        'AFS_SELLER_REFUSE' => '商家拒绝售后申请',
        'AFS_BUYER_CANCEL' => '用户撤销售后申请',
    ];

    /**
     * 传入状态值解析状态
     */
    public function parseState($code, $return_array = true)
    {
        $state = new stdClass();

        $state->code = $code;
        $state->api_code = isset($this->stateMap[$code]['api_code']) ? $this->stateMap[$code]['api_code'] : 0; // API简化状态字段
        $state->name = isset($this->stateMap[$code]['name']) ? $this->stateMap[$code]['name'] : '未知状态';
        $state->alias = isset($this->stateMap[$code]['alias']) ? $this->stateMap[$code]['alias'] : '未知状态别名';

        if ($return_array) {
            return json_decode(json_encode($state), true);
        } else {
            return $state;
        }
    }

    public function setSpaceState($state)
    {
        $this->spaceState = $this->parseState($state, false);

        return $this;
    }

    private function setState($state)
    {
        $this->state = $this->parseState($state, false);

        return $this;
    }

    private function setEvent($event)
    {
        if (!isset($this->event)) {
            $this->event = new stdClass();
        }
        $this->event->code = $event;
        $this->event->name = $this->eventMap[$event] ? $this->eventMap[$event] : '未知事件';

        return $this;
    }

    // 输出格式化事件日志
    public function getEventLog()
    {
        //
    }

    // 输出格式化状态变迁日志
    public function getStateLog()
    {
        //
    }

    /**
     * 处理事件
     */
    public function process($event)
    {
        $method = $this->convertUnderline(strtolower($event));
        if (!method_exists($this, $method)) {
            throw new Exception('FSM:不支持处理 ' . $event . ' 事件');
        }
        $this->setEvent($event);
        return $this->$method();
    }

    /**
     * 下划线转驼峰
     */
    private function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    // 创建订单
    private function orderCreate()
    {
        $this->setState('1010');
        return true;
    }

    // 订单失败
    private function notifyOrderfailed()
    {
        $this->setState('1000');
        return true;
    }

    // 超时取消
    private function notifyUnpaidTimeout()
    {
        // // 前态前两位小于30代表订单状态未支付
        // $ispaid = substr($this->spaceState->code, 0, 2) < 20 ? false : true;
        // if ($ispaid === true) {
        //     throw new Exception('FSM:该订单已支付 不支持超时取消操作');
        // }
        $this->setState('5000');
        return true;
    }

    // 用户取消订单
    private function cancelOrder()
    {
        if ($this->spaceState->code != '1010') {
            $this->msg = '该订单不符合取消条件';
            return false;
        }
        $this->setState('5001');
        return true;
    }

    // 用户全额支付成功
    private function notifyPaidSuccess()
    {
        $this->setState('2020');
        return true;
    }

    // 商家接受订单
    private function sellerAgreeOrder()
    {
        if ($this->spaceState->code != '2020') {
            $this->msg = '该订单不符合接单条件';
            return false;
        }
        if ($this->isSelfPickUp) {
            $this->setState('2035');
        } else {
            // 判断是否是商家派送
            if ($this->isSellerDelivery) {
                $this->setState('2030');
            } else {
                $this->setState('2040');
            }
        }
        return true;
    }

    // 商家拒绝订单
    private function sellerRefuseOrder()
    {
        if ($this->spaceState->code != '2020') {
            $this->msg = '该订单不符合拒单条件';
            return false;
        }
        $this->setState('5011');
        return true;
    }

    // 系统拒绝订单
    private function systemRefuseOrder()
    {
        if ($this->spaceState->code != '2020') {
            $this->msg = '该订单不符合拒单条件';
            return false;
        }
        $this->setState('5010');
        return true;
    }

    // 配送系统返回异常
    private function orderExpressFail()
    {
        if ($this->spaceState->code != '2040') {
            $this->msg = '该订单不符合判为异常订单条件';
            return false;
        }
        $this->setState('5012');
        return true;
    }

    // 用户申请退款
    private function orderApplyRefund()
    {
        switch ($this->spaceState->code) {
            case '2050':
                $this->setState('4040');
                break;
            case '2060':
                $this->setState('4060');
                break;
            case '6060':
                $this->setState('4060');
                break;

            case '2040':
                $this->setState('4040');
                break;

            case '2035':
                $this->setState('4035');
                break;

            case '2030':
                $this->setState('4030');
                break;

            case '2020':
                $this->setState('4020');
                break;

            default:
                $this->msg = '重复提交售后申请';
                return false;
                break;
        }

        return true;
    }

    // 订单转换为商家配送
    private function turnSellerDelivery()
    {
        if ($this->spaceState->code != '2040') {
            $this->msg = '该订单不能转为商家配送';
            return false;
        }
        $this->setState('2030');
        return true;
    }

    // 订单发货[商家配送预留]
    private function orderSendOut()
    {
        if ($this->spaceState->code != '2040') {
            $this->msg = '该订单不符合发货条件';
            return false;
        }
        $this->setState('2060');
        return true;
    }

    // 商家确认送达
    private function sellerOrderDelivered()
    {
        if ($this->spaceState->code == '4030') {
            $this->msg = '请先处理该订单售后请求';
            return false;
        }
        if (!in_array($this->spaceState->code, ['2030', '2035'])) {
            $this->msg = '该订单不符合确认送达条件';
            return false;
        }
        $this->setState('6060');
        return true;
    }

    // 外接物流系统接单
    private function systemOrderTaking()
    {
        if ($this->spaceState->code == '2040') {
            $this->setState('2050');
        } else if ($this->spaceState->code == '4040') {
            $this->setState('4050');
        } else {
            $this->msg = '该订单不符合接单条件';
            return false;
        }
        return true;
    }

    // 外接物流系统骑手接单
    private function systemRiderTaking()
    {
        if ($this->spaceState->code == '2050') {
            $this->setState('2060');
        } else if ($this->spaceState->code == '4050') {
            $this->setState('4060');
        } else {
            $this->msg = '该订单不符合接单条件';
            return false;
        }
        return true;
    }

    // 外接物流系统骑手取消
    private function systemRiderCancel()
    {
        if (in_array($this->spaceState->code, ['2050', '2060'])) {
            $this->setState('2040');
        } else if (in_array($this->spaceState->code, ['4050', '4060'])) {
            $this->setState('4040');
        } else {
            $this->msg = '该订单不符合取消条件';
            return false;
        }
        return true;
    }

    // 系统确认送达[物流平台返回送达信息]
    private function systemOrderDelivered()
    {
        if ($this->spaceState->code == '4060') {
            $this->msg = '请先处理该订单售后请求';
            return false;
        }
        if ($this->spaceState->code != '2060') {
            $this->msg = '该订单不符合发货条件';
            return false;
        }
        $this->setState('6060');
        return true;
    }

    // 商家同意售后申请
    private function afsSellerAgree()
    {
        switch ($this->spaceState->code) {
            case '4060': // 售后处理中-已发货
                if ($this->afsType == 1) {
                    $this->setState('5060'); // 订单关闭-已发货
                } else if ($this->afsType == 2) {
                    $this->setState('6061'); // 订单完成-部分退款
                } else {
                    $this->msg = '售后类型异常';
                    return false;
                }
                break;

            case '4040': // 售后处理中-骑手未接单
                if ($this->afsType != 1) {
                    $this->msg = '未发货订单仅支持全额退款';
                    return false;
                } else {
                    $this->setState('5040'); // 订单关闭-骑手未接单
                }
                break;

            case '4030': // 售后处理中-商家派送中
                if ($this->afsType != 1) {
                    $this->msg = '商家派送中订单仅支持全额退款';
                    return false;
                } else {
                    $this->setState('5030'); // 订单关闭-商家派送中
                }
                break;

            case '4035': // 售后处理中-门店自提中
                if ($this->afsType == 1) {
                    $this->setState('5035'); // 订单关闭-门店自提中
                } else if ($this->afsType == 2) {
                    $this->setState('6061'); // 订单完成-部分退款
                } else {
                    $this->msg = '售后类型异常';
                    return false;
                }
                break;

            case '4020': // 售后处理中-未发货
                if ($this->afsType != 1) {
                    $this->msg = '未发货订单仅支持全额退款';
                    return false;
                } else {
                    $this->setState('5020'); // 订单关闭-未发货
                }
                break;

            default:
                $this->msg = '该订单不符合同意退款条件';
                return false;
                break;
        }

        return true;
    }

    // 商家拒绝售后申请
    private function afsSellerRefuse()
    {
        switch ($this->spaceState->code) {
            case '4060':
                // 查询外卖平台是否送达
                if ($this->isDelivered === 0) {
                    $this->setState('2060');
                } else if ($this->isDelivered === 1) {
                    $this->setState('6060');
                } else {
                    $this->msg = '物流是否送达状态异常';
                    return false;
                }
                break;

            case '4040':
                $this->setState('2040');
                break;

            case '4030':
                $this->setState('2030');
                break;

            case '4035':
                $this->setState('2035');
                break;

            case '4020':
                $this->setState('2020');
                break;

            default:
                $this->msg = '该订单不符合拒绝退款条件';
                return false;
                break;
        }

        return true;
    }

    // 用户撤销售后申请
    private function afsBuyerCancel()
    {
        switch ($this->spaceState->code) {
            case '4060':
                // 查询外卖平台是否送达
                if ($this->isDelivered === 0) {
                    $this->setState('2060');
                } else if ($this->isDelivered === 1) {
                    $this->setState('6060');
                } else {
                    $this->msg = '物流是否送达状态异常';
                    return false;
                }
                break;

            case '4040':
                $this->setState('2040');
                break;

            case '4030':
                $this->setState('2030');
                break;

            case '4020':
                $this->setState('2020');
                break;

            default:
                $this->msg = '该订单不符合撤销退款条件';
                return false;
                break;
        }

        return true;
    }

}

<?php
/**
 * 云店宝（扫码点餐版）父子订单有限状态机FSM（finite-state machine）实现
 * @author dadi
 *
 */
class meal_order_fsm_bll extends base_bll
{
    // 初始前态值
    public $spaceState = null;
    // 初始终态值
    public $state = null;
    // 当前处理的事件
    public $event = null;
    // 当前失败原因
    public $msg = '';
    // 是否是T1下单
    public $is_t1 = false;

    // 状态名称
    private $stateMap = [
        '1010' => [
            'name' => '待审核',
            'alias' => '待审核',
            'api_code' => 1,
        ],
        '2020' => [
            'name' => '已下厨',
            'alias' => '已下厨',
            'api_code' => 2,
        ],
        '5000' => [
            'name' => '已拒绝',
            'alias' => '已拒绝',
            'api_code' => 8,
        ],
        '6060' => [
            'name' => '订单完成',
            'alias' => '订单完成',
            'api_code' => 9,
        ],
    ];
    // 外显订单状态分组
    public $aliasStateGroup = [
        '待审核' => '1010',
        '已下厨' => '2020',
        '已拒绝' => '5000',
        '订单完成' => '6060',
    ];
    // API调用简化状态码
    public $apiStateGroup = [
        '待审核' => 1,
        '已下厨' => 2,
        '已拒绝' => 8,
        '订单完成' => 9,
    ];
    // 事件名称映射表
    private $eventMap = [
        'ORDER_CREATE' => '订单创建成功',
        'SELLER_AGREE_ORDER' => '商家接受订单',
        'SELLER_REFUSE_ORDER' => '商家拒绝订单',
        'SYSTEM_REFUSE_ORDER' => '系统拒绝订单', // T1超时未审核
        'NOTIFY_PAID_SUCCESS' => '用户支付成功',
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
        if ($this->is_t1) {
            $this->setState('2020');
        } else {
            $this->setState('1010');
        }
        return true;
    }

    // 商家接受订单
    private function sellerAgreeOrder()
    {
        if ($this->spaceState->code != '1010') {
            $this->msg = '该订单不符合审核条件';
            return false;
        }

        $this->setState('2020');
        return true;
    }

    // 商家拒绝订单
    private function sellerRefuseOrder()
    {
        if ($this->spaceState->code != '1010') {
            $this->msg = '该订单不符合拒单条件';
            return false;
        }
        $this->setState('5000');
        return true;
    }

    // 系统拒绝订单
    private function systemRefuseOrder()
    {
        if ($this->spaceState->code != '1010') {
            $this->msg = '该订单不符合拒单条件';
            return false;
        }
        $this->setState('5000');
        return true;
    }

    // 用户全额支付成功
    private function notifyPaidSuccess()
    {
        $this->setState('6060');
        return true;
    }
}

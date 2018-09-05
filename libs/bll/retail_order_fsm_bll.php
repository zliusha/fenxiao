<?php
/**
 * 云店宝（零售版）父子订单有限状态机FSM（finite-state machine）实现
 * @author dadi
 *
 */
class retail_order_fsm_bll extends base_bll
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
            'name' => '待付款',
            'alias' => '待付款',
            'api_code' => 1,
        ],
        '6060' => [
            'name' => '订单完成',
            'alias' => '订单完成',
            'api_code' => 9,
        ],
    ];
    // 外显订单状态分组
    public $aliasStateGroup = [
        '待付款' => '1010',
        '订单完成' => '6060',
    ];
    // API调用简化状态码
    public $apiStateGroup = [
        '待付款' => 1,
        '订单完成' => 9,
    ];
    // 事件名称映射表
    private $eventMap = [
        'ORDER_CREATE' => '订单创建成功',
        'NOTIFY_PAID_SUCCESS' => '支付成功',
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

    // 用户全额支付成功
    private function notifyPaidSuccess()
    {
        if ($this->spaceState->code != '1010') {
            $this->msg = '该订单为非待付款订单';
            return false;
        }
        $this->setState('6060');
        return true;
    }
}

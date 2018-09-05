<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/27
 * Time: 13:44
 */
namespace Service\Bll\Hcity;

class OrderFsmBll extends \Service\Bll\BaseBll
{
    //订单TID
    public $tid = null;
    //订单内全部核销完毕
    public $isHxOver = false;
    // 初始前态值
    public $spaceState = null;
    // 初始终态值
    public $state = null;
    // 当前处理的事件
    public $event = null;

    // 状态名称
    private $stateMap = [
        '1010' => [
            'name' => '待付款下单成功',
            'alias' => '待付款',
            'status' => 0,
            'status_code' => 1010
        ],
        '2010' => [
            'name' => '已支付待核销',
            'alias' => '待核销',
            'status' => 1,
            'status_code' => 2010
        ],
        '2020' => [
            'name' => '待核销部分核销',
            'alias' => '待核销',
            'status' => 1,
            'status_code' => 2020
        ],
        '5010' => [
            'name' => '已核销',
            'alias' => '已完成',
            'status' => 2,
            'status_code' => 5010
        ],
        '5040' => [
            'name' => '已核销',
            'alias' => '已完成（部分未使用过期）',
            'status' => 2,
            'status_code' => 5040
        ],
        '6010' => [
            'name' => '订单取消',
            'alias' => '订单关闭',
            'status' => 3,
            'status_code' => 6010
        ],
        '6020' => [
            'name' => '超时未支付',
            'alias' => '订单关闭',
            'status' => 3,
            'status_code' => 6020
        ],
        '6040' => [
            'name' => '订单过期',//支付成功后使用过期
            'alias' => '订单关闭',
            'status' => 3,
            'status_code' => 6040
        ],
    ];

    // 事件名称映射表
    private $eventMap = [
        'ORDER_CREATE' => [
            'name'=>'订单创建成功->待支付'
        ],
        'NOTIFY_PAY_SUCCESS' => [
            'name'=>'通知支付成功->待核销'//
        ],
        'HX_ORDER' => [
            'name'=>'核销订单->订单完成(部分核销)'
        ],
        'CANCEL_ORDER' => [
            'name'=>'订单取消->订单关闭'
        ],
        'NOTIFY_UNPAID_TIMEOUT' => [
            'name'=>'超时未支付->订单关闭'
        ],
        'NOTIFY_EXPIRED_ORDER' => [
            'name'=>'订单过期->订单关闭'
        ],
    ];

    /**
     * 处理事件
     */
    public function process($event)
    {
        $method = $this->convertUnderline(strtolower($event));
        if (!method_exists($this, $method))
        {
            throw new \Exception('FSM:不支持处理 ' . $event . ' 事件');
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
    /**
     * 传入状态值解析状态
     */
    public function parseState($status_code, $is_array = false)
    {
        $state = new \stdClass();
        $state->status_code = $status_code;
        $state->status = isset($this->stateMap[$status_code]['status']) ? $this->stateMap[$status_code]['status'] : 0;
        $state->name = isset($this->stateMap[$status_code]['name']) ? $this->stateMap[$status_code]['name'] : '未知状态';
        $state->alias = isset($this->stateMap[$status_code]['alias']) ? $this->stateMap[$status_code]['alias'] : '未知状态别名';

        if ($is_array)
            return json_decode(json_encode($state), true);
        else
            return $state;
    }

    /**
     * 设置前态状态值
     * @param $state
     * @return $this
     */
    public function setSpaceState($state)
    {
        $this->spaceState = $this->parseState($state, false);
        return $this;
    }

    /**
     * 设置状态值
     * @param $state
     * @return $this
     */
    private function setState($state)
    {
        $this->state = $this->parseState($state);
        return $this;
    }

    /**
     * 设置事件
     * @param $event
     * @return $this
     */
    private function setEvent($event)
    {
        if(!isset($this->eventMap[$event]))
        {
            log_message('error', __METHOD__.'未知事件:'.$event.'-tid:'.$this->tid);
            throw new \Exception('未知事件');
        }
        if (!isset($this->event)) {
            $this->event = new \stdClass();
        }
        $this->event->code = $event;
        $this->event->name = isset($this->eventMap[$event]['name'])?$this->eventMap[$event]['name']:'未知事件';
        return $this;
    }

    /**
     * 订单创建
     * @return bool
     */
    private function orderCreate()
    {
        $this->setState('1010');
        return true;
    }

    /**
     * 通知支付
     */
    private function notifyPaySuccess()
    {
        //判断前置状态
        if($this->spaceState->status_code != '1010')
        {
            log_message('error', __METHOD__.'订单状态异常：无法通知支付TID:'.$this->tid);
            throw new \Exception('订单状态异常：无法通知支付');
        }

        $this->setState('2010');
        return true;
    }

    /**
     * 核销订单
     * @return bool
     * @throws \Exception
     */
    private function hxOrder()
    {
        //判断前置状态
        if(!in_array($this->spaceState->status_code, ['2010', '2020']))
        {
            log_message('error', __METHOD__.'订单状态异常：未支付取消订单TID:'.$this->tid);
            throw new \Exception('订单状态异常：无法核销订单');
        }
        if($this->isHxOver)
            $this->setState('5010');
        else
            $this->setState('2020');
        return true;
    }

    /**
     * 主动取消订单
     */
    private function cancelOrder()
    {
        //判断前置状态
        if($this->spaceState->status_code != '1010')
        {
            log_message('error', __METHOD__.'订单状态异常：无法取消订单TID:'.$this->tid);
            throw new \Exception('订单状态异常：无法取消订单');
        }

        $this->setState('6010');
        return true;
    }

    /**
     * 未支付取消
     */
    private function notifyUnpaidTimeout()
    {
        //判断前置状态
        if($this->spaceState->status_code != '1010')
        {
            log_message('error', __METHOD__.'订单状态异常：未支付取消订单TID:'.$this->tid);
            throw new \Exception('订单状态异常：未支付取消订单');
        }

        $this->setState('6020');
        return true;
    }

    /**
     * 订单未使用过期
     */
    private function notifyExpiredOrder()
    {
        //判断前置状态
        if(!in_array($this->spaceState->status_code, ['2010', '2020']))
        {
            log_message('error', __METHOD__.'订单状态异常：订单未使用过期TID:'.$this->tid.':status_code:'.$this->spaceState->status_code);
            throw new \Exception('订单状态异常：未支付取消订单');
        }

        if($this->spaceState->status_code == '2020')
            $this->setState('5040');
        else
            $this->setState('6040');

        return true;
    }

}
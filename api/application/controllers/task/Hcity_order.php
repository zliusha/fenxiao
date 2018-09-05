<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/31
 * Time: 11:45
 */
use Service\Support\FLock;
use Service\Bll\Hcity\OrderEventBll;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityOrderKzDao;
use Service\Support\Shutdown;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityMainDb;

class Hcity_order extends task_controller
{
    private $unpaidInterrupt = false;
    private $expireInterrupt = false;

    /**
     * 15分钟未支付取消
     */
    public function taskUnpaidTimeout()
    {
        error_reporting(error_reporting() & ~E_WARNING);
        $this->log('hcity_order_unpaid', "进程已启动");
        pcntl_signal(SIGTERM, [$this, 'onUnpaidSignal']);
        pcntl_signal(SIGUSR2, [$this, 'onUnpaidSignal']);

        try {
            while (true) {
                if ($this->unpaidInterrupt) {
                    $this->log('hcity_order_unpaid', "收到中止异常, 已退出循环");
                    break;
                }
                pcntl_signal_dispatch();
                $orderEventBll = new OrderEventBll();
                $hcityOrderKzDao = HcityOrderKzDao::i();
                //获取15分钟未订单表 暂定10条
                $end_time = time() - 15 * 60;
                $order_list = $hcityOrderKzDao->getAllArray(['status' => 0, 'time<' => $end_time], 'id,aid,shop_id,tid', false, 10);
                if (!empty($order_list)) {
                    foreach ($order_list as $order) {
                        $input = ['aid' => $order['aid'], 'tid' => $order['tid']];
                        if (FLock::getInstance()->lock('OrderEventBll:cancelOrder:tid:' . $order['tid'])) {
                            try {
                                $orderEventBll->cancelOrder($input, 'NOTIFY_UNPAID_TIMEOUT');
                            } catch (Throwable $e) {
                                log_message('error', __METHOD__ . '订单支付过期通知失败:' . json_encode($input));
                                log_message('error', __METHOD__ . $e->getMessage());
                                throw $e;
                            } finally {
                                FLock::getInstance()->unlock();
                            }
                        } else {
                            log_message('error', __METHOD__ . '订单支付过期通知繁忙:' . json_encode($input));
                        }
                    }
                }
                sleep(1);
            }
        } catch (Throwable $e) {
            log_message('error', __METHOD__ . '订单支付过期通知失败:');
            log_message('error', __METHOD__ . $e->getMessage());
        }
        Shutdown::getInstance()->trigger();
    }

    /**
     * 2分钟未支付取消(目前针对砍价活动)
     */
    public function taskBargainOrderCancel()
    {
        error_reporting(error_reporting() & ~E_WARNING);
        $this->log('hcity_bargain_order_unpaid', "进程已启动");
        pcntl_signal(SIGTERM, [$this, 'onUnpaidSignal']);
        pcntl_signal(SIGUSR2, [$this, 'onUnpaidSignal']);

        try {
            while (true) {
                if ($this->unpaidInterrupt) {
                    $this->log('hcity_bargain_order_unpaid', "收到中止异常, 已退出循环");
                    break;
                }
                pcntl_signal_dispatch();
                $orderEventBll = new OrderEventBll();
                $hcityOrderKzDao = HcityOrderKzDao::i();
                //获取2分钟未订单表 暂定10条
                $end_time = time() - 2 * 60;
                $order_list = $hcityOrderKzDao->getAllArray(['stock_type'=>5,'status' => 0, 'time<' => $end_time], 'id,aid,shop_id,tid', false, 10);
                if (!empty($order_list)) {
                    foreach ($order_list as $order) {
                        $input = ['aid' => $order['aid'], 'tid' => $order['tid']];
                        if (FLock::getInstance()->lock('OrderEventBll:cancelOrder:tid:' . $order['tid'])) {
                            try {
                                $orderEventBll->cancelOrder($input, 'NOTIFY_UNPAID_TIMEOUT');
                            } catch (Throwable $e) {
                                log_message('error', __METHOD__ . '订单支付过期通知失败:' . json_encode($input));
                                log_message('error', __METHOD__ . $e->getMessage());
                                throw $e;
                            } finally {
                                FLock::getInstance()->unlock();
                            }
                        } else {
                            log_message('error', __METHOD__ . '订单支付过期通知繁忙:' . json_encode($input));
                        }
                    }
                }
                sleep(1);
            }
        } catch (Throwable $e) {
            log_message('error', __METHOD__ . '订单支付过期通知失败:');
            log_message('error', __METHOD__ . $e->getMessage());
        }
        Shutdown::getInstance()->trigger();
    }

    /**
     * 设置支付超时中断命令
     * @param $signo
     * @author ahe<ahe@iyenei.com>
     */
    public function onUnpaidSignal($signo)
    {
        $this->log('hcity_order_unpaid', "收到退出指令, SIGNAL=" . $signo);
        $this->unpaidInterrupt = true;
    }

    /**
     * 设置使用过期中断命令
     * @param $signo
     * @author ahe<ahe@iyenei.com>
     */
    public function onExpireSignal($signo)
    {
        $this->log('hcity_order_expire', "收到退出指令, SIGNAL=" . $signo);
        $this->expireInterrupt = true;
    }

    /**
     * 处理支付后未使用过期的订单
     */
    public function taskExpireOrder()
    {
        error_reporting(error_reporting() & ~E_WARNING);
        $this->log('hcity_order_expire', "进程已启动");
        pcntl_signal(SIGTERM, [$this, 'onExpireSignal']);
        pcntl_signal(SIGUSR2, [$this, 'onExpireSignal']);

        try {
            while (true) {
                if ($this->expireInterrupt) {
                    $this->log('hcity_order_expire', "收到中止异常, 已退出循环");
                    break;
                }
                pcntl_signal_dispatch();
                $orderEventBll = new OrderEventBll();
                $hcityOrderKzDao = HcityOrderKzDao::i();
                //获取过期订单表 暂定10条
                $curr_time = time();
                $order_list = $hcityOrderKzDao->getAllArray(['status' => 1, 'use_end_time<' => $curr_time], 'id,aid,shop_id,tid', false, 10);
                if (!empty($order_list)) {
                    foreach ($order_list as $order) {
                        $input = ['aid' => $order['aid'], 'tid' => $order['tid']];
                        if (FLock::getInstance()->lock('OrderEventBll:taskExpireOrder:tid:' . $order['tid'])) {
                            try {
                                $orderEventBll->notifyExpiredOrder($input);
                            } catch (\Exception $e) {
                                log_message('error', __METHOD__ . '订单核销过期通知失败:' . json_encode($input));
                                log_message('error', __METHOD__ . $e->getMessage());
                                throw $e;
                            } finally {
                                FLock::getInstance()->unlock();
                            }
                        } else {
                            log_message('error', __METHOD__ . '订单核销过期通知繁忙:' . json_encode($input));
                        }
                    }
                }
                sleep(1);
            }
        } catch (Throwable $e) {
            log_message('error', __METHOD__ . '订单核销过期通知失败:');
            log_message('error', __METHOD__ . $e->getMessage());
        }
        Shutdown::getInstance()->trigger();

    }
}
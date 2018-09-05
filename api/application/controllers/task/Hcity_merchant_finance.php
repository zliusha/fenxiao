<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/1
 * Time: 上午11:57
 */
use Service\Bll\Hcity\FinanceBll;
use Service\Support\FLock;
use Service\Support\Shutdown;

class Hcity_merchant_finance extends task_controller
{
    /**
     * crontab 定时任务
     * 执行商户财务计算，结算周期:每月5号和20号，结算七天之前的流水
     * @author ahe<ahe@iyenei.com>
     */
    public function run_cal()
    {
        if (FLock::getInstance()->lock(__METHOD__)) {

            try {
                (new FinanceBll())->doMerchantJs();
            } catch (Throwable $e) {
                $class = get_class($e);
                $this->log('merchant_finance', "出现未未知异常: {$class} Message={$e->getMessage()} Code={$e->getCode()}" . PHP_EOL . "Trace: " . PHP_EOL . $e->getTraceAsString());
            } finally {
                //模拟触发,释放锁
                Shutdown::getInstance()->trigger();
            }
            $this->log('merchant_finance', '商户商品成本结算成功');
            echo '商户商品成本结算成功';
        } else {
            $this->log('merchant_finance', '未取得锁');
            exit('未取得锁');
        }
    }
}
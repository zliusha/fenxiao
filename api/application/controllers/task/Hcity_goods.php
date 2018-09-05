<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/9
 * Time: 下午5:09
 */
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\Support\Shutdown;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityPopularGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBannerGoodsDao;

class Hcity_goods extends task_controller
{
    private $dealInvalidSignal = false;

    /**
     * 处理无效的商品
     * @author ahe<ahe@iyenei.com>
     */
    public function deal_invalid()
    {
        error_reporting(error_reporting() & ~E_WARNING);
        $this->log('hcity_goods_deal_invalid', "进程已启动");
        pcntl_signal(SIGTERM, [$this, 'onDealInvalidSignal']);
        pcntl_signal(SIGUSR2, [$this, 'onDealInvalidSignal']);

        try {
            while (true) {
                if ($this->dealInvalidSignal) {
                    $this->log('hcity_goods_deal_invalid', "收到中止异常, 已退出循环");
                    break;
                }
                $hcityOrderKzDao = HcityGoodsKzDao::i();
                pcntl_signal_dispatch();
                $goodsList = $hcityOrderKzDao->getAllArray("hcity_status = 1 and show_end_time<" . time(), 'id,aid,goods_id', false, 500);
                if (!empty($goodsList)) {
                    //更新快照
                    $kzOptions = [
                        'where_in' => [
                            'id' => array_column($goodsList, 'id')
                        ]
                    ];
                    $hcityOrderKzDao->updateExt(['hcity_status' => 0], $kzOptions);
                    $goodsTmp = [];
                    foreach ($goodsList as $goods) {
                        $goodsTmp[$goods['aid']][] = $goods['goods_id'];
                    }
                    //更新分库商品
                    foreach ($goodsTmp as $aid => $goodsIds) {
                        $options = [
                            'where' => [
                                'aid' => $aid,
                            ],
                            'where_in' => [
                                'id' => $goodsIds
                            ]
                        ];
                        ShcityGoodsDao::i(['aid' => $aid])->updateExt(['hcity_status' => 0], $options);

                        $activityGoodsOptions = [
                            'where' => [
                                'aid' => $aid,
                            ],
                            'where_in' => [
                                'goods_id' => $goodsIds
                            ]
                        ];
                        //删除爆款商品
                        HcityActivityPopularGoodsDao::i()->deleteExt($activityGoodsOptions);
                        //删除广告商品
                        HcityActivityBannerGoodsDao::i()->deleteExt($activityGoodsOptions);
                    }
                    $this->log('hcity_goods_deal_invalid', "处理失效商品，" . count($goodsList) . '个');
                } else {
//                    $this->log('hcity_goods_deal_invalid', "无失效商品");
                }
                sleep(1);
            }
        } catch (Throwable $e) {
            $this->log('hcity_goods_deal_invalid', '检测失效商品失败:');
            $this->log('hcity_goods_deal_invalid', $e->getMessage());
        }
        Shutdown::getInstance()->trigger();
    }

    /**
     * 设置支付超时中断命令
     * @param $signo
     * @author ahe<ahe@iyenei.com>
     */
    public function onDealInvalidSignal($signo)
    {
        $this->log('hcity_order_unpaid', "收到退出指令, SIGNAL=" . $signo);
        $this->dealInvalidSignal = true;
    }
}
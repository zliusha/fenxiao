<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/26
 * Time: 上午9:47
 */

namespace Service\DbFrame\DataBase\HcityMainDbModels;


class HcityCommissionPlatformDao extends BaseDao
{
    public $platformBalanceStatus = [
        '1' => '正常商品交易分佣',
        '2' => '福利池商品交易分佣',
        '3' => '个人邀请开通嘿卡',
        '4' => '商户邀请码邀请开通嘿卡',
        '5' => '邀请开通一店一码',
    ];
}
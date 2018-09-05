<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/27
 * Time: 下午1:54
 */

namespace Service\Enum;


class BalanceRecordTypeEnum
{
    //提现
    const WITHDRAWAL = 1;
    //商品销售收入
    const GOODS_SALES_INCOME = 2;
    //商品交易佣金收入
    const GOODS_COMMISSION = 3;
    //邀请办卡收入
    const INVITE_OPEN_CARD = 4;
    //邀请开通一店一码
    const INVITE_OPEN_YDYM = 5;
    //消费
    const CONSUME = 6;
    //邀请骑士收入
    const INVITE_KNIGHT = 7;
    //骑士邀请商家入驻
    const INVITE_MERCHANT_BY_KNIGHT = 8;
    //骑士新手任务
    const KNIGHT_NEWBIE = 9;
    //充值
    const RECHARGE = 10;
}
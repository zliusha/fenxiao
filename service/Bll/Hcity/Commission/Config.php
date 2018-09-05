<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/27
 * Time: 下午1:53
 */

namespace Service\Bll\Hcity\Commission;


class Config
{
    /********************分佣类型**********************/
    const TYPE_GOODS_TRADE = 1;//正常商品交易
    const TYPE_WELFARE_TRADE = 2;//福利池商品交易
    const TYPE_OPEN_HCARD = 3;//个人邀请码邀请开通嘿卡
    const TYPE_OPEN_HCARD_BY_MERCHANT = 4;//商户邀请码邀请开通嘿卡
    const TYPE_OPEN_BARCODE = 5;//邀请开通一店一码

    /********************核销订单**********************/
    const TYPE_HEXIAO = 10;//核销订单
    const TYPE_HEXIAO_INVALID = 11;//核销失效

    const TYPE_MERCHANT_PRE_JS = 12;//商家预结算

    /********************骑士任务**********************/
    const TYPE_KNIGHT_INVITE_KNIGHT = 13;//骑士拉新任务
    const TYPE_KNIGHT_INVITE_SHOP = 14;//骑士商家拉新任务
    const TYPE_KNIGHT_NEWBIE = 15;//骑士新手任务
}
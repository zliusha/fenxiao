<?php
/**
 * SCRM相关接口对接
 * @author dadi
 */
class ci_scrm
{
    // 微信粉丝充值
    const WECHAT_FANS_PAY = 'openapi/weChatFansAccount/weChatFansPay';
    // 微信粉丝余额
    const FANS_ACCOUNT = 'openapi/weChatFansAccount/fansAccount';
    // 微信粉丝消费
    const USE_FANS_ACCOUNT = 'openapi/weChatFansAccount/useFansAccount';
    // 粉丝充值/消费记录
    const FANS_PAY_LIST = 'openapi/weChatFansAccount/fansPayList';
    // 微信粉丝退款
    const BACK_FANS_ACCOUNT = 'openapi/weChatFansAccount/backFansAccount';
    // 微信粉丝积分余额
    const INTEGRAL = 'openapi/fansIntegral/integral';
    // 微信粉丝积分明细
    const INTEGRAL_INFO = 'openapi/fansIntegral/integralInfo';
    // 活动相关链接
    const GET_ACTIVITY_URL = 'openapi/activity/getActivityUrl';

    // =========== 新整合接口 ===========
    // 获取指定粉丝的 所有优惠券
    const GET_SPECIFIC_FAN_COUPON = 'openapi/weChatCoupon/getSpecificFanCoupon';

    // 微信端核销优惠券接口
    const CONSUME_CARD = 'openapi/weChatCoupon/consumeCard';

    // 通过code 查询已经领取的卡券信息
    const GET_CARD_INFO = 'openapi/weChatCoupon/getCardInfo';

    // 用于对会员卡的处理后的积分的调用
    const SET_BONUS = 'openapi/weChatCoupon/setBonus';

    // 获取会员卡领取连接接口
    const GET_MEMBER_LINK = 'openapi/weChatCoupon/getMemberLink';

    // 获取会员卡详细信息接口
    const GET_MEMBER_CARD_INFO = 'openapi/weChatCoupon/getMemberCardInfo';
    // =========== 新整合接口 ===========

    // 查找会员接口
    const SEARCH_MEMBER = 'openapi/entityCard/searchMember.json';

    // 创建新会员接口
    const CREATE_MEMBER = 'openapi/entityCard/createMember.json';

    // 会员余额明细
    const DETAIL_LIST = 'openapi/entityCard/detailList.json';

    // 绑定/更换实体会员卡接口
    const BIND_MASTER_CARD = 'openapi/entityCard/bindMasterCard.json';

    // 创建礼品卡接口
    const CREATE_GIFT_CARD = 'openapi/entityCard/createGiftCard.json';

    // 获取礼品卡开卡列表接口
    const GIFT_CARD_LIST = 'openapi/entityCard/giftCardList.json';

    // 添加副卡接口
    const BIND_VICE_CARD = 'openapi/entityCard/bindViceCard.json';

    // 获取副卡列表接口
    const VICE_CARD_LIST = 'openapi/entityCard/viceCardList.json';

    // 注销副卡接口
    const CANCEL_VICE_CARD = 'openapi/entityCard/cancelViceCard.json';

    // 现金充值接口
    const CASH_RECHARGE = 'openapi/entityCard/cashRecharge.json';

    // 礼品卡充值接口
    const GIFT_CARD_RECHARGE = 'openapi/entityCard/giftCardRecharge.json';

    // 礼品卡余额明细
    const GIFT_CARD_DETAIL_LIST = 'openapi/entityCard/giftCardDetailList.json';

    // 刷卡消费
    const CONSUME = 'openapi/entityCard/consume.json';

    // 调用远程接口
    public static function call($call, $params, $source = 'h5')
    {

        $inc = get_scrm_config();
        $api = $inc['url'];
        $url = $api . $call; // 拼接完整接口请求地址

        $params['appkey'] = $inc['appkey'];
        $params['timestamp'] = date('Y-m-d H:i:s');
        $params['source'] = $source;

        // 附加接口签名参数
        $params['sign'] = ci_scrm::sign($params, $inc['keysecret']);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $output = curl_exec($ch);
        curl_close($ch);

        $result = json_decode(trim($output), true);

        if (!$result) {
            log_message('error', __METHOD__ . ' SCRM(openapi)接口地址:' . $url);
            log_message('error', __METHOD__ . ' SCRM(openapi)接口请求参数:' . json_encode($params));
            log_message('error', __METHOD__ . ' SCRM(openapi)接口异常错误:' . $output);
        }

        return $result;
    }

    // 生成sign
    public static function sign($params, $keysecret)
    {
        ksort($params);
        $sign = '';
        foreach ($params as $key => $val) {
            $sign .= $key . $val;
        }
        $sign .= 'keysecret' . $keysecret;
        $sign = md5($sign);
        return $sign;
    }

}

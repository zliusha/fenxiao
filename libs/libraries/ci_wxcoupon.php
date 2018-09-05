<?php
/**
 * SCRM微信会员卡&优惠券对接
 * 注意：老用户使用该套接口，待SCRM整合数据后可以废弃
 * @author dadi
 */
class ci_wxcoupon
{
    // 获取指定粉丝的 所有优惠券
    const GET_SPECIFIC_FAN_COUPON = 'api/weChatCouponOpen/getSpecificFanCoupon';

    // 微信端核销优惠券接口
    const CONSUME_CARD = 'api/weChatCouponOpen/consumeCard';

    // 通过code 查询已经领取的卡券信息
    const GET_CARD_INFO = 'api/weChatCouponOpen/getCardInfo';

    // 用于对会员卡的处理后的积分的调用
    const SET_BONUS = 'api/weChatCouponOpen/setBonus';

    // 获取会员卡领取连接接口
    const GET_MEMBER_LINK = 'api/weChatCouponOpen/getMemberLink';

    // 获取会员卡详细信息接口
    const GET_MEMBER_CARD_INFO = 'api/weChatCouponOpen/getMemberCardInfo';

    // 调用远程接口
    public static function call($call, $params)
    {
        $inc = &inc_config('scrm');
        $api = $inc['url'];
        $url = $api . $call;

        // 附加接口签名参数
        $params['sign'] = ci_wxcoupon::simpleSign($params);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $output = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($output, true);

        if (!$result) {
            log_message('error', __METHOD__ . ' SCRM(api)接口异常错误' . $output . ' url:' . $url . ' params:' . json_encode($params));
        }

        return $result;
    }

    // 生成sign
    public static function simpleSign($params)
    {
        $data = object_to_array($params);
        unset($data['callback']);
        unset($data['sign']);
        unset($data['_']);
        ksort($data);
        $sign = '';

        foreach ($data as $key => $val) {
            $sign .= $key . $val;
        }
        $sign .= '3ppTcghVBJhgDuaG';
        $sign = md5($sign);
        return $sign;
    }

}

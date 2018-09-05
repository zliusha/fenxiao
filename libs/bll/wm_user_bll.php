<?php
/**
 * 微商城前端用户
 */
class wm_user_bll extends base_bll
{
    // 获取用户对象
    public function get_s_user($user, $wxbind)
    {
        $s_user = new s_wm_user_do();
        $s_user->uid = $user->id;
        $s_user->nickname = $user->username;
        $s_user->mobile = $user->mobile;
        $s_user->img = $user->img;
        $s_user->openid = isset($wxbind->open_id) ? $wxbind->open_id : '';
        $s_user->unionid = isset($wxbind->unionid) ? $wxbind->unionid : '';
        return $s_user;
    }

    // 获取游客用户对象
    public function get_visitor_s_user($wx_userinfo)
    {
        $s_user = new s_wm_user_do();
        $s_user->uid = 0;
        $s_user->nickname = isset($wx_userinfo['nickname']) ? strip_emoji($wx_userinfo['nickname']) : 'wx_' . create_order_number();
        $s_user->mobile = '';
        $s_user->img = isset($wx_userinfo['headimgurl']) ? $wx_userinfo['headimgurl'] : '';
        $s_user->openid = $wx_userinfo['openid'];
        // 特有字段
        $s_user->unionid = isset($wx_userinfo['unionid']) ? $wx_userinfo['unionid'] : '';

        return $s_user;
    }
}

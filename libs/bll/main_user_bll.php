<?php
/**
 * 微商城前端用户
 */
class main_user_bll extends base_bll
{

    public function get_s_user($user, $wxbind)
    {
        $s_user = new s_main_user_do();
        $s_user->uid = $user->id;
        $s_user->nickname = $user->username;
        $s_user->mobile = $user->mobile;
        $s_user->img = conver_picurl($user->img);
        $s_user->openid = isset($wxbind->open_id) ? $wxbind->open_id : '';

        return $s_user;
    }

}

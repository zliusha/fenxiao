<?php
/**
 * @Author: binghe
 * @Date:   2017-11-30 16:09:34
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:26:14
 */
/**
* 
*/
class Home extends wm_service_controller
{
    
    public function __construct()
    {
        parent::__construct(module_enum::SHOUYIN_MODULE);
    }

    public function index()
    {
        if(!$this->is_zongbu)
        {
            show_error('没有权限');
        }
        $key='shouyin_yundianbao_abc';
        $type = 'ydb';
        $time = time()+600;
        $data['expire_time'] = $time;
        $user_id = $this->s_user->user_id;
        $sign = md5($time.$user_id.$type.$key);
        $shouyin_inc = &inc_config('shouyin');
        $domain = $shouyin_inc['url'];
        $url = "{$domain}?expire_time={$time}&user_id={$user_id}&type={$type}&sign={$sign}";
        log_message('error', $url);
        redirect($url);
    }
}
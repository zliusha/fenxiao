<?php
/**
 * @Author: binghe
 * @Date:   2018-04-11 17:29:40
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-13 15:29:15
 */
class Mq extends sy_controller
{
    
    public function login()
    {
        
        
        $worker_inc = inc_config('workerman');
        $data['environment'] = $worker_inc['environment'];
        $data['time'] = time();
        $data['source'] = $this->source;

        //自定义数据
        $mdata['method'] = 'login';
        $mdata['aid'] = $this->s_user->aid;
        $mdata['shop_id'] = $this->s_user->shop_id;


        $data['data'] = json_encode($mdata);
        $data['sign'] =simple_auth::getSign($data,$worker_inc['key']);
        $out_data = json_encode($data);
        //默认２小时过期
        $json_data['expire_time'] = $data['time'] + 7200;
        $json_data['token'] = $out_data;
        $this->json_do->set_data($json_data);
        $this->json_do->out_put();
    }
}
<?php
/**
 * @Author: binghe
 * @Date:   2018-04-10 20:11:37
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-23 19:39:26
 */
/**
* ws
*/
class Mq extends xmeal_table_controller
{
    
    public function login()
    {
        $rules = [
            ['field'=>'table_id','label'=>'桌位号','rules'=>'trim|required|numeric'],
            ['field'=>'code','label'=>'桌位码','rules'=>'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        
        $worker_inc = inc_config('workerman');
        $data['environment'] = $worker_inc['environment'];
        $data['time'] = time();
        $data['source'] = $this->source;

        //自定义数据
        $mdata['method'] = 'login';
        $mdata['aid'] = $this->aid;
        $mdata['shop_id'] = $this->table->shop_id;
        $mdata['table_id'] = $this->table->id;
        $mdata['openid'] = $this->s_user->openid;

        $data['data'] = json_encode($mdata);
        $data['sign'] =simple_auth::getSign($data,$worker_inc['key']);
        $out_data = json_encode($data);
        
        //默认２小时过期
        $json_data['expire_time'] = $data['time'] + 7200;;
        $json_data['token'] = $out_data;
        $this->json_do->set_data($json_data);
        $this->json_do->out_put();
    }
}
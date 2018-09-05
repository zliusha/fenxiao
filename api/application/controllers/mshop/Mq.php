<?php
/**
 * websocket登录接口
 */
class Mq extends mshop_controller
{

    /**
     * worker man 登录
     */
    public function login()
    {
        
        $worker_inc = inc_config('workerman');
        $data['environment'] = $worker_inc['environment'];
        $data['time'] = time();
        $data['source'] = $this->source;

        //自定义数据
        $mdata['method'] = 'login';
        $mdata['aid'] = $this->aid;
        $mdata['openid'] = $this->s_user->openid;

        $data['data'] = json_encode($mdata);
        $data['sign'] = simple_auth::getSign($data, $worker_inc['key']);
        $out_data = json_encode($data);

        //默认２小时过期
        $json_data['expire_time'] = $data['time'] + 7200;
        $json_data['heartbeat'] = 'peng';//
        $json_data['token'] = $out_data;
        $this->json_do->set_data($json_data);
        $this->json_do->out_put();
    }
    /**
     * worker man 加入区域room
     */
    public function pindan_room()
    {
        $rules = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);

        $worker_inc = inc_config('workerman');
        $data['environment'] = $worker_inc['environment'];
        $data['time'] = time();
        $data['source'] = $this->source;

        //自定义数据

        $mdata['method'] = 'join_room';
        $mdata['aid'] = $this->aid;
        $mdata['openid'] = $this->s_user->openid;
        $mdata['room_id'] = md5('pindan_'.$fdata['pdid']);
        $mdata['pdid'] = $fdata['pdid'];


        $data['data'] = json_encode($mdata);
        $data['sign'] = simple_auth::getSign($data, $worker_inc['key']);
        $out_data = json_encode($data);

        //默认２小时过期
        $json_data['expire_time'] = $data['time'] + 7200;

        $json_data['heartbeat'] = 'peng_'.$mdata['room_id'];
        $json_data['room_id'] = $mdata['room_id'];
        $json_data['room_token'] = $out_data;
        $this->json_do->set_data($json_data);
        $this->json_do->out_put();
    }
}

<?php
/**
 * @Author: binghe
 * @Date:   2017-11-10 14:08:30
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:27:22
 */
/**
* 消息
*/
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
class Mq_api extends wm_service_controller
{
    //新增订单查询
    public function new_order()
    {
        $last_time=$this->input->get('t');
        if(empty($last_time))
        {
            $data['t']=time();
            $data['count']=0;
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        }
        if(!is_numeric($last_time))
            $this->json_do->set_error('001');
        
        //截止时间
        $time=time();
        $where['aid']=$this->s_user->aid;
        if(!$this->is_zongbu)
            $where['shop_id']=$this->currentShopId;
        // $where['status']='2020';
        $where['pay_time <']=$time;
        $where['pay_time >=']=$last_time;
        
        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $count = $wmOrderDao->getCount($where);

        $data['t']=$time;
        $data['count']=$count;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    //消息登录
    public function login()
    {
        $is_shop_user = false;
        $out_data = '';
        if(!$this->is_zongbu)
        {
            $worker_inc = inc_config('workerman');
            $data['environment'] = $worker_inc['environment'];
            $data['time'] = time();
            $data['source'] = 'ydb_manage';

            //自定义数据
            $mdata['method'] = 'login';
            $mdata['aid'] = $this->s_user->aid;
            $mdata['shop_id'] = $this->currentShopId;

            $data['data'] = json_encode($mdata);
            $data['sign'] =simple_auth::getSign($data,$worker_inc['key']);
            $is_shop_user = true;
            $out_data = json_encode($data);
        }
        //默认２小时过期
        $json_data['expire_time'] = time() + 7200;
        $json_data['is_shop_user'] = $is_shop_user;
        $json_data['out_data'] = $out_data;
        $this->json_do->set_data($json_data);
        $this->json_do->out_put();
    }
}
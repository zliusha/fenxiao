<?php
/**
 * @Author: binghe
 * @Date:   2017-11-30 16:09:34
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:26:13
 */
use Service\Cache\ErpLoginTokenCache;
/**
* 
*/
class Home extends wm_service_controller
{
    
    public function __construct()
    {
        parent::__construct(module_enum::SCRM_MODULE);
    }

    public function index()
    {
        if(!$this->is_zongbu)
        {
            show_error('没有权限');
        }
        if(is_new_scrm())
            $this->_new_index();
        else
        {
            $this->_upgrade();
            // $this->_old_index();
        }
    }
    private function _old_index()
    {
        $type = $this->input->get('type');
        if(empty($type))
            $type = 0;
        $target = $this->input->get('target');
        $router = '';
        switch ($target) {
            case 'xcx':
                $router = '&router_id=86#/appletAccount/list';
                break;
        }
        $key='scrm_yundianbao_abc';
        $time=time()+600;
        $data['expire_time']=$time;
        $visit_id=$this->s_user->visit_id;
        $sign=md5($time.$visit_id.$key);
        $scrm_inc = get_scrm_config();
        $domain = $scrm_inc['url'];
        $url="{$domain}welcome?expire_time={$time}&visit_id={$visit_id}&sign={$sign}&type={$type}".$router;
        redirect($url);
    }
    private function _new_index()
    {
        $this->load->view('main/scrm');
        // redirect('home/scrm');
    }
    private function _upgrade()
    {
        $this->load->view('main/scrm_upgrade');
        // redirect('home/scrm');
    }
}
<?php
/**
 * @Author: binghe
 * @Date:   2017-11-14 17:27:27
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-19 13:26:14
 */
/**
* erp对接
*/
class Scrm_api extends wm_service_controller
{
    const KEY = 'scrm_yundianbao_abc';
    public function __construct()
    {
        parent::__construct(module_enum::SCRM_MODULE);
    }
    
    /**
     * 得到token 10分钟失效
     * @return [type] [description]
     */
    public function get_token()
    {
        if(!$this->is_zongbu)
        {
            $this->json_do->set_error('004');
        }
        $time=time()+600;
        $data['expire_time']=$time;
        $visit_id=$this->s_user->visit_id;
        $sign=md5($time.$visit_id.self::KEY);
        $url="http://gzh.waimaishop.com/welcome?&expire_time={$time}&visit_id={$visit_id}&sign={$sign}";
        $data['url']=$url;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
    /**
     * 获取scrm 的菜单
     * @return [type] [description]
     */
    public function get_menu()
    {
        if(!$this->is_zongbu)
        {
            $this->json_do->set_error('004');
        }
        try {
            $erp_sdk = new erp_sdk;
            //[visit_id,user_id]
            $params =[$this->s_user->visit_id,$this->s_user->user_id];
            $result = $erp_sdk->getToken($params);
            $input['token'] = $result['token'];
            $scrm_sdk = new scrm_sdk();
            $res = $scrm_sdk->getMenu($input);
            $data['menus'] = $res['data'];
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
        
    }
    public function upgrade()
    {
        $this->json_do->set_error('004','此方法已过期');
    }
}
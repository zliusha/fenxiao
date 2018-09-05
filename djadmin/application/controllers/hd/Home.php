<?php
/**
 * @Author: binghe
 * @Date:   2017-11-30 16:09:34
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-12 16:56:59
 */
/**
* 
*/
class Home extends wm_service_controller
{
    
    public function __construct()
    {
        parent::__construct(module_enum::HUDONG_MODULE);
    }

    public function index()
    {
      
        $hd_inc = &inc_config('hd');

        $data['appid'] = $hd_inc['appid'];
        $data['connect_id'] = $this->s_user->visit_id;
        $data['timestamp'] = time();
        $data['keysecret'] = $hd_inc['keysecret'];

        $sign = $this->_sign($data, $hd_inc['access_key']);;

        $hd_inc = &inc_config('hd');
        $domain = $hd_inc['url'];
        $url = "{$domain}hd/admin/LoginHd/aj_login?appid={$data['appid']}&connect_id={$data['connect_id']}&timestamp={$data['timestamp']}&keysecret={$data['keysecret']}&sign={$sign}";
        redirect($url);
    }

    /**
     * @param $data
     * @return string
     */
    private function _sign($data, $access_key='a431adb20dbe7469ffec899854240250')
    {
        unset($data['sign']);
        //排序所有参数
        ksort($data);
        $signStr = $access_key;

        //组织签名规则
        foreach($data as $temp => $val)
        {
          $signStr .= $temp.$val;
        }
        $signStr = $signStr.$data['keysecret'];
        $md5signStr = strtoupper(md5($signStr));
        return $md5signStr;
    }

}
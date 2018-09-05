<?php
/**
 * @Author: binghe
 * @Date:   2017-08-23 18:17:37
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:13:41
 */
use Service\Cache\MainSecretCache;
/**
* api controller 验证签名
*/
class open_controller extends base_controller
{
    function __construct()
    {
       parent::__construct();

       //签名验证
        if(!$this->_validate_sign())
            $this->json_do->set_error('006');
    }
    //第三方验证签名
    private function _validate_sign()
    {
        $sign=$this->input->get_post('sign');
        if(empty($sign))
            return false;
        $params=$this->input->post();
        //排除sign
        if(isset($params['sign']))
            unset($params['sign']);
        //参数不能为空，如何接口不需要参数的话，请设置随机数r
        if(empty($params))
            return false;
        $as=explode(':',$sign);
        if(count($as)!==2)
            return false;
        list($access_key,$sign_str)=$as;
        //实际理应缓存
        try {
            $mainSecretCache = new MainSecretCache(['access_key'=>$access_key]);
            $m_main_secret = $mainSecretCache->getDataASNX();
        } catch (Exception $e) {
            return false;
        }
            
        $secret_key = $m_main_secret->secret_key;
        $auth = new auth($access_key,$secret_key);
        return $auth->validSign($sign,$params);
    }
    
}
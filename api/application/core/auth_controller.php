<?php
/**
 * @Author: binghe
 * @Date:   2017-12-25 10:53:56
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-23 14:53:37
 */
use Service\Cache\Wm\WmServiceCache;
/**
* access_controller
*/
class auth_controller extends base_controller
{
    public $source = '';
    //ydb_xcx=>小程序，ydb_meal=>扫码h5,ydb_sy=>t1,ydb_manage=>管理后台,ydb_mshop=>h5,'ydb_meal_xcx'=>扫码点餐,ydb_hcity_xcx=>商圈小程序
    protected $source_map=['ydb_xcx','ydb_meal','ydb_sy','ydb_manage','ydb_mshop','ydb_meal_xcx','ydb_hcity_xcx','ydb_manage_hcity'];
    public function __construct()
    {
        parent::__construct();
        //初始化
        $this->_init();
        //签名验证
        $this->_valid_params();
    }
    /**
     * 加载来源
     * @return [type] [description]
     */
    private function _init()
    {
        //来源
        $_source = $this->input->get_request_header('Source');
        if(empty($_source) || !in_array($_source,$this->source_map))
            $this->json_do->set_error('006','非法请求-source');
        else
            $this->source = $_source;

    }
    /**
     * 验证参数签名, 只是为了防止篡改数据
     * @return [type] [descripthion]
     */
    private function _valid_params()
    {
        if(!is_post())
            return;
        $params = $this->input->post();
        // if(empty($params))
        //     $this->json_do->set_error('003-1','签名错误 no params');

        $secretKey = $this->source;
        $signTime = $this->input->get_request_header('Sign-Time');
        if(empty($signTime))
        {
            //兼容旧版小程序
            // $this->json_do->set_error('003-1','签名错误 no sign time');
        }
        else
        {
            if(is_numeric($signTime) && abs(time() - $signTime) < 300)
                $secretKey .= $signTime;
            else
                $this->json_do->set_error('003-1','超时 sign time');
        }

        $sign = simple_auth::getSign($params,$secretKey);
        // echo $sign;
        // exit;
        $h_sign = $this->input->get_request_header('Sign');
        // log_message('error',__METHOD__.'-key:'.$secretKey.'-sign:'.$sign.'-h_sign:'.$h_sign.'-params:'.json_encode($params));
        if(empty($h_sign) || $h_sign != $sign )
            $this->json_do->set_error('006','签名错误 sign error');
        else
            return;
    }
    //检查是否购买服务
    protected function _check_service($module,$aid)
    {
        $wmServiceCache = new WmServiceCache(['aid'=>$aid]);
        $this->service = $wmServiceCache->getDataASNX();
        //
        if(!$this->service)
            $this->json_do->set_error('004', '店铺未开通此功能');

        //1.判断是否开通服务 或是否过期
        if (!$this->service || $this->service->expire_time < date('Y-m-d')) 
            $this->json_do->set_error('004', '服务已到期');
        //2.判断是否有权限，空值代表拥有全部权限 
        
        if($module == null || empty($this->service->power_keys))
            return;
        $valide = false;
        if(is_array($module))
            count(array_intersect($module,$this->service->power_keys)) && $valide = true;
        else
            in_array($module,$this->service->power_keys) && $valide = true;;
                
        $valide || $this->json_do->set_error('004', '不支持使用该服务');
        return;
    }
}
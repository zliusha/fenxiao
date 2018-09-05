<?php
/**
 * @Author: binghe
 * @Date:   2017-08-04 14:17:38
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-20 17:24:18
 */
use Service\Cache\Wm\WmServiceCache;
/**
 * 服务控制器基类 此处将scrm 和 hd 当成此行业的模块,如有多行业应该区分
 */
class service_controller extends base_controller
{
    public $service=null;
    //$service = 1代表默认行业,因为目前只规划了一个行业,未做任何处理
    public function __construct($module = null,$service = 1)
    {

        parent::__construct();
        
        $this->_check_service($module);

    }
    //检查是否购买服务
    private function _check_service($module)
    {
        $wmServiceCache = new WmServiceCache(['aid'=>$this->s_user->aid]);
        $this->service = $wmServiceCache->getDataASNX();
        //
        if(!$this->service)
            $this->_error('004', '当前版本不支持使用该功能，请联系客服升级版本');

        //1.判断是否开通服务 或是否过期
        if (!$this->service || $this->service->expire_time < date('Y-m-d')) 
            $this->_error('004', '您的账号使用权限已经到期，为了不影响您的正常使用，请尽快联系客服购买版本或续费。');
        //2.判断是否有权限，空值代表拥有全部权限 
        
        if($module == null || empty($this->service->power_keys))
            return;
        $valide = false;
        if(is_array($module))
            count(array_intersect($module,$this->service->power_keys)) && $valide = true;
        else
            in_array($module,$this->service->power_keys) && $valide = true;;
                
        $valide || $this->_error('004', '当前版本不支持使用该功能，请联系客服升级版本');
        return;
    }
    
}

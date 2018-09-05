<?php
/**
 * @Author: binghe
 * @Date:   2018-03-30 15:02:25
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-03-30 15:02:42
 */
/**
* 用户中心　游客不能访问
*/
class xcx_user_controller extends xcx_controller
{
    
    function __construct($filterMethods=[])
    {
        parent::__construct();
        $this->_valide_user($filterMethods);
    }
    /**
     * 验证用户
     * @param  array $filterMethods 过滤无需验证的方法,转为小写
     * @return [type]                [description]
     */
    private function _valide_user($filterMethods=[])
    {
        if(in_array($this->url_method,$filterMethods) || $this->s_user->uid!=0)
            return;
        else
            $this->json_do->set_error('004-2','请绑定手机号');
            
    }
}
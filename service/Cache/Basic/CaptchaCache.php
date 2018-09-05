<?php

/**
 * @Author: binghe
 * @Date:   2018-08-17 17:01:03
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-17 17:49:43
 */
namespace Service\Cache\Basic;
/**
 * 子账号有权限的saas缓存
 */
class CaptchaCache extends \Service\Cache\BaseCache implements \Service\Cache\IAssign
{
    
    
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "Basic:Captcha_{$this->input['token']}";
    }

}
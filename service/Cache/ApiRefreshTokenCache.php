<?php
/**
 * @Author: binghe
 * @Date:   2018-04-24 11:47:52
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-26 16:31:14
 */
namespace Service\Cache;
/**
* 
*/
class ApiRefreshTokenCache extends BaseCache
{
    /**
     * @param array $input 必需:refresh_token
     */
    public function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 实现抽象方法
     * @return string 键名
     */
    public function getKey()
    {
        return "ApiRefreshToken:{$this->input['refresh_token']}";
    }
}
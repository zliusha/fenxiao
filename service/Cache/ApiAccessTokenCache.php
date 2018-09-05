<?php
/**
 * @Author: binghe
 * @Date:   2018-04-04 17:37:09
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-26 16:31:12
 */
namespace Service\Cache;
/**
* 
*/
class ApiAccessTokenCache extends BaseCache
{
    /**
     * @param array $input 必需:access_token
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
        return "ApiAccessToken:{$this->input['access_token']}";
    }
}
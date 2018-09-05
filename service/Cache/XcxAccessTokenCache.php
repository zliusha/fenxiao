<?php

/**
 * @Author: binghe
 * @Date:   2018-08-07 14:55:01
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-07 15:18:48
 */
namespace Service\Cache;
use Service\Sdk\XcxSdk;
/**
 * 
 */
class XcxAccessTokenCache extends BaseCache implements IAssign
{
	
	/**
     * @param array $input 必需:appid,app_secret
     */
    function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * data=>[aid,visit_id]
     * @return mixed [description]
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $xcxSdk = new XcxSdk($input);
            $result = $xcxSdk->getAccessToken();
            return $result['access_token'];
        },6900);
    }
    public function getKey()
    {
        return "XcxAccessToken:{$this->input['appid']}";
    }
}
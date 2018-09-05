<?php
/**
 * @Author: binghe
 * @Date:   2018-05-23 14:09:30
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-06 13:57:50
 */
namespace Service\Cache;
/**
 * erp token
 */
class ErpLoginTokenCache extends BaseCache implements IAssign
{
    /**
     * @param array $input 必需:visit_id,user_id
     */
    function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * @return string token
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $erp_sdk = new \erp_sdk;
            $params[]=$input['visit_id'];
            $params[]=$input['user_id'];
            $res=$erp_sdk->getToken($params);
            return $res['token'];
        });
    }
    public function getKey()
    {
        return "ErpLoginToken:{$this->input['visit_id']}_{$this->input['user_id']}";
    }
}
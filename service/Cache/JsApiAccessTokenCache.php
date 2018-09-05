<?php
namespace Service\Cache;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/25
 * Time: 15:47
 */
class JsApiAccessTokenCache extends BaseCache implements IAssign
{

    /**
     * @param array $input 必需:app_id
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
        return "JsApiAccessToken:{$this->input['app_id']}";
    }
}
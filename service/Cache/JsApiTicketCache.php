<?php
namespace Service\Cache;
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/25
 * Time: 15:47
 */
class JsApiTicketCache extends BaseCache implements IAssign
{
    /**
     * @param array $input 必需:app_id,login_type
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
        return "JsApiTicket:{$this->input['app_id']}-{$this->input['login_type']}";
    }
}
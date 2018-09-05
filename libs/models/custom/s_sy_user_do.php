<?php
/**
 * @Author: binghe
 * @Date:   2018-04-08 09:54:19
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-04-08 09:54:52
 */
/**
* 收银登录
*/
class s_sy_user_do extends s_base_user_do
{
    
    //erp  公司id
    public $visit_id;
    //erp  用户id
    public $user_id;
    //云店宝公司id
    public $aid;
    //云店宝用户id
    public $id;
    //云店宝用户昵称
    public $username;
    //是否为管理员
    public $is_admin=true;
    //非管理员关联的门店id
    public $shop_id;
}
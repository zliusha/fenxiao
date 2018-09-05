<?php
/**
 * @Author: binghe
 * @Date:   2017-11-27 10:11:43
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-14 13:45:49
 */
/**
* s_main_company_account_do
*/
class s_main_company_account_do extends s_base_user_do
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
    public $shop_id=[];
}
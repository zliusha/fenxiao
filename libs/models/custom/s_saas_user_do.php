<?php

/**
 * @Author: binghe
 * @Date:   2018-07-14 13:49:24
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 11:36:39
 */
class s_saas_user_do extends s_base_user_do
{
    //erp  公司id
    public $visit_id ;
    //erp  用户id
    public $user_id ;
    //云店宝公司id
    public $aid ;
    //云店宝用户id
    public $id;
    //云店宝用户昵称
    public $username;
    //是否为管理员
    public $is_admin=true;
}
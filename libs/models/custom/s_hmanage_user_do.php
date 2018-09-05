<?php

/**
 * @Author: binghe
 * @Date:   2018-07-23 10:26:45
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-23 10:28:20
 */
class s_hmanage_user_do extends s_base_user_do
{
    public $id;
    public $username;
    public $mobile;
    public $type=0;//类型 0城市合伙人 1平台方后台
    public $region=null;//管理的地理位置 存id,格式为'省-市-区'
    public $region_name=null;//省-市-区
    public $region_type=null;// 区域1 市 2
}
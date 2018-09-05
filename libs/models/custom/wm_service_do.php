<?php

/**
 * @Author: binghe
 * @Date:   2018-07-20 12:08:10
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-20 13:28:00
 */
/**
* wm service 
*/
class wm_service_do
{
    public $aid;
    //服务id
    public $item_id;
    //服务名称
    public $item_name='';
    //到期时间
    public $expire_time;
    //权限
    public $power_keys=[];
    //门店数量控制，0代表不限不店
    public $shop_limit = 1;
}
<?php
/**
 * @Author: binghe
 * @Date:   2017-10-13 10:48:39
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-12-20 11:46:41
 */
/**
* service 
*/
class service_do
{
    public $aid;
    //服务id
    public $service_id;
    //到期时间
    public $service_day_limit;
    //服务名称
    public $service_type_name='';
    //是否是试用
    public $is_trial=0;
    //权限
    public $power_keys=[];
    //门店数量控制，0代表不限不店
    public $shop_limit = 1;
}
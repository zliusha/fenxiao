<?php
/**
 * @Author: binghe
 * @Date:   2017-08-15 14:25:07
 * @Last Modified by:   binghe
 * @Last Modified time: 2017-08-15 14:45:32
 */
/**
* 模块-海报
*/
class wm_zx_poster_do extends wm_zx_do
{

    public $img = '';
    public $title = '';
    public $start_day = '';
    public $end_day = '';
    public $start_time = '';
    public $end_time = '';
    public $week = "";
    public $good_ids = '';
    //json数组 {title,id,price,img}
    public $list = [];
  
}
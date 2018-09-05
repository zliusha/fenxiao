<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 15:48:08
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-29 16:04:52
 */
namespace Service\Support\Page;
/**
 * 分页配置文件
 */
class PageConfig
{
	
	public $pageSize = 10;
    public $currentPage = 1;
    public $table;
    public $fields = '*';
    public $order;
    public $where = ' 1=1 ';
}
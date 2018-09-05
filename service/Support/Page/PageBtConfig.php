<?php
namespace Service\Support\Page;

class PageBtConfig
{
    public $offset = 0;
    public $limit = 10;

    public $sort = '';
    public $order = 'desc';
    public $where = ' 1=1 ';
    public $table;
    public $fields = '*';

    //中间变量，需手动处理赋值到where
    public $search;
    //优先，此变量赋值，$sort,$order 失效
    public $order_by;

    public function __construct()
    {

        # code...
    }
}

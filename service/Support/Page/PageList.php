<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 15:47:58
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-04 09:48:07
 */
namespace Service\Support\Page;
use Service\Traits\ControllerTrait;
/**
 * 
 */
class PageList
{
	use ControllerTrait;
	private $db;
	
	function __construct($db)
	{
		$this->db= $db;
	}
	public function getList($pageConfig, &$count)
    {
        $sql = "SELECT " . $pageConfig->fields . " FROM " . $pageConfig->table;
        $countSql = "SELECT count(*) as count FROM " . $pageConfig->table;
        if ($pageConfig->where) {
            $sql = $sql . " WHERE " . $pageConfig->where;
            $countSql = $countSql . " WHERE " . $pageConfig->where;
        }
        if ($pageConfig->order) {
            $sql = $sql . " ORDER BY " . $pageConfig->order;
        }
        $sql = $sql . " LIMIT " . $pageConfig->pageSize * ($pageConfig->currentPage - 1) . "," . $pageConfig->pageSize;

        $count = $this->db->query($countSql)->row()->count;
        return $this->db->query($sql)->result_array();
    }
    /**
     * WEB 参数配置 mobile
     */
    public function getConfig()
    {

        $pageConfig = new PageConfig();

        $currentPage = $this->input->get_post('current_page');
        //第几页
        if (isset($currentPage) && is_numeric($currentPage) && $currentPage > 1) {
            $pageConfig->currentPage = $currentPage;
        }

        $pageSize = $this->input->get_post('page_size');
        //每页多少条记录
        if (isset($pageSize) && is_numeric($pageSize) && $pageSize > 0) {
            $pageConfig->pageSize = $pageSize;
        }

        return $pageConfig;
    }
    /**
     * 防SQL注入
     * @return 安全的字符串 \' \-
     */
    public function filter($str)
    {
        return $this->db->escape($str);
    }
    /**
     * 防SQL注入　\- \%
     * @return 安全的like字符串
     */
    public function filterLike($str)
    {
        return $this->db->escape_like_str($str);
    }

    /**
     * 获取 bootstrap http 分页参数
     * @return page_config  分页配置类
     */
    public function getBtConfig()
    {
        $page_bt_config = new PageBtConfig();
        //offset
        $offset = $this->input->get_post('offset');
        if (is_numeric($offset)) {
            $page_bt_config->offset = $offset;
        }

        //每页多少条记录
        $limit = $this->input->get_post('limit');
        if ($limit && is_numeric($limit)) {
            $page_bt_config->limit = $limit;
        }

        //asc,desc
        $sort = $this->input->get_post('sort');
        if (!empty($sort)) {
            $page_bt_config->sort = $sort;
        }

        //order by字段
        $order = $this->input->get_post('order');
        if (!empty($order)) {
            $page_bt_config->order = $order;
        }

        $search = $this->input->get_post('search');
        if (!empty($search)) {
            $where_arr = explode('||', trim($search)); //多个条件
            foreach ($where_arr as $w) {
                if (empty($w)) {
                    continue;
                }

                $w_arr = explode('|', $w); //单个条件 type:name|value 同时支付name:value 此时默认为like
                if (count($w_arr) == 2) {
                    $name_arr = explode(':', $w_arr[0]);
                    $opr = "like";
                    $name = $w_arr[0];
                    if (count($name_arr) == 2) {
                        $opr = $name_arr[0];
                        $name = $name_arr[1];
                    }
                    $page_bt_config->where .= " and " . $this->_getBtWhere($opr, $name, $w_arr[1]);
                }
            }
        }
        return $page_bt_config;
    }

    /**
     * 根据bootstrap 得到列表
     * @param  [type] $bt_page_config [description]
     * @param  [type] &$count         [description]
     * @return [type]                 [description]
     */
    public function getBtList($pageConfig, &$count)
    {
        $sql = "SELECT " . $pageConfig->fields . " FROM " . $pageConfig->table;
        $count_sql = "SELECT count(*) as count FROM " . $pageConfig->table;
        if ($pageConfig->where) {
            $sql = $sql . " WHERE " . $pageConfig->where;
            $count_sql = $count_sql . " WHERE " . $pageConfig->where;
        }
        if ($pageConfig->order_by) {
            $sql = $sql . " ORDER BY " . $pageConfig->order_by;
        } else if ($pageConfig->sort && $pageConfig->order) {
            $sql = $sql . " ORDER BY " . $pageConfig->sort . " " . $pageConfig->order;
        }
        if(!$pageConfig->offset)
        {
            $pageConfig->offset=1;
        }
        $offset=($pageConfig->offset-1)*$pageConfig;
        $sql = $sql . " LIMIT " . $offset . "," . $pageConfig->limit;
        $count = $this->db->query($count_sql)->row()->count;
        return $this->db->query($sql)->result_array();
    }

    /**
     * @param $opr
     * @param $name
     * @param $value
     * @return string
     */
    private function _getBtWhere($opr, $name, $value)
    {
        $where = '';
        switch ($opr) {
            case "equal":$where = $name . " ='" . $value . "'";
                break;
            case "timefrom":$where = $name . " >='" . strtotime($value) . "'";
                break;
            case "timeto":$where = $name . " <='" . strtotime($value) . "'";
                break;
            default: // Edit By dadi: 解决mysql字段关键字问题
                if (strpos($name, '.')) {
                    // 判断是否联表字段 t1.state
                    $where = "{$name} like '%" . $value . "%'";
                } else {
                    $where = "`{$name}` like '%" . $value . "%'";
                }
                break;
        }
        return $where;
    }
}
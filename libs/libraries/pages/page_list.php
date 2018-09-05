<?php
require_once "page_config.php";
require_once "page_bt_config.php";
/**
 * 分页列表
 */
class page_list
{
    public function __get($name)
    {
        $ci = &get_instance();
        if ($name == 'db') {
            if (!isset($ci->db_host)) {
                $ci->db_host = get_db_connect();
            }
            return $ci->db_host;
        } else {
            return $ci->$name;
        }
    }
    public function get_list($page_config, &$count)
    {
        $sql = "SELECT " . $page_config->fields . " FROM " . $page_config->table;
        $count_sql = "SELECT count(*) as count FROM " . $page_config->table;
        if ($page_config->where) {
            $sql = $sql . " WHERE " . $page_config->where;
            $count_sql = $count_sql . " WHERE " . $page_config->where;
        }
        if ($page_config->order) {
            $sql = $sql . " ORDER BY " . $page_config->order;
        }
        $sql = $sql . " LIMIT " . $page_config->page_size * ($page_config->current_page - 1) . "," . $page_config->page_size;

        $count = $this->db->query($this->dbprefix($count_sql))->row()->count;
        return $this->db->query($this->dbprefix($sql))->result_array();
    }

    /**
     * 初始化分页配置 current_page
     * @param  [stdClass] $obj [参数对象]
     * @return [page_config]      [分页配置对象]
     */
    public function get_config($obj = null)
    {
        $page_config = new page_config();
        if (!empty($obj)) {
            //第几页
            if (isset($obj->current_page) && is_numeric($obj->current_page) && $obj->current_page > 1) {
                $page_config->current_page = $obj->current_page;
            }

            //每页多少条记录
            if (isset($obj->page_size) && is_numeric($obj->page_size) && $obj->page_size > 0) {
                $page_config->page_size = $obj->page_size;
            }

        }
        return $page_config;
    }

    /**
     * 获取 bootstrap http 分页参数
     * @return page_config  分页配置类
     */
    public function get_bt_config()
    {
        $page_bt_config = new page_bt_config();
        //offset
        $offset = $this->input->get('offset');
        if (is_numeric($offset)) {
            $page_bt_config->offset = $offset;
        }

        //每页多少条记录
        $limit = $this->input->get('limit');
        if ($limit && is_numeric($limit)) {
            $page_bt_config->limit = $limit;
        }

        //asc,desc
        $sort = $this->input->get('sort');
        if (!empty($sort)) {
            $page_bt_config->sort = $sort;
        }

        //order by字段
        $order = $this->input->get('order');
        if (!empty($order)) {
            $page_bt_config->order = $order;
        }

        $search = $this->input->get('search');
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
                    $page_bt_config->where .= " and " . $this->_get_bt_where($opr, $name, $w_arr[1]);
                }
            }
        }
        return $page_bt_config;
    }

    /**
     * WEB 参数配置 mobile
     */
    public function get_m_config()
    {

        $page_config = new page_config();

        $current_page = $this->input->get_post('current_page');
        //第几页
        if (isset($current_page) && is_numeric($current_page) && $current_page > 1) {
            $page_config->current_page = $current_page;
        }

        $page_size = $this->input->get_post('page_size');
        //每页多少条记录
        if (isset($page_size) && is_numeric($page_size) && $page_size > 0) {
            $page_config->page_size = $page_size;
        }

        return $page_config;
    }

    /**
     * 根据bootstrap 得到列表
     * @param  [type] $bt_page_config [description]
     * @param  [type] &$count         [description]
     * @return [type]                 [description]
     */
    public function get_bt_list($page_config, &$count)
    {
        $sql = "SELECT " . $page_config->fields . " FROM " . $page_config->table;
        $count_sql = "SELECT count(*) as count FROM " . $page_config->table;
        if ($page_config->where) {
            $sql = $sql . " WHERE " . $page_config->where;
            $count_sql = $count_sql . " WHERE " . $page_config->where;
        }
        if ($page_config->order_by) {
            $sql = $sql . " ORDER BY " . $page_config->order_by;
        } else if ($page_config->sort && $page_config->order) {
            $sql = $sql . " ORDER BY " . $page_config->sort . " " . $page_config->order;
        }
        $sql = $sql . " LIMIT " . $page_config->offset . "," . $page_config->limit;

        $count = $this->db->query($this->dbprefix($count_sql))->row()->count;
        return $this->db->query($this->dbprefix($sql))->result_array();
    }

    private function _get_bt_where($opr, $name, $value)
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
    public function filter_like($str)
    {
        return $this->db->escape_like_str($str);
    }
    /**
     * 得到自动替换前缀表
     * @param  string $table [description]
     * @return [type]        [description]
     */
    public function dbprefix($table = '')
    {
        return str_replace(DB_PREFIX_YD, DB_PREFIX, $table);
    }

    public function get_page_html($p_conf, $count, $suffix = '', $page = 'page')
    {
        $buffer = null;
        $first = '首页';
        $prev = '上一页';
        $next = '下一页';
        $last = '末页';

        //总页数
        $_page_count = ceil($count / $p_conf->page_size);
        $_page_count = $_page_count ? $_page_count : 1;

        if ($_page_count <= 1) {
            return '';
        }

        if ($_page_count <= 7) {
            $range = range(1, $_page_count);
        } else {
            $min = $p_conf->current_page - 3;
            $max = $p_conf->current_page + 3;
            if ($min < 1) {
                $max += (3 - $min);
                $min = 1;
            }
            if ($max > $_page_count) {
                $min -= ($max - $_page_count);
                $max = $_page_count;
            }
            $min = ($min > 1) ? $min : 1;
            $range = range($min, $max);
        }

        $buffer .= '<ul class="pagination">';
        if ($p_conf->current_page > 1) {
            if ($_page_count >= 10) {
                $buffer .= "<li class=\"first\"><a href='" . $this->_gen_url($page, 1, $suffix) . "'>{$first}</a></li>";
            }
            $buffer .= "<li class=\"prev\"><a href='" . $this->_gen_url($page, $p_conf->current_page - 1, $suffix) . "'>{$prev}</a></li>";
        } else {
            if ($_page_count >= 10) {
                $buffer .= "<li class=\"first disabled\"><a>{$first}</a></li>";
            }
            $buffer .= "<li class=\"prev disabled\"><a>{$prev}</a></li>";
        }

        foreach ($range as $one) {
            if ($one == $p_conf->current_page) {
                $buffer .= "<li class=\"current\"><a href='" . $this->_gen_url($page, $one, $suffix) . "' data-page=\"{($one-1)}\">{$one}</a></li>";
            } else {
                $buffer .= "<li><a href='" . $this->_gen_url($page, $one, $suffix) . "' data-page=\"{($one-1)}\">{$one}</a></li>";
            }
        }
        if ($p_conf->current_page < $_page_count) {
            $buffer .= "<li class=\"next\"><a href='" . $this->_gen_url($page, $p_conf->current_page + 1, $suffix) . "'>{$next}</a></li>";
            if ($_page_count >= 10) {
                $buffer .= "<li class=\"last\"><a href='" . $this->_gen_url($page, $_page_count, $suffix) . "'>{$last}</a></li>";
            }
        } else {
            $buffer .= "<li class=\"next disabled\"><a>{$next}</a></li>";
            if ($_page_count >= 10) {
                $buffer .= "<li class=\"last disabled\"><a>{$last}</a></li>";
            }
        }

        return $buffer . '</ul>';
    }
    private function _gen_url($param, $value, $suffix)
    {

        $request_uri = html_entity_decode($_SERVER['REQUEST_URI']);
        $uri = parse_url($request_uri);
        @parse_str($uri["query"], $valueArray);
        $url = $uri['path'];
        $valueArray[$param] = $value;
        $url = $url . '?' . http_build_query($valueArray);
        if ($suffix) {
            $url .= $suffix;
        }

        return $url;

    }
    /**
     * 获取 bootstrap http 分页参数
     * @return page_config  分页配置类
     */
    public function get_a_config()
    {
        $page_bt_config = new page_bt_config();
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
        return $page_bt_config;
    }
}

<?php
/**
 * @Author: binghe
 * @Date:   2018-05-18 14:36:35
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-08 19:04:33
 */
namespace Service\DbFrame;
use Service\DbFrame\Protocol\IShardTb;
use Service\DbFrame\Config;
/**
* table
*/
abstract class Table
{
    
    private function __construct($shardNode)
    {
      $this->shardNode = $shardNode;
    }
    /**
     * 输入分片节点对象 object
     */
    protected $shardNode;
    /**
     * 所属db (Service\DbFrame\DataBase)
     */
    // public $db;
    /**
     * 实际分片节点
     */
    protected $shardTbNode;
    /**
     * 表名
     */
    protected $tableName;
    /**
     * 判断当前表是否分表
     * @return bool
     */
    public function isShardTb()
    {
        return $this instanceof IShardTb;
    }
    /**
     * 初始
     * @return [type] [description]
     */
    protected function initialize()
    {
        $this->setTableName();
    }
    /**
     * 设置表名
     */
    protected function setTableName()
    {
        $this->tableName = static::getShardTableName($this->shardNode);
    }
    /**
     * 分片规则 0-0-0 实例-库-表
     * @var string
     */
    public static function instance($shardNode = '0-0-0')
    {
        is_string($shardNode) && $shardNode = ShardNode::getInstance($shardNode);
        $instance = new static($shardNode);
        $instance->isShardTb() && $instance->shardTbNode = $shardNode->shardTbNode;
        $instance->initialize();
        return $instance;
    }
    /**
     * 分片规则 方法简写 0-0-0 实例-库-表 
     * @param  mixed $mixed [description]
     * @return $this
     */
    public static function i($shardNode = '0-0-0')
    {
        is_callable([static::class, 'shardNodeHandler']) && $shardNode = static::shardNodeHandler($shardNode);
        return self::instance($shardNode);
    }
    /**
     * 得到逻辑表名
     * @return string
     */
    protected static function getTableName()
    {
        $className = static::class;
        $tmp = explode('\\', $className);
        $matchCount = preg_match('/^([a-zA-Z0-9]+)'.Config::TABLE_CLASS_SUFFIX.'$/',end($tmp),$matchs);
        if($matchCount != 1 )
          throw new \Exception("db get table name error");
        $tableName = Config::TABLE_PREFIX.ltrim(strtolower(preg_replace("/([A-Z])/", "_\\1", $matchs[1])), "_");
        return $tableName;
    }
    /**
     * 得到分片表名
     * @param $shardNode object/string
     * @return string
     */
    public static function getShardTableName($shardNode = '0-0-0')
    {
      is_string($shardNode) && $shardNode = ShardNode::getInstance($shardNode);
      static $cache = [];
      $className = static::class;
      if (!isset($cache[$className])) {
          $class = new \ReflectionClass($className);
          $cache[$className] = $class->implementsInterface(IShardTb::class);
      }
      $tableName = static::getTableName();
      if($cache[$className] && $shardNode->shardTbNode != 0)
        $tableName .= '_'.$shardNode->shardTbNode;
      return $tableName;

    }
    /**
     * 创建数据库的抽象方法
     * @param  [type] $shardNode [description]
     */
    public abstract function createDb($shardNode);
    /**
     * 魔术方法
     */
    public function __get($name)
    {
        if($name == 'db')
            return $this->db = $this->createDb($this->shardNode);
        else return null;
    }

    /**********************以下是方法**********************/
    /**
     * 返回第一个值
     * @param $where 数字或者为字符串 数组形式的条件
     * @param $fields string 取字段
     * @return object
     */
    public function getOne($where = false, $fields = "*", $orderBy = false)
    {
        if ($where) {
            $this->db->where($where);
        }
        $this->db->select($fields)->from($this->tableName);
        if ($orderBy) {
            $this->db->order_by($orderBy);
        }

        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row();
    }

    /**
     * 返回第一个值
     * @param $where 数字或者为字符串 数组形式的条件
     * @param  $like  field字段  str检索变量   type=both/befor/after
     * @param $fields string 取字段
     * @return object
     */
    public function getOneLike( $like = false ,$where = false, $fields = "*", $orderBy = false)
    {
        if ($where) {
            $this->db->where($where);
        }
        $this->db->select($fields)->from($this->tableName);
        if ($orderBy) {
            $this->db->order_by($orderBy);
        }

        if ($like) {
            $this->db->like($like['field'], $like['str'], $like['type']);
        }

        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row();
    }
    /**
     * 得到最大记录数
     * @param  string  $field 字段
     * @param  $where 数字或者为字符串 数组形式的条件
     * @return object
     */
    public function selectMax($field, $where = false)
    {
        if (empty($field)) {
            return null;
        }
        if ($where) {
            $this->db->where($where);
        }
        $query = $this->db->select_max($field)->from($this->tableName)->get();
        return $query->row();
    }
    //得到最大记录数
    public function selectMin($field, $where = false)
    {
        if (empty($field)) {
            return null;
        }

        if ($where) {
            $this->db->where($where);
        }

        $query = $this->db->select_min($field)->from($this->tableName)->get();
        return $query->row();
    }
    /**
     * @brief 返回第一个值
     * @param $where 数字或者为字符串 数组形式的条件
     * @param $fields string 取字段
     * @return $array
     */
    public function getOneArray($where = false, $fields = "*", $orderBy = false)
    {
        if ($where) {
            $this->db->where($where);
        }
        $this->db->select($fields)->from($this->tableName);
        if ($orderBy) {
            $this->db->order_by($orderBy);
        }

        $this->db->limit(1);
        $query = $this->db->get();
        return $query->row_array();
    }
    /**
     * 得到某字段的各
     * @param  string $field 字段
     * @param  mixed $where 条件
     * @return int        和
     */
    public function getSum($field, $where = false)
    {
        if (empty($field) or !is_string($field)) {
            return 0;
        }

        $this->db->select_sum($field);
        if (!empty($where)) {
            $this->db->where($where);
        }

        $row = $this->db->from($this->tableName)->get()->row();
        if ($row->$field) {
            return $row->$field;
        } else {
            return 0;
        }

    }
    /**
     * [得到所有的条数]
     * @param  [array] $where [description]
     * @return [int]        [total]
     */
    public function getCount($where)
    {
        $this->db->select('count(1) as num');
        if ($where) {
            $this->db->where($where);
        }

        $row = $this->db->from($this->tableName)->get()->row();
        return $row->num;
    }
    /**
     * 得到所有 limit只限一个参数
     */
    public function getAll($where = false, $fields = '*', $orderBy = false, $limit = false)
    {
        $this->db->select($fields)->from($this->tableName);
        if ($where) {
            $this->db->where($where);
        }

        if ($orderBy) {
            $this->db->order_by($orderBy);
        }

        if ($limit) {
            $this->db->limit($limit);
        }

        $query = $this->db->get();
        return $query->result();
    }
    /**
     * 得到所有 limit只限一个参数
     */
    public function getAllArray($where = false, $fields = '*', $orderBy = false, $limit = false)
    {
        $this->db->select($fields)->from($this->tableName);
        if ($where) {
            $this->db->where($where);
        }

        if ($orderBy) {
            $this->db->order_by($orderBy);
        }

        if ($limit) {
            $this->db->limit($limit);
        }

        $query = $this->db->get();
        return $query->result_array();
    }
    /**
     * 批量更新
     * @author dadi
     * 2016/10/21
     */
    public function updateBatch($data = [], $key = '')
    {
        if (!$data || !$key) {
            return false;
        }
        $this->db->update_batch($this->tableName, $data, $key);
        return $this->db->affected_rows();
    }
    /**
     * 删除
     * @param  [array] $where [条件]
     * @return [int]        [删除条数]
     */
    public function delete($where)
    {
        if (empty($where)) //防止全部删掉
        {
            return 0;
        }

        $this->db->where($where);
        $this->db->delete($this->tableName);
        return $this->db->affected_rows();

    }
    /**
     * whereIn 删除
     * @param  array $whereIn 数组
     * @param  string $key      默认为id
     * @return int          影响条数
     */
    public function inDelete($whereIn, $key = 'id')
    {
        if (empty($whereIn)) {
            return 0;
        }

        $this->db->where_in($key, $whereIn)->delete($this->tableName);
        return $this->db->affected_rows();
    }
    //插入数据
    public function create($data)
    {
        //自动添加时间
        if (!isset($data['time']) and $this->db->field_exists('time', $this->tableName)) {
            $data['time'] = time();
        }

        $this->db->insert($this->tableName, $data);
        if ($this->db->affected_rows()) {
            return $this->db->insert_id();
        }
        return 0;
    }
    public function createBatch($dataArr)
    {
        return $this->db->insert_batch($this->tableName, $dataArr);
    }
    //更新
    public function update($data, $where = false)
    {
        //防止全表更新
        if (!$where) {
            return 0;
        }
        $this->db->where($where);
        $this->db->update($this->tableName, $data);
        return $this->db->affected_rows();
    }
    //ar操作 无须设置table,多表可设置
    //field default *
    public function getEntitysByAR($arArr, $returnArray = false)
    {
        //field *
        if (isset($arArr['field']) && !empty($arArr['field'])) {
            $this->db->select($arArr['field']);
        } else {
            $this->db->select('*');
        }

        //table
        if (isset($arArr['table']) && !empty($arArr['table'])) {
            $this->db->from($arArr['table']);
        } else {
            $this->db->from($this->tableName);
        }

        //where
        if (isset($arArr['where']) && !empty($arArr['where'])) {
            $this->db->where($arArr['where']);
        }

        //where_str
        if (isset($arArr['where_str']) && !empty($arArr['where_str'])) {
            if (is_string($arArr['where_str'])) {
                $this->db->where($arArr['where_str']);
            } elseif (is_array($arArr['where_str'])) {
                foreach ($arArr['where_str'] as $where_str_key => $where_str_value) {
                    $this->db->where($where_str_value);
                }
            }
        }
        //where_in
        if (isset($arArr['where_in']) && !empty($arArr['where_in'])) {
            foreach ($arArr['where_in'] as $where_in_key => $where_in_value) {
                $this->db->where_in($where_in_key, $where_in_value);
            }

        }
        //like
        if (isset($arArr['like']) && is_array($arArr['like'])) {
            foreach ($$arArr['like'] as $like_key => $like_value) {
                $like_match;
                $like_lbr = 'both';
                if (is_string($like_value)) {
                    $like_match = $like_value;
                } else if (is_array($like_value)) {
                    $like_match = $like_value[0];
                    $like_lbr = $like_value[1];
                }
                $this->db->like($like_key, $like_match, $like_lbr);
            }
        }
        //order 只支付string
        if (isset($arArr['order_by'])) {
            if (is_string($arArr['order_by'])) {
                $this->db->order_by($arArr['order_by']);
            }

        }
        //group_by
        if (isset($arArr['group_by'])) {
            $this->db->group_by($arArr['group_by']);
        }
        //limit
        if (isset($arArr['limit'])) {
            if (is_numeric($arArr['limit'])) {
                $this->db->limit($arArr['limit']);
            } else if (is_array($arArr['limit']) && count($arArr) == 2) {
                $this->db->limit($arArr['limit'][0], $arArr['limit'][1]);
            }

        }
        //join
        if (isset($arArr['join'])) {
            $join_table;
            $join_con;
            $join_type = '';
            foreach ($arArr['join'] as $join) {
                $join_table = $join[0];
                $join_con = $join[1];
                if (isset($join[2])) {
                    $join_type = $join[2];
                }

                $this->db->join($join_table, $join_con, $join_type);
            }
        }
        if ($returnArray) {
            return $this->db->get()->result_array();
        } else {
            return $this->db->get()->result();
        }
    }

    /**
     * 设置字段增量
     * @param $field
     * @param $number
     * @param $where
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function setInc($field, $number, $options)
    {
        if (is_numeric($options)) {
            $this->db->where('id', $options);
        } else {
            $this->_parse_options($options);
        }
        $this->db->set($field, $field . '+' . $number, FALSE);
        return $this->db->update($this->tableName);
    }

    /**
     * 设置字段减少量
     * @param $field
     * @param $number
     * @param $where
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function setDec($field, $number, $options)
    {
        if (is_numeric($options)) {
            $this->db->where('id', $options);
        } else {
            $this->_parse_options($options);
        }
        $this->db->set($field, $field . '-' . $number, FALSE);
        return $this->db->update($this->tableName);
    }

    /**
     * 更新扩展方法
     * @param $info
     * @param $options
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function updateExt($info, $options)
    {
        if (is_numeric($options)) {
            $this->db->where('id', $options);
        } else {
            $this->_parse_options($options);
        }
        return $this->db->update($this->tableName, $info);
    }

    /**
     * 删除扩展方法
     * @param $info
     * @param $options
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function deleteExt($options)
    {
        if (is_numeric($options)) {
            $this->db->where('id', $options);
        } else {
            $this->_parse_options($options);
        }
        $this->db->delete($this->tableName);
        return $this->db->affected_rows();
    }

    /**
     * getAll 扩展方法
     * @param array $options
     * @param string $fields
     * @param array $orderby
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getAllExt($options = [], $fields = '*', $orderby = [])
    {
        //处理查询条件
        $this->_parse_options($options);
        //处理排序
        if (!empty($orderby)) {
            foreach ($orderby as $ok => $ov) {
                $this->db->order_by($ok, $ov);
            }
        }
        $result = $this->db->select($fields)->get($this->tableName)->result();
        return $result;
    }

    /**
     * getSum 扩展方法
     * @param $field
     * @param array $options
     * @return bool|int
     * @author ahe<ahe@iyenei.com>
     */
    public function getSumExt($field, $options = array())
    {
        if (empty($options)) {
            return false;
        }

        //处理查询条件
        $this->_parse_options($options);
        $result = $this->db->select_sum($field)->get($this->tableName)->row_array();
        return !empty($result) ? $result[$field] : 0;
    }


    private $_methods = array('where_in', 'or_where', 'or_where_in', 'where_not_in', 'or_where_not_in', 'having');

    /**
     * 封装查询参数
     * @param array $options
     * @author ahe<ahe@iyenei.com>
     */
    protected function _parse_options($options = array())
    {
        if (!empty($options)) {
            foreach ($options as $key => $val) {
                if (in_array($key, $this->_methods)) {
                    if (is_string($val)) {
                        $this->db->$key($val);
                    } else {
                        foreach ($val as $k => $v) {
                            $this->db->$key($k, $v);
                        }
                    }
                } else {
                    if ($key == 'where') {
                        if (is_array($val)) {
                            foreach ($val as $vk => $vv) {
                                is_numeric($vk) ? $this->db->where($vv) : $this->db->where($vk, $vv);
                            }
                        } else {
                            $this->db->where($val);
                        }
                    } else {
                        $this->db->$key($val);
                    }
                }
            }
        }
    }
}

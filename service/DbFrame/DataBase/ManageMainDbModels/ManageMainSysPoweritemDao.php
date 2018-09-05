<?php
namespace Service\DbFrame\DataBase\ManageMainDbModels;
/**
* 系统菜单
*/
class ManageMainSysPoweritemDao extends BaseDao
{
    /**
     * 得到所有子节点，大数量时，请分布获取
     * @param  [int] $p_id 父id
     * @return stdClasses      [description]
     */
    function getAllNodes($p_id=0)
    {

        $sql="select * from {$this->tableName} where FIND_IN_SET(id,fun_power_childs({$p_id})) order by sort desc,id asc;";
        return $this->db->query($sql)->result();
    }
    //得到子节点的数量
    function getPidCounts($pids=array())
    {
        if(empty($pids))
            return array();
        $where_in=sql_where_in($pids);
        $sql="select p_id,count(*) as count from {$this->tableName} where p_id in({$where_in}) group by p_id";
        return $this->db->query($sql)->result();
    }
    //得到ids(包括子节点)
    function getNodeTgids($id)
    {
        $sql="SELECT `fun_power_childs`({$id}) as ids;";
        $row=$this->db->query($sql)->row();
        return $row->ids;

    }
    /**
     * 更新排序
     * @param int $sort 自增长，可为负
     * @param ids  $ids   条件
     */
    function setSelfSort($sort,$where)
    {
        if(empty($sort) && empty($where))
            return 0;
        $sql="UPDATE {$this->tableName} SET sort=$sort+{$sort} WHERE {$where};";
        $this->db->query($sql);
        return $this->db->affected_rows();
    }
    /**
     * 是否受保护
     * @return boolean [description]
     */
    function isProFun($path)
    {
        if(empty($path))
            return false;
        $sql="SELECT COUNT(*) AS num FROM {$this->tableName} WHERE CONCAT(',',pro_funs,',') LIKE '%,{$path},%'";
        $row =$this->db->query($sql)->row();
        return $row->num;
    }

}
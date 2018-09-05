<?php
namespace Service\DbFrame\DataBase\ManageMainDbModels;
/**
* 系统用户
*/
class ManageMainSysAccountDao extends BaseDao
{
    /**
     * @param $role_id
     * @return mixed
     */
    public function getRidCount($role_id=0){
        if(!$role_id || empty($role_id))  return 0;

        $where = " CONCAT(',', rid, ',') LIKE CONCAT('%,', $role_id, ',%')";
        $count = $this->getCount($where);
        if($count > 0) return $count;
        return 0;
    }
}
<?php
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 外卖拼单购物车拼单人
 * @author dadi
 */
class WmPindanUserDao extends BaseDao
{
    // 清空购物车拼单人
    public function resetUser($pdid)
    {
        return $this->delete(['pdid' => $pdid]);
    }
}

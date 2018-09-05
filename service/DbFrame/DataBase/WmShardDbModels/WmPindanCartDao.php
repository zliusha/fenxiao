<?php
namespace Service\DbFrame\DataBase\WmShardDbModels;
/**
 * 外卖拼单购物车
 * @author dadi
 */
class WmPindanCartDao extends BaseDao
{
    // 清空购物车列表
    public function resetCart($pdid)
    {
        return $this->delete(['pdid' => $pdid]);
    }

    //删除购物车
    public function deleteCart($where)
    {
        if (empty($where)) {
            return false;
        }

        return $this->delete($where);
    }
}

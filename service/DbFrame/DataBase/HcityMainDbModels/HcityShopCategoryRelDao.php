<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午8:17
 */

namespace Service\DbFrame\DataBase\HcityMainDbModels;


use Service\DbFrame\DataBase\HcityMainDb;

class HcityShopCategoryRelDao extends BaseDao
{
    /**
     * 删除关系
     * @param int $shopId
     * @param array $categoryIds
     * @return int
     */
    public function deleteRel(int $shopId, array $categoryIds)
    {
        $this->db->where(['shop_id' => $shopId]);
        $this->db->where_in('category_id', $categoryIds);
        $this->db->delete($this->tableName);
        return $this->db->affected_rows();
    }

    /**
     * 通过店铺id获取
     * @param int $aid
     * @param array $shopIds
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getRelByShopId(int $aid, array $shopIds)
    {
        $tables = HcityMainDb::i()->tables;
        $sql = sprintf("SELECT a.shop_id,a.category_id,b.name FROM %s a left join %s b on a.category_id = b.id where a.aid = %d and a.shop_id in (%s);", $this->tableName, $tables['hcity_shop_category'], $aid, implode(',', $shopIds));
        $query = $this->db->query($sql);
        return $query->result();
    }
}
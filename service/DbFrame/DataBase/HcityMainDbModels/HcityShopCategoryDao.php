<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午5:28
 */

namespace Service\DbFrame\DataBase\HcityMainDbModels;


class HcityShopCategoryDao extends BaseDao
{
    /**
     * 通过shop_id获取数据
     * @param array $categoryIds
     * @param string $fields
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getAllByCategoryId(array $categoryIds, string $fields = '*')
    {
        $this->db->select($fields)->from($this->tableName);
        $this->db->where_in('id', $categoryIds);
        $this->db->order_by('id desc');

        $query = $this->db->get();
        return $query->result();
    }
}
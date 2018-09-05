<?php

/**
 * @Author: binghe
 * @Date:   2018-07-10 15:14:45
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-10 15:22:16
 */
namespace Service\DbFrame\DataBase\HcityMainDbModels;
class HcityUserCollectShopDao extends BaseDao
{

    /**
     * 通过店铺id获取
     * @param int $aid
     * @param array $shopIds
     * @param string $fields
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getAllByShopIds(int $uid, array $shopIds, string $fields = '*')
    {
        $this->db->select($fields)->from($this->tableName);
        $this->db->where('uid', $uid);
        $this->db->where_in('shop_id', $shopIds);
        $this->db->order_by('id desc');

        $query = $this->db->get();
        return $query->result_array();
    }
}
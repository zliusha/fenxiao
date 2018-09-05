<?php

/**
 * @Author: binghe
 * @Date:   2018-07-10 15:12:50
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-10 15:22:50
 */
namespace Service\DbFrame\DataBase\HcityMainDbModels;
class HcityUserCollectGoodsDao extends BaseDao
{

    /**
     * 通过商品id获取
     * @param int $aid
     * @param array $gooddIds
     * @param string $fields
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getAllByGoodsIds(int $uid, array $gooddIds, string $fields = '*')
    {
        $this->db->select($fields)->from($this->tableName);
        $this->db->where('uid', $uid);
        $this->db->where_in('goods_id', $gooddIds);
        $this->db->order_by('id desc');

        $query = $this->db->get();
        return $query->result_array();
    }
}
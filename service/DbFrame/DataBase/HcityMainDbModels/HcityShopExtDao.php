<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/7/9
 * Time: 下午8:16
 */

namespace Service\DbFrame\DataBase\HcityMainDbModels;


class HcityShopExtDao extends BaseDao
{
    /**********入驻商圈审核状态***********/
    const AUDIT_WAIT = 1; //待审核
    const AUDIT_PASS = 2; //审核通过
    const AUDIT_REFUSE = 3; //审核拒绝

    /**********入驻商圈状态***********/
    const SHOW_ABNORMAL = 0; //未入驻
    const SHOW_NORMAL = 1; //已入驻
    const SHOW_CANCEL = 2; //被清退

    /**********一店一码状态***********/
    const BARCODE_DISABLED = 0; //未开通
    const BARCODE_AVAILABLE = 1; //已开通

    /**
     * 通过店铺id获取
     * @param int $aid
     * @param array $shopIds
     * @param string $fields
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getAllByShopId(int $aid, array $shopIds, string $fields = '*')
    {
        $this->db->select($fields)->from($this->tableName);
        $this->db->where('aid', $aid);
        $this->db->where_in('shop_id', $shopIds);
        $this->db->order_by('id desc');

        $query = $this->db->get();
        return $query->result();
    }

}
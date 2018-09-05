<?php

/**
 * @Author: feiying
 * @Date:   2018-07-25 17:31:06
 * @Last Modified by:  feiying
 */
namespace Service\DbFrame\DataBase\HcityMainDbModels;
class HcityUserBalanceRecordDao extends BaseDao
{
	public $userBalanceStatus = [
					'1' => '提现',
					'2' => '商品销售收入',
					'3' => '商品分享收入',
                    '4' => '邀请办卡收入',
                    '5' => '邀请商家收入',
					'6' => '购买商品消费',
                    '7' => '邀请骑士收入',
                    '8' => '骑士邀请商家入驻',
                    '9' => '骑士新手任务',
                    '10' => '充值',
				];

    /**
     * 下级会员贡献收益
     * @param int $uid
     * @param array $lowerUids
     * @return array
     * @author ahe<ahe@iyenei.com>
     */
    public function contributeIncomeByUid(int $uid, array $lowerUids)
    {
        $this->db->where(['uid' => $uid]);
        $this->db->where_in('lower_uid', $lowerUids);
        $this->db->select('lower_uid');
        $this->db->select_sum('money');
        $this->db->group_by('lower_uid');
        $row = $this->db->from($this->tableName)->get()->result();
        return $row;
    }

    /**
     * 下级商户贡献收益
     * @param int $uid
     * @param array $lowerShopIds
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function contributeIncomeByShopId(int $uid, array $lowerShopIds)
    {
        $this->db->where(['uid' => $uid]);
        $this->db->where_in('lower_shop_id', $lowerShopIds);
        $this->db->select('lower_shop_id');
        $this->db->select_sum('money');
        $this->db->group_by('lower_shop_id');
        $row = $this->db->from($this->tableName)->get()->result();
        return $row;
    }
}
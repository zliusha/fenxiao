<?php

/**
 * @Author: binghe
 * @Date:   2018-07-11 15:26:23
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-18 15:43:13
 */
namespace Service\DbFrame\DataBase\MainDbModels;
use Service\Enum\SaasEnum;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
/**
 * 公司
 */
class MainShopDao extends BaseDao
{
    /**
     * 只通过店铺id查找
     * @param array $shopIds
     * @param string $fields
     * @return mixed
     * @author ahe<ahe@iyenei.com>
     */
    public function getAllByShopId(array $shopIds, string $fields = '*')
    {
        $this->db->select($fields)->from($this->tableName);
        $this->db->where_in('id', $shopIds);
        $this->db->order_by('id desc');

        $query = $this->db->get();
        return $query->result();
    }
    /**
     * 更新操作，同步更新关联门店
     * @param  array  $data  [description]
     * @param  array $where 数组条件
     */
    public function syncUpdateOne($data, array $where)
    {
        $affectedRows = $this->update($data,$where);
        //只更新一条数据库，触发同步更新操作
        if($affectedRows === 1)
        {
            $this->_syncUpdateOne($data,$where);
        }
        return $affectedRows;
    }
    /**
     * 更新操作，同步更新关联门店
     * @param  array  $data  [description]
     * @param  array $where 数组条件
     */
    private function _syncUpdateOne($data, array $where)
    {
        if(!isset($where['id']))
            return 0;
        $uData=[];
        isset($data['shop_name']) && $uData['shop_name'] = $data['shop_name'];
        isset($data['shop_logo']) && $uData['shop_logo'] = $data['shop_logo'];
        isset($data['contact']) && $uData['contact'] = $data['contact'];
        isset($data['shop_state']) && $uData['shop_state'] = $data['shop_state'];
        isset($data['shop_city']) && $uData['shop_city'] = $data['shop_city'];
        isset($data['shop_district']) && $uData['shop_district'] = $data['shop_district'];
        isset($data['region']) && $uData['region'] = $data['region'];
        isset($data['shop_address']) && $uData['shop_address'] = $data['shop_address'];
        isset($data['longitude']) && $uData['longitude'] = $data['longitude'];
        isset($data['latitude']) && $uData['latitude'] = $data['latitude'];
        if(!$uData)
            return 0;
        //目前只需要同步云店门店
        $mMainShopRefitems = MainShopRefitemDao::i()->getOne(['saas_id'=>SaasEnum::YD,'shop_id'=>$where['id']]);
        if(!$mMainShopRefitems)
            return 0;
        $wmShopDao = WmShopDao::i($mMainShopRefitems->aid);
        return $wmShopDao->update($uData,['id'=>$mMainShopRefitems->ext_shop_id]);
    }

}
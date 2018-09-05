<?php

/**
 * @Author: binghe
 * @Date:   2018-06-29 11:39:58
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-07-28 10:45:34
 */
namespace Service\DbFrame\DataBase\WmShardDbModels;
use Service\Enum\SaasEnum;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
/**
 * 公司
 */
class WmShopDao extends BaseDao 
{
	/**
     * 添加金额
     * @param decimal $money  金额
     * @param int $aid 公司id
     * @param int $shopId    门店id
     * @return bool
     */
    function addMoney($money,$aid,$shopId,$des='')
    {

        $this->db->query("UPDATE `{$this->tableName}` SET `money`=`money`+{$money}  WHERE `id`={$shopId}");
        if($this->db->affected_rows())
        {
            //记录
            $data['aid']=$aid;
            $data['shop_id']=$shopId;
            $data['money']=$money;
            $pay_type=1;
            if($money<0)
                $pay_type=0;
            $data['pay_type']=$pay_type;
            $data['des']=$des;
            $wmMoneyRecordDao = WmMoneyRecordDao::i($this->shardNode);
            if($wmMoneyRecordDao->create($data))
                return true;
            else
                return false;
        }
        else return false;
    }
    /**
     * 添加提现金额，并减去金额
     * @param decimal $money  提现金额
     * @param int $aid 公司id
     * @param int $shopId    门店id
     * @return bool
     */
    function addWithdrawMoney($money,$aid,$shopId)
    {
        $this->db->query("UPDATE `{$this->tableName}` SET `money`=`money`-{$money},`withdraw_money` =`withdraw_money`+{$money} WHERE `id`={$shopId}");
        if($this->db->affected_rows())
        {
            //记录
            $data['aid']=$aid;
            $data['shop_id']=$shopId;
            $data['money']=$money;
            $pay_type=0;
            $data['pay_type']=$pay_type;
            $data['des']='提现';
            $wmMoneyRecordDao = WmMoneyRecordDao::i($this->shardNode);
            if($wmMoneyRecordDao->create($data))
                return true;
            else
                return false;
        }
        else return false;
    }

    /**
     * 获取店铺IDS
     * @param bool $where
     * @return string
     */
    public function getShopIds($where=false)
    {
        $list = $this->getAllArray($where, 'id');
        $shop_ids = trim(implode(',',array_column($list, 'id')), ',');
        if(empty($shop_ids)) $shop_ids = '0';
        return $shop_ids;
    }
    /**
     * 同步创建门店|同步创建已有门店
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function syncAutoCreate(array $data)
    {
        $mainShopId = null;
        if(!empty($data['main_shop_id']))
        { 
            $mainShopId = $data['main_shop_id'];
            return $this->createSyncUpdate($data);
        }
        else
            return $this->syncCreateOne($data);
    }
    /**
     * 创建门店通已有店铺，并同步数据
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function createSyncUpdate(array $data)
    {
        //1.创建门店
        $id = $this->create($data);
        if(!$id)
            return 0;
        $uData['saas_id'] = SaasEnum::YD;
        $uData['shop_id'] = $data['main_shop_id'];
        $uData['ext_shop_id'] = $id;
        $uData['aid'] = $data['aid'];
        //2.关联
        $refitemId = MainShopRefitemDao::i()->create($uData);
        //3.同步数据
        $this->_syncUpdateOne($data,['id'=>$id,'aid'=>$data['aid']]);
        return $id;
    }
    /**
     * 同步创建门店
     * @param  array  $data [description]
     */
    public function syncCreateOne(array $data)
    {
        $mainShopId = $this->_syncCreateOne($data);
        if(!$mainShopId)
            return 0;
        $data['main_shop_id'] = $mainShopId;
        //自动添加时间
        $id = $this->create($data);
        if(!$id)
            return 0;
        $uData['saas_id'] = SaasEnum::YD;
        $uData['shop_id'] = $mainShopId;
        $uData['ext_shop_id'] = $id;
        $uData['aid'] = $data['aid'];
        $refitemId = MainShopRefitemDao::i()->create($uData);
        return $id;
    }
    /**
     * 创建主库门店
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function _syncCreateOne($data)
    {
        $uData=[];
        isset($data['aid']) && $uData['aid'] = $data['aid'];
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
        //创建门店关联
        return MainShopDao::i()->create($uData);
    }
    /**
     * 更新操作，同步更新关联门店
     * @param  array  $data  [description]
     * @param  array $where 数组条件 id,aid必带
     */
    public function syncUpdateOne($data, array $where)
    {
        if(!isset($where['id']) || !isset($where['aid']))
            return 0;
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
        if(!isset($where['id']) || !isset($where['aid']))
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
        $mMainShopRefitems = MainShopRefitemDao::i()->getOne(['aid'=>$where['aid'],'saas_id'=>SaasEnum::YD,'ext_shop_id'=>$where['id']]);
        if(!$mMainShopRefitems)
            return 0;
        $mainShopDao = MainShopDao::i();
        return $mainShopDao->update($uData,['id'=>$mMainShopRefitems->shop_id]);
    }
}
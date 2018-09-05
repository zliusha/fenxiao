<?php
/**
 * @Author: binghe
 * @Date:   2018-01-29 15:58:30
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 18:44:59
 */
use Service\Cache\AidToVisitIdCache;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
/**
* erp 
*/
class event_bll extends base_bll
{
    public $erp_sdk = null;
    //门店
    const MNS_SHOP = 'mnsShop';
    const MNS_ORDER = 'mnsOrder';
    const MNS_GOODS = 'mnsGoods';
    //暂不采用工厂事件
    // public $event_map = [
    //     'shop_update'   //门店修改事件,包括
    // ];
    public function __construct()
    {
        parent::__construct();
        $this->erp_sdk = new erp_sdk;
    }
    /**
     * 修改、删除、添加 门店时调用
     * @param $input 必填参数visit_id,aid,expired服务过期时间
     * @return [type] [description]
     */
    public function mnsShop($input)
    {   
        $wmShopDao = WmShopDao::i($input['aid']);
        $shop_arr = $wmShopDao->getAllArray(['aid'=>$input['aid'],'is_delete'=>0],'id,aid,shop_name');
        if(isset($input['visit_id']))
            $content['visit_id'] = $input['visit_id'];
        else
            $content['visit_id'] = $this->convertAidToVisitId($input['aid']);
        $content['shops']=$shop_arr;
        $params=[self::MNS_SHOP,json_encode($content)];
        return $this->erp_sdk->mnsPublishTopicMsg($params);
    }
    /**
     * 订单-创建,更新时调用
     * @param  array $input 必填aid,shop_id,tradeno,status,status_alias;可选visit_id
     * @return [type]        [description]
     */
    public function mnsOrder($input)
    {
        if(isset($input['visit_id']))
            $content['visit_id'] = $input['visit_id'];
        else
            $content['visit_id'] = $this->convertAidToVisitId($input['aid']);
        $content['aid'] = $input['aid'];
        $content['shop_id'] = $input['shop_id'];
        $content['tradeno']=$input['tradeno'];
        $content['status']=$input['status'];
        $content['status_alias']=$input['status_alias'];
        $params=[self::MNS_ORDER,json_encode($content)];
        return $this->erp_sdk->mnsPublishTopicMsg($params);
    }
    /**
     * 商品-添加(add)，删除(delete)，修改(update)，上下架(online,offline),同步商品(sync)
     * @param array $input 必填:aid,items:[shop_id,goods_id:'111,222,333'],operation 可选visit_id
     * @return [type] [description]
     */
    public function mnsGoods($input)
    {
        if(isset($input['visit_id']))
            $content['visit_id'] = $input['visit_id'];
        else
            $content['visit_id'] = $this->convertAidToVisitId($input['aid']);
        $content['aid'] = $input['aid'];
        $content['items'] = $input['items'];
        $content['operation'] = $input['operation'];
        $params = [self::MNS_GOODS,json_encode($content)];
        return $this->erp_sdk->mnsPublishTopicMsg($params);
    }
    /**
     * aid to visit_id
     * @return [type] [description]
     */
    public function convertAidToVisitId($aid)
    {
        $aidToVisitIdCache = new AidToVisitIdCache(['aid'=>$aid]);
        $visit_id = $aidToVisitIdCache->getDataASNX();
        return $visit_id;
    }
}
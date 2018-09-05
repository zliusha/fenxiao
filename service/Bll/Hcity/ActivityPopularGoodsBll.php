<?php
/**
 * Created by PhpStorm.
 * User: yize
 * Date: 2018/7/27
 * Time: 14:58
 */
namespace Service\Bll\Hcity;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsApplyViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityPopularGoodsDao;
use Service\DbFrame\DataBase\HcityMainDb;

class ActivityPopularGoodsBll extends \Service\Bll\BaseBll
{

    /**
     * 爆款商品列表
     * @param array $fdata
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function activityPopularList(\s_hmanage_user_do $sUser,array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        if($fdata['region'])
        {
            $sql =  " and a.region = '{$fdata['region']}'";
            $p_conf->where .= " and a.region = '{$fdata['region']}'";
        }else
        {
            //当没有选择是地址时 直接return空
            $arr=array();
            if($sUser->type==1)
            {
                 return $arr;
            }
            if($sUser->type==0 && $sUser->region_type==1) 
            {
             $sql = " and a.region = '{$sUser->region}'"; 
             $p_conf->where .= " and a.region = '{$fdata['region']}'";
            }
        }
         if($sUser->type==0)
        {
            $sql .=" and b.is_default = 0";
        }else
        {
            $sql .=" and b.is_default = 1";
        }
        $p_conf->fields='a.id,a.aid,a.goods_id,a.shop_id,a.title,a.pic_url,a.group_price,a.hcard_price,a.shop_name,a.region,a.shop_city,a.shop_district,b.id as bid,b.xc_pic_url,b.sort';
        $p_conf->table="{$hcityMainDb->tables['hcity_goods_kz_view'] } a left join {$hcityMainDb->tables['hcity_activity_popular_goods']} b on a.goods_id = b.goods_id and a.aid = b.aid".$sql;
        $p_conf->where .= " and a.hcity_status=1";
        if($fdata['title'])
        {
            $p_conf->where .=" and a.title like '%{$page->filterLike($fdata['title'])}%'";
        }
        $p_conf->order= 'b.id desc, b.sort asc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        $rows['total']=$count;
        return $rows;
    }
    /**
     * 爆款商品列表
     * @param array $fdata
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function activityPopularAdd(\s_hmanage_user_do $sUser,array $fdata)
    {
        $where='id='.$fdata['id'];
        $where.=" and hcity_status=1";
        //城市合伙人和嘿卡管理员区分
        if($sUser->type==0)
        {
            $where .= " and region like '{$sUser->region}%'";
            //如果城市合伙人没有地址筛选则直接用合伙人地址
            if(!$fdata['region'])
            {
                $fdata['region']=$sUser->region;
            }
            //判断一下是不是属于城市合伙人地区的商品
            if(strpos($fdata['region'],$sUser->region) ===false){
                throw new Exception('此地区商品不属于你的权限');
            }
        }
        //检查商品是否存在 属于这个地区
        $goodsInfo=HcityGoodsKzViewDao::i()->getOne($where);
        if(!$goodsInfo)
        {
            throw new Exception('信息不存在');
        }
        $activityPopularGoodsDb= HcityActivityPopularGoodsDao::i();
        $activityWhere['region']=$fdata['region'];
        $params=[
            'aid'=>$goodsInfo->aid,
            'goods_id'=>$goodsInfo->goods_id,
            'shop_id'=>$goodsInfo->shop_id,
            'region'=>$fdata['region'],
            'xc_pic_url'=>$fdata['xc_pic_url'],
            'sort'=>$fdata['sort']
        ];
        //城市合伙人和嘿卡管理员区分
        if($sUser->type==0)
        {
            $activityWhere['is_default']=0;
            $params['is_default']=0;
        }else
        {
            $activityWhere['is_default']=1;
            $params['is_default']=1;
        }
        $activityInfo=$activityPopularGoodsDb->getCount($activityWhere);
        if($activityInfo>=5)
        {
            throw new Exception('爆款商品已经存在上限5个,无法添加');
        }
        //开启事物
        $activityPopularGoodsDb->db->trans_start();
        $activityPopularGoodsDb->create($params);
        if ($activityPopularGoodsDb->db->trans_status())
        {
            $activityPopularGoodsDb->db->trans_complete();
            return true;
        }else
        {
            $activityPopularGoodsDb->db->trans_rollback();
            throw new Exception('设置失败');
        }
    }

    /**
     * 爆款商品列表
     * @param array $fdata
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function activityPopularEdit(\s_hmanage_user_do $sUser,array $fdata)
    {
        $where='id='.$fdata['id'];
        $where.=" and hcity_status=1";
        //城市合伙人和嘿卡管理员区分
        if($sUser->type==0)
        {
            $where .= " and region like '{$sUser->region}%'";
            //如果城市合伙人没有地址筛选则直接用合伙人地址
            if(!$fdata['region'])
            {
                $fdata['region']=$sUser->region;
            }
            //判断一下是不是属于城市合伙人地区的商品
            if(strpos($fdata['region'],$sUser->region) ===false){
                throw new Exception('此地区商品不属于你的权限');
            }
        }
        //检查商品是否存在 属于这个地区
        $goodsInfo=HcityGoodsKzViewDao::i()->getOne($where);
        if(!$goodsInfo)
        {
            throw new Exception('信息不存在');
        }
        $activityPopularGoodsDb= HcityActivityPopularGoodsDao::i();
        $activityWhere['region']=$fdata['region'];
        $activityWhere['goods_id']=$goodsInfo->goods_id;
        $activityWhere['aid']=$goodsInfo->aid;
        //城市合伙人和嘿卡管理员区分
        if($sUser->type==0)
        {
            $activityWhere['is_default']=0;
            $params['is_default']=0;
        }else
        {
            $activityWhere['is_default']=1;
            $params['is_default']=1;
        }

        $params=[
            'aid'=>$goodsInfo->aid,
            'region'=>$fdata['region'],
            'xc_pic_url'=>$fdata['xc_pic_url'],
            'sort'=>$fdata['sort']
        ];
        $activityInfo=$activityPopularGoodsDb->getOne($activityWhere);
        $activityPopularGoodsDb->update($params,['id'=>$activityInfo->id]);
        if(!$activityPopularGoodsDb)
        {
            throw new Exception('修改失败');
        }
        return true;    
    }
    
    /**
     * 爆款商品删除列表
     * @param array $fdata
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function activityPopularDelete(\s_hmanage_user_do $sUser,array $fdata)
    {
        $where='id='.$fdata['id'];
        $where.=" and hcity_status=1";
        //城市合伙人和嘿卡管理员区分
        if($sUser->type==0)
        {
            $where .= " and region like '{$sUser->region}%'";
            //如果城市合伙人没有地址筛选则直接用合伙人地址
            if(!$fdata['region'])
            {
                $fdata['region']=$sUser->region;
            }
            //判断一下是不是属于城市合伙人地区的商品
            if(strpos($fdata['region'],$sUser->region) ===false){
                throw new Exception('此地区商品不属于你的权限');
            }
        }
        //检查商品是否存在 属于这个地区
        $goodsInfo=HcityGoodsKzViewDao::i()->getOne($where);
        if(!$goodsInfo)
        {
            throw new Exception('信息不存在');
        }
        $activityPopularGoodsDb= HcityActivityPopularGoodsDao::i();
        $activityWhere['region']=$fdata['region'];
        $activityWhere['goods_id']=$goodsInfo->goods_id;
        $activityWhere['aid']=$goodsInfo->aid;
        //城市合伙人和嘿卡管理员区分
        if($sUser->type==0)
        {
            $activityWhere['is_default']=0;
        }else
        {
            $activityWhere['is_default']=1;
        }
        $activityInfo=$activityPopularGoodsDb->getOne($activityWhere);
        $activityPopularGoodsDb->delete(['id'=>$activityInfo->id]);
        if(!$activityPopularGoodsDb)
        {
            throw new Exception('设置失败');
        }
        return true;    
    }



}
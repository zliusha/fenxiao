<?php
/**
 * Created by PhpStorm.
 * User: yize
 * Date: 2018/7/27
 * Time: 14:58
 */
namespace Service\Bll\Hcity;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzViewDao;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBannerDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBannerGoodsDao;
use Service\DbFrame\DataBase\HcityMainDb;

class ActivityBannerGoodsBll extends \Service\Bll\BaseBll
{


    /**
     * 横幅商品列表
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function activityBannerGoodsList(array $fdata)
    {
        $bannerInfo=HcityActivityBannerDao::i()->getOne(['id'=>$fdata['banner_id']]);
        if(!$bannerInfo)
        {
            throw new Exception('信息不存在');
        }
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->fields='a.*,b.shop_name,b.title as goods_name';
        $p_conf->table = "{$hcityMainDb->tables['hcity_activity_banner_goods']} a left join {$hcityMainDb->tables['hcity_goods_kz_view']} b using(aid,goods_id) ";
        $p_conf->order= 'sort asc';
        $p_conf->where= 'banner_id = '.$fdata['banner_id'];
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        $bannerInfo->detail_pic_url=conver_picurl($bannerInfo->detail_pic_url);
        $rows['banner']=$bannerInfo;
        $rows['total']=$count;
        return $rows;
    }



    /**
     * 设置横幅商品
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function BannerSetGoods(array $fdata)
    {
        $hcityActivityBannerDb= HcityActivityBannerDao::i();
        $bannerInfo=$hcityActivityBannerDb->getOne(['id'=>$fdata['banner_id']]);
        if(!$bannerInfo)
        {
            throw new Exception('信息不存在');
        }
        $goodsInfo=HcityGoodsKzViewDao::i()->getOne(['id'=>$fdata['goods_list_id']]);
        if(!$goodsInfo)
        {
            throw new Exception('商品信息不存在');
        }
        $hcityActivityBannerGoodsDb=HcityActivityBannerGoodsDao::i();
        //检查商品是否已经存在
        $bannerGoodsInfo=$hcityActivityBannerGoodsDb->getOne(['banner_id'=>$fdata['banner_id'],'aid'=>$goodsInfo->aid,'goods_id'=>$goodsInfo->goods_id]);
        if($bannerGoodsInfo)
        {
            throw new Exception('此商品已加入活动中');
        }
        $orderBy="'sort','desc'";
        $orderByInfo=$hcityActivityBannerGoodsDb->getOne(['banner_id'=>$fdata['banner_id']],'sort',$orderBy);
        $sort=1;
        if($orderByInfo)
        {
            $sort+=$orderByInfo->sort;
        }
        $params=[
            'sort'=>$sort,
            'banner_id'=>$fdata['banner_id'],
            'shop_id'=>$goodsInfo->shop_id,
            'goods_id'=>$goodsInfo->goods_id,
            'aid'=>$goodsInfo->aid,
        ];
        $ret=$hcityActivityBannerGoodsDb->create($params);
        if(!$ret)
        {
            throw new Exception('设置失败');
        }
        return true;
    }


    /**
     * 设置横幅商品
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function BannerSetGoodsBatch(array $fdata)
    {
        $hcityActivityBannerDb= HcityActivityBannerDao::i();
        $bannerInfo=$hcityActivityBannerDb->getOne(['id'=>$fdata['banner_id']]);
        if(!$bannerInfo)
        {
            throw new Exception('信息不存在');
        }
        $hcityActivityBannerGoodsDb=HcityActivityBannerGoodsDao::i();
        $bannerAll=$hcityActivityBannerGoodsDb->getAllArray(['banner_id'=>$fdata['banner_id']],'aid,goods_id');
        //$fdata['goods_id']='10,2,5';
        //$fdata['aid']='1226,1342,2445';
        //$fdata['shop_id']='1,2,4';
        $goodsArr=explode(',',$fdata['goods_id']);
        $aidArr=explode(',',$fdata['aid']);
        $shopArr=explode(',',$fdata['shop_id']);
        //处理提交过来的参数
        $goodsAidArr=[];
        foreach($goodsArr as $key => &$val)
        {
            $goodsAidArr[$key]['goods_id']=$val;
            if(!isset($aidArr[$key]) || !$aidArr[$key])
            {
                throw new Exception('参数错误');
            }
            $goodsAidArr[$key]['aid']=$aidArr[$key];
            if(!isset($shopArr[$key]) || !$shopArr[$key])
            {
                throw new Exception('参数错误');
            }
            $goodsAidArr[$key]['shop_id']=$shopArr[$key];
        }
        //获取最大排序
        $orderBy="'sort','desc'";
        $orderByInfo=$hcityActivityBannerGoodsDb->getOne(['banner_id'=>$fdata['banner_id']],'sort',$orderBy);
        $sort=1;
        if($orderByInfo)
        {
            $sort+=$orderByInfo->sort;
        }

        foreach($goodsAidArr as $key => &$val)
        {
            foreach($bannerAll as $v)
            {
                //如果已存在
                if($v['goods_id'] == $val['goods_id'] && $v['aid'] == $val['aid'])
                {
                   unset($goodsAidArr[$key]);
                }
            }
            $val['banner_id']=$fdata['banner_id'];
            $val['sort']=$sort;
            $sort+=1;
        }
        if(!$goodsAidArr)
        {
            throw new Exception('没有可设置的商品');
        }
        $ret=$hcityActivityBannerGoodsDb->createBatch($goodsAidArr);
        if(!$ret)
        {
            throw new Exception('设置失败');
        }
        return true;
    }


    /**
     * 取消横幅商品
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function BannerRemoveGoods(array $fdata)
    {
        $hcityActivityBannerGoodsDb= HcityActivityBannerGoodsDao::i();
        $bannerGoodsInfo=$hcityActivityBannerGoodsDb->getOne(['id'=>$fdata['id']]);
        if(!$bannerGoodsInfo)
        {
            throw new Exception('信息不存在');
        }
        $ret=$hcityActivityBannerGoodsDb->delete(['id'=>$fdata['id']]);
        if(!$ret)
        {
            throw new Exception('删除失败');
        }
        return true;
    }

    /**
     * 设置横幅商品排序
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function BannerGoodsSetSort(array $fdata)
    {
        $hcityActivityBannerGoodsDb= HcityActivityBannerGoodsDao::i();
        $bannerGoodsInfo=$hcityActivityBannerGoodsDb->getOne(['id'=>$fdata['id']]);
        if(!$bannerGoodsInfo)
        {
            throw new Exception('信息不存在');
        }

        $ret=$hcityActivityBannerGoodsDb->update(['sort'=>$fdata['sort']],['id'=>$fdata['id']]);
        if(!$ret)
        {
            throw new Exception('修改失败');
        }
        return true;
    }



}
<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/17
 * Time: 上午9:45
 */
namespace Service\Bll\Hcity;

use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopCategoryDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityHomepageShopCategoryDao;
use Service\Exceptions\Exception;

class HomepageShopCateBll extends \Service\Bll\BaseBll
{
    /**
     * 设置首页分类
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function setShopCate(\s_hmanage_user_do $sUser,array $fdata)
    {
        $ids=$fdata['cate_ids'];
        $idArr=explode(',',$ids);
        $cateDb=HcityShopCategoryDao::i();
        $cateShopDb=HcityHomepageShopCategoryDao::i();
        $params=[];
        $type=$sUser->type;
        $region=$sUser->region;
        switch ($type)
        {
            case 1:
                if(count($idArr)!=4)
                {
                    throw new Exception('必须选择4个分类');
                }
                foreach($idArr as $k => $v)
                {
                    $cateData=$cateDb->getOne(['id'=>$v]);
                    if(!$cateData)
                    {
                        throw new Exception('ID为'.$v.'的分类信息不存在');
                    }
                    $params[]=[
                        'category_id'=>$v,
                        'sort'=>$k+1,
                        'is_default'=>1,
                    ];
                }
                $list=$cateShopDb->getOne(['is_default'=>1]);
                break;
            case 0:
                if(count($idArr)>4)
                {
                    throw new Exception('你能大于4个分类');
                }
                foreach($idArr as $k => $v)
                {
                    $cateData=$cateDb->getOne(['id'=>$v]);
                    if(!$cateData)
                    {
                        throw new Exception('ID为'.$v.'的分类信息不存在');
                    }
                    $params[]=[
                        'category_id'=>$v,
                        'sort'=>$k+1,
                        'region'=>$region,
                        'is_default'=>0,
                    ];
                }
                $list=$cateShopDb->getOne(['is_default'=>0,'region'=>$region]);
                break;
            default:
                throw new Exception('登录信息错误');
        }
        if($list)
        {
            //开始事物
            $cateShopDb->db->trans_start();
            if($type==1)
            {
                $delRet=$cateShopDb->delete(['is_default'=>1]);
            }else
            {
                $delRet=$cateShopDb->delete(['is_default'=>0,'region'=>$region]);
            }
            $addRet=$cateShopDb->createBatch($params);
            if($delRet && $addRet)
            {
                $cateShopDb->db->trans_complete();
                return true;
            }else
            {
                $cateShopDb->db->trans_rollback();
                throw new Exception('设置失败');
            }
        }else
        {
            $addRet=$cateShopDb->createBatch($params);
            if(!$addRet)
            {
                throw new Exception('设置失败');
            }
        }
        return true;
    }


    /**
     * 设置首页分类列表
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function shopCateList(\s_hmanage_user_do $sUser)
    {

        $cateShopDb=HcityHomepageShopCategoryDao::i();
        $mainDb=HcityMainDb::i();
        $cate_config_arr = array(
            'field' => "a.*,b.name as cate_name",
            'table' => "{$mainDb->tables['hcity_homepage_shop_category']} a",
            'join' => array(
                array("{$mainDb->tables['hcity_shop_category']} b", "a.category_id=b.id", 'left'),
            ),
        );
        //区分城市合伙人和嘿卡
        if($sUser->type==1)
        {
            $cate_config_arr['where'] = "a.is_default= 1";
            $list = $cateShopDb->getEntitysByAR($cate_config_arr, true);
        }else
        {
            $cate_config_arr['where'] = "a.is_default= 0 and a.region = '{$sUser->region}'";
            $list = $cateShopDb->getEntitysByAR($cate_config_arr, true);
        }
        return $list;


    }
}
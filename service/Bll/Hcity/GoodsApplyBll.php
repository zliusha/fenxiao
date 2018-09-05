<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/13
 * Time: 上午10:40
 */
namespace Service\Bll\Hcity;

use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsApplyDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsApplyViewDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsSkuApplyDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsSkuKzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsSkuDao;
use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsAttrgroupDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsAttritemDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsAttrgroupDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsSkuDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsAttritemDao;



class GoodsApplyBll extends \Service\Bll\BaseBll
{

    /**
     * 获取商品上架申请 分页
     * @param array $data
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function goodsApplyList(\s_hmanage_user_do $sUser,array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->table= "{$hcityMainDb->tables['hcity_goods_apply_view']}";
        if($sUser->type==0)
        {
            $p_conf->where .="and region like '{$sUser->region}%'";
        }
        if($fdata['title'])
        {
            $p_conf->where .="and title like '%{$page->filterLike($fdata['title'])}%'";
        }
        if($fdata['region'])
        {
            $p_conf->where .="and region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if($fdata['shop_id'])
        {
            $p_conf->where .=' and shop_id='.$fdata['shop_id'];
        }
        if($fdata['audit_status'])
        {
            $p_conf->where .=' and audit_status='.$fdata['audit_status'];
        }
        $p_conf->order= 'time desc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        $rows['total']=$count;
        return $rows;
    }

    /**
     * 商品上架申请 详情
     * @param array $data
     * @return array $good
     * @author yize<yize@iyenei.com>
     */
    public function goodsApplyDetail(int $id)
    {
        $goodsApply=HcityGoodsApplyViewDao::i()->getOne(['id' => $id]);
        if(!$goodsApply)
        {
            throw new Exception('商圈商品上架申请不存在');
        }
        $goodsApply->desc = htmlspecialchars_decode($goodsApply->desc);
        if($goodsApply->pic_url)
        {
            $goodsApply->pic_url=conver_picurl($goodsApply->pic_url);
        }
        $pic='';
        if($goodsApply->pic_url_list)
        {
            $picArr=explode(',',$goodsApply->pic_url_list);
            foreach($picArr as $v)
            {
                $pic.=conver_picurl($v).',';
            }
        }
        $pic=rtrim($pic,',');
        $shcityGoodsDao = ShcityGoodsDao::i(['aid'=>$goodsApply->aid])->getOne(['id'=>$goodsApply->goods_id,'aid'=>$goodsApply->aid],'use_end_time');
        $goodsApply->use_end_time = $shcityGoodsDao->use_end_time;
        $goodsApply->pic_url_list=$pic;
        return $goodsApply;
    }



    /**
     * 商圈商品审核
     * @param int $aid
     * @param array $data
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function editAuditStatus(\s_hmanage_user_do $sUser , array $fdata)
    {
        $like=[];
        if($sUser->type==0)
        {
            $like=[
                'field'=>'region',
                'str'=>$sUser->region,
                'type'=>'after',
            ];
        }
        $goodsApplyView=HcityGoodsApplyViewDao::i()->getOneLike($like,['id'=>$fdata['id']]);
        if(!$goodsApplyView)
        {
            throw new Exception('商圈商品不存在');
        }
        $goodsApply=HcityGoodsApplyDao::i()->getOne(['id'=>$fdata['id']]);
        if(!$goodsApply)
        {
            throw new Exception('商品不存在');
        }
        if($goodsApply->audit_status!=1)
        {
            throw new Exception('审核状态不能修改');
        }
        if($goodsApply->audit_status==$fdata['audit_status'])
        {
            throw new Exception('审核状态未发生改变');
        }
        $shopExt=HcityShopExtDao::i()->getOne(['shop_id'=>$goodsApply->shop_id,'hcity_show_status>'=>0]);
        if(!$shopExt)
        {
            throw new Exception('此商品所属门店没有入住商圈');
        }
        if($shopExt->hcity_show_status==2)
        {
            throw new Exception('此商品所属名店已被清退');
        }

        //商品多属性信息
        $gsaDb = HcityGoodsSkuApplyDao::i();
        $goodsStuApply=$gsaDb->getAll(['apply_id'=>$goodsApply->id]);
        if(!$goodsStuApply)
        {
            throw new Exception('商品属性不存在');
        }
        $applyParams['audit_status']=$fdata['audit_status'];
        $mainDb = HcityMainDb::i();
        $shardDb=HcityShardDb::i();
        switch ($fdata['audit_status'])
        {
            case 3:
                //审核通过时添加Or更新快照表
                if(!$fdata['hcard_price'])
                {
                    throw new Exception('黑卡价格为空');
                }
                //$g_price=($goodsApply->group_price)*(100-$goodsApply->commission_rate)/100;
                $g_price=($goodsApply->cost_price);
                if($fdata['hcard_price']<$g_price)
                {
                    throw new Exception('黑卡价格必须大于成本价'.$g_price);
                }
                if($fdata['hcard_price']>=$goodsApply->group_price)
                {
                    throw new Exception('黑卡价格必须小于团购价'.$goodsApply->group_price);
                }
                //开启主表事物
                $mainDb->trans_start();
                //开启分表事物
                $shardDb->trans_start();
                $applyParams['hcard_price']=$fdata['hcard_price'];
                $retApply=HcityGoodsApplyDao::i()->update($applyParams,['id' => $fdata['id']]);
                //修改审核状态成功
                if($retApply)
                {
                    $this->_updateGoodsKz($goodsApply,$goodsStuApply,$fdata['hcard_price']);
                    if ($mainDb->trans_status() && $shardDb->trans_status()) {
                        $mainDb->trans_complete();
                        $shardDb->trans_complete();
                        return true;
                    } else {
                        $mainDb->trans_rollback();
                        $shardDb->trans_rollback();
                        throw new Exception('审核修改失败');
                    }
                }else
                {
                    $mainDb->trans_rollback();
                    $shardDb->trans_rollback();
                    throw new Exception('审核修改失败');
                }

                break;
            case 2:
                //审核不通过时 只要修改审核表状态就行
                if(!$fdata['refuse_remark'])
                {
                    throw new Exception('请填写拒绝原因');
                }
                //开启主表事物
                $mainDb->trans_start();
                //开启分表事物
                $shardDb->trans_start();
                $applyParams['refuse_remark']=$fdata['refuse_remark'];
                HcityGoodsApplyDao::i()->update($applyParams,['id' => $fdata['id']]);
                ShcityGoodsDao::i(['aid'=>$goodsApply->aid])->update(['hcity_status'=>3],['aid'=>$goodsApply->aid,'id' => $goodsApply->goods_id]);
                if ($mainDb->trans_status() && $shardDb->trans_status()) {
                    $mainDb->trans_complete();
                    $shardDb->trans_complete();
                    return true;
                } else {
                    $mainDb->trans_rollback();
                    $shardDb->trans_rollback();
                    throw new Exception('审核修改失败');
                }
            default:
                throw new Exception('审核状态错误');
        }

    }
    //商品上架审核通过时 添加or修改快照表
    private function _updateGoodsKz($goodsApply,$goodsStuApply,$hcard_price)
    {
        //商品快照表
        $goodsKz=HcityGoodsKzDao::i()->getOne(['aid'=>$goodsApply->aid,'goods_id'=>$goodsApply->goods_id]);
        //修改商品快照表数据
        $goodsKzParams=[
            'aid'             => $goodsApply->aid,
            'shop_id'         => $goodsApply->shop_id,
            'goods_id'        => $goodsApply->goods_id,
            'title'           => $goodsApply->title,
            'pic_url'         =>$goodsApply->pic_url,
            'price'           =>$goodsApply->price,
            'group_price'     =>$goodsApply->group_price,
            'hcard_price'     =>$hcard_price,
            'show_begin_time'=>$goodsApply->show_begin_time,
            'show_end_time'   =>$goodsApply->show_end_time,
            //'commission_rate' =>$goodsApply->commission_rate,
            'cost_price' =>$goodsApply->cost_price,
            'hcity_status'    =>1,
            'limit_num'       =>$goodsApply->limit_num,
            'free_num'        =>$goodsApply->free_num,
            'hcity_stock_num' =>$goodsApply->hcity_stock_num,
            'is_hcity_stock_open' =>(int)$goodsApply->is_hcity_stock_open,
            'is_limit_open'=>(int)$goodsApply->is_limit_open,
            'total_limit_num' => $goodsApply->total_limit_num,
            'goods_limit' => $goodsApply->goods_limit,
            'is_goods_limit_open' => (int)$goodsApply->is_goods_limit_open,
        ];

        //修改商品表数据
        $goodParams=[
            'is_hcity_goods'=>1,
            'hcity_status'=>1,
            'hcard_price'=>$hcard_price,
            'show_begin_time'=>$goodsApply->show_begin_time,
            'show_end_time'=>$goodsApply->show_end_time,
//            'commission_rate'=>$goodsApply->commission_rate,
        ];
        ShcityGoodsDao::i(['aid'=>$goodsApply->aid])->update($goodParams,['aid'=>$goodsApply->aid,'id' => $goodsApply->goods_id]);
        $goodsStuParams=[];
        $goodsKzStuParams=[];
        //修改商品规格表数据
        foreach ($goodsStuApply as $val) {
            $goodsStuParams[]= [
                'id'=>$val->sku_id,
                'free_num'=>$val->free_num,
                'hcity_stock_num'=>$val->hcity_stock_num,
                'is_hcity_stock_open'=>(int)$goodsApply->is_hcity_stock_open,
                'hcard_price'=>$hcard_price,
                'update_time'=>time(),
            ];
            $goodsKzStuParams[]= [
                'aid'=>$goodsApply->aid,
                'shop_id'=>$goodsApply->shop_id,
                'goods_id'=>$goodsApply->goods_id,
                'sku_id'=>$val->sku_id,
                'free_num'=>$val->free_num,
                'hcity_stock_num'=>$val->hcity_stock_num,
                'is_hcity_stock_open'=>(int)$goodsApply->is_hcity_stock_open,
                'price'=>$val->price,
                'group_price'=>$val->group_price,
                'hcard_price'=>$hcard_price,
            ];
        }
        //修改商品表
        ShcityGoodsSkuDao::i(['aid'=>$goodsApply->aid])->updateBatch($goodsStuParams,'id');
        //删除以前的快照商品多规格表  插入新的数据
        HcityGoodsSkuKzDao::i()->delete(['aid'=>$goodsApply->aid,'goods_id'=>$goodsApply->goods_id]);
        HcityGoodsSkuKzDao::i()->createBatch($goodsKzStuParams);
        //快照表存在就修改 不存在就添加
        if($goodsKz)
        {
            HcityGoodsKzDao::i()->update($goodsKzParams,['aid'=>$goodsApply->aid,'goods_id' => $goodsApply->goods_id]);
        }else
        {
            HcityGoodsKzDao::i()->create($goodsKzParams);
        }

        //添加福利池商品
        //查找店铺的地理位置
        $shop_info = MainShopDao::i()->getOne(['id' => $goodsApply->shop_id],'region');
        $goods_info = ShcityGoodsDao::i(['aid'=>$goodsApply->aid])->getOne(['aid'=>$goodsApply->aid,'id' => $goodsApply->goods_id]);
        //exit;
        $goods_attrgroup = ShcityGoodsAttrgroupDao::i(['aid'=>$goodsApply->aid])->getOne(['aid'=>$goodsApply->aid,'goods_id' => $goodsApply->goods_id]);
        $goods_attritem = ShcityGoodsAttritemDao::i(['aid'=>$goodsApply->aid])->getOne(['aid'=>$goodsApply->aid,'goods_id' => $goodsApply->goods_id]);
        $goods_sku = ShcityGoodsSkuDao::i(['aid'=>$goodsApply->aid])->getOne(['aid'=>$goodsApply->aid,'goods_id' => $goodsApply->goods_id]);
        //开启事务
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();
        $hcityWelfareGoodsDao = HcityWelfareGoodsDao::i();
        $hcityWelfareGoodsAttrgroupDao = HcityWelfareGoodsAttrgroupDao::i();
        $hcityWelfareGoodsAttritemDao = HcityWelfareGoodsAttritemDao::i();
        $hcityWelfareGoodsSkuDao = HcityWelfareGoodsSkuDao::i();
        $goodsData = [
            'aid' => $goodsApply->aid,
            'shop_id' =>$goodsApply->shop_id,
            'title' => $goods_info->title,
            'pic_url' => $goods_info->pic_url,
            'pic_url_list' => $goods_info->pic_url_list,
            'desc' => $goods_info->desc,
            'price' => $goods_info->price,
            'group_price' => $goods_info->group_price,
            'cost_price' => $goods_info->cost_price,
            'hcard_price' => $goods_info->hcard_price,
            'use_end_time' =>$goods_info->use_end_time,
            'show_begin_time' =>$goods_info->show_begin_time,
            'free_num' =>$goods_sku->free_num,
            'region' =>$shop_info->region,
            'original_goods_id' =>$goods_info->id,
            'time' => time(),
        ];
        $goodsId = $hcityWelfareGoodsDao->create($goodsData);
        //保存商品与分类关系
        $attrgroupData = [
            'aid' => $goodsApply->aid,
            'goods_id' => $goodsId,
            'title' => $goods_attrgroup->title,
            'time' => time(),
        ];
        $attrgroupId = $hcityWelfareGoodsAttrgroupDao->create($attrgroupData);
        $attritemData = [
            'aid' => $goodsApply->aid,
            'group_id' => $attrgroupId,
            'attr_name' => $goods_attritem->attr_name,
            'goods_id' => $goodsId,
            'time' => time(),
        ];
        $goodsAttritemId = $hcityWelfareGoodsAttritemDao->create($attritemData);
        $skuData = [
            'aid' => $goodsApply->aid,
            'goods_id' => $goodsId,
            'shop_id' => $goodsApply->shop_id,
            'attr_ids' =>$goodsAttritemId, 
            'attr_names' => $goods_attritem->attr_name,
            'price' =>  $goods_info->price,
            'group_price' =>  $goods_info->group_price,
            'free_num' =>$goods_sku->free_num,
            'hcard_price' => $goods_info->hcard_price,
        ];
        $GoodsSkuId = $hcityWelfareGoodsSkuDao->create($skuData);

        if ($hcityMainDb->trans_status()) {
            $hcityMainDb->trans_complete();
        } else {
            $hcityMainDb->trans_rollback();
            return false;
        }

        //发放骑士奖励
        try {
            (new FinanceBll())->knightReward($goodsApply->shop_id);
        } catch (\Exception $e) {
            log_message('error', __METHOD__ . '发放骑士奖励失败:' . $e->getMessage());
        }
        return true;
    }
}
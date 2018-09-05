<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/17
 * Time: 上午9:45
 */
namespace Service\Bll\Hcity;

use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityHomepageShopCategoryDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopCategoryDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityHomepageShopDao;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Exceptions\Exception;
use Service\Support\Page\PageList;

class HomepageShopBll extends \Service\Bll\BaseBll
{
    /**
     * 设置首页分类
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function addShop(array $fdata)
    {
        $ids=$fdata['shop_ids'];
        $idArr=explode(',',$ids);
        $mainShopDb=MainShopDao::i();
        $homepageShopDb=HcityHomepageShopDao::i();
        $params = [
            'region' => $fdata['region'],
            'region_name' =>$fdata['region_name'],
        ];
        //存在上级则拼接一下
        if($fdata['pid'])
        {
            $pData=$homepageShopDb->getOne(['id'=>$fdata['pid']]);
            if(!$pData)
            {
                throw new Exception('上级广告位不存在');
            }
            $params = [
                'region' => $pData->region.'-'.$fdata['region'],
                'region_name' => $pData->region_name.'-'.$fdata['region_name'],
                'pid'=>$fdata['pid'],
            ];
        }
        $rData=$homepageShopDb->getOne(['region'=>$params['region']]);
        if($rData)
        {
            throw new Exception('此地区已存在推荐门店');
        }
        //保证是5个门店
//        if(count($idArr)!=5)
//        {
//            throw new Exception('必须是5个门店');
//        }
        $shop_list=[];
        //验证门店是否存在
        foreach($idArr as $k => $id)
        {
            $shopData=$mainShopDb->getOne(['id'=>$id]);
            if(!$shopData)
            {
                throw new Exception('ID为'.$id.'的门店信息不存在');
            }
            $shop_list[]=[
                'shop_id'=>$id,
                'shop_name'=>$shopData->shop_name,
                'sort'=>$k+1,
            ];
        }
        if(!$shop_list)
        {
            throw new Exception('请选择门店');
        }
        $params['shop_list']=json_encode($shop_list);
        $homepageShopDb->db->trans_start();
        $homepageShopDb->create($params);
        //成功则改为有子集
        if($fdata['pid'])
        {
            $homepageShopDb->update(['has_children'=>1],['id'=>$fdata['pid']]);
        }
        if ($homepageShopDb->db->trans_status())
        {
            $homepageShopDb->db->trans_complete();
            return true;
        } else {
            $homepageShopDb->db->trans_rollback();
            throw new Exception('添加失败');
        }
    }


    /**
     * 修改分类
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function editShop(array $fdata)
    {
        $ids=$fdata['shop_ids'];
        $idArr=explode(',',$ids);
        $mainShopDb=MainShopDao::i();
        $homepageShopDb=HcityHomepageShopDao::i();
        $shop_list=[];
        //验证门店是否存在
        foreach($idArr as $k => $id)
        {
            $shopData=$mainShopDb->getOne(['id'=>$id]);
            if(!$shopData)
            {
                throw new Exception('ID为'.$id.'的门店信息不存在');
            }
            $shop_list[]=[
                'shop_id'=>$id,
                'shop_name'=>$shopData->shop_name,
                'sort'=>$k+1,
            ];
        }
        if(!$shop_list)
        {
            throw new Exception('请选择门店');
        }
        $params['shop_list']=json_encode($shop_list);
        $homepageShopDb->update($params,['id'=>$fdata['id']]);

    }


    /**
     * 设置首页分类列表
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function shopList(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->table="{$hcityMainDb->tables['hcity_homepage_shop']}";
        $p_conf->where .='and pid=0';
        if($fdata['region'])
        {
            $p_conf->where .=" and region like '{$page->filterLike($fdata['region'])}%'";
        }
        $p_conf->order= 'id asc';
        $count = 0;
        $rows['rows']=$page->getList($p_conf,$count);
        foreach($rows['rows'] as &$v)
        {
            $v['shop_list']=json_decode($v['shop_list']);
        }
        $rows['total']=$count;
        return $rows;

    }


    /**
     * 删除首页门店推荐
     * @param  int  $id
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function shopDelete(int $id)
    {

        $homepageShopDb=HcityHomepageShopDao::i();
        $data=$homepageShopDb->getOne(['id'=>$id]);
        if(!$data)
        {
            throw new Exception('删除的数据不存在');
        }
        $homepageShopDb->db->trans_start();
        $homepageShopDb->delete(['id'=>$id]);
        //删除子推荐门店
        $homepageShopDb->delete(['pid'=>$data->id]);
        if ($homepageShopDb->db->trans_status()) {
            log_message('info', '删除首页推荐门店：' . json_encode(['shop_id' => $id]));
            $homepageShopDb->db->trans_complete();
            return true;
        } else {
            $homepageShopDb->db->trans_rollback();
            throw new Exception('删除失败');
        }
    }


    /**
     * 首页门店推荐详情
     * @param  int  $id
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function shopDetail(int $id)
    {
        $rows=HcityHomepageShopDao::i()->getOne(['id'=>$id]);
        if(!$rows)
        {
            throw new Exception('信息不存在');
        }
        $rows->shop_list=json_decode($rows->shop_list);
        return $rows;
    }

    /**
     * 全部子类详情
     * @param  int  $id
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function shopChildrenDetail(int $id)
    {
        $fields='id,pid,region_name,region';
        $rows=HcityHomepageShopDao::i()->getOne(['id'=>$id],$fields);
        if(!$rows)
        {
            throw new Exception('信息不存在');
        }
        $rows->Children=HcityHomepageShopDao::i()->getAll(['pid'=>$id]);
        foreach($rows->Children as &$v)
        {
            $v->shop_list=json_decode($v->shop_list);
        }
        return $rows;
    }
}
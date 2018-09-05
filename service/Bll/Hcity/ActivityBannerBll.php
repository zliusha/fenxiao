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

class ActivityBannerBll extends \Service\Bll\BaseBll
{


    /**
     * 横幅列表
     * @param array $fdata
     * @return array $rows
     * @author yize<yize@iyenei.com>
     */
    public function activityBannerList(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf=$page->getConfig();
        $p_conf->table="{$hcityMainDb->tables['hcity_activity_banner']}";
        if (!empty($fdata['region'])) {
            $p_conf->where .= " and region={$page->filter($fdata['region'])} ";
            $p_conf->order= 'sort asc';
            $count = 0;
            $rows['rows']=$page->getList($p_conf,$count);
            foreach($rows['rows'] as &$v)
            {
                $v['pic_url']=conver_picurl($v['pic_url']);
            }
            $rows['total']=$count;
        } else {
            $rows['rows'] = [];
            $rows['total']=0;
        }
        return $rows;
    }


    /**
     * 横幅添加
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function activityBannerAdd(array $fdata)
    {
        $hcityActivityBannerDb= HcityActivityBannerDao::i();
        $params=[
            'name'=>$fdata['name'],
            'pic_url'=>$fdata['pic_url'],
            'sort'=>$fdata['sort'],
            'region'=>$fdata['region'],
        ];
        $nameInfo=$hcityActivityBannerDb->getOne(['name' => $fdata['name'],'region' =>$fdata['region']]);
        if($nameInfo)
        {
            throw new Exception('此活动名称已存在');
        }
        $bannerCount=$hcityActivityBannerDb->getCount(['region' =>$fdata['region']]);
        if($bannerCount>=5)
        {
            throw new Exception('已经存在5个');
        }
        $ret=$hcityActivityBannerDb->create($params);
        if(!$ret)
        {
            throw new Exception('添加失败');
        }
        return true;

    }


    /**
     * 横幅编辑
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function activityBannerEdit(array $fdata)
    {
        $hcityActivityBannerDao= HcityActivityBannerDao::i();
        $bannerInfo=$hcityActivityBannerDao->getOne(['id'=>$fdata['id']]);
        if(!$bannerInfo)
        {
            throw new Exception('信息不存在');
        }
        $params=[
            'name'=>$fdata['name'],
            'pic_url'=>$fdata['pic_url'],
            'sort'=>$fdata['sort'],
            'region'=>$fdata['region']
        ];
        $nameInfo=$hcityActivityBannerDao->getOne("name = '{$fdata['name']}' and region = '{$fdata['region']}'  and id != {$fdata['id']}");
        if($nameInfo)
        {
            throw new Exception('此活动名称已存在');
        }
        $ret=$hcityActivityBannerDao->update($params,['id'=>$fdata['id']]);
        if(!$ret)
        {
            throw new Exception('修改失败');
        }
        return true;
    }


    /**
     * 横幅删除
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function activityBannerDelete(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $hcityMainDb->trans_start();

        $hcityActivityBannerDb= HcityActivityBannerDao::i();
        $bannerInfo=$hcityActivityBannerDb->getOne(['id'=>$fdata['id']]);
        if(!$bannerInfo)
        {
            throw new Exception('信息不存在');
        }
        //删除活动横幅 也要把横幅下面的商品移除
        $hcityActivityBannerDb->delete(['id'=>$fdata['id']]);
        HcityActivityBannerGoodsDao::i()->delete(['banner_id'=>$fdata['id']]);
        return $hcityMainDb->trans_complete();
    }

    /**
     * 设置横幅图片
     * @param array $fdata
     * @return bool
     * @author yize<yize@iyenei.com>
     */
    public function BannerSetImg(array $fdata)
    {
        $hcityActivityBannerDb= HcityActivityBannerDao::i();
        $bannerInfo=$hcityActivityBannerDb->getOne(['id'=>$fdata['id']]);
        if(!$bannerInfo)
        {
            throw new Exception('信息不存在');
        }
        $ret=$hcityActivityBannerDb->update(['detail_pic_url'=>$fdata['detail_pic_url']],['id'=>$fdata['id']]);
        if(!$ret)
        {
            throw new Exception('修改失败');
        }
        return true;


    }









}
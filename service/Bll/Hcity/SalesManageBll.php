<?php
/**
 * 骑士管理
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/8/27
 * Time: 上午9:53
 */
namespace Service\Bll\Hcity;

use Service\Support\Page\PageList;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityQsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcitySalesAccountDao;
use Service\DbFrame\DataBase\MainDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\Cache\Hcity\HcityQsCache;

class SalesManageBll extends \Service\Bll\BaseBll
{

    /**
     * 添加销售
     * @param array $fdata
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function create(array $fdata)
    {
        $hcitySalesAccountDao = HcitySalesAccountDao::i();
        $salesinfo = $hcitySalesAccountDao->getOneArray(['mobile'=>$fdata['mobile']]);
        if($salesinfo){
            throw new Exception('此手机号已经存在，请修改');
        }
        $salesData = [
            'name' => $fdata['name'],
            'mobile' => $fdata['mobile'],
            'region'=> $fdata['region'],
            'region_name' => $fdata['region_name'],
        ];
        $salesId = $hcitySalesAccountDao->create($salesData);
        if($salesId){
            return true;
        }else{
            return false;
        }       
    }

    /**
     * 编辑销售
     * @param array $fdata
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function edit(array $fdata)
    {
        $hcitySalesAccountDao = HcitySalesAccountDao::i();
        $salesinfo = $hcitySalesAccountDao->getAllArray("mobile = {$fdata['mobile']} and id not in ({$fdata['id']})");
        if($salesinfo){
            throw new Exception('此手机号已经存在，请修改');
        }
        $salesData = [
            'name' => $fdata['name'],
            'mobile' => $fdata['mobile'],
            'region'=> $fdata['region'],
            'region_name' => $fdata['region_name'],
        ];
        $salesInfo = $hcitySalesAccountDao->update($salesData,['id'=>$fdata['id']]);
        return true;  
    }

    /**
     * 编辑销售
     * @param array $fdata
     * @return bool
     * @author feiying<feiying@iyenei.com>
     */
    public function detail(array $fdata)
    {
        $hcitySalesAccountDao = HcitySalesAccountDao::i();
        $row = $hcitySalesAccountDao->getAllArray(['id'=>$fdata['id']]);
        return $row ;  
    }

    /**
     * 获取粉丝数据列表
     * @param array $fdata
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function salesSearch(array $fdata)
    {
        $hcityMainDb = HcityMainDb::i();
        $page = new PageList($hcityMainDb);
        $p_conf = $page->getConfig();
        $p_conf->table = "{$hcityMainDb->tables['hcity_sales_account']}";
        if ($fdata['region']) {
            $p_conf->where .= " and region like '%{$page->filterLike($fdata['region'])}%'";
        }
        if ($fdata['status'] != null) {
            $p_conf->where .= ' and status=' . $fdata['status'];
        }
        if($fdata['time']){
            $timeArr=explode(' - ',$fdata['time']);
            $p_conf->where .=" and time>=".strtotime($timeArr[0]);
            $lasttime = strtotime($timeArr[1]) + 24*3600;
            $p_conf->where .=" and time<".$lasttime; 
        }
        if ($fdata['name']) {
            $p_conf->where .= " and name like '%{$page->filterLike($fdata['name'])}%'";
        }
        if ($fdata['mobile']) {
            $p_conf->where .= " and mobile like '%{$page->filterLike($fdata['mobile'])}%'";
        }
        $p_conf->fields = '*';
        $p_conf->order = 'id desc';
        $count = 0;
        $rows['rows'] = $page->getList($p_conf, $count);
        $rows['total'] = $count;
        return $rows;
    }

    /**
     * 销售状态修改
     * @param array $data
     * @return array $rows
     * @author feiying<feiying@iyenei.com>
     */
    public function salesStatus(array $fdata)
    {   
        $hcitySalesAccountDao = HcitySalesAccountDao::i();
        $info = $hcitySalesAccountDao->getOneArray(['id' => $fdata['id']]);
        if (!$info) {
            throw new Exception('此用户信息不存在，无法审核');
        }
        //设置为正常
        if ($fdata['status'] == 1) {
            $status = $hcitySalesAccountDao->update(['status' => $fdata['status'],'disable_time' => null], ['id' => $fdata['id']]);
        }
        //拉黑
        if ($fdata['status'] == 2) {
            $status = $hcitySalesAccountDao->update(['status' => $fdata['status'],'disable_time' => time()], ['id' => $fdata['id']]);
        }
        if($status){
            return true;
        } else {
            return false;
        }    
    }
	

}
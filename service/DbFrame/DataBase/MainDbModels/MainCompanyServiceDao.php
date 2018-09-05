<?php

/**
 * @Author: binghe
 * @Date:   2018-06-28 19:40:17
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-06-28 19:45:23
 */
namespace Service\DbFrame\DataBase\MainDbModels;
use Service\Enum\ServiceEnum;
/**
 * 公司
 */
class MainCompanyServiceDao extends BaseDao
{
	/**
    * 开通试用版
    * @param  int $aid 公司id
    * @return [type]      [description]
    */
   public function openTrial($aid,$days=3)
   {
        $data['aid']=$aid;
        $data['service_id']=ServiceEnum::TRIAL;
        $data['service_day_limit']=date('Y-m-d',strtotime("+{$days} day"));
        $data['service_type_name']='试用版';
        $data['is_trial']=1;
        return $this->create($data);
   }
	
}
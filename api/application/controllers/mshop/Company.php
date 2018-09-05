<?php
/**
 * @Author: binghe
 * @Date:   2018-04-17 20:03:53
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 18:55:53
 */
use Service\Cache\Wm\WmServiceCache;
/**
* company
*/
class Company extends mshop_controller
{
    
    /**
     * 获取商家信息
     */
    public function info()
    {
        //服务信息
        $wmServiceCache = new WmServiceCache(['aid'=>$this->aid]);
        $wm_service_do = $wmServiceCache->getDataASNX();
        if(!$wm_service_do)
            $this->json_do->set_error('004','当前商家未开通任何版本');
        //兼容老版
        $serviceInfo = new stdClass;
        $serviceInfo->service_id = $wm_service_do->item_id;
        $data['service_info'] = $serviceInfo;

        // //商家信息
         $companyAccountCache = new \Service\Cache\CompanyAccountCache(['aid'=>$this->aid]);
         $account_do = $companyAccountCache->getDataASNX();
         $account_do->img = conver_picurl($account_do->img);
         $data['account_info'] = $account_do;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
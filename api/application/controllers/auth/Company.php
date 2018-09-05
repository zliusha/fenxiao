<?php
/**
 * @Author: binghe
 * @Date:   2018-04-17 20:03:53
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 18:56:00
 */
use Service\Cache\CompanyAccountCache;
/**
* company
*/
class Company extends xcx_controller
{
    
    /**
     * 获取商家信息
     * @return [type] [description]
     */
    public function info()
    {

        // //商家信息
         $companyAccountCache = new CompanyAccountCache(['aid'=>$this->aid]);
         $account_do = $companyAccountCache->getDataASNX();
         $account_do->img = conver_picurl($account_do->img);
         $data['account_info'] = $account_do;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
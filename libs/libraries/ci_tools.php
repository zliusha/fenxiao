<?php
use Service\Cache\CompanyDomainCache;
/**
 * CI工具类
 */
class ci_tools
{
    public static function get_account_id()
    {
        if (!defined("SUB_DOMAIN")) {
            return 1226;
        }

        $sub_domain = substr(SUB_DOMAIN, 4);
        $subDomainToAidCache = new CompanyDomainCache(['sub_domain'=>$sub_domain]);
        $arr = $subDomainToAidCache->getDataASNX();
        if($arr)
            return $arr['aid'];
    }
}

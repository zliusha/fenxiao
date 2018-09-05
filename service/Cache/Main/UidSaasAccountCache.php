<?php

/**
 * @Author: binghe
 * @Date:   2018-07-21 14:34:15
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 18:29:23
 */
namespace Service\Cache\Main;

use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;

/**
 * 子账号有权限的saas缓存
 */
class UidSaasAccountCache extends \Service\Cache\BaseCache
{
    /**
     * @param array $input 必需:aid,uid
     */
    public function __construct($input)
    {
        parent::__construct($input);
    }

    /**
     * 获取缓存,不存在并保存
     * @return array
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function () use ($input) {
            //获取子账号管理门店
            $mainShopAccountDao = MainShopAccountDao::i();
            $shopAccounts = $mainShopAccountDao->getAllArray(['aid' => $input['aid'], 'account_id' => $input['uid']], 'main_shop_id');
            if (!$shopAccounts)
                return [];
            //2.由管理门店伙伴saas_id
            $mainShopRefitemDao = mainShopRefitemDao::i();
            $mainShopIds = array_column($shopAccounts, 'main_shop_id');
            $where['where_in'] = ['shop_id' => $mainShopIds];
            $shopRefitemArr = $mainShopRefitemDao->getEntitysByAR($where, true);
            if (!$shopRefitemArr)
                return [];
            //由saas_id获取saas
            $mainSaasAccountDao = MainSaasAccountDao::i();
            $saasIds = array_unique(array_column($shopRefitemArr, 'saas_id'));
            $saasWhere['where'] = ['aid' => $input['aid']];
            $saasWhere['where_in'] = ['saas_id' => $saasIds];
            return $mainSaasAccountDao->getEntitysByAR($saasWhere, true);
        });
    }

    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "Main:UidSaasAccount:{$this->input['aid']}_{$this->input['uid']}";
    }

}
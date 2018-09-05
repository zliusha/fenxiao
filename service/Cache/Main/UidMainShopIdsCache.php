<?php

/**
 * @Author: binghe
 * @Date:   2018-08-02 17:13:11
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-03 18:17:56
 */
namespace Service\Cache\Main;

use Service\DbFrame\DataBase\MainDbModels\MainSaasAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopAccountDao;
use Service\DbFrame\DataBase\MainDbModels\MainShopRefitemDao;

/**
 * 子账号有权限的saas缓存
 */
class UidMainShopIdsCache extends \Service\Cache\BaseCache
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
            $rows = MainShopAccountDao::i()->getAllArray(['aid'=>$input['aid'],'account_id'=>$input['uid']],'main_shop_id','main_shop_id asc');
            return array_column($rows, 'main_shop_id');
        });
    }

    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "Main:UidMainShopIds:{$this->input['aid']}_{$this->input['uid']}";
    }

}
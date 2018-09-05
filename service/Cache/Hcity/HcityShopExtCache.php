<?php
/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/3
 * Time: 下午5:47
 */

namespace Service\Cache\Hcity;

use Service\Cache\BaseCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityShopExtDao;
use Service\Exceptions\Exception;

class HcityShopExtCache extends BaseCache
{
    /**
     * @param array $input 必需:shop_id
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
            $hcityShopExtData = HcityShopExtDao::i()->getOneArray(['shop_id' => $input['shop_id']]);
            if (!$hcityShopExtData)
                throw new Exception('店铺不存在');
            return $hcityShopExtData;
        });
    }


    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        $input = $this->input;
        return 'Hcity:HcityShopExt:' . $input['shop_id'];
    }
}
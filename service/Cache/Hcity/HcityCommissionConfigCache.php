<?php
namespace Service\Cache\Hcity;

use Service\Cache\BaseCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityCommissionConfigDao;
use Service\Exceptions\Exception;

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/1
 * Time: 下午8:58
 */
class HcityCommissionConfigCache extends BaseCache
{
    /**
     * @param array $input 必需:type
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
            $commissionConfigDao = HcityCommissionConfigDao::i();
            $mCommissionConfig = $commissionConfigDao->getAllArray(['type' => $input['type']]);
            if(!$mCommissionConfig)
                throw new Exception('分佣比例未配置');
            return $mCommissionConfig;
                
        });
    }

    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        return "Hcity:HcityCommissionConfig:{$this->input['type']}";
    }

}
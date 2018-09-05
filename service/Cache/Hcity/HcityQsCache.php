<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/7
 * Time: 11:05
 */
namespace Service\Cache\Hcity;

use Service\Cache\BaseCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityQsDao;

class HcityQsCache extends BaseCache
{

    /**
     * @param array $input 必需:uid
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
            $hcityQsDao = HcityQsDao::i();
            $mHcityQs = $hcityQsDao->getOne(['uid' => $input['uid']]);
            if(!$mHcityQs)
                throw new \Exception('获取用户骑士信息失败');
            return $mHcityQs;

        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        return "Hcity:HcityQs:{$this->input['uid']}";
    }
}
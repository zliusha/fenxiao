<?php
namespace Service\Cache\Hcity;

use Service\Cache\BaseCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityManageAccountDao;
use Service\Exceptions\Exception;


/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/8/2
 * Time  10:28
 */
class HcityManageAccountCache extends BaseCache
{
    /**
     * @param array $input 必需:id
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
            $hcityManageAccountDao = HcityManageAccountDao::i();
            $mHcityManageAccount = $hcityManageAccountDao->getOne(['id' => $input['id']]);
            if(!$mHcityManageAccount)
                throw new Exception('城市管理员的信息不存在');
            return $mHcityManageAccount;
        });
    }

    

    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        $input = $this->input;
        return 'Hcity:HcityManageAccount:' . $input['id'];
    }

}
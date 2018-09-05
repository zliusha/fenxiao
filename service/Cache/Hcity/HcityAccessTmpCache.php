<?php

/**
 * @Author: binghe
 * @Date:   2018-08-15 11:44:05
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-16 14:01:21
 */
namespace Service\Cache\Hcity;

use Service\Cache\BaseCache;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityAccessTmpDao;
/**
 * 临时访问
 */
class HcityAccessTmpCache extends BaseCache
{
	
	/**
     * @param array $input 必需:open_id,aid,shop_id
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
            $hcityAccessTmpDao = HcityAccessTmpDao::i();
            $where = ['open_id' => $input['open_id'], 'aid' => $input['aid'], 'shop_id' => $input['shop_id']];
            //值为null时不缓存
            return $hcityAccessTmpDao->getOne($where);
        });
    }

    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        return "Hcity:HcityAccessTmpCache:{$this->input['open_id']}_{$this->input['aid']}_{$this->input['shop_id']}";
    }
}
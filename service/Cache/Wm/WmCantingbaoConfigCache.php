<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/13
 * Time: 10:55
 */
namespace Service\Cache\Wm;

use Service\Cache\BaseCache;
use Service\DbFrame\DataBase\WmShardDbModels\WmCantingbaoConfigDao;

class WmCantingbaoConfigCache extends BaseCache
{
    /**
     * @param array $input 必需:aid
     */
    public function __construct($input)
    {
        parent::__construct($input);
    }

    /**
     * 获取缓存,不存在并保存
     * data=>[aid
     * @return mixed [description]
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $wmCantingbaoConfigDao = WmCantingbaoConfigDao::i(['aid'=>$input['aid']]);
            $mWmCantingbaoConfig = $wmCantingbaoConfigDao->getOne(['aid'=>$input['aid']]);
            if(!$mWmCantingbaoConfig)
                throw new \Exception('餐厅宝配送未配置');
            return $mWmCantingbaoConfig;
        });
    }

    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "Wm:WmCantingbaoConfig:{$this->input['aid']}";
    }
}
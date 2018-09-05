<?php

/**
 * @Author: binghe
 * @Date:   2018-06-06 11:14:59
 * @Last Modified by:   binghe
 * @Last Modified time: 2018-08-02 19:40:26
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\WmPrintDao;
/**
 * 打印机设置缓存
 */
class WmPrintConfigCache extends BaseCache
{
	
	/**
     * @param array $input 必需:aid,shop_id
     */
    function __construct($input)
    {
        parent::__construct($input);
    }
    /**
     * 获取缓存,不存在并保存
     * @return array [description]
     */
    public function getDataASNX()
    {
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $wmPrintDao = WmPrintDao::i($input['aid']);
            $arr_config = $wmPrintDao->getOneArray(['shop_id' => $input['shop_id'], 'aid' => $input['aid']]);
            if(!$arr_config)
                return [];
            return $arr_config;
        });
    }
    public function getKey()
    {
        return "WmPrintConfig:{$this->input['aid']}_{$this->input['shop_id']}";
    }
}
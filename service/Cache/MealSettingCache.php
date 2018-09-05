<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/4
 * Time: 17:20
 */
namespace Service\Cache;
use Service\DbFrame\DataBase\WmShardDbModels\MealSettingDao;
use Service\Exceptions\Exception;

class MealSettingCache extends BaseCache
{

    /**
     * 获取缓存,不存在并保存 
     * @return object $m_meal_setting
     */
    public function getDataASNX()
    {
        // $input aid必选
        $input = $this->input;
        return $this->getASNX(function() use ($input){
            $mealSettingDao = MealSettingDao::i($input['aid']);
            $m_meal_setting = $mealSettingDao->getOne(['aid' => $input['aid']]);
            if(!$m_meal_setting)
                throw new Exception('设置不存在');
                
            return $m_meal_setting;
        });
    }
    /**
     * 实现抽象方法
     * @return string 缓存键名
     */
    public function getKey()
    {
        // extract($this->input);
        return "MealSetting:{$this->input['aid']}";
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/4
 * Time: 17:30
 */
use Service\Cache\MealSettingCache;
class Meal_setting extends meal_controller
{
    /**
     * 获取设置信息
     */
    public function info()
    {
        
        try {
            $mealSettingCache = new MealSettingCache(['aid' => $this->aid]);
            $info = $mealSettingCache->getDataASNX();
        } catch (Exception $e) {
            $info = new stdClass();
            $info->aid = $this->aid;
            $info->show_type = 0;
        }
        $data['info'] = $info;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
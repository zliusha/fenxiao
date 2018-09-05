<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 系统设置
 */
use Service\Cache\MealSettingCache;
use Service\DbFrame\DataBase\WmShardDbModels\MealSettingDao;
class Meal_setting_api extends wm_service_controller
{
    /**
     * 获取商城设置
     */
    public function get_meal_setting()
    {
        $mealSettingDao = MealSettingDao::i($this->s_user->aid);
        $model = $mealSettingDao->getOne(['aid' => $this->s_user->aid]);
        if (!$model) {
            $model = new stdClass();
            $model->show_type = 0;
            $model->aid = $this->s_user->aid;
        }

        $this->json_do->set_data($model);
        $this->json_do->out_put();
    }

    /**
     * 保存商城设置
     */
    public function save_meal_setting()
    {
        $rule = [
            ['field' => 'show_type', 'label' => '设置展示类型', 'rules' => 'trim|required|numeric']
        ];

        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);

        $mealSettingDao = MealSettingDao::i($this->s_user->aid);
        $model = $mealSettingDao->getOne(['aid' => $this->s_user->aid]);
        if (!$model) //不存在创建，存在更新
        {
            $f_data['aid'] = $this->s_user->aid;
            $id = $mealSettingDao->create($f_data);
            if ($id > 0) {
                $this->json_do->set_data('保存成功');
                $this->json_do->out_put();
            }
        } else {
            if ($mealSettingDao->update($f_data, ['id' => $model->id]) !== false)
            {
                //清楚缓存
                $mealSettingCache = new MealSettingCache(['aid' => $this->s_user->aid]);
                $mealSettingCache->delete();

                $this->json_do->set_data('保存成功');
                $this->json_do->out_put();
            }
        }
        $this->json_do->set_error('005', '保存失败');
    }

}

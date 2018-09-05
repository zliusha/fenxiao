<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/6/20
 * Time: 10:51
 */
use Service\DbFrame\DataBase\WmMainDbModels\WmSettingDao;
class Setting extends mshop_controller
{
    /**
     * 设置信息
     */
    public function info()
    {
        $wmSettingDao = WmSettingDao::i();
        $setting_model = $wmSettingDao->getOne(['aid' => $this->aid]);
        if($setting_model)
        {
            $setting_model->share_img = conver_picurl($setting_model->share_img);

        }
        $data['info'] = $setting_model;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
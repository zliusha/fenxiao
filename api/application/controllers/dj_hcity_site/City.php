<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/8/1
 * Time: 15:58
 */
use Service\DbFrame\DataBase\MainDbModels\MainAreaDao;

class City extends dj_hcity_site_controller
{
    /**
     * 得到所有省市区
     * @return [type] [description]
     */
    public function get_all()
    {
        $mainAreaDao = MainAreaDao::i();
        $m_main_area_arr = $mainAreaDao->getAllArray('1=1');
        $data['items'] = $m_main_area_arr;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
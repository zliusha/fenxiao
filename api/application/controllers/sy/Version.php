<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/31
 * Time: 10:43
 */
use Service\DbFrame\DataBase\MainDbModels\MainVersionDao;
class Version extends sy_controller
{
    /**
     * 最新版本信息
     */
    public function info()
    {
        //检测是否是最新
        $mainVersionDao = MainVersionDao::i();
        $m_version = $mainVersionDao->getOne(['device_type' => 0,'type' => 0, 'status' => 1],'*','v_int desc');

        $data['info'] = $m_version;
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/4/9
 * Time: 10:50
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Account extends sy_controller
{
    /**
     * 店铺用户信息
     */
    public function info()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $shop_model = $wmShopDao->getOne(['id'=>$this->s_user->shop_id, 'aid'=>$this->s_user->aid], 'id,shop_name,shop_logo');

        $data['username'] = $this->s_user->username;
        $data['shop_id'] = $this->s_user->shop_id;
        $data['shop_name'] = '';
        $data['shop_logo'] = '';

        if($shop_model)
        {
            $data['shop_name'] = $shop_model->shop_name;
            $data['shop_logo'] = conver_picurl($shop_model->shop_logo);
        }

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
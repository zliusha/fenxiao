<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/3
 * Time: 10:58
 */
class Hd_api extends base_controller
{
    /**
     * 活动列表
     */
    public function hd_list()
    {
        try {
            $hd_sdk = new hd_sdk();
            $params['connect_id'] = $this->s_user->visit_id;

            $list = $hd_sdk->getHdList($params);
        } catch (Exception $e) {
            $this->json_do->set_error('005',$e->getMessage());
        }
        $data['rows'] = $list;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
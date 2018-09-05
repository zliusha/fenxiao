<?php

/**
 * Created by PhpStorm.
 * author: ahe<ahe@iyenei.com>
 * Date: 2018/8/15
 * Time: 下午2:27
 */
use Service\Bll\Hcity\AccessBll;

class Access extends xhcity_user_controller
{
    /**
     * 新客访问小程序通知
     * @author ahe<ahe@iyenei.com>
     */
    public function xcx_notice()
    {
        $rules = [
            ['field' => 'aid', 'label' => '商户id', 'rules' => 'trim|required|numeric'],
            ['field' => 'shop_id', 'label' => '店铺id', 'rules' => 'trim|required|numeric'],
            ['field' => 'source', 'label' => '店铺名称', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rules);
        $fdata = $this->form_data($rules);
        $fdata['open_id'] = $this->s_user->openid;
        $fdata['username'] = $this->s_user->nickname;
        $fdata['mobile'] = $this->s_user->mobile;
        $fdata['access_time'] = date('Y-m-d H:i:s', time());
        try {
            (new AccessBll())->shopAccess($fdata);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }
    }
}
<?php

/**
 * 一店一码后台粉丝管理接口
 * Created by sublime.
 * author: feiying<feiying@iyenei.com>
 * Date: 2018/7/23
 * Time: 上午11:40
 */
use Service\Bll\Hcity\ShopFansBll;
use Service\Exceptions\Exception;
use Service\Support\FLock;

class Shop_fans extends dj_ydym_controller
{

    /**
     * 粉丝列表接口
     * @author feiying<feiying@iyenei.com>
     */
    public function fens_search()
    {   
        $rule = [
            ['field' => 'username', 'label' => '用户名称', 'rules' => 'trim'],
            ['field' => 'mobile', 'label' => '手机号', 'rules' => 'integer'],
            ['field' => 'is_focus_on_shop', 'label' => '是否关注门店', 'rules' => 'integer'],
            ['field' => 'time', 'label' => '时间段查询', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $data = $this->form_data($rule);
        $data['shop_id'] = $this->currentShopId;
        $ret = (new ShopFansBll())->fensSearch($this->s_user->aid, $data);
        $this->json_do->set_data($ret);
        $this->json_do->out_put();
    }
    
    /**
     * 粉丝详情接口
    * @author feiying<feiying@iyenei.com>
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => 'id', 'rules' => 'integer|required'], 
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'integer'],
            ['field' => 'time', 'label' => '时间段查询', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new ShopFansBll())->fensDetail($this->s_user->aid,$fdata);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }
    
}
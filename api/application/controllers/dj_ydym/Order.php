<?php

/**
 * 一店一码订单
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/11
 * Time: 下午2:05
 */
use Service\Bll\Hcity\OrderBll;
use Service\Exceptions\Exception;
use Service\Bll\Hcity\OrderEventBll;

class Order extends dj_ydym_controller
{

    /**
     * 获取订单列表
     * @author yize<yize@iyenei.com>
     * @author feiying<feiying@iyenei.com>update
     */
    public function list()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'integer'],
            ['field' => 'status', 'label' => '订单状态', 'rules' => 'integer'],
            ['field' => 'goods_title', 'label' => '商品名称筛选', 'rules' => 'trim'],
            ['field' => 'source_type', 'label' => '订单来源', 'rules' => 'integer'],
            ['field' => 'buyer_phone', 'label' => '用户手机号', 'rules' => 'trim'],
            ['field' => 'time', 'label' => '时间段', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $fdata['shop_id'] = $this->currentShopId;
        $data = (new OrderBll())->orderList($this->s_user->aid,$fdata);
        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 订单详情
     * @author yize<yize@iyenei.com>
     */
    public function detail()
    {
        $rule = [
            ['field' => 'id', 'label' => '订单id', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        try {
            $data = (new OrderBll())->orderDetail($this->s_user->aid,$fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }

    /**
     * 核销订单
     * @author yize<yize@iyenei.com>
     */
    public function edit()
    {
        $rule = [
            ['field' => 'hx_code', 'label' => '订单核销码', 'rules' => 'integer|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $input = [
                'aid' => $this->s_user->aid,
                'hx_code' =>$fdata['hx_code']
            ];
        try {
            //跳转至总核销详情
            (new OrderEventBll())->hxOrder($input);
            $this->json_do->out_put($input);
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }


}
<?php

/**
 * Created by PhpStorm.
 * author: yize<yize@iyenei.com>
 * Date: 2018/7/19
 * Time: 16:05
 */
use Service\Bll\Hcity\OrderKzBll;
use Service\Exceptions\Exception;

class Order extends hcity_manage_controller
{

    /**
     * 获取订单列表
     * @author yize<yize@iyenei.com>
     */
    public function list()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '门店分类id', 'rules' => 'integer'],
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'integer'],
            ['field' => 'status', 'label' => '订单状态', 'rules' => 'integer'],
            ['field' => 'goods_title', 'label' => '商品名称筛选', 'rules' => 'trim'],
            ['field' => 'source_type', 'label' => '订单来源', 'rules' => 'integer'],
            ['field' => 'buyer_phone', 'label' => '用户手机号', 'rules' => 'trim'],
            ['field' => 'time', 'label' => '时间', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $sUser=$this->s_user;
        $data = (new OrderKzBll())->orderList($sUser,$fdata);
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
            $data = (new OrderKzBll())->orderDetail($fdata['id']);
            $this->json_do->set_data($data);
            $this->json_do->out_put();
        } catch (Exception $e) {
            $this->json_do->set_error('005', $e->getMessage());
        }

    }




    /**
     * 添加订单
     * @author yize<yize@iyenei.com>
     */
    public function create()
    {


    }

    public function delete()
    {

    }


}
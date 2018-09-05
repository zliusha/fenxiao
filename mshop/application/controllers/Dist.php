<?php
/**
 * 前后端分离发布器
 * @author dadi
 */
class Dist extends dist_controller
{
    // 主入口
    public function index()
    {
        $this->mshop();
    }

    // 外卖
    public function mshop()
    {
        $this->dist('mshop/index.html');
    }

    // 扫码点餐
    public function meal()
    {
        $this->dist('meal/index.html');
    }

    // 支付中心
    public function order()
    {
        $this->dist('order/pay.html');
    }
}

<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 订单管理
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
class Order extends wm_service_controller
{
    /**
     * 订单列表
     */
    public function index()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if($this->is_zongbu) {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $wm_order_fsm_bll = new wm_order_fsm_bll();
        $data['map']['status'] = $wm_order_fsm_bll->aliasStateGroup; // 订单状态

        $inc = &inc_config('waimai');
        $data['map']['pay_type'] = $inc['pay_type']; // 支付方式
        $data['map']['logistics_type'] = $inc['logistics_type']; // 配送方式

        $this->load->view('mshop/order/index', $data);
    }

    /**
     * 订单详情
     */
    public function detail($order_tid = 0)
    {
        $this->load->view('mshop/order/detail', ['order_tid' => $order_tid]);
    }

    /**
     * 自提订单
     */
    public function pickup()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if($this->is_zongbu) {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $wm_order_fsm_bll = new wm_order_fsm_bll();
        $data['map']['status'] = $wm_order_fsm_bll->aliasStateGroup2; // 订单状态

        $inc = &inc_config('waimai');
        $data['map']['pay_type'] = $inc['pay_type']; // 支付方式
        $data['map']['logistics_type'] = $inc['logistics_type']; // 配送方式

        $this->load->view('mshop/order/pickup', $data);
    }

    /**
     * 自提订单详情
     */
    public function pickup_detail($order_tid = 0)
    {
        $this->load->view('mshop/order/pickup_detail', ['order_tid' => $order_tid]);
    }

    /**
     * 堂食订单
     */
    public function dinein()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if($this->is_zongbu) {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/order/dinein', $data);
    }

    /**
     * 堂食订单详情
     */
    public function dinein_detail($serial_number = 0)
    {
        $this->load->view('mshop/order/dinein_detail', ['serial_number' => $serial_number]);
    }

    /**
     * 零售订单
     */
    public function retail()
    {
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        // 判断子账号权限
        if($this->is_zongbu) {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid,'is_delete'=>0]); // 门店列表
        } else {
            $data['shop_list'] = $wmShopDao->getAllArray(['aid' => $this->s_user->aid, 'id' => $this->currentShopId,'is_delete'=>0]); // 门店列表
        }

        $this->load->view('mshop/order/retail', $data);
    }

    /**
     * 零售订单详情
     */
    public function retail_detail($serial_number = 0)
    {
        $this->load->view('mshop/order/retail_detail', ['serial_number' => $serial_number]);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/5/7
 * Time: 11:38
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\RetailOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
class Prints extends sy_controller
{
    /**
     * 目前针对单个桌位
     * 根据订单号获取打印商品信息
     */
    public function order_goods_info()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required|preg_key[IDS]'],
            ['field' => 'order_table_id', 'label' => '桌位记录ID', 'rules' => 'trim|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $tids = trim($f_data['tid'], ',');
        $order_table_id = $f_data['order_table_id'];

        $mealOrderExtDao = MealOrderExtDao::i($this->s_user->aid);
        $mealOrderTableDao = MealOrderTableDao::i($this->s_user->aid);
        $mealOrderDao = MealOrderDao::i($this->s_user->aid);

        $where = "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id} AND tid in ({$tids})";
        $order_ext_list = $mealOrderExtDao->getAllArray($where, 'id,aid,order_table_id,tid,goods_id,sku_id,goods_title,goods_pic,price,pay_money,num,sku_str');

        //保留处理
        foreach($order_ext_list as $ok => $order_ext)
        {
            $order_ext_list[$ok]['goods_pic'] = conver_picurl($order_ext['goods_pic']);
        }

        $data['print_time'] = date('Y-m-d H:i:s', time());
        $data['total_money'] = $mealOrderDao->getSum('pay_money', $where);
        $data['goods_list'] = $order_ext_list;

        //桌位设置信息
        $m_order_table = $mealOrderTableDao->getOne(['id' => $order_table_id, 'aid' => $this->s_user->aid]);
        $data['order_table'] = $m_order_table;
        $data['shop_name'] = isset($m_order_table->shop_name) ? $m_order_table->shop_name : '';
        $data['username'] = $this->s_user->username;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 零售订单打印信息
     */
    public function retail_order()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required|preg_key[IDS]']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $tids = trim($f_data['tid'], ',');

        $retailOrderDao = RetailOrderDao::i($this->s_user->aid);
        $retailOrderExtDao = RetailOrderExtDao::i($this->s_user->aid);

        $where = "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id} AND tid in ({$tids})";
        $order_ext_list = $retailOrderExtDao->getAllArray($where, 'id,aid,order_table_id,tid,goods_id,sku_id,goods_title,goods_pic,price,pay_money,num,sku_str');

        //保留处理
        foreach($order_ext_list as $ok => $order_ext)
        {
            $order_ext_list[$ok]['goods_pic'] = conver_picurl($order_ext['goods_pic']);
        }

        $data['print_time'] = date('Y-m-d H:i:s', time());
        $data['total_money'] = $retailOrderDao->getSum('pay_money', $where);
        $data['goods_list'] = $order_ext_list;

        //店铺信息
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $m_shop = $wmShopDao->getOne(['aid' => $this->s_user->aid, 'id' => $this->s_user->shop_id], 'id,shop_name');
        $data['shop_name'] = isset($m_shop->shop_name) ? $m_shop->shop_name : '';
        $data['username'] = $this->s_user->username;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }

    /**
     * 外卖订单打印信息
     */
    public function wm_order()
    {
        $rule = [
            ['field' => 'tid', 'label' => '订单号', 'rules' => 'trim|required|numeric']
        ];
        $this->check->check_ajax_form($rule);
        $f_data = $this->form_data($rule);
        $tid = $f_data['tid'];

        $wmOrderDao = WmOrderDao::i($this->s_user->aid);
        $wmOrderExtDao = WmOrderExtDao::i($this->s_user->aid);

        $where = "aid={$this->s_user->aid} AND shop_id={$this->s_user->shop_id} AND tid={$tid}";
        $order_ext_list = $wmOrderExtDao->getAllArray($where, 'id,aid,tid,goods_id,sku_id,goods_title,goods_pic,price,pay_money,num');

        //保留处理
        foreach($order_ext_list as $ok => $order_ext)
        {
            $order_ext_list[$ok]['goods_pic'] = conver_picurl($order_ext['goods_pic']);
        }

        $data['print_time'] = date('Y-m-d H:i:s', time());
        $data['total_money'] = $wmOrderDao->getSum('pay_money', $where);
        $data['goods_list'] = $order_ext_list;

        //店铺信息
        $wmShopDao = WmShopDao::i($this->s_user->aid);
        $m_shop = $wmShopDao->getOne(['aid' => $this->s_user->aid, 'id' => $this->s_user->shop_id], 'id,shop_name');
        $data['shop_name'] = isset($m_shop->shop_name) ? $m_shop->shop_name : '';
        $data['username'] = $this->s_user->username;

        //订单信息
        $fields = 'id,aid,tid,receiver_name,receiver_phone,receiver_site,receiver_address,sex,pay_money,freight_money,discount_money,package_money,remark';
        $m_order = $wmOrderDao->getOne(['aid'=>$this->s_user->aid, 'tid'=>$tid], $fields);
        $data['order_info'] = $m_order;

        $this->json_do->set_data($data);
        $this->json_do->out_put();
    }
}
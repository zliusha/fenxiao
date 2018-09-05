<?php
/**
 * H5桌位操作
 * @author dadi
 */
use Service\Cache\MealShopAreaTableCache;

use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealShopAreaTableDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealOrderTableDao;
class Table extends xmeal_table_controller
{
    // 读取桌位信息
    public function load()
    {
        // $rule = [
        //     // ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
        //     // ['field' => 'code', 'label' => '防刷码', 'rules' => 'trim|required|numeric'],
        // ];
        // $this->check->check_ajax_form($rule);
        // $fdata = $this->form_data($rule);


        if ($this->table->status == 0 && empty($this->table->cur_order_table_id)) {
            $this->json_do->set_error('001', '请设置用餐人数及备注');
        }

        // 查询桌位记录
        $mealOrderTableDao = MealOrderTableDao::i($this->aid);
        $order_table = $mealOrderTableDao->getOne(['id' => $this->table->cur_order_table_id]);

        if (!$order_table) {
            $this->json_do->set_error('005', '桌位信息读取失败');
        }
        $this->json_do->set_data($order_table);

        $this->json_do->set_msg('读取成功');
        $this->json_do->out_put();
    }

    // 设置桌位信息
    public function setting()
    {
        $rule = [
            // ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
            // ['field' => 'code', 'label' => '防刷码', 'rules' => 'trim|required|numeric'],
            ['field' => 'number', 'label' => '就餐人数', 'rules' => 'trim|required|numeric'],
            ['field' => 'remark', 'label' => '备注', 'rules' => 'trim'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $mealOrderTableDao = MealOrderTableDao::i($this->aid);

        if (empty($this->table->cur_order_table_id)) {
            // 加载门店信息
            $wmShopDao = WmShopDao::i($this->aid);
            $shop = $wmShopDao->getOne(['id' => $this->table->shop_id]);
            if (!$shop) {
                $this->json_do->set_error('004', '门店不存在');
            }
            //加载区域信息
            $mealShopAreaDao = MealShopAreaDao::i($this->aid);
            $m_area = $mealShopAreaDao->getOne(['id' => $this->table->shop_area_id, 'aid'=>$this->aid]);

            $record_id = $mealOrderTableDao->create([
                'aid' => $this->table->aid,
                'shop_id' => $shop->id,
                'shop_name' => $shop->shop_name,
                'shop_contact' => $shop->contact,
                'shop_logo' => $shop->shop_logo,
                'table_id' => $this->table->id,
                'table_name' => $this->table->name,
                'number' => $fdata['number'],
                'remark' => $fdata['remark'],
                'status' => 2,
                'area_name' => $m_area ? $m_area->name : ''
            ]);

            $mealShopAreaTableDao = MealShopAreaTableDao::i($this->aid);
            $result = $mealShopAreaTableDao->update(['cur_order_table_id' => $record_id, 'status' => 2], ['id' => $this->table->id]);
            $order_table_id = $record_id;
        } else {
            $result = $mealOrderTableDao->update(['number' => $fdata['number'], 'remark' => $fdata['remark']], ['id' => $this->table->cur_order_table_id]);
            $order_table_id = $this->table->cur_order_table_id;
        }

        if ($result === false) {
            $this->json_do->set_error('005', '操作失败');
        } else {
            // 清除桌位信息缓存
            $mealShopAreaTableCache = new MealShopAreaTableCache(['aid'=>$this->aid,'table_id' => $this->table->id, 'code' => $this->table->code]);
            $mealShopAreaTableCache->delete();

            //获取设置信息
            $order_table = $mealOrderTableDao->getOne(['id' => $order_table_id, 'aid' => $this->aid]);
            $data['meal_order_table'] = $order_table;

            $this->json_do->set_data($data);
            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        }
    }
}

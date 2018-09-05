<?php
/**
 * 购物车操作
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\WmCateDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\MealCartDao;
class Cart extends xmeal_table_controller
{
    // 读取购物车
    public function load()
    {
        $cart_item = [];

        $mealCartDao = MealCartDao::i($this->aid);
        $meal_cart = $mealCartDao->getAllArray(['table_id' => $this->table->id]);

        // log_message('error', __METHOD__ . $this->table->id . ' 购物车=> ' . json_encode($meal_cart));

        if ($meal_cart) {

            $wmGoodsDao = WmGoodsDao::i($this->aid);
            $wmGoodsSkuDao = WmGoodsSkuDao::i($this->aid);

            $goods_ids = array_column($meal_cart, 'goods_id');
            $result = $wmGoodsDao->getEntitysByAR(['where_in' => ['id' => $goods_ids]], true);
            $wm_goods = array_column($result, null, 'id');

            $sku_ids = array_column($meal_cart, 'sku_id');
            $result = $wmGoodsSkuDao->getEntitysByAR(['where_in' => ['id' => $sku_ids]], true);
            $wm_goods_sku = array_column($result, null, 'id');

            $_delete_ids = '';
            foreach ($meal_cart as $key => $row) {
                //过滤无效商品
                if(!isset($wm_goods[$row['goods_id']]) || !isset($wm_goods_sku[$row['sku_id']]))
                {
                    unset($meal_cart[$key]);
                    $_delete_ids .= $row['id'].',';
                }
                else
                {
                    $meal_cart[$key]['goods'] = isset($wm_goods[$row['goods_id']]) ? $wm_goods[$row['goods_id']] : null;
                    $meal_cart[$key]['goods_sku'] = isset($wm_goods_sku[$row['sku_id']]) ? $wm_goods_sku[$row['sku_id']] : null;
                    $meal_cart[$key]['is_owner'] = $this->s_user->openid == $row['open_id'] ? 1 : 0; // 是否是点餐人
                }
            }
            //删除失效商品
            $_delete_ids = trim($_delete_ids, ',');
            if(!empty($_delete_ids))
                $mealCartDao->delete("table_id={$this->table->id} AND id in ($_delete_ids)");

            // 按分类分组
            foreach ($meal_cart as $key => $row) {
                if (isset($cart_item[$row['cate_id']])) {
                    $cart_item[$row['cate_id']]['items'][] = $row;
                } else {
                    $cart_item[$row['cate_id']] = ['cate_id' => $row['cate_id'], 'cate_name' => $row['cate_name']];
                    $cart_item[$row['cate_id']]['items'][] = $row;
                }
            }
            // if (!$cart_item) {
            //     log_message('error', __METHOD__ . ' 购物车异常=> ' . json_encode($meal_cart));
            // }
        }

        $this->json_do->set_data($cart_item);
        $this->json_do->set_msg('读取成功');
        $this->json_do->out_put();
    }

    // 购物车商品操作
    public function opt()
    {
        $rule = [
            // ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
            // ['field' => 'code', 'label' => '防刷码', 'rules' => 'trim|required|numeric'],
            ['field' => 'goods_id', 'label' => '商品ID', 'rules' => 'trim|numeric|required'],
            ['field' => 'sku_id', 'label' => 'SKUID', 'rules' => 'trim|numeric|required'],
            ['field' => 'quantity', 'label' => '操作数量', 'rules' => 'trim|numeric|required'],
            ['field' => 'cate_id', 'label' => '分类ID', 'rules' => 'trim|numeric|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $mealCartDao = MealCartDao::i($this->aid);
        $is_cart_item = $mealCartDao->getCount(['table_id' => $this->table->id, 'goods_id' => $fdata['goods_id'], 'sku_id' => $fdata['sku_id'], 'cate_id'=>$fdata['cate_id'],'open_id' => $this->s_user->openid]);

        if (!$is_cart_item && $fdata['quantity'] < 0) {
            $this->json_do->set_error('005', '该商品不存在购物车中');
        }
        $wmShardDb = WmShardDb::i($this->aid);
        if ($is_cart_item) {
            // 更新数量
            $update_part = $fdata['quantity'] >= 0 ? 'quantity+' . $fdata['quantity'] : 'quantity-' . abs($fdata['quantity']);
            $wmShardDb->set('quantity', $update_part, false);
            $wmShardDb->where('table_id', $this->table->id);
            $wmShardDb->where('goods_id', $fdata['goods_id']);
            $wmShardDb->where('sku_id', $fdata['sku_id']);
            $wmShardDb->where('open_id', $this->s_user->openid);
            $wmShardDb->update($wmShardDb->tables['meal_cart']);
            $result = $wmShardDb->affected_rows();
            // 删除多余项目
            if ($fdata['quantity'] < 0) {
                $mealCartDao->delete(['table_id' => $this->table->id, 'goods_id' => $fdata['goods_id'], 'sku_id' => $fdata['sku_id'], 'quantity <=' => 0]);
            }
        } else {

            $wmCateDao = WmCateDao::i($this->aid);
            $cate = $wmCateDao->getOneArray(['id' => $fdata['cate_id']]);

            if (!$this->table->cur_order_table_id) {
                $this->json_do->set_error('001', '请设置用餐人数及备注');
            }

            $result = $mealCartDao->create([
                'aid' => $this->aid,
                'order_table_id' => $this->table->cur_order_table_id,
                'cate_id' => $fdata['cate_id'],
                'cate_name' => isset($cate['cate_name']) ?$cate['cate_name'] :'',
                'table_id' => $this->table->id,
                'table_name' => $this->table->name,
                'open_id' => $this->s_user->openid,
                'nickname' => $this->s_user->nickname,
                'headimg' => $this->s_user->headimg,
                'goods_id' => $fdata['goods_id'],
                'sku_id' => $fdata['sku_id'],
                'quantity' => $fdata['quantity'],
            ]);
        }

        if ($result) {
            // 购物车项变动通知至H5
            $worker_bll = new worker_bll;
            $winput['aid'] = $this->table->aid;
            $winput['shop_id'] = $this->table->shop_id;
            $winput['openid'] = $this->s_user->openid;
            $winput['nickname'] = $this->s_user->nickname;
            $winput['table_id'] = $this->table->id;
            $worker_bll->mealCartChange($winput);

            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '操作失败');
        }
    }

    // 购物车单项操作
    public function update()
    {
        $rule = [
            // ['field' => 'table_id', 'label' => '桌位号', 'rules' => 'trim|required|numeric'],
            // ['field' => 'code', 'label' => '防刷码', 'rules' => 'trim|required|numeric'],
            ['field' => 'cart_item_id', 'label' => '购物车项目ID', 'rules' => 'trim|numeric|required'],
            ['field' => 'quantity', 'label' => '操作数量', 'rules' => 'trim|numeric|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $mealCartDao = MealCartDao::i($this->aid);

        $wmShardDb = WmShardDb::i($this->aid);
        // 更新数量
        $update_part = $fdata['quantity'] >= 0 ? 'quantity+' . $fdata['quantity'] : 'quantity-' . abs($fdata['quantity']);
        $wmShardDb->set('quantity', $update_part, false);
        $wmShardDb->where('id', $fdata['cart_item_id']);
        $wmShardDb->update($wmShardDb->tables['meal_cart']);
        $result = $wmShardDb->affected_rows();
        // 删除多余项目
        if ($fdata['quantity'] < 0) {
            $mealCartDao->delete(['id' => $fdata['cart_item_id'], 'quantity <=' => 0]);
        }

        if ($result) {
            // 购物车项变动通知至H5
            $worker_bll = new worker_bll;
            $winput['aid'] = $this->table->aid;
            $winput['shop_id'] = $this->table->shop_id;
            $winput['openid'] = $this->s_user->openid;
            $winput['nickname'] = $this->s_user->nickname;
            $winput['table_id'] = $this->table->id;
            $worker_bll->mealCartChange($winput);

            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '操作失败');
        }
    }

    // 购物车清空
    public function reset()
    {
        $mealCartDao = MealCartDao::i($this->aid);
        if ($mealCartDao->resetCart($this->table->id)) {
            // 购物车项变动通知至H5
            $worker_bll = new worker_bll;
            $winput['aid'] = $this->table->aid;
            $winput['shop_id'] = $this->table->shop_id;
            $winput['openid'] = $this->s_user->openid;
            $winput['nickname'] = $this->s_user->nickname;
            $winput['table_id'] = $this->table->id;
            $worker_bll->mealCartChange($winput);

            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '操作失败');
        }
    }

    //同步预点餐商品到某个桌位
    public function sync()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|numeric|required'],
            ['field' => 'json', 'label' => '商品数据', 'rules' => 'trim|required']
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);
        $shop_id = $fdata['shop_id'];
        $json = @json_decode($fdata['json'], true);

        if($shop_id != $this->table->shop_id)
            $this->json_do->set_error('004', '店铺ID不匹配');

        if (!$this->table->cur_order_table_id) {
            $this->json_do->set_error('001', '请设置用餐人数及备注');
        }

        $wmCateDao = WmCateDao::i($this->aid);
        $mealCartDao = MealCartDao::i($this->aid);

        $cate_list = $wmCateDao->getAllArray(['aid'=>$this->aid, 'shop_id'=>$shop_id]);
        $list = $mealCartDao->getAllArray(['aid'=>$this->aid, 'table_id'=>$this->table->id,'open_id' => $this->s_user->openid]);

        $update_data = [];
        $add_data = [];

        foreach($json as $cart)
        {
            if( !($cart['sku_id'] && $cart['goods_id'] && $cart['quantity'] && $cart['cate_id']) )
                continue;
            $goods_list = array_find_list($list,'sku_id', $cart['sku_id']);
            $cart_goods = array_find($goods_list, 'cate_id', $cart['cate_id']);
            if(isset($cart_goods['id']))
            {
                $_update['id'] = $cart_goods['id'];
                $_update['quantity'] = $cart_goods['quantity']+$cart['quantity'];
                $_update['open_id'] = $this->s_user->openid;

                $update_data[] = $_update;
            }
            else
            {
                $cate = array_find($cate_list, 'id', $cart['cate_id']);
                $_add['aid'] = $this->aid;
                $_add['order_table_id'] = $this->table->cur_order_table_id;
                $_add['table_id'] = $this->table->id;
                $_add['table_name'] = $this->table->name;
                $_add['open_id'] = $this->s_user->openid;
                $_add['nickname'] = $this->s_user->nickname;
                $_add['headimg'] = $this->s_user->headimg;
                $_add['goods_id'] = $cart['goods_id'];
                $_add['sku_id'] = $cart['sku_id'];
                $_add['quantity'] = $cart['quantity'];
                $_add['cate_id'] = $cart['cate_id'];
                $_add['cate_name'] = isset($cate['cate_name'])?$cate['cate_name']:'';

                $add_data[] = $_add;
            }

        }
        //更新数据
        if(!empty($update_data))
            $mealCartDao->updateBatch($update_data, 'id');
        if(!empty($add_data))
            $mealCartDao->createBatch($add_data);

        // 购物车项变动通知至H5
        $worker_bll = new worker_bll;
        $winput['aid'] = $this->table->aid;
        $winput['shop_id'] = $this->table->shop_id;
        $winput['openid'] = $this->s_user->openid;
        $winput['nickname'] = $this->s_user->nickname;
        $winput['table_id'] = $this->table->id;
        $worker_bll->mealCartChange($winput);

        $this->json_do->set_msg('操作成功');
        $this->json_do->out_put();

    }
}

<?php
/**
 * 外卖拼单操作
 * @author dadi
 */
use Service\DbFrame\DataBase\WmShardDbModels\WmPindanCartDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPindanUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPindanRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
class Pindan extends mshop_user_controller
{
    // 创建拼单
    public function create()
    {
        $rule = [
            ['field' => 'shop_id', 'label' => '店铺ID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmPindanRecordDao = WmPindanRecordDao::i($this->aid);



        if (!$this->s_user->uid) {
            $this->json_do->set_error('005', '登录异常');
        }

        // 创建拼单
        $pdid = $wmPindanRecordDao->create([
            'aid' => $this->aid,
            'shop_id' => $fdata['shop_id'],
            'uid' => $this->s_user->uid,
            'status' => 1,
        ]);

        if ($pdid) {
            $this->json_do->set_data(['pdid' => $pdid]);
            $this->json_do->set_msg('创建成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '创建失败');
        }
    }

    // 锁定拼单
    public function lock()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 获取订单信息
        $wmPindanRecordDao = WmPindanRecordDao::i($this->aid);
        $pindan_record = $wmPindanRecordDao->getOne(['id' => $fdata['pdid'], 'aid' => $this->aid]);

        if ($pindan_record->status == 3) {
            $this->json_do->set_error('005', '当前拼单已提交');
        }

        if ($pindan_record->status == 9) {
            $this->json_do->set_error('005', '当前拼单已关闭');
        }

        // 创建拼单
        $result = $wmPindanRecordDao->update(['status' => 2], ['id' => $fdata['pdid'], 'status' => 1, 'aid' => $this->aid]);

        if ($result !== false) {
            if ($result === 0) {
                $this->json_do->set_msg('不存在需要锁定的拼单');
                $this->json_do->out_put();
            } else {
                // 购物车项变动通知至H5
                $worker_bll = new worker_bll;
                $winput['aid'] = $this->aid;
                $winput['pdid'] = $fdata['pdid'];
                $winput['openid'] = $this->s_user->openid;
                $worker_bll->roomPindanCartChange($winput);

                $this->json_do->set_msg('锁定成功');
                $this->json_do->out_put();
            }
        } else {
            $this->json_do->set_error('005', '锁定失败');
        }
    }

    // 解锁拼单
    public function unlock()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 获取订单信息
        $wmPindanRecordDao = WmPindanRecordDao::i($this->aid);
        $pindan_record = $wmPindanRecordDao->getOne(['id' => $fdata['pdid'], 'aid' => $this->aid]);

        if ($pindan_record->status == 3) {
            $this->json_do->set_error('005', '当前拼单已提交');
        }

        if ($pindan_record->status == 9) {
            $this->json_do->set_error('005', '当前拼单已关闭');
        }

        // 创建拼单
        $result = $wmPindanRecordDao->update(['status' => 1], ['id' => $fdata['pdid'], 'status' => 2]);

        if ($result !== false) {
            if ($result === 0) {
                $this->json_do->set_msg('不存在需要解锁的拼单');
                $this->json_do->out_put();
            } else {
                // 购物车项变动通知至H5
                $worker_bll = new worker_bll;
                $winput['aid'] = $this->aid;
                $winput['pdid'] = $fdata['pdid'];
                $winput['openid'] = $this->s_user->openid;
                $worker_bll->roomPindanCartChange($winput);

                $this->json_do->set_msg('解锁成功');
                $this->json_do->out_put();
            }
        } else {
            $this->json_do->set_error('005', '解锁失败');
        }
    }

    // 关闭拼单
    public function close()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmPindanCartDao = WmPindanCartDao::i($this->aid);
        $wmPindanUserDao = WmPindanUserDao::i($this->aid);
        $wmPindanRecordDao = WmPindanRecordDao::i($this->aid);

        $result = $wmPindanRecordDao->update(['status' => 9], ['id' => $fdata['pdid'], 'status !=' => 3]);

        if ($result === false) {
            $this->json_do->set_error('005', '关闭失败');
        } else {
            $wmPindanCartDao->resetCart($fdata['pdid']);
            $wmPindanUserDao->resetUser($fdata['pdid']);

            // 购物车项变动通知至H5
            $worker_bll = new worker_bll;
            $winput['aid'] = $this->aid;
            $winput['pdid'] = $fdata['pdid'];
            $winput['openid'] = $this->s_user->openid;
            $worker_bll->roomPindanCartChange($winput);

            $this->json_do->set_msg('关闭成功');
            $this->json_do->out_put();
        }
    }

    // 添加订餐人
    public function add_user()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
        ];

        if (!$this->s_user->openid) {
            $this->json_do->set_error('005', '用户未登录');
        }

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        // 获取订单信息
        $wmPindanRecordDao = WmPindanRecordDao::i($this->aid);
        $pindan_record = $wmPindanRecordDao->getOne(['id' => $fdata['pdid']]);

        if ($pindan_record->status == 2) {
            $this->json_do->set_error('005', '当前拼单已锁定');
        }

        if ($pindan_record->status == 3) {
            $this->json_do->set_error('005', '当前拼单已提交');
        }

        if ($pindan_record->status == 9) {
            $this->json_do->set_error('005', '当前拼单已关闭');
        }

        $wmPindanUserDao = WmPindanUserDao::i($this->aid);

        if ($pindan_record->uid == $this->s_user->uid) {
            // 拼单人添加订餐人
            $number = $wmPindanUserDao->getCount(['pdid' => $fdata['pdid'], 'is_weixin' => 0, 'aid'=>$this->aid]);
            $number += 1;
            $nickname = $number . '号订餐人';

            $puid = $wmPindanUserDao->create([
                'aid' => $pindan_record->aid,
                'shop_id' => $pindan_record->shop_id,
                'pdid' => $fdata['pdid'],
                'open_id' => $this->s_user->openid,
                'nickname' => $nickname,
                'headimg' => '',
                'is_weixin' => 0,
            ]);

            if ($puid) {
                $this->json_do->set_data([
                    'is_weixin' => 0,
                    'puid' => $puid,
                    'nickname' => $nickname,
                ]);
                $this->json_do->set_msg('添加成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '添加失败');
            }
        } else {
            // 分享链接自动添加拼友
            // 检查该用户是否已经存在
            if ($wmPindanUserDao->getCount(['pdid' => $fdata['pdid'], 'open_id' => $this->s_user->openid, 'aid'=>$this->aid]) > 0) {
                $this->json_do->set_error('005', '拼单用户已存在');
            }
            $puid = $wmPindanUserDao->create([
                'aid' => $pindan_record->aid,
                'shop_id' => $pindan_record->shop_id,
                'pdid' => $fdata['pdid'],
                'open_id' => $this->s_user->openid,
                'nickname' => $this->s_user->nickname ? $this->s_user->nickname : '',
                'headimg' => $this->s_user->img ? $this->s_user->img : '',
                'is_weixin' => 1,
            ]);

            if ($puid) {
                $this->json_do->set_data([
                    'is_weixin' => 1,
                    'puid' => $puid,
                    'nickname' => $this->s_user->nickname ? $this->s_user->nickname : '',
                ]);
                $this->json_do->set_msg('添加成功');
                $this->json_do->out_put();
            } else {
                $this->json_do->set_error('005', '添加失败');
            }
        }
    }

    // 修改昵称
    public function change_nickname()
    {
        $rule = [
            ['field' => 'puid', 'label' => '拼单UID', 'rules' => 'trim|required|numeric'],
            ['field' => 'nickname', 'label' => '新的昵称', 'rules' => 'trim|required'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmPindanUserDao = WmPindanUserDao::i($this->aid);
        if ($wmPindanUserDao->update(['nickname' => $fdata['nickname']], ['id' => $fdata['puid'], 'aid'=>$this->aid]) !== false) {
            // 获取拼友pdid
            $pindan_user = $wmPindanUserDao->getOne(['id' => $fdata['puid'], 'aid'=>$this->aid], 'pdid');
            if ($pindan_user->pdid) {
                // 购物车项变动通知至H5
                $worker_bll = new worker_bll;
                $winput['aid'] = $this->aid;
                $winput['pdid'] = $pindan_user->pdid;
                $winput['openid'] = $this->s_user->openid;
                $worker_bll->roomPindanCartChange($winput);
            } else {
                log_message('error', __METHOD__ . '通知失败::拼单ID获取失败');
            }

            $this->json_do->set_msg('修改成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '修改失败');
        }
    }

    // 读取购物车
    public function load()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
        ];

        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $cart_item = [];

        $wmPindanCartDao = WmPindanCartDao::i($this->aid);
        $wmPindanUserDao = WmPindanUserDao::i($this->aid);
        $wmPindanRecordDao = WmPindanRecordDao::i($this->aid);
        $wm_pindan_cart = $wmPindanCartDao->getAllArray(['pdid' => $fdata['pdid'], 'aid'=>$this->aid], '*', 'puid desc, id desc');

        if ($wm_pindan_cart) {

            $wmGoodsDao = WmGoodsDao::i($this->aid);
            $wmGoodsSkuDao = WmGoodsSkuDao::i($this->aid);

            $goods_ids = array_column($wm_pindan_cart, 'goods_id');
            $result = $wmGoodsDao->getEntitysByAR(['where_in' => ['id' => $goods_ids]], true);
            $wm_goods = array_column($result, null, 'id');

            $sku_ids = array_column($wm_pindan_cart, 'sku_id');
            $result = $wmGoodsSkuDao->getEntitysByAR(['where_in' => ['id' => $sku_ids]], true);
            $wm_goods_sku = array_column($result, null, 'id');

            $puids = array_column($wm_pindan_cart, 'puid');
            $result = $wmPindanUserDao->getEntitysByAR(['where_in' => ['id' => $puids]], true);
            $wm_pindan_user = array_column($result, null, 'id');

            $_delete_ids = '';
            foreach ($wm_pindan_cart as $key => $row) {
                //过滤无效商品
                if (!isset($wm_goods[$row['goods_id']]) || !isset($wm_goods_sku[$row['sku_id']])) {
                    unset($wm_pindan_cart[$key]);
                    $_delete_ids .= $row['id'] . ',';
                } else {
                    $wm_pindan_cart[$key]['goods'] = isset($wm_goods[$row['goods_id']]) ? $wm_goods[$row['goods_id']] : null;
                    $wm_pindan_cart[$key]['goods_sku'] = isset($wm_goods_sku[$row['sku_id']]) ? $wm_goods_sku[$row['sku_id']] : null;
                    $wm_pindan_cart[$key]['is_owner'] = $this->s_user->openid == $row['open_id'] ? 1 : 0; // 是否是点餐人
                }
            }
            // 删除失效商品
            $_delete_ids = trim($_delete_ids, ',');
            if (!empty($_delete_ids)) {
                $wmPindanCartDao->delete("`pdid` = '{$fdata['pdid']}' AND `id` in ($_delete_ids)");
            }

            // 按订单人分组
            foreach ($wm_pindan_cart as $key => $row) {
                $cart_item[$row['puid']]['items'][] = $row;
                if (!isset($cart_item[$row['puid']]['pindan_user'])) {
                    $cart_item[$row['puid']]['pindan_user'] = isset($wm_pindan_user[$row['puid']]) ? $wm_pindan_user[$row['puid']] : null;
                }
            }

            $new_cart_item = [];
            foreach ($cart_item as $key => $row) {
                $new_cart_item[] = $row;
            }
        }

        $pindan_record = $wmPindanRecordDao->getOne(['id' => $fdata['pdid'], 'aid'=>$this->aid]);
        $userinfo = $wmPindanUserDao->getOne(['pdid' => $fdata['pdid'], 'open_id' => $this->s_user->openid, 'aid'=>$this->aid]);

        $this->json_do->set_data(['user' => $userinfo ? $userinfo : null, 'record' => $pindan_record, 'cart_items' => $new_cart_item, 'is_main_user' => $pindan_record->uid == $this->s_user->uid ? 1 : 0]);
        $this->json_do->set_msg('读取成功');
        $this->json_do->out_put();
    }

    // 购物车商品操作
    public function opt()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'puid', 'label' => '拼单UID', 'rules' => 'trim|required|numeric'],
            ['field' => 'items', 'label' => '商品信息', 'rules' => 'trim|required'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmPindanRecordDao = WmPindanRecordDao::i($this->aid);
        $wmPindanCartDao = WmPindanCartDao::i($this->aid);

        // 获取订单信息
        $pindan_record = $wmPindanRecordDao->getOne(['id' => $fdata['pdid'], 'aid'=>$this->aid]);
        if ($pindan_record->status == 2) {
            $this->json_do->set_error('005', '当前拼单已锁定');
        }

        if ($pindan_record->status == 3) {
            $this->json_do->set_error('005', '当前拼单已提交');
        }

        if ($pindan_record->status == 9) {
            $this->json_do->set_error('005', '当前拼单已关闭');
        }

        // 删除原先数据
        $wmPindanCartDao->delete(['pdid' => $fdata['pdid'], 'puid' => $fdata['puid'], 'aid'=>$this->aid]);

        $items = json_decode($fdata['items'], true);

        $insert_items = [];

        foreach ($items as $key => $row) {
            if ($row['goods_id'] && $row['sku_id'] && $row['quantity']) {
                $insert_items[] = [
                    'pdid' => $fdata['pdid'],
                    'puid' => $fdata['puid'],
                    'aid' => $this->aid,
                    'goods_id' => $row['goods_id'],
                    'sku_id' => $row['sku_id'],
                    'quantity' => $row['quantity'],
                    'pro_attr' => $row['pro_attr'],
                    'time' => time(),
                ];
            }
        }

        if (!$insert_items) {
            $this->json_do->set_error('005', '数据为空');
        }

        // 批量插入数据
        $result = $wmPindanCartDao->createBatch($insert_items);

        if ($result) {
            // 购物车项变动通知至H5
            $worker_bll = new worker_bll;
            $winput['aid'] = $this->aid;
            $winput['pdid'] = $fdata['pdid'];
            $winput['openid'] = $this->s_user->openid;
            $worker_bll->roomPindanCartChange($winput);

            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '操作失败');
        }
    }

    // 购物车清空
    public function reset()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmPindanCartDao = WmPindanCartDao::i($this->aid);
        if ($wmPindanCartDao->resetCart($fdata['pdid']) !== false) {
            // 购物车项变动通知至H5
            $worker_bll = new worker_bll;
            $winput['aid'] = $this->aid;
            $winput['pdid'] = $fdata['pdid'];
            $winput['openid'] = $this->s_user->openid;
            $worker_bll->roomPindanCartChange($winput);

            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '操作失败');
        }
    }

    // 拼友取消订餐
    public function user_cancel()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'puid', 'label' => '拼单UID', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmPindanCartDao = WmPindanCartDao::i($this->aid);

        if ($wmPindanCartDao->delete(['puid' => $fdata['puid'], 'pdid' => $fdata['pdid'], 'aid'=>$this->aid]) !== false) {


            // 购物车项变动通知至H5
            $worker_bll = new worker_bll;
            $winput['aid'] = $this->aid;
            $winput['pdid'] = $fdata['pdid'];
            $winput['openid'] = $this->s_user->openid;
            $worker_bll->roomPindanCartChange($winput);

            $this->json_do->set_msg('操作成功');
            $this->json_do->out_put();
        } else {
            $this->json_do->set_error('005', '操作失败');
        }
    }

    // 拼友成功订单详情
    public function order_detail()
    {
        $rule = [
            ['field' => 'pdid', 'label' => '拼单ID', 'rules' => 'trim|required|numeric'],
            ['field' => 'puid', 'label' => '拼单UID', 'rules' => 'trim|required|numeric'],
        ];
        $this->check->check_ajax_form($rule);
        $fdata = $this->form_data($rule);

        $wmPindanUserDao = WmPindanUserDao::i($this->aid);
        $pindan_user = $wmPindanUserDao->getOne(['id' => $fdata['puid'], 'pdid' => $fdata['pdid'], 'aid' => $this->aid]);

        if (!$pindan_user) {
            $this->json_do->set_error('005', '数据为空');
        }

        if ($pindan_user->open_id != $this->s_user->openid) {
            $this->json_do->set_error('005', '没有查看权限');
        }

        $wmOrderExtDao = WmOrderExtDao::i($this->aid);
        $order_ext = $wmOrderExtDao->getOne(['puid' => $fdata['puid'], 'aid' => $this->aid], 'tid');

        if (!$order_ext) {
            $this->json_do->set_error('005', '订单不存在');
        }

        $wm_order_query_bll = new wm_order_query_bll();
        $order = $wm_order_query_bll->query(['aid'=>$this->aid,'tradeno' => $order_ext->tid])->get();

        if (count($order['rows']) > 1) {
            log_message('error', '存在重复订单号 => ' . $order_ext->tid);
        }

        $order = $order['rows'][0];

        // 拼单订单进行订单详情重组
        if ($order['type'] == 3) {
            // 分组子订单
            $order_ext = [];
            // 点餐人订单金额统计
            $order_ext_puid_pay = [];
            // 点餐人信息
            $puid_info = [];
            foreach ($order['order_ext'] as $key => $row) {
                $order_ext[$row['puid']][] = $row;
                $order_ext_puid_pay[$row['puid']] += $row['order_money'];
                if (!isset($puid_info[$row['puid']])) {
                    $puid_info[$row['puid']] = ['puid' => $row['puid'], 'nickname' => $row['nickname']];
                }
            }

            // 总订单金额
            $sum_order_money = array_sum($order_ext_puid_pay);

            // 重新组装的数据结构
            $new_order_ext = [];
            foreach ($order_ext as $puid => $row) {
                $new_order_ext[] = [
                    'pindan_user' => $puid_info[$puid],
                    'aa_money' => round($order_ext_puid_pay[$puid] / $sum_order_money * $order['pay_money'], 2),
                    'items' => $row,
                ];
            }

            $order['order_ext'] = $new_order_ext;
        } else {
            $this->json_do->set_error('005', '非法类型');
        }

        $wmShopDao = WmShopDao::i($this->aid);
        $order['shop'] = $wmShopDao->getOneArray(['id' => $order['shop_id'], 'aid'=>$this->aid]);

        $this->json_do->set_data($order);
        $this->json_do->out_put();
    }
}

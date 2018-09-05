<?php
use Service\Cache\AidToVisitIdCache;
use Service\DbFrame\DataBase\MainDbModels\MainCompanyDao;
use Service\DbFrame\DataBase\WmShardDb;
use Service\DbFrame\DataBase\WmShardDbModels\WmAfsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmCouponDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmGoodsSkuDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmOrderExtDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPaymentRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPindanCartDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPindanRecordDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPindanUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmPromotionDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmReceiverAddressDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmShopDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserDao;
use Service\DbFrame\DataBase\WmShardDbModels\WmUserWxbindDao;

/**
 * 微商城（外卖版）订单处理机
 * @author dadi
 *
 */
class wm_order_event_bll extends base_bll
{
    // 订单对象
    public $order = null;
    // 状态机对象
    public $fsm = null;
    // json_do对象
    public $json_do = null;
    // 当前锁键
    public $lockkey = '';
    // 使用return返回结果
    public $return = false;

    // SCRM接口请求参数
    public $scrmParams = [];

    public function __construct()
    {
        parent::__construct();
        $this->fsm = new wm_order_fsm_bll();
        $this->json_do = new json_do();
    }

    // 上锁
    private function lock($key)
    {
        $locked = false;
        if ($locked) {
            $this->json_do->set_error('004', '系统繁忙，请稍候重试');
        }

        $this->lockkey = $key;
        return true;
    }

    // 释放锁
    private function unlock()
    {
        // 释放锁
    }

    // 订单日志记录
    private function eventLogger($event, $tradeno)
    {
        //
    }

    // 第三方物流通知取消事件
    public function _eventCancelThirdPartyDelivery($logistics_type, $tradeno, $aid)
    {
        switch ($logistics_type) {
            case 2: // 达达配送
                try {
                    $wm_dada_bll = new wm_dada_bll();
                    $wm_dada_bll->cancel(['tradeno' => $tradeno, 'aid' => $aid]);
                    // log_message('error', __METHOD__ . '--2');
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '--' . $e->getMessage());
                }
                break;

            case 3: // 点我达配送 取消配送
                try {
                    $wm_dianwoda_bll = new wm_dianwoda_bll();
                    $wm_dianwoda_bll->cancel(['tradeno' => $tradeno, 'aid' => $aid]);
                    // log_message('error', __METHOD__ . '--2');
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '--' . $e->getMessage());
                }
                break;

            case 5: // 风达配送 取消配送
                try {
                    $wm_fengda_bll = new wm_fengda_bll();
                    $wm_fengda_bll->cancel(['tradeno' => $tradeno, 'aid' => $aid]);
                    // log_message('error', __METHOD__ . '--2');
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '--' . $e->getMessage());
                }
                break;

            case 6: // 餐厅宝
                try {
                    $wm_cantingbao_bll = new wm_cantingbao_bll();
                    $wm_cantingbao_bll->cancel(['tid' => $tradeno, 'aid' => $aid]);
                    // log_message('error', __METHOD__ . '--2');
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '--' . $e->getMessage());
                }
                break;

            default:
                # code...
                break;
        }

        return;
    }

    /**
     * 订单状态改变（创建,更新）通知
     */
    private function _mnsOrder($input = ['tradeno' => '', 'shop_id' => '', 'aid' => '', 'status' => '', 'status_alias' => ''])
    {
        try {
            $input['tradeno'] = $input['tradeno'] ? $input['tradeno'] : $this->order->tid;
            $input['shop_id'] = $input['shop_id'] ? $input['shop_id'] : $this->order->shop_id;
            $input['aid'] = $input['aid'] ? $input['aid'] : $this->order->aid;
            $input['status'] = $input['status'] ? $input['status'] : $this->fsm->state->code;
            $input['status_alias'] = $input['status_alias'] ? $input['status_alias'] : $this->fsm->state->alias;

            $event_bll = new event_bll;
            return $event_bll->mnsOrder($input);
        } catch (Exception $e) {
            return false;
        }
    }

    // 输出结果 带解锁功能
    public function respond($unlock = true)
    {
        $this->unlock();
        return $this->respond;
    }
    /**
     * 加载订单
     * @param  array  $input 必填:aid
     * @return [type]        [description]
     */
    private function loadOrder($input = [])
    {
        // 自动加载订单
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $this->order = $wmOrderDao->getOne($input);

        if (!$this->order) {
            $this->json_do->set_error('004', '订单读取失败');
        } else {
            return $this->order;
        }
    }

    /**
     * 运行FSM状态机
     * @param  string $event [description]
     * @param  array  $input 必填:aid
     * @return [type]        [description]
     */
    public function runFsm($event = '', $input = [])
    {
        // 不需要加载订单的事件
        $except_event = ['ORDER_CREATE'];

        if (!$input && !in_array($event, $except_event)) {
            $this->json_do->set_error('004', '程序问题，请联系管理员');
        }

        if (!in_array($event, $except_event)) {
            // 加载订单信息
            $order = $this->loadOrder($input);
            if (!$order) {
                return false;
            }

            // 传递订单是否商家派送条件
            $this->fsm->isSellerDelivery = $order->logistics_type == 1 ? 1 : 0;
            // 传递订单是否到店自提条件
            $this->fsm->isSelfPickUp = $order->logistics_type == 4 ? 1 : 0;

            $tradeno = $order->tid;
            $state = $order->status;
        } else {
            $tradeno = '0';
            $state = '0000';
        }

        // 调用状态机获取新状态
        try {
            if ($this->fsm->setSpaceState($state)->process($event)) {
                return $this->fsm->state;
            } else {
                log_message('error', '订单编号 => ' . $tradeno . ' 状态机执行失败 => ' . $this->fsm->msg);
                if ($this->return === true) {
                    return false;
                    // log_message();
                } else {
                    $this->json_do->set_error('004', $this->fsm->msg);
                }
            }
        } catch (Exception $e) {
            log_message('error', '订单编号 => ' . $tradeno . ' 状态机执行失败 => ' . $e->getMessage());
            $this->json_do->set_error('004', '操作失败');
        }
    }

    // 商品限时优惠
    private function _xianShi($goods_id = 0, $promo_id = 0, $uid = 0, $aid = 0, $store_goods_id = 0)
    {
        // $promo_id = 32;
        // $goods_id = 212;
        // $uid = 1225;

        // 今日下单时间段统计
        $start_time = strtotime(date('Y-m-d'));
        $end_time = $start_time + 86400;
        $wmOrderExtDao = WmOrderExtDao::i($aid);
        $wmPromotionDao = WmPromotionDao::i($aid);

        $promo = $wmPromotionDao->getOne(['id' => $promo_id, 'aid' => $aid, 'status' => 2]);

        if ($promo) {
            $setting = json_decode($promo->setting, true);
            $setting = array_column($setting, null, 'id');
        } else {
            log_message('error', __METHOD__ . ' 读取到不存在的优惠活动，ID：' . $promo_id);
            $setting = [];
        }
        if ($promo->shop_id > 0) {
            // 单门店活动
            if (isset($setting[$goods_id])) {
                if ($promo->limit_times == 1) {
                    // 活动期间内仅限1次参与
                    if (($wmOrderExtDao->orderUserGoodsNumber($goods_id, $uid, $promo->start_time, $promo->end_time, $aid)) > 0) {
                        return false;
                    }
                }
                return [
                    'able_quantity' => $promo->limit_buy > 0 ? $promo->limit_buy : -1,
                    'discount_type' => $promo->discount_type,
                    'discount_value' => $setting[$goods_id]['dec_input'],
                ];
            } else {
                return false;
            }
        } else {
            // 全部门店活动
            if (isset($setting[$store_goods_id])) {
                if ($promo->limit_times == 1) {
                    // 活动期间内仅限1次参与
                    if (($wmOrderExtDao->orderUserGoodsNumber($goods_id, $uid, $promo->start_time, $promo->end_time, $aid)) > 0) {
                        return false;
                    }
                }
                return [
                    'able_quantity' => $promo->limit_buy > 0 ? $promo->limit_buy : -1,
                    'discount_type' => $promo->discount_type,
                    'discount_value' => $setting[$store_goods_id]['dec_input'],
                ];
            } else {
                return false;
            }
        }

    }

    // 订单金额满减
    private function _manJian($money = 0, $condition = '')
    {
        $condition = json_decode($condition, true);

        if ($money <= 0 || !is_array($condition)) {
            return [];
        }

        $ret = [];
        $reduce_price = 0;

        foreach ($condition as $key => $row) {
            if ($money >= $row['price']) {
                if ($row['red_price'] > $reduce_price) {
                    $ret['condition_price'] = $row['price']; // 满足的条件金额
                    $ret['reduce_price'] = $row['red_price']; // 减去的优惠金额
                    $reduce_price = $row['red_price']; // 方法内部判断使用
                }
            }
        }

        return $ret;
    }

    /**
     * 下单流程 => 用户锁 调用状态机 计算订单金额[包含处理折扣和运费] 锁定sku库存 插入订单记录 扣除sku库存 订单处理日志 解除用户锁
     * @param  array   &$input   [description]
     * @param  boolean $preorder 是否是预下单
     * @return [type]            [description]
     */
    // $input 数组结构
    // rec_addr_id => 收货地址ID
    // is_self_pick => 是否是自提订单
    // self_pick_info => [] 到店自取信息
    // is_pindan => 是否是拼单
    // pdid => 拼单ID
    // uid => 用户ID
    // shop_id => 店铺ID
    // remark => 订单留言
    // coupon_id => 优惠券ID（系统内优惠券）
    // card_id => 代金券ID（微信代金券）
    // items => 订单JSON
    // aid => aid
    public function orderCreate(&$input = [], $preorder = false)
    {
        $this->lock($key = 1);

        // 到店自提订单判断
        $is_pindan = isset($input['is_pindan']) && $input['is_pindan'] == 1 ? true : false; // 是否拼单

        if ($is_pindan) {
            if (!$input['pdid']) {
                $this->unlock();
                $this->json_do->set_error('004', '拼单ID必填');
            }
            $wmPindanRecordDao = WmPindanRecordDao::i($input['aid']);
            $pindan_record = $wmPindanRecordDao->getOne(['id' => $input['pdid']]);

            if (!isset($pindan_record->status)) {
                $this->unlock();
                $this->json_do->set_error('004', '拼单ID错误');
            }

            if ($pindan_record->status != 2) {
                $this->unlock();
                $this->json_do->set_error('004', '拼单非锁定状态下禁止下单');
            }

            $wmPindanCartDao = WmPindanCartDao::i($input['aid']);
            $input['items'] = $wmPindanCartDao->getAllArray(['pdid' => $input['pdid']], '*', 'puid asc, id asc');

            if (!$input['items']) {
                $this->unlock();
                $this->json_do->set_error('004', '拼单异常，商品信息为空');
            }

            $puids = array_column($input['items'], 'puid');
            $wmPindanUserDao = WmPindanUserDao::i($input['aid']);
            $result = $wmPindanUserDao->getEntitysByAR(['where_in' => ['id' => $puids]], true);
            $wm_pindan_user = array_column($result, null, 'id');

            // 用户数据赋值
            foreach ($input['items'] as $key => $row) {
                $input['items'][$key]['nickname'] = isset($wm_pindan_user[$row['puid']]['nickname']) ? $wm_pindan_user[$row['puid']]['nickname'] : '';
            }
        }

        if (!$input['items']) {
            $this->unlock();
            $this->json_do->set_error('004', '商品信息为空');
        }

        // 原始下单数据
        $input_items = $input['items'];

        // 优化锁库存流程
        $wmGoodsSkuDao = WmGoodsSkuDao::i($input['aid']);
        #=============================================================================
        // 锁定库存
        if (!$preorder) {
            $is_lock_store = $wmGoodsSkuDao->lockStock($input_items, $input['shop_id']);

            if ($is_lock_store !== true) {
                $this->unlock();
                $this->json_do->set_error('004', '库存不足');
            }
        }
        #=============================================================================
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);
        $wmPromotionDao = WmPromotionDao::i($input['aid']);
        $wmCouponDao = WmCouponDao::i($input['aid']);

        // 加载门店信息
        $wmShopDao = WmShopDao::i($input['aid']);
        $shop = $wmShopDao->getOne(['id' => $input['shop_id']]);

        if (!$shop) {
            $this->unlock();
            $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
            $this->json_do->set_error('004', '门店不存在');
        }

        // 加载买家信息
        $wmUserDao = wmUserDao::i($input['aid']);
        $buyer = $wmUserDao->getOne(['id' => $input['uid']]);

        if (!$buyer) {
            $this->unlock();
            $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
            $this->json_do->set_error('004', '买家信息不存在');
        }

        #=============================================================================
        // 批量获取商品相关数据开始
        $wmGoodsDao = WmGoodsDao::i($input['aid']);

        // 获取商品数据列表
        $goods_ids = array_unique(array_column($input['items'], 'goods_id'));
        $goods_list = $wmGoodsDao->getEntitysByAR([
            'where_in' => ['id' => $goods_ids],
        ], true);
        $goods_list = array_column($goods_list, null, 'id');

        // 获取SKU数据列表
        $sku_ids = array_unique(array_column($input['items'], 'sku_id'));
        $sku_list = $wmGoodsSkuDao->getEntitysByAR([
            'where_in' => ['id' => $sku_ids],
        ], true);
        $sku_list = array_column($sku_list, null, 'id');
        // 批量获取商品相关数据结束
        #=============================================================================

        #=============================================================================
        // 遍历子订单开始
        $new = [];
        // 一次循环 => 获取商品及库存数据+优惠商品拆单
        foreach ($input['items'] as $key => $item) {
            if (!isset($goods_list[$item['goods_id']])) {
                $this->unlock();
                log_message('error', __METHOD__ . '下单失败，商品数据不存在，goods_id=>' . $item['goods_id']);
                $this->json_do->set_error('004', '下单失败，商品数据不存在');
            }
            if (!isset($sku_list[$item['sku_id']])) {
                $this->unlock();
                log_message('error', __METHOD__ . '下单失败，库存数据不存在，sku_id=>' . $item['sku_id']);
                $this->json_do->set_error('004', '下单失败，库存数据不存在');
            }
            // 商品信息
            $item['goods'] = $goods_list[$item['goods_id']];
            // sku信息
            $item['sku'] = $sku_list[$item['sku_id']];

            $item['xianShi'] = false;

            // 限时优惠商品处理
            if ($item['goods']['promo_id'] > 0) {
                $xianShi = $this->_xianShi($item['goods']['id'], $item['goods']['promo_id'], $input['uid'], $input['aid'], $item['goods']['store_goods_id']);
                if (is_array($xianShi)) {
                    // able_quantity = -1 代表不限购
                    // log_message('error', json_encode($xianShi));
                    if ($item['quantity'] > $xianShi['able_quantity'] && !in_array($xianShi['able_quantity'], [0, -1])) {
                        // 需要拆单的情况
                        $new_item = $item;
                        $new_item['quantity'] = $xianShi['able_quantity'];
                        $new_item['xianShi'] = $xianShi;

                        // 保存新数据
                        $new['items'][] = $new_item;

                        // 更新原数据
                        $item['quantity'] = $item['quantity'] - $xianShi['able_quantity'];
                        $item['xianShi'] = false;
                    } else {
                        if ($xianShi['able_quantity'] == 0) {
                            $item['xianShi'] = false;
                        } else {
                            $item['xianShi'] = $xianShi;
                        }
                    }
                }
            }
            // 保存数据
            $new['items'][] = $item;
        }

        $ext_total_money = 0; // 商品总额（不含运费及订单折扣）
        $ext_total_num = 0; // 商品总数
        $ext_pay_money = 0; // 支付总金额（不含运费及订单折扣）
        $ext_discount_money = 0; // 商品折扣总金额（不含运费及订单折扣）
        $package_money = 0; // 餐盒费
        $isXianShi = false; // 是否参加限时优惠

        // 二次循环 => 计算订单数据
        foreach ($new['items'] as $key => $item) {
            $item['discount_type'] = 0;
            $item['discount_price'] = 0;
            // 处理限时折扣
            if (is_array($item['xianShi'])) {
                $item['discount_type'] = $item['xianShi']['discount_type'];
                if ($item['xianShi']['discount_type'] == 1) {
                    // 打折
                    $item['discount_price'] = bcmul($item['sku']['sale_price'], $item['xianShi']['discount_value'], 2);
                } else if ($item['xianShi']['discount_type'] == 2) {
                    // 减价
                    $item['discount_price'] = bcsub($item['sku']['sale_price'], $item['xianShi']['discount_value'], 2);
                } else {
                    // 未知
                    $item['discount_price'] = $item['sku']['sale_price'];
                }
                // 优惠金额（子订单优惠金额）
                $item['discount_money'] = bcmul(bcsub($item['sku']['sale_price'], $item['discount_price'], 2), $item['quantity'], 2);
                $isXianShi = true;
            } else {
                // 优惠金额（子订单优惠金额）
                $item['discount_money'] = 0;
            }
            // 订单金额 (sku售价*商品数量)
            $item['order_money'] = bcmul($item['sku']['sale_price'], $item['quantity'], 2);
            // 餐盒费
            $item['package_money'] = bcmul($item['sku']['box_fee'], $item['quantity'], 2);
            // 待支付金额（不包含餐盒费）
            $item['pay_money'] = bcsub($item['order_money'], $item['discount_money'], 2);

            // 商品总额（不含运费及订单折扣）
            $ext_total_money = bcadd($ext_total_money, $item['order_money'], 2);
            // 餐盒费
            $package_money = bcadd($package_money, $item['package_money'], 2);
            // 商品总数
            $ext_total_num += $item['quantity'];
            // 商品折扣总金额（不含运费及订单折扣）
            $ext_discount_money = bcadd($ext_discount_money, $item['discount_money'], 2);
            // 支付总金额（不含运费、不包含餐盒费）
            $ext_pay_money = bcadd($ext_pay_money, $item['pay_money'], 2);
            // 保存数据
            $input['items'][$key] = $item;
        }
        // 遍历子订单结束
        #=============================================================================

        #=============================================================================
        // 调用状态机获取新状态
        $_state = $this->runFsm('ORDER_CREATE', ['aid' => $input['aid']]);
        #=============================================================================

        #=============================================================================
        // 读取微信会员卡&优惠券列表
        // 兼容老用户，待数据迁移完成可仅保留ci_scrm
        if (is_new_scrm($shop->aid)) {
            if (in_array(ENVIRONMENT, ['development'])) {
                $this->scrmParams['openid'] = 'ovPui0Vs-iKyricaa40m-FY84WBs';
                $this->scrmParams['visit_id'] = '9169744';
                $this->scrmParams['phone'] = '15505885184';
            } else {
                // 获取openid
                $wmUserWxbindDao = WmUserWxbindDao::i($input['aid']);
                $wxbind = $wmUserWxbindDao->getOne(['uid' => $input['uid'], 'type' => 'weixin'], 'open_id', 'id desc');
                $this->scrmParams['openid'] = $wxbind->open_id;
                $source = 'h5';
                if (!$this->scrmParams['openid']) {
                    $wxbind = $wmUserWxbindDao->getOne(['uid' => $input['uid'], 'type' => 'xcx'], 'open_id', 'id desc');
                    $this->scrmParams['openid'] = $wxbind->open_id;
                    $source = 'xcx';
                }
                // 获取visit_id
                $aidToVisitIdCache = new AidToVisitIdCache(['aid' => $shop->aid]);
                $this->scrmParams['visit_id'] = $aidToVisitIdCache->getDataASNX();
                // 获取手机号
                $wmUserDao = WmUserDao::i($input['aid']);
                $m_wm_user = $wmUserDao->getOne(['id' => $input['uid']], 'mobile');
                $this->scrmParams['phone'] = $m_wm_user->mobile;
            }

            $api = ci_scrm::GET_SPECIFIC_FAN_COUPON;
            $fan_coupon = ci_scrm::call($api, $this->scrmParams, $source);

        } else {
            // 获取必要参数
            if (in_array(ENVIRONMENT, ['development'])) {
                $openId = 'ovPui0Vs-iKyricaa40m-FY84WBs';
                $visitId = '9169744';
            } else {
                // 获取openid
                $wmUserWxbindDao = WmUserWxbindDao::i($input['aid']);
                $wxbind = $wmUserWxbindDao->getOne(['uid' => $input['uid'], 'type' => 'weixin'], 'open_id', 'id desc');
                $openId = $wxbind->open_id;
                // 获取visit_id
                $aidToVisitIdCache = new AidToVisitIdCache(['aid' => $shop->aid]);
                $visitId = $aidToVisitIdCache->getDataASNX();
            }

            $api = ci_wxcoupon::GET_SPECIFIC_FAN_COUPON;
            $params = ['openId' => $openId, 'visitId' => $visitId];
            $fan_coupon = ci_wxcoupon::call($api, $params);
        }

        // 暂无数据
        if ($fan_coupon['code'] == 1000) {
            $fan_coupon['data'] = [];
        } else {
            if ($fan_coupon['code'] != 0) {
                // 如果存在错误信息提示返回的错误信息
                if ($fan_coupon['data']) {
                    $tips = $fan_coupon['data'];
                } else {
                    log_message('error', '微信优惠信息读取未知错误：' . json_encode($fan_coupon));
                    $tips = '微信优惠信息读取失败';
                }
                $this->unlock();
                $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
                $this->json_do->set_error('004', $tips);
            }
        }

        #=============================================================================

        #=============================================================================
        // 构建订单优惠信息开始
        // 订单满减
        // 满减不与折扣同享
        // 满减计算商品总和+餐盒费（不包含运费）
        $tips = '';
        $manjian = null;
        $xinren = 0;
        $huiyuan = 0;
        // 微信会员卡折扣
        $wxMemberCard = [];

        if ($fan_coupon['data']) {
            foreach ($fan_coupon['data'] as $row) {
                if ($row['cardInfo']['card_type'] == 'MEMBER_CARD') {
                    $wxMemberCard = $row;
                    break;
                }
            }
        }

        // 满减优惠（门店优先原则）
        $manJianPromo = $wmPromotionDao->getOne(['shop_id' => $shop->id, 'start_time<' => time(), 'end_time>' => time(), 'status' => 2, 'type' => 2], '*', 'id desc');
        if (!$manJianPromo) {
            $manJianPromo = $wmPromotionDao->getOne(['aid' => $shop->aid, 'shop_id' => 0, 'start_time<' => time(), 'end_time>' => time(), 'status' => 2, 'type' => 2], '*', 'id desc');
        }

        if ($manJianPromo) {
            if ($isXianShi) {
                $tips = '满减活动与折扣商品不能同享';
            } else {
                $money = bcadd($ext_pay_money, $package_money, 2);
                $manjian = $this->_manJian($money, $manJianPromo->setting);
            }
        }

        // 新人优惠
        if ($buyer->order_count == 0 && $shop->is_newbie_coupon == 1) {
            $xinren = $shop->newbie_coupon;
        }

        // 会员折扣与限时折扣、满减活动不可同享
        if (!empty($wxMemberCard) && !$isXianShi && !$manjian) {
            // 兼容老用户，待数据迁移完成可仅保留ci_scrm
            if (is_new_scrm($shop->aid)) {
                $api = ci_scrm::GET_CARD_INFO;
                $this->scrmParams['code'] = $wxMemberCard['code'];
                $result = ci_scrm::call($api, $this->scrmParams, $source);
            } else {
                // 获取微信会员卡信息详情
                $api = ci_wxcoupon::GET_CARD_INFO;
                $params = ['code' => $wxMemberCard['code'], 'visitId' => $visitId];
                $result = ci_wxcoupon::call($api, $params);
            }

            if ($result['code'] != 0) {
                $this->unlock();
                $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
                $this->json_do->set_error('004', '微信会员卡信息读取失败');
            }

            $wxMemberCard = $result['data'];
            if ($wxMemberCard['dataLocal']['is_discount']) {
                $member_discount = isset($wxMemberCard['dataLocal']['discount']) ? $wxMemberCard['dataLocal']['discount'] / 10 : 0;
                if ($member_discount < 1 && $member_discount > 0) {
                    $ori_ext_pay_money = $ext_pay_money;
                    $ext_pay_money = bcmul($ext_pay_money, $member_discount, 2);
                    $huiyuan = bcsub($ori_ext_pay_money, $ext_pay_money, 2);
                }
            }
        }
        #=============================================================================

        #=============================================================================
        // 构建基础父订单开始
        // 加载收货地址信息（非预加载）
        if (!$preorder && $input['rec_addr_id']) {
            $wmReceiverAddressDao = WmReceiverAddressDao::i($input['aid']);
            $addr = $wmReceiverAddressDao->getOne(['id' => $input['rec_addr_id']]);

            if (!$addr) {
                $this->unlock();
                $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
                $this->json_do->set_error('004', '收货地址不存在');
            }
        }

        $manjian['reduce_price'] = isset($manjian['reduce_price']) ? $manjian['reduce_price'] : 0;
        $order_discount_money = bcadd($manjian['reduce_price'], $xinren, 2);
        $order_discount_money = bcadd($order_discount_money, $huiyuan, 2);
        $order['tid'] = create_order_number();
        $order['aid'] = $shop->aid;
        $order['shop_id'] = $shop->id;
        $order['shop_name'] = $shop->shop_name;
        $order['shop_contact'] = $shop->contact;
        $order['shop_logo'] = $shop->shop_logo;
        $order['uid'] = $buyer->id;
        $order['buyer_username'] = $buyer->username;
        $order['pay_type'] = 0;
        // 获取配送方式
        $mainCompanyDao = MainCompanyDao::i();
        $m_main_company = $mainCompanyDao->getOne(['id' => $shop->aid], 'shipping');
        $order['package_money'] = $package_money;

        #=============================================================================
        // 构建运费计算
        // 到店自提订单判断
        $is_self_pick = isset($input['is_self_pick']) && $input['is_self_pick'] == 1 ? true : false; // 是否到店自提订单

        if ($is_self_pick) {
            $self_pick_info = json_decode($input['self_pick_info'], true);
            if (!$preorder && (!$self_pick_info['pick_phone'] || !$self_pick_info['pick_time'])) {
                $this->unlock();
                $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
                $this->json_do->set_error('004', '自提信息不完整，请补充');
            }
            // 到店自提免运费
            $order['freight_money'] = 0;
            $order['logistics_status'] = json_encode([
                'pick_time' => $self_pick_info['pick_time'],
                'pick_phone' => $self_pick_info['pick_phone'],
                'pick_address' => $shop->shop_state . $shop->shop_city . $shop->shop_district . $shop->shop_address,
                'pick_lat' => $shop->latitude,
                'pick_lng' => $shop->longitude,
            ]);
        } else {
            // 满足免运费条件
            if ($shop->use_flow == 1 && bcadd($ext_pay_money, $package_money, 2) >= $shop->flow_free_money) {
                $order['freight_money'] = 0;
            } else {
                // 兼容旧版$shop->freight_money字段
                $shipping_fee = json_decode($shop->shipping_fee);
                if ($shipping_fee) {
                    // 阶梯运费计算
                    // 计算商家用户直线距离（单位米）
                    if (!$preorder) {
                        $buyer_latitude = $addr->latitude;
                        $buyer_longitude = $addr->longitude;
                    } else {
                        $buyer_latitude = $input['latitude'];
                        $buyer_longitude = $input['longitude'];
                    }
                    // 兼容小程序老版本不传经纬度的情况
                    if (!$buyer_latitude && !$buyer_longitude) {
                        $distance = 800;
                    } else {
                        $distance = get_distance($shop->latitude, $shop->longitude, $buyer_latitude, $buyer_longitude) * 1000;
                    }
                    foreach ($shipping_fee as $key => $value) {
                        $distance_region = explode('_', $key);
                        $distance_region[1] = $distance_region[1] ? $distance_region[1] : 999999;
                        if ($distance >= $distance_region[0] && $distance <= $distance_region[1]) {
                            $order['freight_money'] = $value;
                            break;
                        }
                    }
                } else {
                    $order['freight_money'] = $shop->freight_money;
                }
            }
            $order['logistics_status'] = '';
        }
        // 构建运费计算结束
        #=============================================================================
        $order['total_money'] = $ext_total_money;
        $order['total_num'] = $ext_total_num;
        $order['discount_money'] = bcadd($ext_discount_money, $order_discount_money, 2);
        $order['pay_money'] = bcsub(bcadd(bcadd($order['total_money'], $order['freight_money'], 2), $package_money, 2), $order['discount_money'], 2);
        $order['tableware'] = isset($input['tableware']) ? $input['tableware'] : '';
        $order['remark'] = isset($input['remark']) ? $input['remark'] : '';
        $order['status'] = $_state->code;
        $order['api_status'] = $_state->api_code;
        // 判断订单类型
        if ($is_self_pick) {
            // 自提订单
            $order['type'] = 2;
        } else if ($is_pindan) {
            // 拼单订单
            $order['type'] = 3;
        } else {
            // 普通订单
            $order['type'] = 1;
        }
        $order['logistics_type'] = $is_self_pick ? 4 : $m_main_company->shipping;
        $order['pay_time'] = 0;
        $order['fh_time'] = 0;
        $order['update_time'] = time();
        // 构建基础父订单结束
        #=============================================================================

        #=============================================================================
        // 优惠信息详情
        $discount_detail = [
            'manjian' => $manjian,
            'huiyuan' => $huiyuan,
            'xinren' => $xinren,
            'tips' => $tips,
        ];
        #=============================================================================

        // 预加载订单直接输出结果
        if ($preorder) {
            // 查询优惠券列表
            $coupon_list = $wmCouponDao->getAllArray(['mobile' => $buyer->mobile, 'aid' => $this->aid, 'status' => 1], 'id, title, amount, use_start_time, use_end_time, condition_limit');

            // 遍历判断优惠券是否符合使用条件
            foreach ($coupon_list as $key => $row) {
                if (time() < $row['use_start_time']) {
                    $row['disabled'] = 1;
                    $row['disabled_reason'] = '未到优惠券使用时间';
                } else if (time() > $row['use_end_time']) {
                    $row['disabled'] = 1;
                    $row['disabled_reason'] = '优惠券已过期';
                } else if ($row['condition_limit'] == -1 && $manjian['reduce_price'] > 0) {
                    $row['disabled'] = 1;
                    $row['disabled_reason'] = '该优惠券不可与满减同享';
                } else if ($row['condition_limit'] >= 0 && $order['pay_money'] < $row['condition_limit']) {
                    $row['disabled'] = 1;
                    $row['disabled_reason'] = '不满足优惠券使用条件，还差' . ($row['condition_limit'] - $order['pay_money']) . '元';
                } else {
                    $row['disabled'] = 0;
                    $row['disabled_reason'] = '';
                }

                $row['use_start_time'] = date('Y-m-d H:i', $row['use_start_time']);
                $row['use_end_time'] = date('Y-m-d H:i', $row['use_end_time']);

                $coupon_list[$key] = $row;
            }

            $preOrderData = [
                'items' => $input['items'],
                'discount_detail' => $discount_detail,
                'discount_money' => $order['discount_money'],
                'freight_money' => $order['freight_money'],
                'pay_money' => $order['pay_money'],
                'coupon_list' => $coupon_list,
            ];
            $this->unlock();
            $this->json_do->set_data($preOrderData);
            $this->json_do->out_put();
        }

        #=============================================================================
        // 订单优惠券处理
        if (isset($input['coupon_id']) && !empty($input['coupon_id'])) {
            $coupon = $wmCouponDao->getOneArray(['id' => $input['coupon_id'], 'aid' => $this->aid], 'id, amount, use_start_time, use_end_time, condition_limit, status');

            // 检查优惠券是否有效
            if ($coupon['status'] != 1 || time() < $coupon['use_start_time'] || time() > $coupon['use_end_time']) {
                $this->unlock();
                $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
                $this->json_do->set_error('004', '优惠券失效');
            }

            // 不可与满减同享的情况
            if ($coupon['condition_limit'] == -1 && $manjian['reduce_price'] > 0) {
                $this->unlock();
                $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
                $this->json_do->set_error('004', '该优惠券不可与满减同享');
            }

            // 实付金额是否满足优惠券使用条件
            if ($coupon['condition_limit'] >= 0 && $order['pay_money'] < $coupon['condition_limit']) {
                $this->unlock();
                $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
                $this->json_do->set_error('004', '不满足优惠券使用条件');
            }

            // 优惠券记录
            $discount_detail['coupon'] = $coupon;
            $discount_detail['coupon']['amount'] = isset($discount_detail['coupon']['amount']) ? $discount_detail['coupon']['amount'] : 0;

            // 执行主订单扣减
            $order['discount_money'] = bcadd($order['discount_money'], $discount_detail['coupon']['amount'], 2);
            $order['pay_money'] = bcsub($order['pay_money'], $discount_detail['coupon']['amount'], 2);
        }

        #=============================================================================
        // 订单代金券处理
        $wxCard = [];
        if (isset($input['card_id']) && !empty($input['card_id'])) {

            foreach ($fan_coupon['data'] as $row) {
                if ($row['cardInfo']['card_type'] == 'CASH' && $row['cardId'] == $input['card_id']) {
                    $wxCard = $row;
                    // break;
                }
            }

            // 读取微信代金券
            if ($wxCard) {
                if ($order['pay_money'] < $wxCard['cardInfo']['least_cost'] / 100) {
                    $this->unlock();
                    $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
                    $this->json_do->set_error('004', '代金券不满足使用条件');
                }
            } else {
                log_message('error', __METHOD__ . '代金券读取失败' . $input['card_id'] . ' ' . json_encode($fan_coupon));
            }

            // 代金券记录
            $discount_detail['card'] = $wxCard;
            $discount_detail['card']['amount'] = isset($wxCard['cardInfo']['cash']['reduce_cost']) ? $wxCard['cardInfo']['cash']['reduce_cost'] / 100 : 0;

            // 执行主订单扣减
            $order['discount_money'] = bcadd($order['discount_money'], $discount_detail['card']['amount'], 2);
            $order['pay_money'] = bcsub($order['pay_money'], $discount_detail['card']['amount'], 2);
        }

        // 订单优惠总数统计（不包含子订单折扣或减价）
        $discount_detail['discount_detail_amount'] = $discount_detail['manjian']['reduce_price'] + $discount_detail['xinren'];
        // 优惠券优惠金额
        if (isset($discount_detail['coupon']['amount'])) {
            $discount_detail['discount_detail_amount'] += $discount_detail['coupon']['amount'];
        }
        // 代金券优惠金额
        if (isset($discount_detail['card']['amount'])) {
            $discount_detail['discount_detail_amount'] += $discount_detail['card']['amount'];
        }

        // 记录优惠详情
        $order['discount_detail'] = json_encode($discount_detail);

        // 平摊订单优惠到每一个子订单实付金额
        if ($discount_detail['discount_detail_amount'] > 0) {
            $items_pay_money_sum = array_sum(array_column($input['items'], 'pay_money'));
            foreach ($input['items'] as $key => $item) {
                // 计算平摊到每个子订单的优惠金额
                $_spare_money = bcmul($discount_detail['discount_detail_amount'], bcdiv($item['pay_money'], $items_pay_money_sum, 2), 2);
                // 计算最终实付金额
                $item['pay_money'] = bcsub($item['pay_money'], $_spare_money, 2);
                // 保存处理结果
                $input['items'][$key] = $item;
            }
        }

        #=============================================================================
        // 构建父订单收货信息开始
        if ($is_self_pick) {
            // 无收货地址（到店自提订单）
            $order['receiver_name'] = '';
            $order['receiver_phone'] = $self_pick_info['pick_phone'];
            $order['receiver_site'] = '';
            $order['receiver_address'] = '';
            $order['sex'] = '';
            $order['receiver_lat'] = '';
            $order['receiver_lng'] = '';
            $order['logistics_code'] = mt_rand(100000, 999999); // 取货码
        } else {
            $order['receiver_name'] = $addr->receiver_name;
            $order['receiver_phone'] = $addr->receiver_phone;
            $order['receiver_site'] = $addr->receiver_site;
            $order['receiver_address'] = $addr->receiver_address;
            $order['sex'] = $addr->sex;
            $order['receiver_lat'] = $addr->latitude;
            $order['receiver_lng'] = $addr->longitude;
        }
        // 构建父订单收货信息结束
        #=============================================================================

        // 增加下单金额判断
        if ($order['pay_money'] <= 0) {
            $this->unlock();
            $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
            log_message('error', __METHOD__ . '下单失败 支付金额必须大于0 ' . json_encode($order));
            $this->json_do->set_error('004', '下单失败，支付金额必须大于0');
        }

        // 记录会员卡ID
        $order['card_id'] = isset($wxMemberCard['dataRemote']['member_card']['base_info']['id']) ? $wxMemberCard['dataRemote']['member_card']['base_info']['id'] : '';
        // 记录会员卡CODE
        $order['card_code'] = isset($wxMemberCard['code']) ? $wxMemberCard['code'] : '';
        // 计算奖励积分
        if (isset($wxMemberCard['dataLocal']) && $wxMemberCard['dataLocal']['is_trade_bonus']) {
            $order['card_bonus'] = floor($order['pay_money'] / $wxMemberCard['dataLocal']['per_rmb']) * $wxMemberCard['dataLocal']['given_bonus'];
            // 判断积分上限
            if ($wxMemberCard['dataLocal']['per_limit_bonus'] > 0) {
                $order['card_bonus'] = $order['card_bonus'] >= $wxMemberCard['dataLocal']['per_limit_bonus'] ? $wxMemberCard['dataLocal']['per_limit_bonus'] : $order['card_bonus'];
            }
        }

        // 获取最终付款金额计算预期收益金额
        $order['expect_settle_money'] = bcsub($order['pay_money'], $order['freight_money'], 2); // 预期收益金额

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();
        $order_id = $wmOrderDao->create($order);

        foreach ($input['items'] as $key => $item) {
            $ext_tid = $key + 1;
            $order_ext = [];

            $order_ext['type'] = $order['type'];
            $order_ext['aid'] = $shop->aid;
            $order_ext['shop_id'] = $shop->id;
            $order_ext['puid'] = $is_pindan ? $item['puid'] : 0;
            $order_ext['nickname'] = $is_pindan ? $item['nickname'] : '';
            $order_ext['uid'] = $buyer->id;
            $order_ext['tid'] = $order['tid'];
            $order_ext['ext_tid'] = $ext_tid;
            $order_ext['order_id'] = $order_id;
            $order_ext['goods_id'] = $item['goods_id'];
            $order_ext['goods_title'] = $item['goods']['title'];
            $order_ext['goods_pic'] = $item['goods']['pict_url'];
            $order_ext['sku_id'] = $item['sku_id'];
            $order_ext['sku_str'] = implode(' ', explode(',', $item['sku']['attr_names'])); // SKU属性名
            $order_ext['status'] = $_state->code;
            $order_ext['api_status'] = $_state->api_code;
            $order_ext['ori_price'] = isset($item['sku']['price']) ? $item['sku']['price'] : 0; // 原价
            $order_ext['price'] = isset($item['sku']['sale_price']) ? $item['sku']['sale_price'] : 0; // 售价
            $order_ext['pay_money'] = $item['pay_money'];
            $order_ext['package_money'] = $item['package_money'];
            $order_ext['order_money'] = $item['order_money'];
            $order_ext['discount_type'] = $item['discount_type'];
            $order_ext['discount_price'] = $item['discount_price'];
            $order_ext['discount_money'] = $item['discount_money'];
            $order_ext['num'] = $item['quantity'];
            $order_ext['update_time'] = time();
            if (isset($item['pro_attr'])) {
                $order_ext['pro_attr'] = $item['pro_attr'];
            }

            $order_ext_id[$key] = $wmOrderExtDao->create($order_ext);
        }

        $wmShardDb->trans_complete();

        if (!$order_id) {
            $this->unlock();
            $wmGoodsSkuDao->restoreStock($input_items, $input['shop_id']);
            log_message('error', __METHOD__ . '下单失败 主订单创建失败 ' . json_encode($order));
            $this->json_do->set_error('004', '下单失败');
        }

        // 使用优惠券
        if (isset($input['coupon_id'])) {
            $wmCouponDao->update(['status' => 2, 'use_time' => time(), 'use_tid' => $order['tid']], ['id' => $input['coupon_id'], 'aid' => $this->aid]);
        }

        // 用户下单计数器+1
        $wmShardDb->query("UPDATE `{$wmShardDb->tables['wm_user']}` SET `order_count` = `order_count`+1 WHERE id = " . $buyer->id . ';');

        // 核销微信优惠券
        if ($wxCard) {
            // 兼容老用户，待数据迁移完成可仅保留ci_scrm
            if (is_new_scrm($shop->aid)) {
                $api = ci_scrm::CONSUME_CARD;
                $this->scrmParams['code'] = $wxCard['code'];
                $this->scrmParams['cardId'] = $wxCard['cardId'];
                $this->scrmParams['coupon_type'] = 'CASH';
                $result = ci_scrm::call($api, $this->scrmParams, $source);
            } else {
                $api = ci_wxcoupon::CONSUME_CARD;
                $params = ['code' => $wxCard['code'], 'cardId' => $wxCard['cardId'], 'visitId' => $visitId, 'coupon_type' => 'CASH'];
                $result = ci_wxcoupon::call($api, $params);
            }
        }

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $order_id);
        // 订单事件通知
        $this->_mnsOrder(['tradeno' => $order['tid'], 'shop_id' => $order['shop_id'], 'aid' => $order['aid'], 'status' => $_state->code, 'status_alias' => $_state->api_code]);

        if ($is_pindan) {
            // 拼单完成状态变更
            $wmPindanRecordDao->update(['status' => 3], ['id' => $input['pdid'], 'status' => 2]);

            // 购物车项变动通知至H5
            $worker_bll = new worker_bll;
            $winput['aid'] = $order['aid'];
            $winput['pdid'] = $input['pdid'];
            $winput['openid'] = $input['openid'] ? $input['openid'] : '';
            $worker_bll->roomPindanCartChange($winput);
        }

        $this->unlock();
        $this->json_do->set_data([
            'tradeno' => $order['tid'],
            'pay_expire' => date('Y-m-d H:i:s', order_pay_expire()),
            'server_time' => date('Y-m-d H:i:s'),
        ]);
        $this->json_do->set_msg('下单成功');
        $this->json_do->out_put();
    }

    // 该方法暂时无用
    public function notifyOrderfailed()
    {
        //
    }

    /**
     * 15分钟未支付超时取消
     * @param  array   $input   [description]
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // uid => 用户ID
    // aid => aid
    public function notifyUnpaidTimeout($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('NOTIFY_UNPAID_TIMEOUT', ['tid' => $input['tradeno'], 'uid' => $input['uid'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno'], 'uid' => $input['uid']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno'], 'uid' => $input['uid']]);

        // 回滚释放库存
        $order = $wmOrderDao->getOne(['tid' => $input['tradeno'], 'uid' => $input['uid']], 'shop_id');
        $order_ext = $wmOrderExtDao->getAllArray(['tid' => $input['tradeno'], 'uid' => $input['uid']], 'sku_id, num as quantity');
        $wmGoodsSkuDao->restoreStock($order_ext, $order->shop_id);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('订单关闭成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 用户取消订单流程 => 用户锁 调用状态机 库存处理 处理订单状态 订单处理日志 解除用户锁
     * @param  array   $input   [description]
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // uid => 用户ID
    // aid => aid
    public function cancelOrder($input = [])
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('CANCEL_ORDER', ['tid' => $input['tradeno'], 'uid' => $input['uid'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);

        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno'], 'uid' => $input['uid']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno'], 'uid' => $input['uid']]);

        // 回滚释放库存
        $order = $wmOrderDao->getOne(['tid' => $input['tradeno'], 'uid' => $input['uid']], 'shop_id');
        $order_ext = $wmOrderExtDao->getAllArray(['tid' => $input['tradeno'], 'uid' => $input['uid']], 'sku_id, num as quantity');
        $wmGoodsSkuDao->restoreStock($order_ext, $order->shop_id);

        // 重置优惠券为可用状态
        $wmCouponDao = WmCouponDao::i($input['aid']);
        $wmCouponDao->update(['status' => 1, 'use_tid' => 0, 'use_time' => 0], ['use_tid' => $input['tradeno'], 'status' => 2]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();
        $this->json_do->set_msg('取消成功');
        $this->json_do->out_put();
    }

    /**
     * 通知订单已经支付
     * @param  array   $input   [description]
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // uid => 用户ID
    // pay_trade_no => 支付编号
    // pay_type => 支付类型
    // aid = > aid
    public function notifyPaidSuccess($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 全局设置return
        $this->return = $return;

        // 调用状态机获取新状态
        $_state = $this->runFsm('NOTIFY_PAID_SUCCESS', ['tid' => $input['tradeno'], 'uid' => $input['uid'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);
        $wmGoodsSkuDao = WmGoodsSkuDao::i($input['aid']);
        $wmShopDao = WmShopDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['pay_trade_no' => $input['pay_trade_no'], 'pay_type' => $input['pay_type'], 'status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time(), 'pay_time' => time()], ['tid' => $input['tradeno'], 'uid' => $input['uid']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno'], 'uid' => $input['uid']]);

        // 扣除库存
        // $order = $wmOrderDao->getOne(['tid' => $input['tradeno'], 'uid' => $input['uid']], 'tid,aid,shop_id');
        $order_ext = $wmOrderExtDao->getAllArray(['tid' => $input['tradeno'], 'uid' => $input['uid']], 'sku_id, goods_id, num as quantity');
        $wmGoodsSkuDao->reduceStock($order_ext, $this->order->shop_id);

        $wmShardDb->trans_complete();

        // 通知SCRM增加积分
        if ($this->order->card_bonus > 0) {
            // 兼容老用户，待数据迁移完成可仅保留ci_scrm
            if (is_new_scrm($this->order->aid)) {
                if (in_array(ENVIRONMENT, ['development'])) {
                    $this->scrmParams['openid'] = 'ovPui0Vs-iKyricaa40m-FY84WBs';
                    $this->scrmParams['visit_id'] = '9169744';
                    $this->scrmParams['phone'] = '15505885184';
                } else {
                    // 获取openid
                    $wmUserWxbindDao = WmUserWxbindDao::i($input['aid']);
                    $wxbind = $wmUserWxbindDao->getOne(['uid' => $input['uid'], 'type' => 'weixin'], 'open_id', 'id desc');
                    $this->scrmParams['openid'] = $wxbind->open_id;
                    $source = 'h5';
                    if (!$this->scrmParams['openid']) {
                        $wxbind = $wmUserWxbindDao->getOne(['uid' => $input['uid'], 'type' => 'xcx'], 'open_id', 'id desc');
                        $this->scrmParams['openid'] = $wxbind->open_id;
                        $source = 'xcx';
                    }
                    // 获取visit_id
                    $aidToVisitIdCache = new AidToVisitIdCache(['aid' => $this->order->aid]);
                    $this->scrmParams['visit_id'] = $aidToVisitIdCache->getDataASNX();
                    // 获取手机号
                    $wmUserDao = WmUserDao::i($input['aid']);
                    $m_wm_user = $wmUserDao->getOne(['id' => $input['uid']], 'mobile');
                    $this->scrmParams['phone'] = $m_wm_user->mobile;
                }

                $api = ci_scrm::SET_BONUS;
                $this->scrmParams['from'] = 2;
                $this->scrmParams['cardId'] = $this->order->card_id;
                $this->scrmParams['code'] = $this->order->card_code;
                $this->scrmParams['coupon_type'] = 'MEMBER_CARD';
                $this->scrmParams['bonus'] = $this->order->card_bonus;
                $result = ci_scrm::call($api, $this->scrmParams, $source);

                if (!$result || $result['code'] != 0) {
                    log_message('error', __METHOD__ . ' 支付订单增加积分奖励SCRM通知失败： ' . json_encode($result) . ' Params:' . json_encode($this->scrmParams));
                }
            } else {
                if (in_array(ENVIRONMENT, ['development'])) {
                    $openId = 'ovPui0Vs-iKyricaa40m-FY84WBs';
                    $visitId = '9169744';
                } else {
                    // 获取openid
                    $wmUserWxbindDao = WmUserWxbindDao::i($input['aid']);
                    $wxbind = $wmUserWxbindDao->getOne(['uid' => $this->order->uid, 'type' => 'weixin'], 'open_id', 'id desc');
                    $openId = $wxbind->open_id;

                    // 获取visit_id
                    $aidToVisitIdCache = new AidToVisitIdCache(['aid' => $this->order->aid]);
                    $visitId = $aidToVisitIdCache->getDataASNX();
                }

                $api = ci_wxcoupon::SET_BONUS;
                $params = ['from' => 2, 'cardId' => $this->order->card_id, 'openId' => $openId, 'code' => $this->order->card_code, 'visitId' => $visitId, 'coupon_type' => 'MEMBER_CARD', 'bonus' => $this->order->card_bonus];
                $result = ci_wxcoupon::call($api, $params);

                if (!$result || $result['code'] != 0) {
                    log_message('error', __METHOD__ . ' 支付订单增加积分奖励SCRM通知失败： ' . json_encode($result) . ' Params:' . json_encode($params));
                }
            }

        }

        // 计算订单商品最后的计算单价
        $this->_calculateOrderGoodsFinalPrice(['aid' => $this->order->aid, 'tid' => $this->order->tid]);
        // 获取店铺信息
        $shop = $wmShopDao->getOne(['id' => $this->order->shop_id]);

        //商家接单消息转发至workerman workerman转发至客户端
        $worker_bll = new worker_bll;
        $winput['aid'] = $this->order->aid;
        $winput['shop_id'] = $this->order->shop_id;
        $winput['tradeno'] = $this->order->tid;
        $worker_bll->transpondNewOrder($winput);

        // 触发商家自动接单
        if ($shop->auto_receiver == 1) {
            $fdata['tradeno'] = $this->order->tid;
            $fdata['aid'] = $shop->aid;
            $fdata['shop_id'] = $shop->id;
            $fdata['auto_printer'] = $shop->auto_printer;
            $this->sellerAgreeOrder($fdata, true);
        }

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('支付成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 商家同意订单
     * @param  array   $input   必填：aid,tradeno,auto_printer
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // aid => 商户ID
    // shop_id => 门店ID
    // auto_printer => 是否自动打印
    public function sellerAgreeOrder($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 全局设置return
        $this->return = $return;

        // 调用状态机获取新状态
        $_state = $this->runFsm('SELLER_AGREE_ORDER', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmShardDb->trans_complete();

        // 自动打单
        if ($input['auto_printer']) {
            // 打印订单
            $wm_print_order_bll = new wm_print_order_bll();
            $wm_print_order_bll->printOrder(['aid' => $input['aid'], 'tradeno' => $input['tradeno']], true);
        }

        // 查询配送方式
        $mainCompanyDao = MainCompanyDao::i();
        $main_company = $mainCompanyDao->getOne(['id' => $input['aid']]);

        // 0未设置 1商家配送 2达达配送 3点我达配送
        switch ($main_company->shipping) {
            case 2: // 达达配送
                try {
                    $wm_dada_bll = new wm_dada_bll();
                    $wm_dada_bll->push(['tradeno' => $input['tradeno'], 'aid' => $input['aid']], false);
                    log_message('error', __METHOD__ . '--2');
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '--' . $e->getMessage());
                }
                break;

            case 3: // 点我达配送
                try {
                    $wm_dianwoda_bll = new wm_dianwoda_bll();
                    $wm_dianwoda_bll->push(['tradeno' => $input['tradeno'], 'aid' => $input['aid']], false);
                    log_message('error', __METHOD__ . '--2');
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '--' . $e->getMessage());
                }
                break;

            case 5: // 风达配送
                try {
                    $wm_fengda_bll = new wm_fengda_bll();
                    $wm_fengda_bll->push(['tradeno' => $input['tradeno'], 'aid' => $input['aid']], false);
                    log_message('error', __METHOD__ . '--5');
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '--' . $e->getMessage());
                }
                break;

            case 6: // 餐厅宝
                try {
                    $wm_cantingbao_bll = new wm_cantingbao_bll();
                    $wm_cantingbao_bll->push(['tid' => $input['tradeno'], 'aid' => $input['aid']], false);
                    log_message('error', __METHOD__ . '--6');
                } catch (Exception $e) {
                    log_message('error', __METHOD__ . '--' . $e->getMessage());
                }
                break;

            default:
                # code...
                break;
        }

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($this->return === true) {
            return true;
        } else {
            $this->json_do->set_msg('接单成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 商家拒绝订单
     * @param  array   $input   必填：aid,tradeno
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // aid => 商户ID
    public function sellerRefuseOrder($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('SELLER_REFUSE_ORDER', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);
        $wmPaymentRecordDao = WmPaymentRecordDao::i($input['aid']);


        if ($this->order->pay_type == 1) {
            // 余额支付退款
            // 查询付款信息
            $pay_order = $wmPaymentRecordDao->getOneArray(['code' => $input['tradeno'], 'gateway' => 'balance', 'status' => 1]);

            if (!$pay_order) {
                $this->unlock();
                $this->json_do->set_error('004', '支付信息异常');
            }

            if (in_array(ENVIRONMENT, ['development'])) {
                $this->scrmParams['openid'] = 'ovPui0Vs-iKyricaa40m-FY84WBs';
                $this->scrmParams['visit_id'] = '9169744';
                $this->scrmParams['phone'] = '15505885184';
            } else {
                // 获取openid
                $wmUserWxbindDao = WmUserWxbindDao::i($input['aid']);
                $wxbind = $wmUserWxbindDao->getOne(['uid' => $this->order->uid, 'type' => 'weixin'], 'open_id', 'id desc');
                $this->scrmParams['openid'] = $wxbind->open_id;
                $source = 'h5';
                if (!$this->scrmParams['openid']) {
                    $wxbind = $wmUserWxbindDao->getOne(['uid' => $this->order->uid, 'type' => 'xcx'], 'open_id', 'id desc');
                    $this->scrmParams['openid'] = $wxbind->open_id;
                    $source = 'xcx';
                }
                // 获取visit_id
                $aidToVisitIdCache = new AidToVisitIdCache(['aid' => $input['aid']]);
                $this->scrmParams['visit_id'] = $aidToVisitIdCache->getDataASNX();
                // 获取手机号
                $wmUserDao = WmUserDao::i($input['aid']);
                $m_wm_user = $wmUserDao->getOne(['id' => $this->order->uid], 'mobile');
                $this->scrmParams['phone'] = $m_wm_user->mobile;
            }

            $api = ci_scrm::BACK_FANS_ACCOUNT;
            $this->scrmParams['money'] = $pay_order['money'];
            $result = ci_scrm::call($api, $this->scrmParams, $source);

            if (!$result || $result['code'] != 0) {
                log_message('error', __METHOD__ . ' 会员储值SCRM退款失败提示: ' . json_encode($result));
            }
        } else if ($this->order->pay_type == 2) {
            //只有正式环境才可以退款
            if (in_array(ENVIRONMENT, ['production', 'testing'])) {
                // 调用微信退款接口
                // 查询付款信息
                $pay_order = $wmPaymentRecordDao->getOneArray(['code' => $input['tradeno'], 'gateway' => 'weixin', 'status' => 1]);

                if (!$pay_order) {
                    $this->unlock();
                    $this->json_do->set_error('004', '支付信息异常');
                }

                try {
                    if ($pay_order['source'] == 'xcx' && $pay_order['appid']) {
                        ci_wxpay::load('jsapi', $pay_order['aid'], $xcxAppid = $pay_order['appid']);
                    } else {
                        ci_wxpay::load('jsapi', $pay_order['aid']);
                    }

                    $total_fee = bcmul($pay_order['money'], 100);
                    $refund_fee = bcmul($pay_order['money'], 100);
                    $wx_input = new WxPayRefund();
                    $wx_input->SetTransaction_id($pay_order['trade_no']);
                    $wx_input->SetTotal_fee($total_fee);
                    $wx_input->SetRefund_fee($refund_fee);
                    $wx_input->SetOut_refund_no(WXPAY_MCHID . date("YmdHis"));
                    $wx_input->SetOp_user_id(WXPAY_MCHID);
                    $result = WxPayApi::refund($wx_input);

                    if (!(isset($result['return_code']) && $result['return_code'] == 'SUCCESS' && isset($result['result_code']) && $result['result_code'] == 'SUCCESS')) {
                        log_message('error', __METHOD__ . '微信退款：' . json_encode($result));
                        $this->unlock();
                        $this->json_do->set_error('004', '微信退款失败');
                    }

                } catch (Exception $e) {
                    log_message('error', '微信退款错误：' . $e->getMessage());
                    if ($e->getMessage() == 'curl出错，错误码:58') {
                        $this->unlock();
                        $this->json_do->set_error('004', '微信退款证书错误，请检查配置');
                    } else {
                        $this->unlock();
                        $this->json_do->set_error('004', '微信退款错误');
                    }
                }
            }
        }

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单（预期结算收益重置为0）
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'tk_money' => $this->order->pay_money, 'expect_settle_money' => 0, 'update_time' => time()], ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('拒单成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 系统拒绝订单
     * @param  array   $input   必填：aid,tradeno
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    public function systemRefuseOrder($input = [], $return = true)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('SYSTEM_REFUSE_ORDER', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('系统拒单成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 配送系统返回异常
     * @param  array   $input   必填：aid,tradeno
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    public function orderExpressFail($input = [], $return = true)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('ORDER_EXPRESS_FAIL', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('配送系统返回异常');
            $this->json_do->out_put();
        }
    }

    /**
     * 订单转换为商家配送
     * @param  array   $input   必填：aid,tradeno
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    public function turnSellerDelivery($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('TURN_SELLER_DELIVERY', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'logistics_type' => 1, 'update_time' => time()], ['tid' => $input['tradeno']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        $wmShardDb->trans_complete();

        // 通知取消第三方物流
        $this->_eventCancelThirdPartyDelivery($this->order->logistics_type, $this->order->tid, $this->order->aid);

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('成功转换为商家配送');
            $this->json_do->out_put();
        }
    }

    /**
     * 订单发货[商家配送预留]
     * @param  array   $input   [description]
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    public function orderSendOut($input = [], $return = false)
    {
    }

    /**
     * 商家配送确认送达
     * @param  array   $input   必填：aid,tradeno
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    public function sellerOrderDelivered($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('SELLER_ORDER_DELIVERED', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('成功确认送达');
            $this->json_do->out_put();
        }
    }

    /**
     * 外接物流系统接单
     * @param  array   $input   必填：aid,tradeno
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // tracking_no => 追踪订单编号
    public function systemOrderTaking($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('SYSTEM_ORDER_TAKING', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'logistics_code' => $input['tracking_no'], 'update_time' => time()], ['tid' => $input['tradeno']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('订单骑手接单成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 外接物流系统骑手取货
     * @param  array   $input   必填：aid,tradeno
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // tracking_no => 追踪订单编号
    public function systemRiderTaking($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('SYSTEM_RIDER_TAKING', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'logistics_code' => $input['tracking_no'], 'update_time' => time()], ['tid' => $input['tradeno']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // // 订单事件通知
        // $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('订单骑手取货成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 外接物流系统骑手取消
     * @param  array   $input   必填：aid,tradeno
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // tracking_no => 追踪订单编号
    public function systemRiderCancel($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('SYSTEM_RIDER_CANCEL', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'logistics_code' => $input['tracking_no'], 'update_time' => time()], ['tid' => $input['tradeno']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // // 订单事件通知
        // $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('订单骑手取消成功');
            $this->json_do->out_put();
        }
    }

    /**
     * 系统确认送达[物流平台返回送达信息]　　必填:aid,tradeno
     * @param  array   $input   [description]
     * @param  boolean $return   是否直接返回json输出
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    public function systemOrderDelivered($input = [], $return = false)
    {
        $this->lock($key = 1);

        // 调用状态机获取新状态
        $_state = $this->runFsm('SYSTEM_ORDER_DELIVERED', ['tid' => $input['tradeno'], 'aid' => $input['aid']]);
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        // 更新子订单状态
        $wmOrderExtDao->update(['status' => $_state->code, 'api_status' => $_state->api_code, 'update_time' => time()], ['tid' => $input['tradeno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();

        if ($return) {
            return true;
        } else {
            $this->json_do->set_msg('系统确认送达');
            $this->json_do->out_put();
        }
    }

    /**
     * 售后申请
     * @param  array   $input   必选 aid,uid,tradeno
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // uid => 用户ID
    // afs_detail => 售后详情
    // type => 退款类型
    // reason => 售后申请原因
    // remark => 售后申请备注
    public function orderApplyRefund($input = [])
    {
        $this->lock($key = 1);
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);
        $wmAfsDao = WmAfsDao::i($input['aid']);

        // 调用状态机获取新状态
        $_state = $this->runFsm('ORDER_APPLY_REFUND', ['tid' => $input['tradeno'], 'uid' => $input['uid'], 'aid' => $input['aid']]);

        // 查询子订单
        $order_ext = $wmOrderExtDao->getAllArray(['tid' => $input['tradeno']]);
        $order_ext = array_column($order_ext, null, 'ext_tid');

        // 计算退款金额和退款数量
        $afs_detail = json_decode($input['afs_detail'], true);
        $tk_money = 0;
        $tk_quantity = 0;

        if ($input['type'] == 1) {
            // 全额退款
            $tk_money = $this->order->pay_money;
            $tk_quantity = $this->order->total_num;
            $afs_detail = [];
        } else if ($input['type'] == 2) {
            // 部分退款
            foreach ($afs_detail as $key => $row) {
                // 对应子订单
                $_order_ext = $order_ext[$row['ext_tid']];
                // 附加字段
                $row['goods_id'] = $_order_ext['goods_id'];
                $row['goods_title'] = $_order_ext['goods_title'];
                $row['goods_pic'] = $_order_ext['goods_pic'];
                $row['sku_id'] = $_order_ext['sku_id'];
                $row['sku_str'] = $_order_ext['sku_str'];
                if ($row['num'] == $_order_ext['num']) {
                    $row['tk_money'] = $_order_ext['pay_money'];
                    $row['tk_quantity'] = $_order_ext['num'];
                } else {
                    $row['tk_money'] = bcmul(bcdiv($_order_ext['pay_money'], $_order_ext['num'], 2), $row['num'], 2);
                    $row['tk_quantity'] = $row['num'];
                }
                $tk_money = bcadd($tk_money, $row['tk_money'], 2);
                $tk_quantity += $row['tk_quantity'];

                $afs_detail[$key] = $row;
            }

            // 检查退款金额是否小于等于支付
            if ($tk_money > $this->order->pay_money) {
                $this->unlock();
                $this->json_do->set_error('004', '申请退款金额大于支付金额，如需全额退款请选择全额退款');
            }
        } else {
            $this->unlock();
            // 不支持的退款类型
            $this->json_do->set_error('004', '不支持的退款类型');
        }
        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 生成售后单号
        $afsno = create_order_number();

        // 更新主订单
        $wmOrderDao->update(['afsno' => $afsno, 'status' => $_state->code, 'update_time' => time()], ['tid' => $input['tradeno'], 'uid' => $input['uid']]);

        // 更新子订单
        $wmOrderExtDao->update(['status' => $_state->code, 'update_time' => time()], ['tid' => $input['tradeno'], 'uid' => $input['uid']]);

        // 增加售后记录
        $wmAfsDao->create([
            'afsno' => $afsno,
            'type' => $input['type'],
            'shop_id' => $this->order->shop_id,
            'aid' => $this->order->aid,
            'tid' => $this->order->tid,
            'uid' => $this->order->uid,
            'order_money' => $this->order->total_money,
            'pay_money' => $this->order->pay_money,
            'freight_money' => $this->order->freight_money,
            'reason' => $input['reason'],
            'remark' => $input['remark'],
            'refuse_reason' => '',
            'tk_money' => $tk_money,
            'final_tk_money' => 0,
            'tk_quantity' => $tk_quantity,
            'final_tk_quantity' => 0,
            'afs_detail' => json_encode($afs_detail),
            'status' => 1,
            'update_time' => time(),
        ]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $input['tradeno']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();
        $this->json_do->set_msg('申请成功');
        $this->json_do->set_data($afsno);
        $this->json_do->out_put();
    }

    /**
     * 商家同意售后申请
     * @param  array   $input   必填:aid,afsno
     * @return [type]            [description]
     */
    // $input 数组结构
    // tradeno => 订单编号
    // uid => 用户ID
    // afs_detail => 售后详情
    // type => 退款类型
    // reason => 售后申请原因
    // remark => 售后申请备注
    public function afsSellerAgree($input = [])
    {
        $this->lock($key = 1);
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);
        $wmAfsDao = WmAfsDao::i($input['aid']);
        $wmPaymentRecordDao = WmPaymentRecordDao::i($input['aid']);

        // 获取售后单信息
        $afs = $wmAfsDao->getOneArray(['afsno' => $input['afsno'], 'aid' => $input['aid']]);

        // // 获取主订单信息
        // $order = $wmOrderDao->getOneArray(['tid' => $afs['tid']]);

        // 设置是否全额退款
        $this->fsm->afsType = $afs['type'];
        // 调用状态机获取新状态
        $_state = $this->runFsm('AFS_SELLER_AGREE', ['tid' => $afs['tid'], 'aid' => $input['aid']]);

        // 调用退款接口
        if ($this->order->pay_type == 1) {
            // 余额支付退款
            // 查询付款信息
            $pay_order = $wmPaymentRecordDao->getOneArray(['code' => $afs['tid'], 'gateway' => 'balance', 'status' => 1]);

            if (!$pay_order) {
                $this->unlock();
                $this->json_do->set_error('004', '支付信息异常');
            }

            if (in_array(ENVIRONMENT, ['development'])) {
                $this->scrmParams['openid'] = 'ovPui0Vs-iKyricaa40m-FY84WBs';
                $this->scrmParams['visit_id'] = '9169744';
                $this->scrmParams['phone'] = '15505885184';
            } else {
                // 获取openid
                $wmUserWxbindDao = WmUserWxbindDao::i($input['aid']);
                $wxbind = $wmUserWxbindDao->getOne(['uid' => $this->order->uid, 'type' => 'weixin'], 'open_id', 'id desc');
                $this->scrmParams['openid'] = $wxbind->open_id;
                $source = 'h5';
                if (!$this->scrmParams['openid']) {
                    $wxbind = $wmUserWxbindDao->getOne(['uid' => $this->order->uid, 'type' => 'xcx'], 'open_id', 'id desc');
                    $this->scrmParams['openid'] = $wxbind->open_id;
                    $source = 'xcx';
                }
                // 获取visit_id
                $aidToVisitIdCache = new AidToVisitIdCache(['aid' => $this->order->aid]);
                $this->scrmParams['visit_id'] = $aidToVisitIdCache->getDataASNX();
                // 获取手机号
                $wmUserDao = WmUserDao::i($input['aid']);
                $m_wm_user = $wmUserDao->getOne(['id' => $this->order->uid], 'mobile');
                $this->scrmParams['phone'] = $m_wm_user->mobile;
            }

            $api = ci_scrm::BACK_FANS_ACCOUNT;
            $this->scrmParams['money'] = $afs['tk_money'];
            $result = ci_scrm::call($api, $this->scrmParams, $source);

            if (!$result || $result['code'] != 0) {
                log_message('error', __METHOD__ . ' 会员储值SCRM退款失败提示: ' . json_encode($result));
            }
        } else if ($this->order->pay_type == 2) {
            // 微信支付退款（正式环境才需要调用）
            if (in_array(ENVIRONMENT, ['production', 'testing'])) {
                // 查询付款信息
                $pay_order = $wmPaymentRecordDao->getOneArray(['code' => $afs['tid'], 'gateway' => 'weixin', 'status' => 1]);

                if (!$pay_order) {
                    $this->unlock();
                    $this->json_do->set_error('004', '支付信息异常');
                }

                try {
                    if ($pay_order['source'] == 'xcx' && $pay_order['appid']) {
                        ci_wxpay::load('jsapi', $afs['aid'], $xcxAppid = $pay_order['appid']);
                    } else {
                        ci_wxpay::load('jsapi', $afs['aid']);
                    }

                    $total_fee = bcmul($pay_order['money'], 100);
                    $refund_fee = bcmul($afs['tk_money'], 100);
                    $wx_input = new WxPayRefund();
                    $wx_input->SetTransaction_id($pay_order['trade_no']);
                    $wx_input->SetTotal_fee($total_fee);
                    $wx_input->SetRefund_fee($refund_fee);
                    $wx_input->SetOut_refund_no(WXPAY_MCHID . date("YmdHis"));
                    $wx_input->SetOp_user_id(WXPAY_MCHID);
                    $result = WxPayApi::refund($wx_input);

                    if (!(isset($result['return_code']) && $result['return_code'] == 'SUCCESS' && isset($result['result_code']) && $result['result_code'] == 'SUCCESS')) {
                        log_message('error', __METHOD__ . '微信退款：' . json_encode($result));
                        $this->unlock();
                        $this->json_do->set_error('004', '微信退款失败');
                    }

                } catch (Exception $e) {
                    log_message('error', '微信退款错误：' . $e->getMessage());
                    if ($e->getMessage() == 'curl出错，错误码:58') {
                        $this->unlock();
                        $this->json_do->set_error('004', '微信退款证书错误，请检查配置');
                    } else {
                        $this->unlock();
                        $this->json_do->set_error('004', '微信退款错误');
                    }
                }

            }
        }
        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 预期结算收益
        $expect_settle_money = $afs['type'] == 1 ? 0 : bcsub($this->order->pay_money, $afs['tk_money'], 2);

        // 更新主订单
        $wmOrderDao->update(['is_afs_finished' => 1, 'tk_money' => (float) $afs['tk_money'], 'expect_settle_money' => (float) $expect_settle_money, 'tk_quantity' => (int) $afs['tk_quantity'], 'status' => $_state->code, 'update_time' => time()], ['tid' => $afs['tid']]);

        // 更新子订单
        $wmOrderExtDao->update(['status' => $_state->code, 'update_time' => time()], ['tid' => $afs['tid']]);

        // 更新售后记录表
        $wmAfsDao->update(['final_tk_money' => (float) $afs['tk_money'], 'final_tk_quantity' => (int) $afs['tk_quantity'], 'status' => 2, 'update_time' => time()], ['afsno' => $afs['afsno']]);

        $wmShardDb->trans_complete();

        // 全额退款
        // 通知取消第三方物流
        if ($afs['type'] == 1 && $this->order->logistics_type) {
            $this->_eventCancelThirdPartyDelivery($this->order->logistics_type, $this->order->tid, $this->order->aid);
        }

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $afs['tid']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();
        $this->json_do->set_msg('同意操作成功');
        $this->json_do->out_put();
    }

    /**
     * 商家拒绝售后申请
     * @param  array   $input   [description] 必选:aid,afsno
     * @return [type]            [description]
     */
    // $input 数组结构
    // afsno => 售后编号
    // aid => 商户ID
    public function afsSellerRefuse($input = [])
    {
        $this->lock($key = 1);
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmAfsDao = WmAfsDao::i($input['aid']);

        // 获取售后单信息
        $afs = $wmAfsDao->getOneArray(['afsno' => $input['afsno'], 'aid' => $input['aid']]);

        // 获取主订单信息
        $order = $wmOrderDao->getOneArray(['tid' => $afs['tid']]);

        // 检查物流是否送达
        if ($order['logistics_code']) {
            $this->fsm->isDelivered = $this->queryIsDelivered($order['logistics_code']);
        } else if ($order['logistics_type'] == 1) {
            // 商家配送用户取消售后申请，默认送达
            $this->fsm->isDelivered = 1;
        }
        // 调用状态机获取新状态
        $_state = $this->runFsm('AFS_SELLER_REFUSE', ['tid' => $afs['tid'], 'aid' => $input['aid']]);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update([
            'is_afs_finished' => 1,
            'tk_money' => 0,
            'tk_quantity' => 0,
            'status' => $_state->code,
            'update_time' => time(),
        ], ['tid' => $afs['tid']]);

        // 更新售后记录表
        $wmAfsDao->update([
            'status' => -9,
            'update_time' => time(),
        ], ['afsno' => $afs['afsno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $afs['tid']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();
        $this->json_do->set_msg('拒绝操作成功');
        $this->json_do->out_put();
    }

    /**
     * 用户撤销售后申请 判断是否需要更新主订单状态
     * @param  array   $input   必填：aid,afsno
     * @return [type]            [description]
     */
    // $input 数组结构
    // afsno => 售后编号
    // uid => 用户ID
    public function afsBuyerCancel($input = [])
    {
        $this->lock($key = 1);

        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmAfsDao = WmAfsDao::i($input['aid']);

        // 查询售后单
        $afs = $wmAfsDao->getOneArray(['afsno' => $input['afsno'], 'uid' => $input['uid']]);

        // 获取主订单信息
        $order = $wmOrderDao->getOneArray(['tid' => $afs['tid']]);

        // 检查物流是否送达
        if ($order['logistics_code']) {
            $this->fsm->isDelivered = $this->queryIsDelivered($order['logistics_code']);
        } else if ($order['logistics_type'] == 1) {
            // 商家配送用户取消售后申请，默认送达
            $this->fsm->isDelivered = 1;
        }
        // 调用状态机获取新状态
        $_state = $this->runFsm('AFS_BUYER_CANCEL', ['tid' => $afs['tid'], 'aid' => $input['aid']]);

        $wmShardDb = WmShardDb::i($input['aid']);
        $wmShardDb->trans_start();

        // 更新主订单
        $wmOrderDao->update([
            'is_afs_finished' => 1,
            'tk_money' => 0,
            'tk_quantity' => 0,
            'status' => $_state->code,
            'update_time' => time(),
        ], ['tid' => $afs['tid']]);

        // 更新售后记录
        $wmAfsDao->update([
            'status' => -1,
            'update_time' => time(),
        ], ['afsno' => $afs['afsno']]);

        $wmShardDb->trans_complete();

        // 订单日志记录
        $this->eventLogger($this->fsm->event, $afs['tid']);
        // 订单事件通知
        $this->_mnsOrder();

        $this->unlock();
        $this->json_do->set_msg('撤销成功');
        $this->json_do->out_put();
    }

    /**
     * 计算订单商品最后的计算单价
     * @param array $input 必须aid tid
     */
    private function _calculateOrderGoodsFinalPrice($input = [])
    {
        $wmOrderDao = WmOrderDao::i($input['aid']);
        $wmOrderExtDao = WmOrderExtDao::i($input['aid']);

        $m_order = $wmOrderDao->getOne(['aid' => $input['aid'], 'tid' => $input['tid']]);
        $order_exts = $wmOrderExtDao->getAllArray(['aid' => $input['aid'], 'tid' => $input['tid']]);
        $order_exts_count = $wmOrderExtDao->getCount(['aid' => $input['aid'], 'tid' => $input['tid']]);
        $order_exts_money = $wmOrderExtDao->getSum('pay_money', ['aid' => $input['aid'], 'tid' => $input['tid']]);
        //子订单餐盒费
        $order_exts_package_money = $wmOrderExtDao->getSum('package_money', ['aid' => $input['aid'], 'tid' => $input['tid']]);
        $order_ext_total_money = bcadd($order_exts_money, $order_exts_package_money, 2);

        //去除配送费
        $order_pay_money = bcsub($m_order->pay_money, $m_order->freight_money, 2); //实际支付金额-配送费
        $update_data = [];
        $i = 0;
        $order_total_money = 0;
        foreach ($order_exts as $order_ext) {
            $i++;
            //踢出单独商品折扣金额后 商品按照金额比例分配的优惠金额
            $ext_pay_money = $order_ext['pay_money'] + $order_ext['package_money'];

            //如果最后一个则保留剩余金额
            if ($i == $order_exts_count) {
                $final_price = bcsub($order_pay_money, $order_total_money, 2);
            } else {
                $final_price = bcmul($ext_pay_money / $order_ext_total_money, $order_pay_money, 2);
                $order_total_money = bcadd($order_total_money, $final_price, 2);
            }

            $tmp['id'] = $order_ext['id'];
            $tmp['aid'] = $input['aid'];
            $tmp['final_price'] = $final_price;

            $update_data[] = $tmp;
        }
        if (!empty($update_data)) {
            $wmOrderExtDao->updateBatch($update_data, 'id');
        }

    }
    // 查询物流是否送达
    // 预留接口
    private function queryIsDelivered($no = '')
    {
        return 1;
    }

}

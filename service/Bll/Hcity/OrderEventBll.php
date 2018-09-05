<?php
/**
 * Created by PhpStorm.
 * User: liusha
 * Date: 2018/7/18
 * Time: 19:13
 */
namespace Service\Bll\Hcity;

use Service\Support\FLock;
use Service\Enum\ActivityEnum;
use Service\Enum\XcxTemplateMessageEnum;
use Service\Cache\Hcity\HcityUserCache;
use Service\Bll\Hcity\Xcx\XcxBll;
use Service\Bll\Hcity\StockBll;
use Service\Exceptions\Exception;
use Service\DbFrame\DataBase\HcityShardDb;
use Service\DbFrame\DataBase\HcityMainDb;
use Service\DbFrame\DataBase\MainDbModels\MainShopDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserWxbindDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityGoodsSkuDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsKzDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserRedPacketDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityCompanyRedPacketDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityRedPacketConsumeDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderExtDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityUserOrderDao;
use Service\DbFrame\DataBase\HcityShardDbModels\ShcityOrderHxDetailDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityWelfareGoodsSkuDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityGoodsJzDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityGoodsJzJoinDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityUserBalanceRecordDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainJoinDao;
use Service\DbFrame\DataBase\HcityMainDbModels\HcityActivityBargainTurnsDao;

class OrderEventBll extends \Service\Bll\BaseBll
{
    // 用户信息对象
    private $user = null;
    // 订单对象
    private $order = null;
    // 主店铺对象
    private $shop = null;
    // 状态机对象
    private $fsm = null;

    // 初始化
    public function __construct()
    {
        // 初始化状态机
        $this->fsm = new OrderFsmBll();
    }

    /**
     * 运行状态机
     * @param string $event
     * @param array $input
     * @throws Exception
     * @throws \Exception
     */
    private function _runFsm(string $event='', array $input=[])
    {
        // 不需要加载订单的事件
        $except_event = ['ORDER_CREATE'];

        $state = '0000';
        //校验事件和参数
        if (!in_array($event, $except_event))
        {
            if(!valid_keys_exists(['aid', 'tid'], $input))
            {
                log_message('error', __METHOD__.'参数错误,程序出错 event:'.$event.'-input:'.json_encode($input));
                throw new Exception("参数错误,程序出错");
            }

            $this->_loadOrder($input['aid'], $input['tid']);

            $this->fsm->tid = $this->order->tid;
            $state = $this->order->status_code;
        }

        // 调用状态机获取新状态
        $this->fsm->setSpaceState($state)->process($event);

    }

    /**
     * 正常订单创建 #####//TODO 注意 商品单sku有效(因目前商品规定单sku，如果修改请更改重写订单)
     * @param array $input aid uid source_type shop_id welfare_goods_id pay_type is_user_packet items 必须 activity_type  activity_id 可选
     * @param bool $pre
     */
    public function createOrder(array $input, $pre = false)
    {
        /*
         # 嘿卡会员->商圈商品->会员价
         # 非会员  ->商圈商品->团购价
         # 嘿卡会员->一点一码->如果是商圈商品->会员价->否团购价
         # 非会员  ->一点一码->团购价
         # item = [[goods_id sku_id num]]
         */
        //TODO 只支持单商品
        // #################福利池订单踢出#####################
        if($input['source_type'] == 3)
        {
            return $this->createWelfareOrder($input, $pre);
        }


        $hcityGoodsKzDao = HcityGoodsKzDao::i();
        $shcityGoodsDao = ShcityGoodsDao::i(['aid' => $input['aid']]);
        $shcityGoodsSkuDao = ShcityGoodsSkuDao::i(['aid' => $input['aid']]);

        //加载用户信息 & 校验用户账户状态
        $this->_loadUser($input['uid']);
        if($this->user->status != 0)
        {
            throw new Exception('用户账户异常，请联系平台客服');
        }
        //加载用户信息
        $this->_loadShop($input['aid'], $input['shop_id']);

        //结算商品类型  1结算商圈商品 2结算一点一码商品 3 福利池商品 4 助力(集赞)活动
        $stock_type = StockBll::SQ_STOCK;
        //订单来源
        $source_type = $input['source_type'];
        $curr_time = time();
        $kz_where = [
            'aid' => $input['aid'],
            'goods_id' => $input['goods_id'],
            'hcity_status' => 1
        ];
        //加在商品信息
        $goods = $shcityGoodsDao->getOne(['aid' => $input['aid'], 'id' => $input['goods_id'],'is_delete'=>0]);
        if (!$goods) {
            log_message('error', __METHOD__ . json_encode($input));
            throw new Exception("商品信息不存在");
        }
        if ($goods->use_end_time<$curr_time) {
            log_message('error', __METHOD__ .'商品过期'. json_encode($input));
            throw new Exception("商品已过期");
        }

        // 非活动类型 增加商圈展示时间限制
        if(!($input['activity_type']>0 && $input['activity_id']>0))
        {
            $kz_where['show_end_time >'] = $curr_time;
            $kz_where['show_begin_time <='] = $curr_time;
        }

        $goods_kz = $hcityGoodsKzDao->getOne($kz_where);

        //设置结算商品库存类型
        switch ($source_type) {
            case 1: //商圈订单
                if (!$goods_kz) {
                    log_message('error', __METHOD__ . json_encode($input));
                    throw new Exception("商品已下架或已过期");
                }
                break;
            case 2: //一店一码订单
                //一点一码商品如果存在商圈则结算使用商圈库存 商圈类型的库存如果库存不足自动变成一点一码库存
                if (!$goods_kz || ($goods_kz->is_hcity_stock_open==1 && $goods_kz->hcity_stock_num<=0))
                    $stock_type = StockBll::YDYM_STOCK;
                //非会员库存类型为YDYM库存
                if($this->user->is_open_hcard==0 || ($this->user->is_open_hcard == 1 && $this->user->hcard_expire_time < $curr_time))
                    $stock_type = StockBll::YDYM_STOCK;

                break;
            default:
                throw new Exception("异常错误");
                break;
        }

        $activity_type = 0;
        $activity_id = 0;

        //活动校验
        if($input['activity_type']>0 && $input['activity_id']>0)
        {
            $activityObj = $this->_activityValid($input, $activity_type, $activity_id, $stock_type);

        }

        $goods_sku_ids = array_column($input['items'], 'sku_id');
        $sku_list = $shcityGoodsSkuDao->getEntitysByAR(['where_in' => ['id'=>$goods_sku_ids], 'where' => ['goods_id' => $input['goods_id'], 'aid' => $input['aid']]], true);
        $sku_list = array_column($sku_list, null, 'id');

        foreach ($input['items'] as $k => $item) {
            $sku = $sku_list[$item['sku_id']];
            $input['items'][$k]['aid'] = $input['aid'];
            $input['items'][$k]['goods_id'] = $item['goods_id'];
            $input['items'][$k]['sku_id'] = $item['sku_id'];
            $input['items'][$k]['num'] = $item['num'];
            $input['items'][$k]['pay_price'] = $sku['group_price'];

            // 商圈商品/福利池 && 是否是嘿卡会员,h嘿卡会员使用嘿卡价
            if ($stock_type==1  && $this->user->is_open_hcard == 1 && $this->user->hcard_expire_time > $curr_time) {
                $input['items'][$k]['pay_price'] = $sku['hcard_price'];
            }
            // 助力集赞 活动价
            if($stock_type==4)
            {
                // 限购一件
                $input['items'][$k]['num'] = 1;
                // 支付价格活动价
                $input['items'][$k]['pay_price'] = $activityObj->activity_price;
            }
            // 砍价活动
            if($stock_type==5)
            {
                // 限购一件
                $input['items'][$k]['num'] = 1;
                // 支付价格活动价
                $input['items'][$k]['pay_price'] = $activityObj->current_price;
            }

            $input['items'][$k]['price'] = $sku['price'];
            $input['items'][$k]['group_price'] = $sku['group_price'];
            $input['items'][$k]['hcard_price'] = $sku['hcard_price'];
        }


        $tid = create_order_number();// 订单号
        $ext_total_money = 0; // 商品总额（不含运费及订单折扣）
        $cost_total_money = 0;// 成本
        $commission_money = 0;// 可分佣金额
        $ext_total_num = 0; // 商品总数
        $ext_pay_money = 0; // 支付总金额（不含运费及订单折扣）
        $ext_discount_money = 0; // 商品折扣总金额（不含运费及订单折扣）
        $ext_total_hey_money = 0; // 商品嘿卡币总额
        $packet_money = 0; //红包金额
        $pay_hey_coin = 0; //嘿卡币
        //获取状态
        $this->_runFsm('ORDER_CREATE');

        //构建子订单
        $order_ext = [];
        foreach ($input['items'] as $item) {
            $_ext['aid'] = $input['aid'];
            $_ext['uid'] = $this->user->id;
            $_ext['shop_id'] = $input['shop_id'];
            $_ext['tid'] = $tid;
            $_ext['time'] = $curr_time;
            $_ext['goods_id'] = $item['goods_id'];
            $_ext['sku_id'] = $item['sku_id'];
            $_ext['goods_title'] = $goods->title;
            $_ext['goods_pic_url'] = $goods->pic_url;
            $_ext['price'] = $item['price'];
            $_ext['group_price'] = $item['group_price'];
            $_ext['hcard_price'] = $item['hcard_price'];
            $_ext['cost_price'] = $goods->cost_price;
            $_ext['pay_price'] = $item['pay_price'];
            $_ext['status'] = $this->fsm->state->status;
            $_ext['status_code'] = $this->fsm->state->status_code;
            $_ext['num'] = $item['num'];
            $_ext['total_money'] = bcmul($_ext['pay_price'], $_ext['num'], 2);
            $_ext['payment'] = bcmul($_ext['pay_price'], $_ext['num'], 2);
            $_ext['discount_money'] = 0;
            $_ext['use_end_time'] = $goods->use_end_time;
            $_ext['source_type'] = $input['source_type'];
            $_ext['stock_type'] = $stock_type;
            $_ext['pay_hey_coin'] = 0;
            $_ext['activity_type'] = $activity_type;
            $_ext['activity_id'] = $activity_id;

            array_push($order_ext, $_ext);

            $ext_total_num += $item['num'];
            $ext_total_money = bcadd($ext_total_money, $_ext['total_money'], 2);
            $ext_pay_money = bcadd($ext_pay_money, $_ext['payment'], 2);
            $ext_discount_money = bcadd($ext_discount_money, $_ext['discount_money'], 2);
            $ext_total_hey_money = bcadd($ext_total_hey_money, $_ext['pay_hey_coin'], 2);

            //商圈商品计算佣金，成本
            if($stock_type==1)
            {
                $ext_total_cost = bcmul($_ext['cost_price'], $_ext['num'], 2);
                $cost_total_money = bcadd($ext_total_cost, $cost_total_money, 2);
            }
        }
        $shcityUserRedPacketDao = ShcityUserRedPacketDao::i(['uid' => $this->user->id]);
        $red_where = [
            'aid' => $input['aid'],
            'uid' => $this->user->id,
            'shop_id' => $input['shop_id'],
            'expire_time >' => $curr_time,
            'money >' => 0,
            'status ' => 1
        ];
        $mUserRedPacket = $shcityUserRedPacketDao->getOne($red_where);
        //判断是否使用店铺红包(排除福利池)
        if (isset($input['is_user_packet']) && $input['is_user_packet'] == 1 && $source_type <= 2) {

            if ($mUserRedPacket) {
                //余额红包金额大于支付金额 则支付金额全部使用红包
                $pay_total_money = bcsub($ext_pay_money, $ext_discount_money, 2);
                $packet_money = ($mUserRedPacket->money >= $pay_total_money) ? $pay_total_money : $mUserRedPacket->money;

                $red_packet_consume_data['uid'] = $this->user->id;
                $red_packet_consume_data['shop_id'] = $input['shop_id'];
                $red_packet_consume_data['tid'] = $tid;
                $red_packet_consume_data['red_packet_id'] = $mUserRedPacket->red_packet_id;
                $red_packet_consume_data['time'] = $curr_time;
                $red_packet_consume_data['use_money'] = $packet_money;
                //赋值店铺信息
                $mUserRedPacket->shop_info = $this->order;
            }
        }

        //排除嘿卡币支付 & 实际支付金额
        $pay_not_discount_money = bcsub($ext_pay_money, $ext_discount_money, 2);
        $payment = bcsub($pay_not_discount_money, $packet_money, 2);

        //商圈商品产生可分佣金额
        if($stock_type==1)
        {
            $commission_money = bcsub($payment, $cost_total_money, 2);
        }
        // 商圈助力活动佣金
        elseif($stock_type==4 && $source_type==1)
        {
            $commission_money = bcmul($payment, 5/100, 2);
        }
        // 商圈砍价助力活动全部分佣
        elseif($stock_type==5 && $source_type==1)
        {
            $commission_money = $payment;
        }

        //预下单数据
        if ($pre) {
            return [
                'items' => $input['items'],
                'packet_money' => 0,
                'total_money' => $ext_total_money,
                'pay_money' => $payment,
                'pay_hey_coin' => $pay_hey_coin,
                'valid_packet'=>null
            ];
        }
        //支付金额验证
        if($payment < 0)
        {
            throw new Exception("支付金额错误");
        }

        //判断账户余额
        if($input['pay_type'] == 2)
        {
            if($payment>0 && $this->user->balance<$payment)
            {
                throw new Exception("账户余额不足");
            }
        }

        //限制下单金额 10万
        if($payment > 100000)
        {
            throw new Exception("支付金额过大");
        }

        // 助力活动校验库存
        $stockBll = new StockBll();
        $stockBll->checkStock($stock_type, $input['aid'], $input['goods_id'], $input['items'], $activity_id);

        // 限购校验
        if($stock_type<=2)
        {
            //检测，限购，商圈和一定一码共享

            // 每人每日限购
            if ($goods->is_limit_open == 1) {
                // 每人每日限购
                $order_ext_where = [
                    'aid' => $input['aid'],
                    'uid' => $this->user->id,
                    'goods_id' => $input['goods_id'],
                    'status >=' => 1,
                    'status <=' => 2,
                    'time >=' => strtotime(date('Ymd')),
                    'source_type <=' => 2
                ];
                //已买数量
                $day_buy_num = ShcityOrderExtDao::i(['aid' => $input['aid']])->getSum('num', $order_ext_where);
                if (($day_buy_num + $ext_total_num) > $goods->limit_num) {
                    throw new Exception("超出每人每日限购数量，已购数量：{$day_buy_num}");
                }
            }
            // 每人总限购
            if ($goods->is_limit_open == 2) {
                // 每人限购
                $order_ext_where = [
                    'aid' => $input['aid'],
                    'uid' => $this->user->id,
                    'goods_id' => $input['goods_id'],
                    'status >=' => 1,
                    'status <=' => 2,
                    'time >=' => empty($goods->update_time)?$goods->time:$goods->update_time,
                    'source_type <=' => 2
                ];
                //已买数量
                $day_buy_num = ShcityOrderExtDao::i(['aid' => $input['aid']])->getSum('num', $order_ext_where);
                if (($day_buy_num + $ext_total_num) > $goods->total_limit_num) {
                    throw new Exception("超出每人总限购数量，已购数量：{$day_buy_num}");
                }
            }
            // 商品每日限购
            if ($goods->is_goods_limit_open == 1) {
                $order_ext_where = [
                    'aid' => $input['aid'],
                    'goods_id' => $input['goods_id'],
                    'status >=' => 1,
                    'status <=' => 2,
                    'time >=' => strtotime(date('Ymd')),
                    'source_type <=' => 2
                ];
                //已买数量
                $day_buy_num = ShcityOrderExtDao::i(['aid' => $input['aid']])->getSum('num', $order_ext_where);
                if (($day_buy_num + $ext_total_num) > $goods->goods_limit) {
                    throw new Exception("超出商品每日限购数量，已购数量：{$day_buy_num}");
                }
            }
        }


        //=========开启事务数据入库==========
        $HcityMainDb = HcityMainDb::i();
        $aidhcityShardDb = HcityShardDb::i(['aid' => $input['aid']]);
        $uidhcityShardDb = HcityShardDb::i(['uid' => $this->user->id]);
        $HcityMainDb->trans_start();
        $aidhcityShardDb->trans_start();
        $uidhcityShardDb->trans_start();


        $shcityOrderDao = ShcityOrderDao::i(['aid' => $input['aid']]);
        $shcityOrderExtDao = ShcityOrderExtDao::i(['aid' => $input['aid']]);
        $ShcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid' => $input['aid']]);

        //构建主订单
        $order['tid'] = $tid;
        $order['aid'] = $input['aid'];
        $order['uid'] = $this->user->id;
        $order['shop_id'] = $input['shop_id'];
        $order['buyer_username'] = $this->user->username;
        $order['buyer_phone'] = $this->user->mobile;
        $order['total_money'] = $ext_total_money;
        $order['total_num'] = $ext_total_num;
        $order['payment'] = $payment;
        $order['pay_hey_coin'] = $pay_hey_coin;
        $order['packet_money'] = $packet_money;
        $order['discount_money'] = bcadd($ext_discount_money, $packet_money, 2);
        $order['pay_type'] = $input['pay_type'];
        $order['status'] = $this->fsm->state->status;;
        $order['status_code'] = $this->fsm->state->status_code;
        $order['source_type'] = $input['source_type'];
        $order['stock_type'] = $stock_type;
        $order['time'] = $curr_time;
        $order['use_end_time'] = $goods->use_end_time;
        $order['region'] = $this->shop->region;
        //创建主订单
        $order_id = $shcityOrderDao->create($order);

        //创建子订单 && 核销详情
        $n = 1;
        $hx_detail = [];
        $_order_ext_count = count($order_ext);
        $_total_payment = 0;
        $_total_discount_money = 0;
        foreach ($order_ext as $ext) {
            //创建子订单
            //####平摊订单优惠到每一个子订单实付金额####

            //商圈一点一码  实付金额均摊  (优惠金额暂不处理)
            $ext['payment'] = 0;
            if($n==$_order_ext_count)
            {
                //实付金额
                if($payment>0)
                {
                    $ext['payment'] = bcsub($payment, $_total_payment, 2);
                }
            }
            else
            {
                //实付金额
                if($payment>0)
                {
                    $ext['payment'] = bcmul($payment, bcdiv($ext['total_money'], $ext_total_money, 2), 2);
                    $_total_payment = bcadd($_total_payment, $ext['payment'], 2);
                }
            }

            $n++;
            $order_ext_id = $shcityOrderExtDao->create($ext);

            $ext_commission_money = 0;
            //构建核销码记录
            for ($i = 1; $i <= $ext['num']; $i++) {
                $tmp_hx_detail['tid'] = $tid;
                $tmp_hx_detail['ext_id'] = $order_ext_id;
                $tmp_hx_detail['aid'] = $input['aid'];
                $tmp_hx_detail['uid'] = $this->user->id;
                $tmp_hx_detail['hx_code'] = create_serial_number();
                $tmp_hx_detail['hx_status'] = 0;
                $tmp_hx_detail['hx_time'] = 0;
                $tmp_hx_detail['use_end_time'] = $goods->use_end_time;

                //商圈商品/商圈助力活动/商圈砍价活动 结算佣金
                if($stock_type==1 || ($source_type==1 && $stock_type==4) || ($source_type==1 && $stock_type==5))
                {
                    //可分佣金额>0
                    if($commission_money>0)
                    {
                        if($i==$ext['num'])
                        {
                            $tmp_hx_detail['commission_money'] = bcsub($commission_money, $ext_commission_money, 2);
                        }
                        else
                        {
                            $tmp_hx_detail['commission_money'] = bcdiv($commission_money, $ext['num'], 2);
                            $ext_commission_money = bcadd($tmp_hx_detail['commission_money'], $ext_commission_money, 2);
                        }
                    }
                    else
                    {
                        $tmp_hx_detail['commission_money'] = 0;
                    }
                }

                $hx_detail[] = $tmp_hx_detail;
            }

            //扣减库存
            $reduce_data = ['goods_id' => $ext['goods_id'], 'sku_id' => $ext['sku_id'], 'num' => $ext['num'], 'activity_id'=>$activity_id];
            $reduce_return = $stockBll->changeStock($stock_type, $input['aid'], $reduce_data);

            if (!$reduce_return) {
                $aidhcityShardDb->trans_rollback();
                $uidhcityShardDb->trans_rollback();
                $HcityMainDb->trans_rollback();
                log_message('error', __METHOD__ . json_encode($input));
                throw new Exception("库存扣减出错，订单创建出失败");
            }
        }

        //主订单快照 订单订单映射到用户订单关系表(触发器已有)


        //创建核销=待核销记录信息
        if (!empty($hx_detail))
            $ShcityOrderHxDetailDao->createBatch($hx_detail);

        //扣减红包 & 创建使用记录
        if (isset($mUserRedPacket) && $packet_money > 0) {
            $shcityUserRedPacketDao = ShcityUserRedPacketDao::i(['uid' => $this->user->id]);
            $shcityRedPacketConsumeDao = ShcityRedPacketConsumeDao::i(['uid' => $this->user->id]);
            $shcityCompanyRedPacketDao = ShcityCompanyRedPacketDao::i(['aid' => $input['aid']]);
            //创建红包金额使用记录
            $shcityRedPacketConsumeDao->create($red_packet_consume_data);
            $user_red_packet_where['where'] = [
                'red_packet_id' => $mUserRedPacket->red_packet_id,
                'aid' => $input['aid'],
                'uid' => $this->user->id,
                'shop_id' => $input['shop_id']
            ];
            $shcityUserRedPacketDao->setDec('money', $packet_money, $user_red_packet_where);
            $shcityUserRedPacketDao->setInc('used_money', $packet_money, $user_red_packet_where);

            //扣减红包金额
            $company_red_packet_where['where'] = [
                'id' => $mUserRedPacket->red_packet_id,
                'aid' => $input['aid'],
                'uid' => $this->user->id,
                'shop_id' => $input['shop_id']
            ];
            $shcityCompanyRedPacketDao->setDec('balance', $packet_money, $company_red_packet_where);
            $shcityCompanyRedPacketDao->setInc('used_money', $packet_money, $company_red_packet_where);
        }

        // stock_type 特有业务处理
        if($stock_type == 4)
        {
            //助力集赞状态更改
            HcityGoodsJzJoinDao::i()->update(['status'=>3],['uid'=>$this->user->id,'activity_id'=>$input['activity_id']]);
        }
        // 砍价逻辑处理
        if($stock_type == 5)
        {
            //砍价状态更改
            HcityActivityBargainTurnsDao::i()->update(['status'=>1,'uid'=>$this->user->id, 'order_create_time'=>$curr_time, 'tid'=>$tid],['id'=>$activityObj->id]);
        }


        if ($aidhcityShardDb->trans_status() === FALSE || $HcityMainDb->trans_status() === FALSE || $uidhcityShardDb->trans_status() === FALSE) {//
            $aidhcityShardDb->trans_rollback();
            $uidhcityShardDb->trans_rollback();
            $HcityMainDb->trans_rollback();
            log_message('error', __METHOD__ . json_encode($input));
            throw new Exception("订单创建出失败");
        }
        $aidhcityShardDb->trans_complete();
        $uidhcityShardDb->trans_complete();
        $HcityMainDb->trans_complete();

        //======事务完成======

        return ['tid' => $tid, 'aid'=>$input['aid']];
    }

    /**
     * 福利池订单    //TODO 只支持单商品
     * @param array $input
     * @param bool $pre
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function createWelfareOrder(array $input, $pre = false)
    {
        $hcityWelfareGoodsDao = HcityWelfareGoodsDao::i();
        $hcityWelfareGoodsSkuDao = HcityWelfareGoodsSkuDao::i();

        //加载用户信息 & 校验用户账户状态
        $this->_loadUser($input['uid']);
        if($this->user->status != 0)
        {
            throw new Exception('用户账户异常，请联系平台客服');
        }
        //加载用户信息
        $this->_loadShop($input['aid'], $input['shop_id']);

        $curr_time = time();
        $hcityWelfareGoods = $hcityWelfareGoodsDao->getOne(['id'=>$input['goods_id'],'is_delete'=>0,'welfare_status'=>1]);
        if(!$hcityWelfareGoods)
        {
            log_message('error', __METHOD__ . json_encode($input));
            throw new Exception("商品信息不存在");
        }
        if ($hcityWelfareGoods->use_end_time<$curr_time) {
            log_message('error', __METHOD__ .'商品过期'. json_encode($input));
            throw new Exception("商品已过期");
        }
        if ($hcityWelfareGoods->free_num<=0) {
            log_message('error', __METHOD__ .'商品已售罄'. json_encode($input));
            throw new Exception("商品已售罄");
        }

        $welfare_goods_sku_ids = array_column($input['items'], 'sku_id');
        $sku_list = $hcityWelfareGoodsSkuDao->getEntitysByAR(['where_in' => ['id'=>$welfare_goods_sku_ids], 'where' => ['goods_id' => $input['goods_id'], 'aid' => $input['aid']]], true);
        $sku_list = array_column($sku_list, null, 'id');

        foreach ($input['items'] as $k => $item) {
            $sku = $sku_list[$item['sku_id']];
            $input['items'][$k]['aid'] = $input['aid'];
            $input['items'][$k]['goods_id'] = $item['goods_id'];
            $input['items'][$k]['sku_id'] = $item['sku_id'];
            $input['items'][$k]['num'] = $item['num'];
            $input['items'][$k]['pay_price'] = $sku['group_price'];

            // 商圈商品/福利池 && 是否是嘿卡会员,h嘿卡会员使用嘿卡价
            if ($this->user->is_open_hcard == 1 && $this->user->hcard_expire_time > $curr_time) {
                $input['items'][$k]['pay_price'] = $sku['hcard_price'];
            }
            $input['items'][$k]['price'] = $sku['price'];
            $input['items'][$k]['group_price'] = $sku['group_price'];
            $input['items'][$k]['hcard_price'] = $sku['hcard_price'];
        }

        $tid = create_order_number();// 订单号
        $ext_total_money = 0; // 商品总额（不含运费及订单折扣）
        $cost_total_money = 0;// 成本
        $commission_money = 0;// 可分佣金额
        $ext_total_num = 0; // 商品总数
        $ext_pay_money = 0; // 支付总金额（不含运费及订单折扣）
        $ext_total_hey_money = 0; // 商品嘿卡币总额
        //获取状态
        $this->_runFsm('ORDER_CREATE');

        //构建子订单
        $order_ext = [];
        foreach ($input['items'] as $item) {
            $_ext['aid'] = $input['aid'];
            $_ext['uid'] = $this->user->id;
            $_ext['shop_id'] = $input['shop_id'];
            $_ext['tid'] = $tid;
            $_ext['goods_id'] = $item['goods_id'];
            $_ext['sku_id'] = $item['sku_id'];
            $_ext['goods_title'] = $hcityWelfareGoods->title;
            $_ext['goods_pic_url'] = $hcityWelfareGoods->pic_url;
            $_ext['price'] = $item['price'];
            $_ext['group_price'] = $item['group_price'];
            $_ext['hcard_price'] = $item['hcard_price'];
            $_ext['cost_price'] = $hcityWelfareGoods->cost_price;
            $_ext['pay_price'] = $item['pay_price'];
            $_ext['status'] = $this->fsm->state->status;
            $_ext['status_code'] = $this->fsm->state->status_code;
            $_ext['num'] = $item['num'];
            $_ext['total_money'] = bcmul($_ext['pay_price'], $_ext['num'], 2);
            $_ext['payment'] = bcmul($_ext['pay_price'], $_ext['num'], 2);
            $_ext['discount_money'] = 0;
            $_ext['use_end_time'] = $hcityWelfareGoods->use_end_time;
            $_ext['source_type'] = $input['source_type'];
            $_ext['stock_type'] = 3;
            $_ext['pay_hey_coin'] = 0;
            //判断是否福利池商品

            $_ext['pay_hey_coin'] = bcmul($item['hcard_price'], $item['num'], 2);

            array_push($order_ext, $_ext);

            $ext_total_num += $item['num'];
            $ext_total_money = bcadd($ext_total_money, $_ext['total_money'], 2);
            $ext_pay_money = bcadd($ext_pay_money, $_ext['payment'], 2);
            $ext_total_hey_money = bcadd($ext_total_hey_money, $_ext['pay_hey_coin'], 2);
        }

        //验证嘿卡币，如果为0则无法购买
        if($this->user->hey_coin <= 0)
        {
            throw new Exception("无绑定余额，无法购买");
        }
        //如果支付嘿卡币大于用户嘿卡币
        if($ext_total_hey_money>$this->user->hey_coin)
        {
            $pay_hey_coin = $this->user->hey_coin;
            $payment = bcsub($ext_total_hey_money, $pay_hey_coin, 2);
        }
        else
        {
            $pay_hey_coin = $ext_total_hey_money;
            $payment = 0;
        }
        //福利池判断支付金额
        if($payment>0 && $this->user->balance<$payment)
        {
            throw new Exception("账户余额不足，请先充值");
        }

        //限制下单金额 10万
        if($payment > 100000 || $pay_hey_coin>100000)
        {
            throw new Exception("支付金额过大");
        }
        $stockBll = new StockBll();
        //检测库存
        $stockBll->checkStock(StockBll::WELFARE_STOCK, $input['aid'], $input['goods_id'], $input['items']);

        //=========开启事务数据入库==========
        $HcityMainDb = HcityMainDb::i();
        $aidhcityShardDb = HcityShardDb::i(['aid' => $input['aid']]);
        $uidhcityShardDb = HcityShardDb::i(['uid' => $this->user->id]);
        $HcityMainDb->trans_start();
        $aidhcityShardDb->trans_start();
        $uidhcityShardDb->trans_start();


        $shcityOrderDao = ShcityOrderDao::i(['aid' => $input['aid']]);
        $shcityOrderExtDao = ShcityOrderExtDao::i(['aid' => $input['aid']]);
        $ShcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid' => $input['aid']]);

        //构建主订单
        $order['tid'] = $tid;
        $order['aid'] = $input['aid'];
        $order['uid'] = $this->user->id;
        $order['shop_id'] = $input['shop_id'];
        $order['buyer_username'] = $this->user->username;
        $order['buyer_phone'] = $this->user->mobile;
        $order['total_money'] = $ext_total_money;
        $order['total_num'] = $ext_total_num;
        $order['payment'] = $payment;
        $order['pay_hey_coin'] = $pay_hey_coin;
        $order['packet_money'] = 0;
        $order['discount_money'] = 0;
        $order['pay_type'] = $input['pay_type'];
        $order['status'] = $this->fsm->state->status;;
        $order['status_code'] = $this->fsm->state->status_code;
        $order['source_type'] = $input['source_type'];
        $order['stock_type'] = 3;
        $order['time'] = time();
        $order['use_end_time'] = $hcityWelfareGoods->use_end_time;
        $order['region'] = $this->shop->region;
        //创建主订单
        $order_id = $shcityOrderDao->create($order);

        //创建子订单 && 核销详情
        $n = 1;
        $hx_detail = [];
        $_order_ext_count = count($order_ext);
        $_total_pay_hey_coin = 0;
        $_total_payment = 0;
        $commission_money = $payment;
        foreach ($order_ext as $ext) {
            //创建子订单
            //####平摊订单优惠到每一个子订单实付金额####

            $ext['payment'] = 0;
            if($n==$_order_ext_count)
            {
                $ext['pay_hey_coin'] = bcsub($pay_hey_coin, $_total_pay_hey_coin, 2);
                //实付金额
                if($payment>0)
                {
                    $ext['payment'] = bcsub($payment, $_total_payment, 2);
                }
            }
            else
            {
                $ext['pay_hey_coin'] = bcmul($pay_hey_coin, bcdiv($ext['pay_hey_coin'], $ext_total_hey_money, 2), 2);
                $_total_pay_hey_coin = bcadd($_total_pay_hey_coin, $ext['pay_hey_coin'], 2);
                //实付金额
                if($payment>0)
                {
                    $ext['payment'] = bcmul($payment, bcdiv($ext['total_money'], $ext_total_money, 2), 2);
                    $_total_payment = bcadd($_total_payment, $ext['payment'], 2);
                }
            }
            $n++;

            $order_ext_id = $shcityOrderExtDao->create($ext);

            $ext_commission_money = 0;
            //构建核销码记录
            for ($i = 1; $i <= $ext['num']; $i++) {
                $tmp_hx_detail['tid'] = $tid;
                $tmp_hx_detail['ext_id'] = $order_ext_id;
                $tmp_hx_detail['aid'] = $input['aid'];
                $tmp_hx_detail['uid'] = $this->user->id;
                $tmp_hx_detail['hx_code'] = create_serial_number();
                $tmp_hx_detail['hx_status'] = 0;
                $tmp_hx_detail['hx_time'] = 0;
                $tmp_hx_detail['use_end_time'] = $hcityWelfareGoods->use_end_time;

                //商圈商品/福利池额外付款 结算佣金
                $tmp_hx_detail['commission_money'] = 0;
                if($commission_money>0)
                {
                    //可分佣金额>0
                    if($i==$ext['num'])
                    {
                        $tmp_hx_detail['commission_money'] = bcsub($commission_money, $ext_commission_money, 2);
                    }
                    else
                    {
                        $tmp_hx_detail['commission_money'] = bcdiv($commission_money, $ext['num'], 2);
                        $ext_commission_money = bcadd($tmp_hx_detail['commission_money'], $ext_commission_money, 2);
                    }

                }

                $hx_detail[] = $tmp_hx_detail;
            }

            //扣减库存
            $reduce_data = ['goods_id' => $ext['goods_id'], 'sku_id' => $ext['sku_id'], 'num' => $ext['num']];
            $reduce_return = $stockBll->changeStock(StockBll::WELFARE_STOCK, $input['aid'], $reduce_data);
            if (!$reduce_return) {
                $aidhcityShardDb->trans_rollback();
                $uidhcityShardDb->trans_rollback();
                $HcityMainDb->trans_rollback();
                log_message('error', __METHOD__ . json_encode($input));
                throw new Exception("库存扣减出错，订单创建出失败");
            }
        }

        //主订单快照 订单订单映射到用户订单关系表(触发器已有)


        //创建核销=待核销记录信息
        if (!empty($hx_detail))
            $ShcityOrderHxDetailDao->createBatch($hx_detail);

        if ($aidhcityShardDb->trans_status() === FALSE || $HcityMainDb->trans_status() === FALSE || $uidhcityShardDb->trans_status() === FALSE) {//
            $aidhcityShardDb->trans_rollback();
            $uidhcityShardDb->trans_rollback();
            $HcityMainDb->trans_rollback();
            log_message('error', __METHOD__ . json_encode($input));
            throw new Exception("订单创建出失败");
        }
        $aidhcityShardDb->trans_complete();
        $uidhcityShardDb->trans_complete();
        $HcityMainDb->trans_complete();

        //======事务完成======

        return ['tid' => $tid, 'aid'=>$input['aid']];
    }
    /**
     * 取消订单 (主动取消 超时取消)
     * @param array $input [aid tid]必须
     * @param string $eventCode CANCEL_ORDER 主动取消 NOTIFY_UNPAID_TIMEOUT 超时取消
     * @return bool
     * @throws Exception
     */
    public function cancelOrder(array $input, string $eventCode='CANCEL_ORDER')
    {
        // 获取相关状态码
        $curr_time = time();
        //获取状态机
        $this->_runFsm($eventCode, $input);

        //=========开启事务数据入库==========
        $HcityMainDb = HcityMainDb::i();
        $aidhcityShardDb = HcityShardDb::i(['aid' => $input['aid']]);
        $uidhcityShardDb = HcityShardDb::i(['uid' => $this->order->uid]);

        try{
            $HcityMainDb->trans_start();
            $aidhcityShardDb->trans_start();
            $uidhcityShardDb->trans_start();

            $shcityOrderDao = ShcityOrderDao::i(['aid'=>$input['aid']]);
            $shcityOrderExtDao = ShcityOrderExtDao::i(['aid'=>$input['aid']]);

            //获取子订单
            $order_ext_list = $shcityOrderExtDao->getAllArray(['aid'=>$input['aid'], 'tid'=>$input['tid']]);

            // 订单回原库存
            $stockBll = new StockBll();
            foreach($order_ext_list as $order_ext)
            {
                $restore_return =  $stockBll->changeStock($this->order->stock_type, $input['aid'], $order_ext, 'setInc');
                if (!$restore_return) {
                    $aidhcityShardDb->trans_rollback();
                    $uidhcityShardDb->trans_rollback();
                    $HcityMainDb->trans_rollback();
                    log_message('error', __METHOD__ . json_encode($input));
                    throw new Exception("库存回滚出错，订单取消失败");
                }

                // 处理砍价活动逻辑 回滚活动状态
                if($order_ext['activity_type'] == ActivityEnum::BARGAIN)
                {
                    HcityActivityBargainTurnsDao::i()->update(['status'=>0,'tid'=>'','order_create_time'=>0,'uid'=>0], ['activity_id'=>$order_ext['activity_id'], 'tid'=>$this->order->tid]);
                }
            }


            $shcityRedPacketConsumeDao = ShcityRedPacketConsumeDao::i(['uid'=>$this->order->uid]);
            $mRedPacketConsume = $shcityRedPacketConsumeDao->getOne(['uid'=>$this->order->uid, 'shop_id'=>$this->order->shop_id, 'tid'=>$input['tid']]);
            //回原红包 & 创建使用记录
            if($mRedPacketConsume)
            {
                $shcityUserRedPacketDao = ShcityUserRedPacketDao::i(['uid' => $this->order->uid]);
                $shcityCompanyRedPacketDao = ShcityCompanyRedPacketDao::i(['aid' => $input['aid']]);

                $red_packet_consume_data['uid'] = $mRedPacketConsume->uid;
                $red_packet_consume_data['shop_id'] = $mRedPacketConsume->shop_id;
                $red_packet_consume_data['tid'] = $mRedPacketConsume->tid;
                $red_packet_consume_data['red_packet_id'] = $mRedPacketConsume->red_packet_id;
                $red_packet_consume_data['time'] = $curr_time;
                $red_packet_consume_data['use_money'] = $mRedPacketConsume->use_money;
                $red_packet_consume_data['type'] = 1;
                $red_packet_consume_data['remark'] = '红包返还';
                //创建红包金额返还记录
                $shcityRedPacketConsumeDao->create($red_packet_consume_data);
                $user_red_packet_where['where'] = [
                    'red_packet_id' => $mRedPacketConsume->red_packet_id,
                    'aid' => $input['aid'],
                    'uid' => $mRedPacketConsume->uid,
                    'shop_id' => $input['shop_id']
                ];
                $shcityUserRedPacketDao->setInc('money', $mRedPacketConsume->use_money, $user_red_packet_where);
                $shcityUserRedPacketDao->setDec('used_money', $mRedPacketConsume->use_money, $user_red_packet_where);

                //回原红包
                $company_red_packet_where['where'] = [
                    'id' => $mRedPacketConsume->red_packet_id,
                    'aid' => $input['aid'],
                    'uid' => $mRedPacketConsume->uid,
                    'shop_id' => $input['shop_id']
                ];
                $shcityCompanyRedPacketDao->setInc('balance', $mRedPacketConsume->use_money, $company_red_packet_where);
                $shcityCompanyRedPacketDao->setDec('used_money', $mRedPacketConsume->use_money, $company_red_packet_where);
            }



            //变更订单状态
            $order_where = ['aid' => $input['aid'], 'tid' => $input['tid']];
            $shcityOrderDao->update(['status' => $this->fsm->state->status, 'status_code' => $this->fsm->state->status_code, 'update_time' => $curr_time], $order_where);
            $shcityOrderExtDao->update(['status' => $this->fsm->state->status, 'status_code' => $this->fsm->state->status_code], $order_where);

            if ($aidhcityShardDb->trans_status() === FALSE || $uidhcityShardDb->trans_status() === FALSE || $HcityMainDb->trans_status() === FALSE) {
                $aidhcityShardDb->trans_rollback();
                $uidhcityShardDb->trans_rollback();
                $HcityMainDb->trans_rollback();
                log_message('error', __METHOD__ . json_encode($input));
                throw new Exception("订单取消失败");
            }
            $aidhcityShardDb->trans_complete();
            $uidhcityShardDb->trans_complete();
            $HcityMainDb->trans_complete();
        }catch(\Exception $e){
            $aidhcityShardDb->trans_rollback();
            $uidhcityShardDb->trans_rollback();
            $HcityMainDb->trans_rollback();
            log_message('error', __METHOD__ . json_encode($input));
            throw new Exception("订单取消失败");
        }
        //======事务完成======
        //删除user缓存
        (new HcityUserCache(['uid'=>$this->order->uid]))->delete();

        return true;

    }
    /**
     * 核销订单
     * @param array $input aid hx_code 必须
     */
    public function hxOrder(array $input)
    {
        $shcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid'=>$input['aid']]);
        $mHxDetail = $shcityOrderHxDetailDao->getOne(['hx_code'=>$input['hx_code']]);
        if(!$mHxDetail)
        {
            log_message('error', __METHOD__.json_encode($input));
            throw new Exception("无此核销码信息");
        }
        //加载用户信息 & 校验用户账户状态
        $this->_loadUser($mHxDetail->uid);
        if($this->user->status != 0)
        {
            throw new Exception('订单用户账户异常，请联系平台客服');
        }

        $curr_time = time();
        //判断此商品时间使用截至日期
        if($mHxDetail->use_end_time < $curr_time){
            throw new Exception('此订单已过期，无法核销');
        }
        //开启事务
        $aidhcityShardDb = HcityShardDb::i(['aid' => $input['aid']]);
        $aidhcityShardDb->trans_start();

        //核销单个产品
        $detailData = [
            'hx_status' => 1,
            'hx_time' => $curr_time
        ];
        $shcityOrderHxDetailDao->update($detailData, ['id'=>$mHxDetail->id, 'aid'=>$input['aid']]);

        //判断此订单是否全部核销
        $count = $shcityOrderHxDetailDao->getCount(['tid'=> $mHxDetail->tid,'hx_status'=>0]);
        // 变更订单状态为全部核销
        if($count == 0)
            $this->fsm->isHxOver = true;

        $input['tid'] = $mHxDetail->tid;
        //获取状态机
        try{
            $this->_runFsm('HX_ORDER', $input);
        }catch(\Exception $e){
            $aidhcityShardDb->trans_rollback();
            log_message('error', __METHOD__.'核销订单状态异常：'.json_encode($input));
            throw new Exception($e->getMessage());
        }

        //更新订单状态

        ShcityOrderDao::i(['aid'=>$input['aid']])->update([
            'status' => $this->fsm->state->status,
            'status_code' => $this->fsm->state->status_code,
            'hx_time' => $curr_time
        ], ['aid'=>$input['aid'], 'tid'=>$mHxDetail->tid]);
        ShcityOrderExtDao::i(['aid'=>$input['aid']])->update([
            'status' => $this->fsm->state->status,
            'status_code' => $this->fsm->state->status_code,
        ], ['aid'=>$input['aid'], 'tid'=>$mHxDetail->tid]);

        //更新用户订单状态 触发器已有


        if ($aidhcityShardDb->trans_status()===false) {
            $aidhcityShardDb->trans_rollback();
            throw new Exception('核销失败');
        }

        $aidhcityShardDb->trans_complete();

        //发送分佣任务
        try {
            (new \Service\Bll\Hcity\FinanceBll())->hxOrder($this->order->tid, $input['hx_code']);

        } catch (\Exception $e) {
            log_message('error', __METHOD__ . '确认分佣任务失败:' . $e->getMessage());
        }

        try {
            //发放骑士奖励
            (new \Service\Bll\Hcity\FinanceBll())->knightReward($this->order->shop_id);
        } catch (\Exception $e) {
            log_message('error', __METHOD__ . '发放骑士奖励失败:' . $e->getMessage());
        }

        //删除user缓存
        (new HcityUserCache(['uid'=>$this->order->uid]))->delete();

        return true;
    }

    /**
     * 通知订单支付成功
     * @param array $input aid tid 必须
     */
    public function notifyPaySuccessOrder(array $input)
    {
        $curr_time = time();
        //获取状态机
        $this->_runFsm('NOTIFY_PAY_SUCCESS', $input);

        //=========开启事务数据入库==========
        $HcityMainDb = HcityMainDb::i();
        $aidhcityShardDb = HcityShardDb::i(['aid' => $input['aid']]);
        $HcityMainDb->trans_start();
        $aidhcityShardDb->trans_start();

        $shcityOrderDao = ShcityOrderDao::i(['aid'=>$input['aid']]);
        $shcityOrderExtDao = ShcityOrderExtDao::i(['aid'=>$input['aid']]);
        //获取子订单
        $order_ext_list = $shcityOrderExtDao->getAllArray(['aid'=>$input['aid'], 'tid'=>$input['tid']]);

        // 更新销量 && 组合佣金数据
        // TODO 因目前订单只有一个子订单，增加数据只取了第一条子订单数据，销量增加失败目前不做处理
        $order_ext = $order_ext_list[0];
        $stockBll = new StockBll();
        $stockBll->addSalesNum($this->order->stock_type, $input['aid'], $order_ext);


        //变更订单状态
        $order_where = ['aid' => $input['aid'], 'tid' => $input['tid']];
        $shcityOrderDao->update(['status' => $this->fsm->state->status, 'status_code' => $this->fsm->state->status_code, 'pay_time'=>$curr_time, 'update_time' => $curr_time], $order_where);
        $shcityOrderExtDao->update(['status' => $this->fsm->state->status, 'status_code' => $this->fsm->state->status_code, 'pay_time'=>$curr_time], $order_where);

        //累计会员消费总额
        $user_where['where'] = [
            'id' => $this->order->uid
        ];
        HcityUserDao::i()->setInc('consumption', $this->order->payment, $user_where);

        //保存消费余额流水
        $recordCreateData = [
            'fid' => $this->order->tid,
            'money' => $this->order->payment,
            'type' => 6,
            'time' => time(),
            'uid' => $this->order->uid,
            'remark' => '购买商品消费',
            'lower_uid' => 0,
            'lower_shop_id' => 0
        ];
        HcityUserBalanceRecordDao::i()->create($recordCreateData);


        // 处理砍价活动逻辑 回滚活动状态
        if($order_ext['activity_type'] == ActivityEnum::BARGAIN)
        {
            HcityActivityBargainTurnsDao::i()->update(['status'=>2], ['activity_id'=>$order_ext['activity_id'], 'tid'=>$this->order->tid]);
        }

        if ($aidhcityShardDb->trans_status() === FALSE  || $HcityMainDb->trans_status() === FALSE) {
            $aidhcityShardDb->trans_rollback();
            $HcityMainDb->trans_rollback();
            log_message('error', __METHOD__ . json_encode($input));
            throw new Exception("订单支付成功通知失败");
        }
        $aidhcityShardDb->trans_complete();
        $HcityMainDb->trans_complete();

        //======事务完成======

        //======发送分佣任务start========
        $this->_sendCommission();

        //======发送分佣任务end========

        // 小程序消息通知
        try{
            //通知用户
            //$keys = ['goods_name','shop_name','expire_time','buy_price','buy_time','trade_no','page_params'];
            $this->_loadShop($this->order->aid, $this->order->shop_id);
            $userWxbind = HcityUserWxbindDao::i()->getOne(['uid'=>$this->order->uid]);
            $params = [
                'openid' => $userWxbind->open_id,
                'goods_name' => $order_ext['goods_title'],
                'shop_name' => $this->shop->shop_name,
                'expire_time' => date('Y-m-d', $this->order->use_end_time-3600*24),
                'buy_price' => $order_ext['pay_price'],
                'buy_time' => date('Y-m-d H:i:s', $curr_time),
                'trade_no' => $this->order->tid,
                'page_params'=>['tid'=>$this->order->tid]
            ];
            (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::BUY_SUCCESS);

            //通知商家
            $shopUser = HcityUserDao::i()->getOne(['aid'=>$this->order->aid]);
            if($shopUser)
            {
                //$keys = ['trade_no','order_time','goods_name','order_money'];
                $shopUserWxbind = HcityUserWxbindDao::i()->getOne(['uid'=>$shopUser->id]);
                $params = [
                    'openid' => $shopUserWxbind->open_id,
                    'order_time' => date('Y-m-d H:i:s', $curr_time),
                    'goods_name' => $order_ext['goods_title'],
                    'order_money' => $this->order->payment,
                    'trade_no' => $this->order->tid,
                    'page_params'=>['tid'=>$this->order->tid]
                ];
                (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::SELL_SUCCESS);
            }

        }catch(\Exception $e){
            log_message('error', __METHOD__.'通知失败:'.$e->getMessage());
        }

        try{
            //保存门店粉丝
            (new ShopFansBll())->addOrUpdateShopFans($this->order->uid, $this->order->aid, $this->order->shop_id);
        }catch(\Exception $e){
            log_message('error', __METHOD__.'增加门店粉丝失败:'.$e->getMessage());
        }
        //删除user缓存
        (new HcityUserCache(['uid'=>$this->order->uid]))->delete();

        return true;
    }

    /**
     * 通知订单未使用过期
     * @param array $input aid tid 必须
     */
    public function notifyExpiredOrder(array $input)
    {
        $curr_time = time();
        //获取状态机
        $this->_runFsm('NOTIFY_EXPIRED_ORDER', $input);

        //=========开启事务数据入库==========
        $HcityMainDb = HcityMainDb::i();
        $aidhcityShardDb = HcityShardDb::i(['aid' => $input['aid']]);
        $uidhcityShardDb = HcityShardDb::i(['uid' => $this->order->uid]);

        try{
            $HcityMainDb->trans_start();
            $aidhcityShardDb->trans_start();
            $uidhcityShardDb->trans_start();


            $shcityOrderDao = ShcityOrderDao::i(['aid'=>$input['aid']]);
            $shcityOrderExtDao = ShcityOrderExtDao::i(['aid'=>$input['aid']]);
            //变更订单状态
            $order_where = ['aid' => $input['aid'], 'tid' => $input['tid']];


            //处理退款(非福利池商品)
            if($this->order->source_type != 3)
            {
                $shcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid'=>$input['aid']]);
                $hx_list = $shcityOrderHxDetailDao->getAllArray($order_where);

                $refund_packet_money = 0;
                $refund_total_money = 0;
                $average_packet_money = 0;
                $average_goods_money = 0;
                //获取平均分摊红包金额
                if($this->order->packet_money > 0)
                {
                    $average_packet_money = bcdiv($this->order->packet_money, $this->order->total_num, 2);
                }
                //获取平均实际支付金额
                if($this->order->payment > 0)
                {
                    $average_goods_money = bcdiv($this->order->payment, $this->order->total_num, 2);
                }
                //统计需退款红包和金额
                foreach($hx_list as $hx_detail)
                {
                    if($hx_detail['hx_status']==0)
                    {
                        $refund_packet_money = bcadd($average_packet_money, $refund_packet_money, 2);
                        $refund_total_money = bcadd($average_goods_money, $refund_total_money, 2);
                    }
                }
                //退还红包
                if($refund_packet_money>0)
                {
                    $shcityUserRedPacketDao = ShcityUserRedPacketDao::i(['uid' => $this->order->uid]);
                    $shcityCompanyRedPacketDao = ShcityCompanyRedPacketDao::i(['aid' => $input['aid']]);
                    $shcityRedPacketConsumeDao = ShcityRedPacketConsumeDao::i(['uid'=>$this->order->uid]);
                    $mRedPacketConsume = $shcityRedPacketConsumeDao->getOne(['uid'=>$this->order->uid, 'shop_id'=>$this->order->shop_id, 'tid'=>$input['tid']]);

                    $red_packet_consume_data['uid'] = $this->order->uid;
                    $red_packet_consume_data['shop_id'] = $this->order->shop_id;
                    $red_packet_consume_data['tid'] = $this->order->tid;
                    $red_packet_consume_data['red_packet_id'] = $mRedPacketConsume->red_packet_id;
                    $red_packet_consume_data['time'] = $curr_time;
                    $red_packet_consume_data['use_money'] = $refund_packet_money;
                    $red_packet_consume_data['type'] = 1;
                    $red_packet_consume_data['remark'] = '红包返还';
                    //创建红包金额返还记录
                    $shcityRedPacketConsumeDao->create($red_packet_consume_data);
                    $user_red_packet_where['where'] = [
                        'red_packet_id' => $mRedPacketConsume->red_packet_id,
                        'aid' => $input['aid'],
                        'uid' => $mRedPacketConsume->uid,
                        'shop_id' => $this->order->shop_id
                    ];
                    $shcityUserRedPacketDao->setInc('money', $refund_packet_money, $user_red_packet_where);
                    $shcityUserRedPacketDao->setDec('used_money', $refund_packet_money, $user_red_packet_where);

                    //回原红包
                    $company_red_packet_where['where'] = [
                        'id' => $mRedPacketConsume->red_packet_id,
                        'aid' => $input['aid'],
                        'uid' => $mRedPacketConsume->uid,
                        'shop_id' => $this->order->shop_id
                    ];
                    $shcityCompanyRedPacketDao->setInc('balance', $refund_packet_money, $company_red_packet_where);
                    $shcityCompanyRedPacketDao->setDec('used_money', $refund_packet_money, $company_red_packet_where);
                }

                //返还余额
                if($refund_total_money>0)
                {
                    $user_where['where'] = [
                        'id' => $this->order->uid
                    ];

                    HcityUserDao::i()->setInc('balance', $refund_total_money, $user_where);
                    //消费总额减少
                    HcityUserDao::i()->setDec('consumption', $refund_total_money, $user_where);
                }

            }

            $shcityOrderDao->update(['status' => $this->fsm->state->status, 'status_code' => $this->fsm->state->status_code, 'update_time' => $curr_time], $order_where);
            $shcityOrderExtDao->update(['status' => $this->fsm->state->status, 'status_code' => $this->fsm->state->status_code], $order_where);

            if ($aidhcityShardDb->trans_status() === FALSE || $uidhcityShardDb->trans_status() === FALSE || $HcityMainDb->trans_status() === FALSE) {//
                $aidhcityShardDb->trans_rollback();
                $uidhcityShardDb->trans_rollback();
                $HcityMainDb->trans_rollback();
                log_message('error', __METHOD__ . json_encode($input));
                throw new Exception("订单取消失败");
            }

            $aidhcityShardDb->trans_complete();
            $uidhcityShardDb->trans_complete();
            $HcityMainDb->trans_complete();

            //取消分佣
            try{
                (new \Service\Bll\Hcity\FinanceBll())->hxCancel( $this->order->tid);
            }catch(\Exception $e){
                log_message('error', __METHOD__.'取消分佣任务失败:'.$e->getMessage());
            }
        }catch(\Exception $e){
            $aidhcityShardDb->trans_rollback();
            $uidhcityShardDb->trans_rollback();
            $HcityMainDb->trans_rollback();
            log_message('error', __METHOD__ . json_encode($input));
            throw new Exception("订单取消失败");
        }

        //======事务完成======

        //删除user缓存
        (new HcityUserCache(['uid'=>$this->order->uid]))->delete();
        // 小程序消息通知
        try{
            $this->_loadUser($this->order->uid);
            if(isset($refund_total_money) && $refund_total_money>0)
            {
                $userWxbind = HcityUserWxbindDao::i()->getOne(['uid'=>$this->order->uid]);
                $params = [
                    'openid'=> $userWxbind->open_id,
                    'type'=> '退款到账',
                    'change_money'=>'+'.$refund_total_money,
                    'change_time'=>time(),
                    'current_money'=>bcadd($this->user->balance, $refund_total_money, 2)
                ];

                (new XcxBll())->pushMessageFac($params, XcxTemplateMessageEnum::MONEY_CHANGE);
            }

        }catch(\Exception $e){
            log_message('error', __METHOD__.'通知失败:'.$e->getMessage());
        }


        return true;
    }


    /**
     * 发送分佣任务
     * @user liusha
     * @date 2018/9/4 11:50
     */
    private function _sendCommission()
    {
        $financeBll = new \Service\Bll\Hcity\FinanceBll();

        //单个商品成本 平均实际支付价格 平均优惠
        $shcityOrderExtDao = ShcityOrderExtDao::i(['aid'=>$this->order->aid]);
        //获取子订单
        $order_ext = $shcityOrderExtDao->getOneArray(['aid' => $this->order->aid, 'tid' => $this->order->tid]);
        $average_goods_money = bcdiv($this->order->payment, $this->order->total_num, 2);
        $average_discount_money = bcdiv($this->order->discount_money, $this->order->total_num, 2);
        $commission_data = [];
        $shcityOrderHxDetailDao = ShcityOrderHxDetailDao::i(['aid'=>$this->order->aid]);
        $hx_list = $shcityOrderHxDetailDao->getAllArray(['aid' => $this->order->aid, 'tid' => $this->order->tid]);
        foreach($hx_list as $hx_detail)
        {
            // 商圈
            if($this->order->stock_type==1)
            {
                $tmp_data['hx_code'] = $hx_detail['hx_code'];
                $tmp_data['pay_money'] = $average_goods_money;
                $tmp_data['cost_money'] = $order_ext['cost_price'];
                $tmp_data['discount_money'] = $average_discount_money;
            }
            //一店一码 | 一店一码助力集赞 | YDYM砍价活动
            elseif($this->order->stock_type==2 || ($this->order->source_type==2 && $this->order->stock_type==4) ||  ($this->order->source_type==2 && $this->order->stock_type==5))
            {
                $tmp_data['hx_code'] = $hx_detail['hx_code'];
                $tmp_data['pay_money'] = $average_goods_money;
            }
            // 福利池
            elseif($this->order->stock_type==3)
            {
                $tmp_data['hx_code'] = $hx_detail['hx_code'];
                $tmp_data['over_money'] = $average_goods_money;
            }
            // 商圈的助力集赞
            elseif($this->order->source_type==1 && $this->order->stock_type==4)
            {
                $tmp_data['hx_code'] = $hx_detail['hx_code'];
                $tmp_data['fy_money'] = $hx_detail['commission_money'];
                $tmp_data['js_money'] = bcsub($order_ext['pay_price'], $hx_detail['commission_money'], 2);
            }

            if(!empty($tmp_data)) $commission_data[] = $tmp_data;
        }

        try{
            if(!empty($commission_data))
            {
                // 商圈
                if($this->order->stock_type==1)
                {
                    $financeBll->hcityGoodsTrade($this->order->uid, $this->order->shop_id, $this->order->tid, $commission_data, $this->order->source_type);
                }
                //一店一码 | 一店一码助力集赞 | YDYM砍价活动
                elseif($this->order->stock_type==2 || ($this->order->source_type==2 && $this->order->stock_type==4) || ($this->order->source_type==2 && $this->order->stock_type==5))
                {
                    $financeBll->goodsTrade($this->order->shop_id, $this->order->tid, $commission_data);
                }
                // 福利池
                elseif($this->order->stock_type==3)
                {
                    $financeBll->welfareTrade($this->order->uid, $this->order->shop_id, $this->order->tid, $commission_data);
                }
                // 商圈的助力集赞
                elseif($this->order->stock_type==4 && $this->order->source_type==1)
                {
                    $financeBll->activityGoodsTrade($this->order->uid, $this->order->shop_id, $this->order->tid, $commission_data, $this->order->source_type);
                }
                else
                    log_message('error', __METHOD__.'其他');
            }

        }catch(\Exception $e){
            log_message('error', __METHOD__.'发送分佣任务失败:'.$e->getMessage());
        }
    }
    /**
     * 校验活动
     * @param $input
     * @param int $activity_type 活动类型
     * @param int $activity_id  活动ID
     * @param int $stock_type  扣减库存类型
     * @return object|void
     * @throws Exception
     * @user liusha
     * @date 2018/9/3 17:31
     */
    private function _activityValid($input, int &$activity_type, int &$activity_id, int &$stock_type)
    {
        switch($activity_type)
        {
            case ActivityEnum::JZ:// 集赞活动
                return $this->_validJz($input,  $activity_type,  $activity_id,  $stock_type);
                break;
            case ActivityEnum::BARGAIN: // 砍价活动
                return $this->_validBargain($input,  $activity_type,  $activity_id,  $stock_type);
                break;
            default:
                throw new Exception("异常活动类型");
                break;
        }
    }

    /**
     * 校验集赞活动
     * @param $input 必要数据
     * @param int $activity_type 活动类型
     * @param int $activity_id  活动ID
     * @param int $stock_type  扣减库存类型
     * @return object
     * @throws Exception
     * @user liusha
     * @date 2018/9/3 17:29
     */
    private function _validJz($input, int &$activity_type, int &$activity_id, int &$stock_type)
    {
        $jz_where = [
            'id' => $input['activity_id'],
            'aid' => $input['aid'],
            'goods_id' => $input['goods_id'],
            'start_time <=' => $input['curr_time'],
            'end_time >' => $input['curr_time'],
            'status' => 1,
            'source_type'=>$input['source_type']
        ];
        // 判断活动信息
        $hcityActivityGoodsJzDao = HcityActivityGoodsJzDao::i();
        $activityGoodsJz = $hcityActivityGoodsJzDao->getOne($jz_where);
        if(!$activityGoodsJz)
        {
            log_message('error', __METHOD__ .'助力活动：'. json_encode($input));
            throw new Exception("活动不存在或已结束");
        }
        // 库存判断
        if($activityGoodsJz->stock_num <= 0)
        {
            log_message('error', __METHOD__ .'助力活动库存：'. json_encode($input));
            throw new Exception("活动商品已售罄");
        }

        // 助力集赞资格判断
        $hcityGoodsJzJoinDao = HcityGoodsJzJoinDao::i();
        $hcityGoodsJzJoin = $hcityGoodsJzJoinDao->getOne(['uid'=>$this->user->id,'activity_id'=>$input['activity_id']]);
        if(!$hcityGoodsJzJoin || $hcityGoodsJzJoin->status<=1)
        {
            throw new Exception("未取得活动购买资格");
        }
        if($hcityGoodsJzJoin->status==3)
        {
            throw new Exception("活动商品限购一份");
        }

        $stock_type = StockBll::JZ_STOCK;
        $activity_type = ActivityEnum::JZ;
        $activity_id = $input['activity_id'];

        return $activityGoodsJz;
    }

    /**
     * 校验砍价活动
     * @param $input 必要数据
     * @param int $activity_type 活动类型
     * @param int $activity_id  活动ID
     * @param int $stock_type  扣减库存类型
     * @return object
     * @throws Exception
     * @user liusha
     * @date 2018/9/3 17:52
     */
    private function _validBargain($input, int &$activity_type, int &$activity_id, int &$stock_type)
    {
        if (!FLock::getInstance()->lock('Bargain:do_bargain:' . $this->user->id)) { // TODO 键名与砍价同用，请注意
            throw new Exception("系统繁忙，请重试");
        }
        $bargain_where = [
            'id' => $input['activity_id'],
            'aid' => $input['aid'],
            'goods_id' => $input['goods_id'],
            'start_time <=' => $input['curr_time'],
            'end_time >' => $input['curr_time'],
            'status' => 1,
            'source_type' => $input['source_type']
        ];
        $hcityActivityBargainDao = HcityActivityBargainDao::i();
        $activityBargain = $hcityActivityBargainDao->getOne($bargain_where);
        if (!$activityBargain) {
            log_message('error', __METHOD__ . '砍价活动：' . json_encode($input));
            throw new Exception("当前活动无效");
        }
        // 库存判断
        if ($activityBargain->stock_num <= 0) {
            log_message('error', __METHOD__ . '砍价活动stock：' . json_encode($input));
            throw new Exception("活动商品已售罄");
        }
        // 校验资格
        $HcityActivityBargainTurnsDao = HcityActivityBargainTurnsDao::i();
        $activityBargainTurns = $HcityActivityBargainTurnsDao->getOne(['activity_id' => $activityBargain->id, 'turns_id' => $activityBargain->current_turns_id]);
        if (!$activityBargainTurns)
        {
            log_message('error', __METHOD__ . '砍价活动turns不存在：' . json_encode($input));
            throw new Exception("活动信息异常");
        }
        if($activityBargain->status>0)
        {
            log_message('error', __METHOD__ . '砍价活动无资格：' . json_encode($input));
            throw new Exception("当天活动商品已售罄");
        }

        $stock_type = StockBll::BARGAIN_STOCK;
        $activity_type = ActivityEnum::BARGAIN;
        $activity_id = $input['activity_id'];

        return $activityBargainTurns;
    }

    /**
     * 加载用户信息
     * @param int $uid
     * @throws Exception
     */
    private function _loadUser(int $uid)
    {
        $mHcityUser = HcityUserDao::i()->getOne(['id' => $uid]);
        if(!$mHcityUser)
            throw new \Exception('用户信息获取失败');
        $this->user = $mHcityUser;
    }

    /**
     * 加载主订单信息
     * @param int $aid
     * @param int $tid
     * @throws Exception
     */
    private function _loadOrder(int $aid, int $tid)
    {
        $shcityOrderDao = ShcityOrderDao::i(['aid'=>$aid]);
        $order = $shcityOrderDao->getOne(['aid'=>$aid, 'tid'=>$tid]);
        if(!$order)
        {
            log_message('error', __METHOD__."--tid:{$tid}");
            throw new Exception("订单信息不存在");
        }
        $this->order = $order;
    }
    /**
     * 加载主店铺信息
     * @param int $aid
     * @param int $tid
     * @throws Exception
     */
    private function _loadShop(int $aid, int $shop_id)
    {
        $mainShopDao = MainShopDao::i();
        $shop = $mainShopDao->getOne(['aid'=>$aid, 'id'=>$shop_id]);
        if(!$shop)
        {
            log_message('error', __METHOD__."--shop_id:{$shop_id}");
            throw new Exception("店铺信息不存在");
        }
        $this->shop = $shop;
    }

}
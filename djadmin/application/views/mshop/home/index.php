<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>概况 - 挖到后台</title>
  <?php $this->load->view('inc/global_header');?>
  <?=static_original_url('djadmin/css/calendar.min.css');?>
  <?=static_original_url('djadmin/mshop/css/home.min.css');?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <div class="home-body">
        <div class="home-body-main">
          <div class="form-inline mb20">
            <div class="form-group">
              <select id="shop" class="form-control" name="shop_id">
                <?php if($this->is_zongbu): ?>
                  <option value="">全部门店</option>
                <?php endif; ?>
                <?php if(!empty($shop_list)):?>
                  <?php foreach($shop_list as $shop):?>
                    <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                  <?php endforeach;?>
                <?php endif;?>
              </select>
            </div>
          </div>
          <div class="home-module">
            <div class="row home-data-list">
              <div class="col-sm-4 col-md-4 col-lg-4 home-data-item">
                <strong id="pay_order_money" class="home-data-num">--</strong>
                <p class="home-data-label">今日营业额</p>
              </div>
              <div class="col-sm-4 col-md-4 col-lg-4 home-data-item">
                <strong id="pay_order_count" class="home-data-num">--</strong>
                <p class="home-data-label">今日付款订单</p>
              </div>
              <div class="col-sm-4 col-md-4 col-lg-4 home-data-item">
                <strong id="average_order_price" class="home-data-num">--</strong>
                <p class="home-data-label">今日客单价</p>
              </div>
              <!-- <div class="home-data-item">
                <strong class="home-data-num">120</strong>
                <p class="home-data-label">昨日交易额</p>
              </div>
              <div class="home-data-item">
                <strong class="home-data-num">120.00</strong>
                <p class="home-data-label">会员储值金额</p>
              </div> -->
            </div>
          </div>
          <div class="home-module">
            <div class="row">
              <?php if( power_exists(module_enum::WM_MODULE,$this->service->power_keys)):?>
              <div class="col-sm-4 col-md-4 col-lg-4">
                <h3 class="home-module-title">外卖（今日）</h3>
                <div class="home-order-item">
                  <span class="pull-left">待接单</span>
                  <a class="pull-right" href="javascript:;"><span id="wm_new_order_count">--</span>个订单</a>
                </div>
                <div class="home-order-item">
                  <span class="pull-left">待自取</span>
                  <a class="pull-right" href="javascript:;"><span id="wm_selfpick_order_count">--</span>个订单</a>
                </div>
                <div class="home-order-item">
                  <span class="pull-left">待配送及配送中</span>
                  <a class="pull-right" href="javascript:;"><span id="wm_delivery_order_count">--</span>个订单</a>
                </div>
              </div>
              <?php endif;?>
              <?php if( power_exists(module_enum::MEAL_MODULE,$this->service->power_keys)):?>
              <div class="col-sm-4 col-md-4 col-lg-4">
                <h3 class="home-module-title">堂食点餐（今日）</h3>
                <div class="home-order-item">
                  <span class="pull-left">待付款</span>
                  <a class="pull-right" href="javascript:;"><span id="meal_cooked_order_money">--</span>个订单</a>
                </div>
                <div class="home-order-item">
                  <span class="pull-left">待审核</span>
                  <a class="pull-right" href="javascript:;"><span id="meal_audit_order_count">--</span>个订单</a>
                </div>
                <div class="home-order-item">
                  <span class="pull-left">已支付</span>
                  <a class="pull-right" href="javascript:;"><span id="meal_done_order_count">--</span>个订单</a>
                </div>
              </div>
              <?php endif;?>
              <?php if( power_exists(module_enum::LS_MODULE,$this->service->power_keys)):?>
              <div class="col-sm-4 col-md-4 col-lg-4">
                <h3 class="home-module-title">门店零售（今日）</h3>
                <div class="home-order-item">
                  <span class="pull-left">支付流水</span>
                  <a class="pull-right" href="javascript:;"><span id="ls_pay_order_count">--</span>笔</a>
                </div>
              </div>
              <?php endif;?>
            </div>
          </div>
          <!-- <div class="home-module">
            <div class="row">
              <div class="col-sm-4 col-md-4 col-lg-4">
                <h3 class="home-module-title home-msg-title">会员</h3>
                <ul class="home-msg-list home-msg-member">
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>总会员数
                    <span class="pull-right">3252</span>
                  </li>
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>昨日新增
                    <span class="pull-right">3252</span>
                  </li>
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>昨日活跃
                    <span class="pull-right">3252</span>
                  </li>
                </ul>
              </div>
              <div class="col-sm-4 col-md-4 col-lg-4">
                <h3 class="home-module-title home-msg-title">消息</h3>
                <ul class="home-msg-list home-msg-msg">
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>昨日短信群发
                    <span class="pull-right">3252</span>
                  </li>
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>昨日互动群发
                    <span class="pull-right">3252</span>
                  </li>
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>昨日回复
                    <span class="pull-right">3252</span>
                  </li>
                </ul>
              </div>
              <div class="col-sm-4 col-md-4 col-lg-4">
                <h3 class="home-module-title home-msg-title">互动</h3>
                <ul class="home-msg-list home-msg-hd">
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>近7日客转粉数
                    <span class="pull-right">3252</span>
                  </li>
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>近7日会员成交
                    <span class="pull-right">￥3252</span>
                  </li>
                  <li class="home-msg-item">
                    <span class="iconfont icon-home"></span>会员复购率
                    <span class="pull-right">32%</span>
                  </li>
                </ul>
              </div>
            </div>
          </div> -->
          <div class="row">
            <div class="col-sm-4 col-md-4 col-lg-4">
              <div id="calendar" class="home-module calendar" style="height: 280px;"></div>
            </div>
            <div class="col-sm-8 col-md-8 col-lg-8">
              <div class="home-module" style="height: 280px;">
                <h3 class="home-module-title">营销待办</h3>
                <div class="row">
                  <div class="col-sm-6 col-md-6 col-lg-6">
                    <div class="home-coupon-item">
                      <img class="home-coupon-pic" src="<?=STATIC_URL?>djadmin/mshop/img/ing_promotion.png" alt="">
                      <p class="home-coupon-label">进行中</p>
                      <p id="ing_promotion_count" class="home-coupon-num" style="color: #5CB3FF;">--</p>
                    </div>
                  </div>
                  <div class="col-sm-6 col-md-6 col-lg-6">
                    <div class="home-coupon-item">
                      <img class="home-coupon-pic" src="<?=STATIC_URL?>djadmin/mshop/img/not_start_promotion.png" alt="">
                      <p class="home-coupon-label">待上线</p>
                      <p id="not_start_promotion_count" class="home-coupon-num" style="color: #49CC93;">--</p>
                    </div>
                  </div>
                </div>
                <div class="text-right mt20">
                  <a class="btn btn-primary" href="<?=DJADMIN_URL?>mshop/promotion/discount_list">查看营销活动</a>
                </div>
              </div>
            </div>
          </div>
          <!-- <div class="home-module">
            <h3 class="home-module-title">常用功能</h3>
            <div class="home-func-list">
              <a class="home-func-item" href="javascript:;"><span class="iconfont icon-home"></span>新建商品</a>
              <a class="home-func-item" href="javascript:;"><span class="iconfont icon-home"></span>新建优惠券</a>
              <a class="home-func-item" href="javascript:;"><span class="iconfont icon-home"></span>设置满减</a>
              <a class="home-func-item" href="javascript:;"><span class="iconfont icon-home"></span>堂食点餐</a>
              <a class="home-func-item" href="javascript:;"><span class="iconfont icon-home"></span>会员卡</a>
              <a class="home-func-item" href="javascript:;"><span class="iconfont icon-home"></span>红包管理</a>
            </div>
          </div>
          <div class="home-module">
            <h3 class="home-module-title">更多服务</h3>
            <div class="row">
              <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="home-service-item home-service-erp" href="javascript:;">
                  <p class="home-service-name">ERP</p>
                  <p class="home-service-desc">移动办公软件</p>
                </a>
              </div>
              <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="home-service-item home-service-sy" href="javascript:;">
                  <p class="home-service-name">智能收银</p>
                  <p class="home-service-desc">移动办公软件</p>
                </a>
              </div>
              <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="home-service-item home-service-xcx" href="javascript:;">
                  <p class="home-service-name">小程序</p>
                  <p class="home-service-desc">移动办公软件</p>
                </a>
              </div>
              <div class="col-sm-3 col-md-3 col-lg-3">
                <a class="home-service-item home-service-more" href="javascript:;">
                  <p class="home-service-name">更多</p>
                  <p class="home-service-desc">移动办公软件</p>
                </a>
              </div>
            </div>
          </div> -->
        </div>
        <div class="home-body-right">
          <div class="home-module">
            <div class="user-info">
              <?php if($this->is_zongbu):?>
                <img class="user-avatar" src="<?php if(!empty($model->img)):?> <?=$model->img?><?php else:?><?=STATIC_URL?>djadmin/img/avatar.jpg<?php endif;?>" alt="<?=$model->username?>">
                <p class="user-nick"><?=$model->username?></p>
              <?php else:?>
                <img class="user-avatar" src="<?php if(isset($shop_model->shop_logo)):?> <?=$shop_model->shop_logo?><?php else:?><?=STATIC_URL?>djadmin/img/avatar.jpg<?php endif;?>" alt="<?=$shop_model->shop_name?>">
                <p class="user-nick"><?=$shop_model->shop_name?></p>
              <?php endif;?>
              <p class="user-desc">到期时间：<span><?=$service_model->expire_time?></span></p>
              <span class="label label-success user-label"><?=$service_model->item_name?></span>
            </div>
            <!-- <div class="user-account">
              <div class="clearfix">红包余额 <span class="text-primary">999</span>元 <a class="pull-right text-primary" href="javascript:;">发活动</a></div>
              <div class="clearfix">短信余量 <span class="text-primary">999</span>元 <a class="pull-right text-primary" href="javascript:;">发短信</a></div>
            </div> -->
          </div>
          <!-- <div class="home-module">
            <div class="row">
              <div class="col-sm-6 col-md-6 col-lg-6">
                <div class="home-action-item">
                  <span class="iconfont icon-home"></span>
                  <p class="home-action-label">未连接打印机</p>
                </div>
              </div>
              <div class="col-sm-6 col-md-6 col-lg-6">
                <div class="home-action-item">
                  <span class="iconfont icon-home"></span>
                  <p class="home-action-label">未自动接单</p>
                </div>
              </div>
            </div>
          </div> -->
          <div class="home-module">
            <div class="user-info">
              <img class="kf-avatar" src="<?=STATIC_URL?>djadmin/img/ydb-kf.png" alt="">
              <a class="label user-label" style="color: #5CB3FF;" href="http://wpa.qq.com/msgrd?v=3&amp;uin=2494760072&amp;site=qq&amp;menu=yes" target="_blank">在线客服</a>
              <p class="user-desc">客户经理：<span style="color: #1F2D3D;">樱桃</span></p>
              <p class="user-desc">电话客服：<span style="color: #1F2D3D;">0571-28121938</span></p>
            </div>
          </div>
          <div class="home-module">
            <h3 class="home-module-title">公告<a class="pull-right" href="<?=DJADMIN_URL?>mshop/article">更多&gt;</a></h3>
            <div id="noticeCon" style="padding-bottom: 20px;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <script id="noticeTpl" type="text/html">
    <% if(rows.length > 0) { %>
      <ul class="m-article-list">
        <% for(var i = 0, l = rows.length; i < l; i++) { %>
          <li class="m-article-item has-time">
            <a href="<?=DJADMIN_URL?>mshop/article/detail/<%:=rows[i].id%>"><%:=i+1%>、<%:=rows[i].title%> <span class="m-article-time"><%:=rows[i].time%></span></a>
          </li>
        <% } %>
      </ul>
    <% } else { %>
      <div class="m-empty-box">
        <p>暂无公告消息</p>
      </div>
    <% } %>
  </script>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('djadmin/js/calendar.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/home.min.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>云店宝 - 商家后台</title>
  <?php $this->load->view('inc/global_header'); ?>
  <script>
    function GetQueryString(name) {
      var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"),
        r = window.location.search.substr(1).match(reg);

      if (r != null) {
        return unescape(r[2]);
      }

      return null;
    }

    var spread_visit_id  = '1';
    var spread_app_id = '4';
    var spread_company_id = GetQueryString('visit_id') || '';
  </script>
  <script async src='//acrm.ecbao.cn/assert/spread_reg.js'></script>
</head>
<body class="has-header has-aside">
  <header id="w-header" class="w-header">
    <nav class="navbar navbar-inverse">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar" aria-expanded="false">
            <span class="sr-only">切换导航</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="<?=SITE_URL?>">
            <img src="<?=STATIC_URL?>djadmin/img/logo.png" alt="云店宝">
          </a>
        </div>
        <div class="collapse navbar-collapse" id="main-navbar">
          <ul class="nav navbar-nav">
            <li class="J_NAVBAR_NAV_ITEM active"><a data-type="index" href="<?=DJADMIN_URL?>mshop/home"><span class="iconfont icon-cart"></span>微商城</a></li>
            <?php if($this->is_zongbu):?>
              <li class="J_NAVBAR_NAV_ITEM"><a data-type="market" href="<?=DJADMIN_URL?>scrm/home"><span class="iconfont icon-zhishihudong"></span>互动营销</a></li>
            <?php endif;?>
            <li class="J_NAVBAR_NAV_ITEM"><a data-type="cashier" href="<?=DJADMIN_URL?>mshop/content/cashier"><span class="iconfont icon-shouyintai"></span>门店收银</a></li>
            <li class="J_NAVBAR_NAV_ITEM"><a data-type="hardware" href="<?=DJADMIN_URL?>mshop/content/hardware"><span class="iconfont icon-yingjian"></span>智能硬件</a></li>
            <li class="J_NAVBAR_NAV_ITEM"><a data-type="game" href="<?=DJADMIN_URL?>hd/home"><span class="iconfont icon-xiaoyouxi"></span>互动游戏</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li class="dropdown">
              <a href="javascript:;">
                <span class="iconfont icon-service"></span>客户服务
              </a>
              <div class="dropdown-content kf-content">
                <h2 class="kf-role">专属VIP客户经理<br>樱桃</h2>
                <h3 class="kf-title">在线咨询</h3>
                <p class="kf-tip">热线电话：0571-28121938</p>
                <div class="J_NAVBAR_NAV_ITEM kf-contact">
                  <a data-type="qq" class="kf-contact-item kf-contact-qq" href="http://wpa.qq.com/msgrd?v=3&uin=2494760072&site=qq&menu=yes" target="_blank">
                    <span class="iconfont icon-qq"></span>QQ联系
                  </a>
                  <a data-type="article" class="kf-contact-item kf-contact-help" href="<?=DJADMIN_URL?>mshop/article">
                    <span class="iconfont icon-help"></span>新手帮助
                  </a>
                  <a data-type="article" class="kf-contact-item kf-contact-video" href="<?=DJADMIN_URL?>mshop/article">
                    <span class="iconfont icon-video"></span>视频教程
                  </a>
                </div>
                <h3 class="kf-title">服务时间</h3>
                <p class="kf-tip">工作日：09:00--21:00<br>节假日：09:00--21:00</p>
              </div>
            </li>
            <li class="dropdown">
              <a href="javascript:;">
                <img class="avatar" src="<?php if(!empty($model->img)):?> <?=$model->img?><?php else:?><?=STATIC_URL?>djadmin/img/avatar.jpg<?php endif;?>" alt="<?=$model->username?>"><span class="username"><?=$model->username?></span><span class="iconfont icon-arrow-down"></span>
              </a>
              <div class="dropdown-content" style="left: auto;right: 0;width: 240px;margin-left:-120px;">
                <ul class="nav">
                  <li class="J_NAVBAR_NAV_ITEM"><a href="<?=DJADMIN_URL?>home/price"><span class="iconfont icon-info"></span>当前版本：<?=$service_model->item_name?></a></li>
                  <li class="J_NAVBAR_NAV_ITEM"><a href="<?=DJADMIN_URL?>home/price"><span class="iconfont icon-time"></span>到期时间：<?=$service_model->expire_time?> <span class="text-primary">续费</span></a></li>
                  <li><a href="javascript:;"><span class="iconfont icon-info"></span>商户ID：<?=$model->aid?></a></li>
                  <?php if($this->is_zongbu):?>
                    <li class="J_NAVBAR_NAV_ITEM"><a data-type="account" href="<?=DJADMIN_URL?>user"><span class="iconfont icon-shop"></span>账户信息</a></li>
                  <?php endif;?>
                  <li><a href="<?=DJADMIN_URL?>passport/logout"><span class="iconfont icon-quit"></span>退出登录</a></li>
                </ul>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>
  <aside id="w-aside" class="w-aside">
    <ul class="nav">
      <li class="J_NAV_ITEM active">
        <a id="J_NAV_HOME" href="<?=DJADMIN_URL?>mshop/home"><span class="iconfont icon-home"></span>工作台</a>
      </li>
      <?php if(power_exists(module_enum::WM_MODULE,$service_model->power_keys)):?>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/order/index"><span class="iconfont icon-order"></span>订单管理</a>
      </li>
      <?php elseif(power_exists(module_enum::MEAL_MODULE,$service_model->power_keys)):?>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/order/dinein"><span class="iconfont icon-order"></span>订单管理</a>
      </li>
      <?php else:?>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/order/retail"><span class="iconfont icon-order"></span>订单管理</a>
      </li>
      <?php endif;?>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/items/index"><span class="iconfont icon-good"></span>商品管理</a>
      </li>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/comment"><span class="iconfont icon-pingjia"></span>顾客评价</a>
      </li>
      <?php if($this->is_zongbu):?>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/shop"><span class="iconfont icon-shop"></span>门店管理</a>
      </li>
      <?php else:?>
      <li>
        <a class="J_TOGGLE_SUBNAV" href="javascript:;"><span class="iconfont icon-shop"></span>门店管理<span class="iconfont icon-arrow-down"></span></a>
        <ul class="subnav">
          <li class="J_NAV_ITEM">
            <a href="<?=DJADMIN_URL?>mshop/shop/info">门店信息</a>
          </li>
          <?php if(power_exists(module_enum::WM_MODULE,$service_model->power_keys)):?>
          <li class="J_NAV_ITEM">
            <a href="<?=DJADMIN_URL?>mshop/decorate/index">外卖设置</a>
          </li>
          <?php endif;?>
          <?php if(power_exists(module_enum::MEAL_MODULE,$service_model->power_keys)):?>
          <li class="J_NAV_ITEM">
            <a href="<?=DJADMIN_URL?>mshop/shop_area/table">桌位管理</a>
          </li>
          <?php endif;?>
        </ul>
      </li>
      <?php endif;?>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/finance/index"><span class="iconfont icon-finance"></span>财务统计</a>
      </li>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/statistics/trade"><span class="iconfont icon-tongji"></span>数据统计</a>
      </li>
      <li class="J_NAV_ITEM">
        <a href="<?=DJADMIN_URL?>mshop/promotion/discount_list"><span class="iconfont icon-yingxiao"></span>营销活动</a>
      </li>
      <?php if($this->is_zongbu):?>
        <?php if(power_exists(module_enum::XCX_WM_MODULE,$service_model->power_keys) || power_exists(module_enum::XCX_MEAL_MODULE,$service_model->power_keys)):?>
        <li>
          <a class="J_TOGGLE_SUBNAV" href="javascript:;"><span class="iconfont icon-xiaochengxu"></span>小程序管理<span class="iconfont icon-arrow-down"></span></a>
          <ul class="subnav">
            <?php if(power_exists(module_enum::XCX_WM_MODULE,$service_model->power_keys)):?>
            <li class="J_NAV_ITEM">
              <a href="<?=DJADMIN_URL?>mshop/xcx_config/index">外卖小程序</a>
            </li>
            <?php endif;?>
            <?php if(power_exists(module_enum::XCX_MEAL_MODULE,$service_model->power_keys)):?>
            <li class="J_NAV_ITEM">
              <a href="<?=DJADMIN_URL?>mshop/xcx_meal_config/index">点餐小程序</a>
            </li>
            <?php endif;?>
          </ul>
        </li>
        <?php endif;?>
        <li class="J_NAV_ITEM">
          <a href="<?=DJADMIN_URL?>mshop/setting/index"><span class="iconfont icon-setting"></span>系统设置</a>
        </li>
      <?php else:?>
        <li class="J_NAV_ITEM">
          <a href="<?=DJADMIN_URL?>mshop/setting/printer"><span class="iconfont icon-setting"></span>系统设置</a>
        </li>
      <?php endif;?>
    </ul>
    <div class="bottom-action">
      <a href="<?=SAAS_URL?>" class="btn btn-default">切换应用</a>
      <div class="dropup mt20">
        <button class="btn btn-default" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <?php foreach($shop_list as $shop):?>
            <?php if($this->currentShopId == $shop['shop_id']):?>
              <span class="shop-name"><?=$shop['shop_name']?></span>
            <?php endif;?>
          <?php endforeach;?>
          <span class="iconfont icon-arrow-up" style="margin-top: 2px;margin-left: 3px; margin-right: 0;font-size: 12px;line-height: 1;"></span>
        </button>
        <ul class="dropdown-menu" aria-labelledby="dLabel">
          <?php foreach($shop_list as $shop):?>
            <li class="<?php if($this->currentShopId == $shop['shop_id']):?>active<?php endif;?>">
              <a href="<?=DJADMIN_URL?>shop/toggle/<?=$shop['shop_id']?>"><?=$shop['shop_name']?></a>
            </li>
          <?php endforeach;?>
        </ul>
      </div>
    </div>
  </aside>
  <section id="w-content" class="w-content">
    <iframe id="main-frame" src="<?=DJADMIN_URL?>mshop/home" frameborder="0" style="width: 100%;height: 100%;border: 0;"></iframe>
    <div id="loading-box" class="loading-box">
      <div class="loading">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
        <div></div>
      </div>
    </div>
  </section>
  <audio id="order-music" src="<?=STATIC_URL?>djadmin/mshop/media/new_order.mp3" preload style="display: none;">您的浏览器不支持 audio 标签。</audio>
  <script id="orderPrintTpl" type="text/html">
    <div style="width: 94%;margin: 0;font-family: '宋体', Arial;font-weight: 400;font-size: 9pt;overflow: hidden;">
      <div style="margin: 5pt 0;text-align: center;font-size: 16pt;line-height: 1;margin-top: 8pt;"><span style="font-size: 8pt;">**</span>云店宝订单<span style="font-size: 7pt;">**</span></div>
      <div style="margin-bottom: 8pt;text-align: center;">* <%:=shop_name%> *</div>
      <div>下单时间：<%:=time.alias%></div>
      <div style="margin-top: 3pt;text-align: center;white-space: nowrap;overflow: hidden;">*****************************</div>
      <% if(remark) { %>
        <div style="font-size: 14pt;font-weight: 600;margin-top: 3pt;margin-bottom: 5pt;">
          备注：<%:=remark%>
        </div>
      <% } %>
      <div style="text-align: center;margin: 2pt 0;white-space: nowrap;overflow: hidden;">-------------1号口袋------------</div>
      <table style="width: 100%;font-size: 9pt;border-spacing: 0;border-collapse: collapse;" cellspacing="0">
        <% if(order_ext.length > 0) { %>
          <% for(var i = 0; i < order_ext.length; i++) { %>
            <tr>
              <td style="width: 60%;vertical-align: top;"><%:=order_ext[i].goods_title%>
                <% if(order_ext[i].sku_str) { %>
                  <span style="font-size: 8pt;"><%:=order_ext[i].sku_str%></span>
                <% } %>
                <% if(order_ext[i].pro_attr) { %>
                  <span style="font-size: 8pt;">(<%:=order_ext[i].pro_attr%>)</span>
                <% } %>
              </td>
              <td style="width: 15%;text-align: left;vertical-align: top;">x <%:=order_ext[i].num%></td>
              <td style="width: 25%;text-align: right;vertical-align: top;"><%:=(parseFloat(order_ext[i].order_money) - parseFloat(order_ext[i].discount_money)).toFixed(2)%></td>
            </tr>
          <% } %>
        <% } %>
      </table>
      <div style="text-align: center;margin: 2pt 0;white-space: nowrap;overflow: hidden;">--------------其它-------------</div>
      <table style="width: 100%;font-size: 9pt;border-spacing: 0;border-collapse: collapse;" cellspacing="0">
        <tr>
          <td style="width: 60%;">餐盒费</td>
          <td style="width: 40%;text-align: right;" colspan="2"><%:=package_money%></td>
        </tr>
        <tr>
          <td style="width: 60%;">配送费</td>
          <td style="width: 40%;text-align: right;" colspan="2"><%:=freight_money%></td>
        </tr>
        <% if(discount_detail && ((discount_detail.coupon && +discount_detail.coupon.amount > 0) || (discount_detail.card && +discount_detail.card.amount > 0) || (discount_detail.manjian && +discount_detail.manjian.reduce_price > 0) || +discount_detail.xinren > 0 || +discount_detail.huiyuan > 0)) { %>
          <% if(discount_detail.coupon && +discount_detail.coupon.amount > 0) { %>
            <tr>
              <td style="width: 60%;">优惠券</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.coupon.amount%></td>
            </tr>
          <% } %>
          <% if(discount_detail.card && +discount_detail.card.amount > 0) { %>
            <tr>
              <td style="width: 60%;">代金券</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.card.amount%></td>
            </tr>
          <% } %>
          <% if(discount_detail.manjian && +discount_detail.manjian.reduce_price > 0) { %>
            <tr>
              <td style="width: 60%;">在线支付立减</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.manjian.reduce_price%></td>
            </tr>
          <% } %>
          <% if(+discount_detail.xinren > 0) { %>
            <tr>
              <td style="width: 60%;">门店新客立减</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.xinren%></td>
            </tr>
          <% } %>
          <% if(+discount_detail.huiyuan > 0) { %>
            <tr>
              <td style="width: 60%;">会员折扣</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.huiyuan%></td>
            </tr>
          <% } %>
        <% } %>
      </table>
      <div style="margin: 2pt 0;text-align: center;white-space: nowrap;overflow: hidden;">*****************************</div>
      <div style="text-align: right;">（用户在线支付）<span style="font-size: 12pt;"><%:=pay_money%>元</span></div>
      <div style="text-align: center;margin: 0 0 2pt;white-space: nowrap;overflow: hidden;">--------------------------------------------</div>
      <div style="font-size: 15pt;">
        <%:=receiver_site%> <%:=receiver_address%>
      </div>
      <div style="margin-bottom: 15pt;font-size: 13pt;">
        <%:=receiver_phone%></br>
        <%:=receiver_name%>
      </div>
    </div>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/js/LodopFuncs.min.js');?>
  <?=static_original_url('djadmin/js/index.js');?>
</body>
</html>

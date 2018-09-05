<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>流量分析 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
  <?=static_original_url('djadmin/mshop/css/statistics.min.css');?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_statistics');?>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="form-inline search-form mb10">
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
              <span class="text-primary ml10 mr10" style="display: none;">营业中</span>
            </div>
            <div class="form-group">
              <div class="form-control-time">
                <input id="time" class="form-control" type="text" name="time" placeholder="输入时间" readonly style="width: 200px;">
                <span class="iconfont icon-rili"></span>
              </div>
            </div>
          </div>
          <h3 class="main-title">店铺流量
            <div class="m-tooltip">
              <span class="iconfont icon-help"></span>
              <div class="m-tooltip-content">
                <h3>访客</h3>
                <p>累计进入你店铺的顾客数</p>
                <h3>下单</h3>
                <p>提交订单人顾客数（已去掉重复下单的顾客）</p>
                <h3>支付</h3>
                <p>提交订单并完成付款的顾客数</p>
                <h3>下单转化率</h3>
                <p>访客数下单比例</p>
                <p>计算公式：下单顾客数/访客数</p>
                <h3>付款转化率</h3>
                <p>下单顾客中付款比例</p>
                <p>计算公式：付款顾客/下单顾客</p>
                <h3>全店转化率</h3>
                <p>访客数中付款顾客比例</p>
                <p>计算公式：支付顾客/访客数</p>
              </div>
            </div>
          </h3>
          <div class="w-flow-data mb20">
            <div class="w-flow-left">
              <div class="w-flow-block">
                <div class="w-flow-item">
                  <p class="text-dark">访客数</p>
                  <p><span id="uv">--</span>人</p>
                  <p><span class="text-muted">比昨日</span> <span id="compare-uv">--</span></p>
                </div>
              </div>
              <div class="w-flow-block">
                <div class="w-flow-item">
                  <p class="text-dark">下单人数</p>
                  <p><span id="order-num">--</span>人</p>
                  <p><span class="text-muted">比昨日</span> <span id="compare-order-num">--</span></p>
                </div>
                <div class="w-flow-item">
                  <p class="text-dark">下单金额</p>
                  <p>￥<span id="order-money">-.--</span></p>
                  <p><span class="text-muted">比昨日</span> <span id="compare-order-money">￥-.--</span></p>
                </div>
              </div>
              <div class="w-flow-block">
                <div class="w-flow-item">
                  <p class="text-dark">付款人数</p>
                  <p><span id="pay-num">--</span>人</p>
                  <p><span class="text-muted">比昨日</span> <span id="compare-pay-num">--</span></p>
                </div>
                <div class="w-flow-item">
                  <p class="text-dark">付款金额</p>
                  <p>￥<span id="pay-money">-.--</span></p>
                  <p><span class="text-muted">比昨日</span> <span id="compare-pay-money">￥-.--</span></p>
                </div>
                <div class="w-flow-item">
                  <p class="text-dark">客单价</p>
                  <p>￥<span id="customer-unit-price">-.--</span></p>
                  <p><span class="text-muted">比昨日</span> <span id="compare-customer-unit-price">￥-.--</span></p>
                </div>
              </div>
            </div>
            <div class="w-flow-right">
              <div class="w-flow-item" style="position: absolute;top: 62px;left: 294px;">
                <p class="text-dark">下单转化率：<span id="order-conversion-rate">-.--</span>%</p>
                <p><span class="text-muted">比昨日</span> <span id="compare-order-conversion-rate">-.--%</span></p>
              </div>
              <div class="w-flow-item" style="position: absolute;top: 158px;left: 256px;">
                <p class="text-dark">付款转化率：<span id="pay-conversion-rate">-.--</span>%</p>
                <p><span class="text-muted">比昨日</span> <span id="compare-pay-conversion-rate">-.--%</span></p>
              </div>
              <div class="w-flow-item" style="position: absolute;top: 204px;left: 394px;">
                <p class="text-dark">全店转化率：<span id="shop-conversion-rate">-.--</span>%</p>
                <p><span class="text-muted">比昨日</span> <span id="compare-shop-conversion-rate">-.--%</span></p>
              </div>
            </div>
          </div>
          <h3 class="main-title">七天趋势图</h3>
          <div id="trend-chart" style="width: 100%; height: 360px;"></div>
        </div>
      </div>
    </div>
  </div>           
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?=static_original_url('libs/echarts/3.7.1/echarts.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/statistics_flow_shop.min.js');?>
</body>
</html>

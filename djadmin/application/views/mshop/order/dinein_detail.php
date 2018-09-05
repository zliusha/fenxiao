<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>订单详情 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <input id="serial_number" type="hidden" value="<?=$serial_number?>">
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li><a href="<?=DJADMIN_URL?>mshop/order/dinein">堂食订单</a></li>
        <li class="active">订单详情</li>
      </ol>
      <div class="main-body">
        <div id="orderDetail" class="main-body-inner order-detail">
          <div class="m-empty-box">
            <p>加载中...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script id="orderDetailTpl" type="text/html">
    <h3 class="main-title" style="margin-top: -10px;">基本信息</h3>
    <hr>
    <div class="row">
      <div class="col-md-6 col-sm-6">
        <dl class="dl-horizontal">
          <dt>订单状态：</dt>
          <dd>
            <% if(statement_info.status.value == '1') { %>
              <span class="label label-primary"><%:=statement_info.status.alias %></span>
            <% } else if(statement_info.status.value == '2') { %>
              <span class="label label-success"><%:=statement_info.status.alias %></span>
            <% } else { %>
              <span class="label label-danger"><%:=statement_info.status.alias %></span>
            <% } %>
          </dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>交易金额：</dt>
          <dd>￥<%:=statement_info.amount %></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>收银方式：</dt>
          <dd><%:=statement_info.pay_source.alias %></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>支付方式：</dt>
          <dd><%:=statement_info.gateway.alias %></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>付款时间：</dt>
          <dd><%:=statement_info.time.alias %></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>收银员：</dt>
          <dd><%:=statement_info.operator ? statement_info.operator : '--' %></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>流水号：</dt>
          <dd><%:=statement_info.serial_number %></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>销售单号：</dt>
          <dd><%:=statement_info.order_number %></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>打印状态：</dt>
          <dd>
            <% if (statement_info.is_print == '1') { %>
              <span>已打印</span>
            <% } else { %>
              <span>未打印</span>
            <% } %>
          </dd>
        </dl>
        <% if (statement_info.is_print == '1') { %>
          <dl class="dl-horizontal">
            <dt>小票号：</dt>
            <dd><%:=statement_info.print_sn%></dd>
          </dl>
        <% } %>
      </div>
      <div class="col-md-6 col-sm-6">
        <dl class="dl-horizontal">
          <dt>门店：</dt>
          <dd><%:=order_table.shop_name%></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>桌号：</dt>
          <dd><%:=order_table.area_name%> <%:=order_table.table_name%></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>用餐人数：</dt>
          <dd><%:=order_table.number%>人</dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>开单时间：</dt>
          <dd><%:=statement_info.time.alias %></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>订单备注：</dt>
          <dd><%:=order_table.remark%></dd>
        </dl>
      </div>
    </div>
    <% if(statement_info.order_ext.length > 0) {%>
      <h3 class="main-title">菜品信息</h3>
      <hr>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>商品信息</th>
              <th>单价</th>
              <th>数量</th>
              <th>小计</th>
            </tr>
          </thead>
          <tbody>
            <% for(var i = 0, l = statement_info.order_ext.length; i < l; i++) {%>
              <tr>
                <td>
                  <div class="good-info">
                    <span class="good-title"><%:=statement_info.order_ext[i].goods_title%></span>
                  </div>
                </td>
                <td>￥<%:=statement_info.order_ext[i].price%></td>
                <td><%:=statement_info.order_ext[i].num%></td>
                <td>￥<%:=statement_info.order_ext[i].pay_money%></td>
              </tr>
            <% } %>
          </tbody>
        </table>
      </div>
    <% } %>
    <p class="text-right">共<%:=statement_info.goods_number%>件，合计：<span>￥<%:=statement_info.order_money%></span></p>
    <p class="text-right">实付：<span class="text-danger" style="font-size: 20px;">￥<%:=statement_info.amount%></span></p>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/dinein_order_detail.min.js');?>
</body>
</html>

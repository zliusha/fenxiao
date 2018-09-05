<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>堂食订单 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_order');?>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="clearfix mb20">
            <div class="form-inline search-form pull-left">
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
              <div class="form-group">
                <div class="form-control-time">
                  <input id="create_time" class="form-control" type="text" name="create_time" placeholder="输入下单时间" readonly>
                  <span class="iconfont icon-rili"></span>
                </div>
              </div>
              <div class="form-group">
                <input id="serial_number" class="form-control" type="text" name="serial_number" placeholder="流水号" style="width: 220px;">
              </div>
              <div class="form-group">
                <input id="table_name" class="form-control" type="text" name="table_name" placeholder="桌号" style="width: 120px;">
              </div>
              <button id="btn-search" class="btn btn-primary"><span class="iconfont icon-search"></span>搜索</button>
            </div>
            <div class="pull-right">
              <a id="export-order" class="btn btn-primary" href="javascript:;" onclick="exportOrder()"><span class="iconfont icon-shangjia"></span>导出订单</a>
              <a id="btn-refresh" class="btn btn-primary ml10" href="javascript:;"><span class="iconfont icon-refresh"></span>刷新</a>
            </div>
          </div>
          <div class="order-con table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>门店 / 流水号</th>
                  <th>桌号</th>
                  <th>用餐人数</th>
                  <th>开单时间</th>
                  <th>订单状态</th>
                  <th>订单金额</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="orderTbody">
                <tr>
                  <td class="text-center" colspan="7">加载中...</td>
                </tr>
              </tbody>          
            </table>
          </div>
          <div id="orderPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
  <script id="orderTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td>
            <p><%:=rows[i].shop_name %></p>
            <p><%:=rows[i].serial_number %></p>
          </td>
          <td><%:=rows[i].area_name%> <%:=rows[i].table_name%></td>
          <td><%:=rows[i].meal_order.number%>人</td>
          <td><%:=rows[i].time.alias %></td>
          <td>
            <% if(rows[i].status.value == '1') { %>
              <span class="label label-primary"><%:=rows[i].status.alias %></span>
            <% } else if(rows[i].status.value == '2') { %>
              <span class="label label-success"><%:=rows[i].status.alias %></span>
            <% } else { %>
              <span class="label label-danger"><%:=rows[i].status.alias %></span>
            <% } %>
          </td>
          <td>￥<%:=rows[i].amount %></td>
          <td>
            <a class="btn-link" href="<?=DJADMIN_URL?>mshop/order/dinein_detail/<%:=rows[i].serial_number %>">详情</a>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="7">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/dinein_order_list.min.js');?>
</body>
</html>

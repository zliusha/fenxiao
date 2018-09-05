<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>商品分析 - 微外卖</title>
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
          <p class="mb20"><strong>提示</strong>：销售额调整为统计商品的实际销售额（剔除优惠金额），销售额分析请以2018年7月13日及以后销售的商品为准</p>
          <div class="form-inline search-form mb20">
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
              <?php $this->load->view('inc/select_service');?>
            </div>
            <div class="form-group">
              <select id="measure_type" class="form-control" name="measure_type">
                <option value="">全部商品</option>
                <option value="1">计件商品</option>
                <option value="2">计重商品</option>
              </select>
            </div>
            <div class="form-group">
              <div class="form-control-time">
                <input id="time" class="form-control" type="text" name="time" placeholder="输入时间" readonly style="width: 200px;">
                <span class="iconfont icon-rili"></span>
              </div>
            </div>
          </div>
          <div id="goodCon">
            <table class="table good-table">
              <thead>
                <tr>
                  <th>商品名称</th>
                  <th>门店</th>
                  <th>价格</th>
                  <th><span id="sort-sale-num" class="sort-ctrl sort-down">销量<span class="btn-sort"></span></span></th>
                  <th><span id="sort-sale-money" class="sort-ctrl">销售额<span class="btn-sort"></span></span></th>
                </tr>
              </thead>
              <tbody id="goodTbody">
                <tr>
                  <td class="text-center" colspan="5">加载中...</td>
                </tr>
              </tbody>
            </table>
            <div id="goodPage" class="m-pager"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script id="goodTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td>
            <% if (rows[i].sku_type == 1 && rows[i].sku_list.length > 1) { %>
              <div class="good-info">
                <img class="good-pic" src="<%:=rows[i].pict_url %>">
                <span class="J_VIEW_MORE_GOOD good-title"><span class="good-more-arrow iconfont icon-arrow-down"></span><%:= rows[i].title %></span>
              </div>
              <div class="good-more-info" style="padding-left: 60px">
                <% for(var j = 0; j < rows[i].sku_list.length; j++) { %>
                  <p><%:=rows[i].sku_list[j].attr_names %></p>
                <% } %>  
              </div>
            <%} else {%>
              <div class="good-info">
                <img class="good-pic" src="<%:=rows[i].pict_url %>">
                <span class="good-title"><%:=rows[i].title %></span>
              </div>
            <% } %>
          </td>
          <td><%:=rows[i].shop_name%></td>
          <td>￥<%:=rows[i].inner_price%> /
            <% if(rows[i].measure_type == 1) { %>
              份
            <% } else { %>
              <%:=rows[i].unit_name%>
            <% } %>
            <% if (rows[i].sku_list.length > 1) { %>
              <div class="good-more-info">
                <% for(var j = 0; j < rows[i].sku_list.length; j++) { %>
                  <p>￥<%:=rows[i].sku_list[j].sale_price %> /
                    <% if(rows[i].measure_type == 1) { %>
                      份
                    <% } else { %>
                      <%:=rows[i].unit_name%>
                    <% } %>
                  </p>
                <% } %>
              </div>
            <% } %>
          </td>
          <td>
            <p><%:=rows[i].sale_number%>
              <% if(rows[i].measure_type == 1) { %>
                份
              <% } else { %>
                <%:=rows[i].unit_name%>
              <% } %>
            </p>
            <% if (rows[i].sku_list.length > 1) { %>
              <div class="good-more-info">
                <% for(var j = 0; j < rows[i].sku_list.length; j++) { %>
                  <p><%:=rows[i].sku_list[j].sale_num %>
                    <% if(rows[i].measure_type == 1) { %>
                      份
                    <% } else { %>
                      <%:=rows[i].unit_name%>
                    <% } %>
                  </p>
                <% } %>  
              </div>
            <% } %>
          </td>
          <td>
            <p>￥<%:=rows[i].sale_money%></p>
            <% if (rows[i].sku_list.length > 1) { %>
              <div class="good-more-info">
                <% for(var j = 0; j < rows[i].sku_list.length; j++) { %>
                  <p>￥<%:=rows[i].sku_list[j].sale_money %></p>
                <% } %>  
              </div>
            <% } %>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="5">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/statistics_good.min.js');?>
</body>
</html>

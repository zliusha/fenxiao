<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>领取记录 - 优惠券</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li><a id="coupon_type" href="<?=DJADMIN_URL?>mshop/promotion/coupon_list">裂变优惠券</a></li>
        <li class="active">领取记录</li>
      </ol>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="form-inline search-form mb20">
            <div class="form-group">
              <select id="status" class="form-control" name="status">
                <option value="">全部状态</option>
                <option value="1">未使用</option>
                <option value="2">已使用</option>
                <option value="3">已过期</option>
              </select>
            </div>
            <div class="form-group">
              <input id="mobile" class="form-control" type="text" name="mobile" placeholder="手机号" style="width: 200px;">
            </div>
            <button id="btn-search" class="btn btn-primary"><span class="iconfont icon-search"></span>搜索</button>
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>优惠券名称</th>
                  <th>手机号</th>
                  <th>领取金额</th>
                  <th>领取时间</th>
                  <th>状态</th>
                </tr>
              </thead>
              <tbody id="couponRecordTbody">
                <tr>
                  <td class="text-center" colspan="5">加载中...</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div id="couponRecordPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
  <script id="couponRecordTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].title%></td>
          <td><%:=rows[i].mobile%></td>
          <td>￥<%:=rows[i].amount%></td>
          <td><%:=rows[i].time%></td>
          <td>
            <% if (rows[i].status == '1') { %>
              <span class="label label-warning">未使用</span>
            <% } else if (rows[i].status == '2') { %>
              <span class="label label-success">已使用</span>
            <% } else if (rows[i].status == '3') { %>
              <span class="label label-danger">已过期</span>
            <% } %>
          </td>
        </tr>
      <% } %>
    <% } else { %>
      <tr>
        <td class="text-center" colspan="5">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/coupon_record.min.js');?>
</body>
</html>

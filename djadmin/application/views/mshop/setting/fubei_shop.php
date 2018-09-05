<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>银行通道配置 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .shop-form .col-md-3 {
      width: 22%;
      padding-right: 0;
    }
    .shop-form .col-md-9 {
      width: 76%;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <h3 class="main-title" style="margin-top: -10px;">银行通道支付</h3>
          <div class="mb20">
            <span class="text-muted">支付宝、微信的银行通道通用配置；银行支付通道仅适用于云店宝收银台场景</span><br>
            <span class="text-primary">注意：银行通道配置适用于所有门店的收银台，所有门店也可以分别使用各自的银行通道账号</span>
          </div>
          <div class="mb20">
            <a class="btn btn-primary" href="<?=DJADMIN_URL?>mshop/setting/fubei_list">银行通道列表</a>
            <span class="text-muted ml10">在银行通道列表添加可能用到的银行通道，在下方列表进行店铺和银行通道绑定。</span>
          </div>
          <div id="shopCon">
            <table class="table">
              <thead>
                <tr>
                  <th>门店名称</th>
                  <th>银行通道名称</th>
                  <th>银行通道ID</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="shopTbody">
                <tr>
                  <td class="text-center" colspan="4">加载中...</td>
                </tr>
              </tbody>
            </table>
            <div id="shopPage" class="m-pager"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="editShopModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="shop-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">配置银行通道</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal fubei-form">
            <div class="form-group">
              <label class="col-md-3 control-label">门店名称：</label>
              <div class="col-md-9">
                <input id="shop_id" type="hidden" name="shop_id">
                <p id="shop_name" class="form-control-static">--</p>
              </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-3 control-label">银行通道：</label>
              <div class="col-md-9">
                <select id="fubei_id" class="form-control" name="fubei_id">
                  <option value="" data-id="">请选择</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="edit-confirm" class="btn btn-primary">确定</button>
        </div>
      </form>
    </div>
  </div>
  <script id="fubeiTpl" type="text/html">
    <option value="">请选择</option>
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <option value="<%:=rows[i].id %>"><%:=rows[i].name%></option>
      <% } %>
    <% } %>
  </script>
  <script id="shopTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=(rows[i].shop_name && rows[i].shop_name !== 'null') ? rows[i].shop_name : '--'%></td>
          <td><%:=(rows[i].name && rows[i].name !== 'null') ? rows[i].name : '--'%></td>
          <td><%:=(rows[i].fubei_id && rows[i].fubei_id !== 'null') ? rows[i].fubei_id : '--'%></td>
          <td>
            <a class="btn-link" href="javascript:;" onclick="editShop('<%:=(rows[i].shop_id && rows[i].shop_id !== 'null') ? rows[i].shop_id : ''%>', '<%:=(rows[i].shop_name && rows[i].shop_name !== 'null') ? rows[i].shop_name : ''%>', '<%:=(rows[i].fubei_id && rows[i].fubei_id !== 'null') ? rows[i].fubei_id : ''%>')">配置</a>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="4">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/setting_fubei_shop.min.js');?>
</body>
</html>

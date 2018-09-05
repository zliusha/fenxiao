<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>银行通道列表 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .fubei-form .col-md-3 {
      width: 26%;
      padding-right: 0;
    }
    .fubei-form .col-md-9 {
      width: 70%;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li><a href="<?=DJADMIN_URL?>mshop/setting/fubei_shop">银行通道配置</a></li>
        <li class="active">银行通道列表</li>
      </ol>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="mb20">
            <a class="btn btn-primary" href="javascript:;" onclick="addFubei()">添加银行通道</a><span class="text-muted ml10">添加可能用到的银行通道，然后在银行通道配置页面进行店铺和银行通道绑定</span>
          </div>
          <div id="fubeiCon">
            <table class="table">
              <thead>
                <tr>
                  <th>银行通道名称</th>
                  <th>商户平台ID</th>
                  <th>商户门店ID</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="fubeiTbody">
                <tr>
                  <td class="text-center" colspan="4">加载中...</td>
                </tr>
              </tbody>
            </table>
            <div id="fubeiPage" class="m-pager"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="editFubeiModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <form id="fubei-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">修改银行通道</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal fubei-form">
            <div class="form-group">
              <label class="col-md-3 control-label">银行通道名称：</label>
              <div class="col-md-9">
                <input id="name" class="form-control" type="text" name="name" placeholder="请输入银行通道名称">
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">商户平台ID：</label>
              <div class="col-md-9">
                <input id="app_id" class="form-control" type="text" name="app_id" placeholder="请输入商户平台ID">
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">商户平台Secret：</label>
              <div class="col-md-9">
                <input id="app_secret" class="form-control" type="text" name="app_secret" placeholder="请输入商户平台Secret">
              </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-3 control-label">商户门店ID：</label>
              <div class="col-md-9">
                <input id="store_id" class="form-control" type="text" name="store_id" placeholder="请输入商户门店ID">
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
  <div id="delFubeiModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">银行通道删除后不可恢复，确定要删除吗？</h4>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button type="button" class="btn btn-danger" id="del-confirm">删除</button>
        </div>
      </div>
    </div>
  </div>
  <script id="fubeiTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].name %></td>
          <td><%:=rows[i].app_id %></td>
          <td><%:=rows[i].store_id %></td>
          <td>
            <a class="btn-link" href="javascript:;" onclick="editFubei('<%:=rows[i].id%>', '<%:=rows[i].name%>', '<%:=rows[i].app_id%>', '<%:=rows[i].app_secret%>', '<%:=rows[i].store_id%>')">修改</a>
            <a class="btn-link btn-danger" href="javascript:;" onclick="delFubei('<%:=rows[i].id%>')">删除</a>
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
  <?=static_original_url('djadmin/mshop/js/setting_fubei_list.min.js');?>
</body>
</html>

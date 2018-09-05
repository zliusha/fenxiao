<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>子账号管理 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .account-form .col-md-3 {
      width: 30%;
      padding-right: 0;
    }
    .account-form .col-md-9 {
      width: 68%;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="mb20">
            <a class="btn btn-primary" onclick="addAccount()"><span class="iconfont icon-add"></span>添加子账号</a>
            <a id="work_url" class="btn btn-primary ml10" href="javascript:;" target="_blank"><span class="iconfont icon-fenzu"></span>员工管理</a>
          </div>
          <div class="mb20 text-muted">
            <span>总账号：具备一切管理权限，包含删除其他管理员帐号、新建店铺等权限</span><br>
            <span>子账号：具备绝大部分管理权限，不能添加、删除其他管理员、不能新建店铺</span>
          </div>
          <div id="accountCon">
            <table class="table">
              <thead>
                <tr>
                  <th>管理员(员工)</th>
                  <th>管理门店</th>
                  <th>添加时间</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="accountTbody">
                <tr>
                  <td class="text-center" colspan="4">加载中...</td>
                </tr>
              </tbody>
            </table>
            <div id="accountPage" class="m-pager"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="editAccountModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="account-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">编辑子账号</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal account-form">
            <div class="form-group work_name">
              <label class="col-md-3 control-label">管理员(员工)：</label>
              <div class="col-md-9">
                <select id="account_work" class="form-control" name="account_work" style="display: none;">
                  <option value="">请选择</option>
                </select>
                <p id="account_work_name" class="form-control-static">--</p>
              </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-3 control-label">管理门店：</label>
              <div class="col-md-9">
                <select id="account_shop" class="form-control" name="account_shop">
                  <option value="">请选择</option>
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
  <div id="delAccountModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">子账号删除后不可恢复，确定要删除吗？</h4>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button type="button" class="btn btn-danger" id="del-confirm">删除</button>
        </div>
      </div>
    </div>
  </div>
  <script id="accountWorkTpl" type="text/html">
    <option value="" data-id="">请选择</option>
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <option value="<%:=list[i].user_id %>"><%:=list[i].user_name %></option>
      <% } %>
    <% } %>
  </script>
  <script id="accountShopTpl" type="text/html">
    <option value="">请选择</option>
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <option value="<%:=list[i].id %>"><%:=list[i].shop_name %></option>
      <% } %>
    <% } %>
  </script>
  <script id="accountTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].username %></td>
          <td><%:=rows[i].shop_name %></td>
          <td><%:=rows[i].time %></td>
          <td>
            <a class="btn-link" href="javascript:;" onclick="editAccount('<%:=rows[i].id%>', '<%:=rows[i].username%>', '<%:=rows[i].shop_id%>')">编辑</a>
            <a class="btn-link btn-danger" href="javascript:;" onclick="delAccount('<%:=rows[i].id %>')">删除</a>
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
  <?=static_original_url('djadmin/mshop/js/setting_account.js');?>
</body>
</html>

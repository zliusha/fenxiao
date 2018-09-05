<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>微商城 - 管理后台</title>
  <?php $this->load->view('inc/global_header');?>
  <?=static_original_url('admin/css/main.min.css');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/css/bootstrapValidator.min.css');?>
  <style type="text/css">
    .dl-rights dd {
      margin-bottom: 10px;
      margin-left: 85px;
    }
    .dl-rights dt {
      width: 80px;
      font-weight: normal;
    }
  </style>
</head>
<body>
<div id="main">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="active">用户查询</li>
    </ol>
    <div class="main-body">
      <div class="main-body-inner">
        <div class="clearfix mb20">
          <div class="form-inline pull-left">
            <div class="form-group">
              <select id="active_state" class="form-control active_state" name="active_state">
                <option value="mobile">手机号</option>
                <option value="user_id">用户id</option>
                <option value="visit_id">visit_id</option>
              </select>
            </div>
            <div class="form-group">
              <input id="searchVal" class="form-control searchVal" type="text" name="searchVal" placeholder="输入对应的值">
            </div>
            <a href="javascript:;" id="btn-search" class="btn btn-primary"><span class="iconfont icon-search"></span>查询</a>
          </div>
        </div>
        <div id="shopCon">
          <dl id="erpTbody" class="dl-horizontal dl-rights">
          <div class="m-empty-box">
            <p>暂无信息</p>
          </div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>
<script id="erpTpl" type="text/html">
  <% if(data.length!=0) { %>
    <% if(data.model) { %>
      <dt>aid：<dd><%:=data.model.aid%></dd></dt>
    <% } %>
    <% if(data.erp_model) { %>
      <dt>visit_id：<dd><%:=data.erp_model.visit_id%></dd></dt>
      <dt>user_id：<dd><%:=data.erp_model.user_id%></dd></dt>
      <dt>用户名：<dd><%:=data.erp_model.user_name%></dd></dt>
      <dt>手机号：<dd><%:=data.erp_model.phone%></dd></dt>
      <dt>user_nature:<dd><%:=data.erp_model.user_nature%></dd></dt>
    <% } %>
  <% } else { %>
    <div class="m-empty-box">
      <p>暂无信息</p>
    </div>
  <% } %>
</script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('admin/js/main.min.js');?>
  <?=static_original_url('admin/vshop/js/erp_list.js');?>
</body>
</html>
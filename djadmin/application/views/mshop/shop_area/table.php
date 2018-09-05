<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>桌位管理 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .m-empty-box {
      padding: 40px 20px;
    }
  </style>
</head>
<body>
	<div id="main">
    <input id="shop" type="hidden" value="<?=$shop_id?>">
		<div class="container-fluid">
      <?php $this->load->view('inc/nav_shop_area');?>
			<div class="main-body">
				<div class="main-body-inner">
          <div class="clearfix mb20">
            <div class="form-inline pull-left">
              <div class="form-group">
                <select id="shop_area" class="form-control" name="shop_area_id">
                  <option value="">全部区域</option>
                </select>
              </div>
              <div class="form-group">
                <input id="title" class="form-control" type="text" name="title" placeholder="输入桌位" style="width: 200px;">
              </div>
              <a id="btn-search" href="javascript:;" class="btn btn-primary"><span class="iconfont icon-search"></span>搜索</a>                   
            </div>
            <div class="pull-right">
              <a href="javascript:;" class="btn btn-primary" onclick="addTable()"><span class="iconfont icon-add"></span>添加桌位</a>
              <a href="javascript:;" class="btn btn-primary ml10" onclick="downloadQrcodePkg()"><span class="iconfont icon-qrcode"></span>下载全部二维码</a>
            </div>
          </div>
					<div id="tableCon"></div>
				</div>
			</div>
		</div>
	</div>
  <div id="editTableModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="table-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">添加 / 编辑桌位</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group">
              <label class="col-md-3 control-label">区域：</label>
              <div class="col-md-9">
                <select id="table_shop_area" class="form-control" name="table_shop_area">
                  <option value="">全部区域</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">桌位名称：</label>
              <div class="col-md-9"><input id="table_name" class="form-control" type="text" name="table_name" placeholder="请输入桌位名称"></div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-3 control-label">建议人数：</label>
              <div class="col-md-9"><input id="table_number" class="form-control" type="number" name="table_number" min="1" placeholder="请输入建议人数"></div>
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
  <script id="shopAreaTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <option value="">全部区域</option>
      <% for(var i = 0; i < rows.length; i++) { %>
        <option value="<%:=rows[i].id%>"><%:=rows[i].name%></option>
      <% } %>
    <% } %>
  </script>
	<script id="tableTpl" type="text/html">
		<% if (rows.length > 0) { %>
			<% for(var i = 0; i < rows.length; i++) { %>
        <h2 class="table-area"><%:=rows[i].name%></h2>
        <% if (rows[i].area_table_list.length > 0) { %>
          <ul class="table-list">
            <% for(var j = 0; j < rows[i].area_table_list.length; j++) { %>
              <li class="table-item" onclick="goTableDetail('<%:=rows[i].area_table_list[j].id%>')">
                <h3 class="table-name">桌位 <%:=rows[i].area_table_list[j].name%></h3>
                <p class="table-number"><%:=rows[i].area_table_list[j].number%>人桌</p>
                <div>
                  <% if(rows[i].area_table_list[j].status == '0') { %>
                    <span class="label label-primary">空桌</span>
                  <% } else if(rows[i].area_table_list[j].status == '1') { %>
                    <span class="label label-warning">点餐中</span>
                  <% } else if(rows[i].area_table_list[j].status == '2') { %>
                    <span class="label label-danger">就餐中</span>
                  <% } else if(rows[i].area_table_list[j].status == '3') { %>
                    <span class="label label-success">已结算</span>
                  <% } else { %>
                    <span class="label label-danger">未知状态</span>
                  <% } %>
                </div>
              </li>
            <% } %>
          </ul>
        <% } else { %>
          <div class="m-empty-box">
            <p><%:=rows[i].name%>暂无桌位</p>
          </div>
        <% } %>
			<% } %>
		<%} else {%>
			<div class="m-empty-box">
        <p>暂无桌位</p>
      </div>
		<% } %>
  </script>
	<?php $this->load->view('inc/global_footer'); ?>
	<?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
	<?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
	<?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
	<?=static_original_url('djadmin/js/main.min.js');?>
	<?=static_original_url('djadmin/mshop/js/shop_area_table.min.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>门店信息 - 微外卖</title>
	<?php $this->load->view('inc/global_header'); ?>
	<style>
		.m-logo {
			width: 80px;
			height: 80px;
			border-radius: 6px;
		}
		.m-form-horizontal .form-group {
			position: relative;
			padding-left: 90px;
			margin: 0 0 10px;
		}
		.m-form-horizontal .control-label {
			position: absolute;
			top: 0;
			left: 0;
			width: 90px;
			padding-left: 0;
		}
		.m-form-horizontal .form-group>div {
			width: 100%;
		}
		.m-form-horizontal .col-md-2,
		.m-form-horizontal .col-md-10,
		.m-form-horizontal .col-sm-3,
		.m-form-horizontal .col-sm-9 {
			padding-left: 0;
		}

		.m-form-horizontal p {
			margin-bottom: 0;
		}
		.m-form-horizontal p+p {
			margin-top: 10px;
		}

		.logo-list{
			list-style-type: none;
    	padding-left: 0;
		}
		.logo-item {
	    position: relative;
	    float: left;
	    margin-right: 10px;
		}
	</style>
</head>
<body>
	<input id="shop_id" type="hidden" value="<?=$shop_id?>">
	<div id="main">
		<div class="container-fluid">
			<ol class="breadcrumb">
				<li class="active">门店信息</li>
			</ol>
			<div class="main-body">
				<div class="main-body-inner clearfix">
					<div class="form-horizontal m-form-horizontal col-md-6 col-sm-6">
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">门店logo：</label>
							<div class="col-md-10 col-sm-9 clearfix">
								<img id="shop_logo" class="m-logo" src="<?=STATIC_URL?>djadmin/img/avatar.jpg">
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">门店名称：</label>
							<div class="col-md-10 col-sm-9">
								<p id="shop_name" class="form-control-static"></p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">门店地址：</label>
							<div class="col-md-10 col-sm-9">
								<p id="shop_detailAddress" class="form-control-static"></p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">联系电话：</label>
							<div class="col-md-10 col-sm-9">
								<p id="contact" class="form-control-static"></p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">营业时间：</label>
							<div class="col-md-10 col-sm-9">
								<div id="openTime" class="form-control-static"></div>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">服务半径：</label>
							<div class="col-md-10 col-sm-9">
								<p class="form-control-static"><span id="shop_radius"></span>km</p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">服务区域：</label>
							<div class="col-md-10 col-sm-9">
								<p id="shop_area" class="form-control-static"></p>
							</div>
						</div>
					</div>
					<div class="form-horizontal m-form-horizontal col-md-6 col-sm-6">	
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">送餐速度：</label>
							<div class="col-md-10 col-sm-9" style="position: relative;">
								<p class="form-control-static">
									<span id="shop_sendTime"></span>分钟
								</p>
								<a href="<?=DJADMIN_URL?>mshop/shop/edit/<?=$shop_id?>" class="btn btn-primary pull-right" style="position: absolute;right: 0;bottom: 0">编辑</a>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">起送价：</label>
							<div class="col-md-10 col-sm-9">
								<p class="form-control-static"><span id="shop_startPrice"></span>元</p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">配送费：</label>
							<div class="col-md-10 col-sm-9">
								<p class="form-control-static"><span id="shop_dispatchPrice"></span>元</p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">门店公告：</label>
							<div class="col-md-10 col-sm-9">
								<p class="form-control-static"><span id="shop_notice"></span></p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">自动接单：</label>
							<div class="col-md-10 col-sm-9">
								<div id="receiver" style="padding-top: 7px;"></div>
							</div>
						</div>
            <div class="form-group">
              <label class="control-label col-md-2 col-sm-3">自动打单：</label>
              <div class="col-md-10 col-sm-9">
                <div id="make_printer" style="padding-top: 7px;"></div>
              </div>
            </div>
            <div class="form-group">
							<label class="control-label col-md-2 col-sm-3">门店图片：</label>
							<div class="col-md-10 col-sm-9">
								<div id="logoTbody"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script id="logoTpl" type="text/html">
		<% if (list.length > 0) { %>
			<ul class="logo-list clearfix">
				<% for(var i = 0; i < list.length; i++) { %>
					<% if (list[i]) { %>
					<li class="logo-item logo-item-good">
						<img class="m-logo" src="<?=UPLOAD_URL?><%:=list[i]%>">
					</li>
					<% } %>
				<% } %>
			</ul>
		<% } %>
	</script>
	<?php $this->load->view('inc/global_footer'); ?>
	<?=static_original_url('djadmin/js/main.min.js');?>
	<?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
	<?=static_original_url('djadmin/mshop/js/shop_info.min.js');?>
</body>
</html>





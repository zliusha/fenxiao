<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>门店管理 - 微外卖</title>
	<?php $this->load->view('inc/global_header'); ?>
	<?=static_original_url('djadmin/mshop/css/shop.css');?>
</head>
<body>
	<input id="shop_limit" type="hidden" value="<?=$shop_limit?>">
	<div id="main">
		<div class="container-fluid">
			<ol class="breadcrumb">
				<li class="active">门店管理</li>
			</ol>
			<div class="main-body">
				<div class="main-body-inner">
					<div class="mb20">
						<a class="btn btn-primary" href="javascript:;" onclick="addShop()"><span class="iconfont icon-add"></span>添加门店</a>
					</div>
					<div id="shopCon">
						<table class="table">
							<thead>
								<tr>
									<th>门店ID</th>
									<th>门店名称</th>
									<th>联系方式</th>
									<th>门店服务</th>
									<th>操作</th>
								</tr>
							</thead>
							<tbody id="shopTbody">
								<tr>
									<td class="text-center" colspan="5">加载中...</td>
								</tr>
							</tbody>
						</table>
						<div id="shopPage" class="m-pager"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="mainShopModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">门店入驻</h4>
        </div>
        <div class="modal-body">
					<div class="clearfix mb20">
						<span class="pull-left" style="line-height: 36px;">选择现有门店：<small class="text-muted">门店管理中未入驻过的门店</small></span>
						<a class="btn btn-primary pull-right" href="<?=DJADMIN_URL?>mshop/shop/add"><span class="iconfont icon-add"></span>添加新门店</a>
					</div>
					<div id="mainShopCon">
						<table class="table">
							<thead>
								<tr>
									<th>门店名称</th>
									<th class="text-right">操作</th>
								</tr>
							</thead>
							<tbody id="mainShopTbody">
								<tr>
									<td class="text-center" colspan="2">加载中...</td>
								</tr>
							</tbody>
						</table>
					</div>
        </div>
      </div>
    </div>
  </div>
  <div id="shopLimitModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">温馨提示</h4>
        </div>
        <div class="modal-body">
					<div>当前版本只支持<?=$shop_limit?>个门店，如需添加更多门店，请联系客服升级版本。客服电话：<span class="text-primary">0571-26201018</span></div>
        </div>
				<div class="modal-footer" style="padding-top: 0;">
					<a class="btn btn-primary" href="javascript:;" data-dismiss="modal">确定</a>
				</div>
      </div>
    </div>
  </div>
	<div id="delShopModal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-sm" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
					<h4 class="modal-title">门店删除后不可恢复，确定要删除吗？</h4>
				</div>
				<div class="modal-footer">
					<a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
					<button type="button" class="btn btn-danger" id="del-confirm">删除</button>
				</div>
			</div>
		</div>
	</div>
	<script id="mainShopTpl" type="text/html">
		<% if (list.length > 0) { %>
			<% for(var i = 0; i < list.length; i++) { %>
				<tr>
					<td><%:=list[i].shop_name %></td>
					<td class="text-right"><a class="btn-link" href="<?=DJADMIN_URL?>mshop/shop/add?main_shop_id=<%:=list[i].id%>">选择</a></td>
				</tr>
			<% } %>
		<%} else {%>
			<tr>
				<td class="text-center" colspan="2">暂无数据</td>
			</tr>
		<% } %>
	</script>
	<script id="shopTpl" type="text/html">
		<% if (rows.length > 0) { %>
			<% for(var i = 0; i < rows.length; i++) { %>
				<tr>
					<td><%:=rows[i].id %></td>
					<td><%:=rows[i].shop_name %></td>
					<td><%:=rows[i].contact %></td>
					<td>
						<% if((+rows[i].type & 1) > 0) { %>
							<?php if(power_exists(module_enum::WM_MODULE,$this->service->power_keys)):?>
							<span class="label label-primary">外卖</span>
							<?php else:?>
							<span class="label label-default">外卖</span>
							<?php endif;?>
						<% } %>
						<% if((+rows[i].type & 2) > 0) { %>
							<?php if(power_exists(module_enum::MEAL_MODULE,$this->service->power_keys)):?>
							<span class="label label-primary">堂食</span>
							<?php else:?>
							<span class="label label-default">堂食</span>
							<?php endif;?>
						<% } %>
						<% if((+rows[i].type & 4) > 0) { %>
							<?php if(power_exists(module_enum::LS_MODULE,$this->service->power_keys)):?>
							<span class="label label-primary">零售</span>
							<?php else:?>
							<span class="label label-default">零售</span>
							<?php endif;?>
						<% } %>
					</td>
					<td class="lineEdit shop-type-box">
							<% if((+rows[i].type & 2) > 0) { %>
								<?php if(power_exists(module_enum::MEAL_MODULE,$this->service->power_keys)):?>
								<a class="btn-link" href="<?=DJADMIN_URL?>mshop/shop_area/table/<%:=rows[i].id%>">桌位管理</a>
								<a class="btn-link" href="<?=DJADMIN_URL?>mshop/decorate/saoma/<%:=rows[i].id%>">堂食装修</a>
								<?php endif;?>
							<% } %>
							<% if((+rows[i].type & 1) > 0) { %>
								<?php if(power_exists(module_enum::WM_MODULE,$this->service->power_keys)):?>
								<a class="btn-link" href="<?=DJADMIN_URL?>mshop/decorate/index/<%:=rows[i].id%>">外卖设置</a>
								<div class="dropdown inline-block ml10 mr10">
									<a class="btn-link" href="javascript:;" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">外卖预览</a>
									<div class="dropdown-menu dropdown-menu-right link-box" aria-labelledby="dLabel">
										<span>扫一扫访问门店</span>
										<p><img class="shop-qrcode" src="<?=DJADMIN_URL?>qr_api/index?s=6&d=<%:=encodeURIComponent(rows[i].qr_url)%>" alt="二维码"></p>
										<div class="form-inline">
											<div class="form-group">
												<label class="sr-only">门店链接</label>
												<input class="form-control" type="text" value="<%:=rows[i].qr_url%>" readonly>
											</div>
											<button class="btn btn-primary" onclick="copyUrl(this)">复制</button>
										</div>
									</div>
								</div>
								<?php endif;?>
							<% } %>
						<a class="btn-link" href="<?=DJADMIN_URL?>mshop/shop/edit/<%:=rows[i].id%>">编辑</a>
						<a class="btn-link btn-danger" href="javascript:;" onclick="delShop('<%:=rows[i].id %>')">删除</a>
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
	<?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
	<?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
	<?=static_original_url('djadmin/js/main.min.js');?>
	<?=static_original_url('djadmin/mshop/js/shop_list.min.js');?>
</body>
</html>

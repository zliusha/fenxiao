<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>编辑门店 - 微外卖</title>
	<?php $this->load->view('inc/global_header'); ?>
	<?=static_original_url('libs/datetimepicker/css/jquery.datetimepicker.css');?>
	<?=static_original_url('djadmin/mshop/css/shop.css');?>
	<style>
		.distance-item {
			width: 360px;
			line-height: 36px;
		}
	</style>
</head>
<body>
	<input id="shop_id" type="hidden" value="<?=$shop_id?>">
	<input id="is_admin" type="hidden" value="<?=$this->is_zongbu?>">
	<div id="main">
		<div class="container-fluid">
			<ol class="breadcrumb">
				<li><a href="javascript:;" onclick="Return()">门店管理</a></li>
				<li class="active">编辑门店</li>
			</ol>
			<div class="main-body">
				<div class="main-body-inner row">
					<form id="shop-form" class="form-horizontal m-form-horizontal">
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">门店名称：</label>
							<div class="col-md-10 col-sm-9">
								<input id="shop_name" class="form-control w360" type="text" name="shop_name" placeholder="门店名称不得超过30个字符">
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">门店logo：</label>
							<div class="col-md-10 col-sm-9">
								<div id="upload-logo-container" class="m-upload m-upload-logo-card">
									<span class="btn-plus upload-plus"></span>
									<img class="upload-pic" src="" alt="">
									<a class="upload-again" href="javascript:;" style="display: none;">重新上传</a>
									<input id="shop_logo" type="text" name="shop_logo" value="">
									<a id="upload-logo" class="upload-input" href="javascript:;"></a>
								</div>
								<p class="form-tips">建议尺寸400*400，大小不超过1M</p>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">联系方式：</label>
							<div class="col-md-10 col-sm-9">
								<input id="contact" class="form-control w360" type="text" name="contact" placeholder="请输入手机号码">
							</div>
						</div>
						<div class="form-group mb10">
							<label class="control-label col-md-2 col-sm-3">门店地址：</label>
							<div class="col-md-10 col-sm-9">
								<div id="shop_region" class="row" style="width: 380px;">
									<div class="col-md-4 col-sm-4 col-xs-4">
										<select id="shop_state" class="form-control" name="shop_state"></select>
									</div>
									<div class="col-md-4 col-sm-4 col-xs-4">
										<select id="shop_city" class="form-control" name="shop_city"></select>
									</div>
									<div class="col-md-4 col-sm-4 col-xs-4">
										<select id="shop_district" class="form-control" name="shop_district"></select>
									</div>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">&nbsp;</label>
							<div class="col-md-10 col-sm-9">
								<input id="shop_address" class="form-control w360" type="text" name="shop_address" placeholder="请输入详细地址">
							</div>
						</div>
						<div class="form-group" style="display: none">
							<label class="control-label col-md-2 col-sm-3">经纬度：</label>
							<div class="col-md-10 col-sm-9">
								<input id="shop_location" class="form-control 360" type="text" name="shop_location" readonly>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">&nbsp;</label>
							<div class="col-md-10 col-sm-9">
								<div id="map-container" style="width: 360px;height: 300px;"></div>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3">门店服务：</label>
							<div class="col-md-10 col-sm-9">
								<div class="shop-type-box">
									<?php if(power_exists(module_enum::WM_MODULE,$this->service->power_keys)):?>
				  				<label class="u-switch">
                    <input type="checkbox" name="service" value="1" onchange="changeService()">
                    <div class="u-switch-checkbox" data-on="开启外卖" data-off="关闭外卖"></div>
                  </label>
              		<?php endif;?>
              		<?php if(power_exists(module_enum::MEAL_MODULE,$this->service->power_keys)):?>
                  <label class="u-switch">
                    <input type="checkbox" name="service" value="2" onchange="changeService()">
                    <div class="u-switch-checkbox" data-on="开启堂食" data-off="关闭堂食"></div>
                  </label>
              		<?php endif;?>
              		<?php if(power_exists(module_enum::LS_MODULE,$this->service->power_keys)):?>
									<label class="u-switch">
                    <input type="checkbox" name="service" value="4" onchange="changeService()">
                    <div class="u-switch-checkbox" data-on="开启零售" data-off="关闭零售"></div>
                  </label>
              		<?php endif;?>
                </div>
							</div>
						</div>
						<div class="waimai-box" style="display: none;">
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">起送价：</label>
								<div class="col-md-10 col-sm-9">
									<div class="input-group w360">
										<input id="shop_startPrice" class="form-control" type="text" name="shop_startPrice">
										<span class="input-group-addon">元</span>
									</div>
								</div>
							</div>
							<div class="form-group mb10">
								<label class="control-label col-md-2 col-sm-3">配送费：</label>
								<div class="col-md-10 col-sm-9">
									<div class="distance-item clearfix">
										<span class="pull-left">配送距离 ≤ 1KM</span>
										<div class="pull-right">
											<span class="pull-left mr10">运费</span>
											<div class="input-group w148 pull-left">
												<input id="shipping_distance1" class="form-control" type="text" name="shipping_distance1">
												<span class="input-group-addon">元</span>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="form-group mb10">
								<label class="control-label col-md-2 col-sm-3">&nbsp;</label>
								<div class="col-md-10 col-sm-9">
									<div class="distance-item clearfix">
										<span class="pull-left">1KM < 配送距离 ≤ 3KM</span>
										<div class="pull-right">
											<span class="pull-left mr10">运费</span>
											<div class="input-group w148 pull-left">
												<input id="shipping_distance2" class="form-control" type="text" name="shipping_distance2">
												<span class="input-group-addon">元</span>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="form-group mb10">
								<label class="control-label col-md-2 col-sm-3">&nbsp;</label>
								<div class="col-md-10 col-sm-9">
									<div class="distance-item clearfix">
										<span class="pull-left">3KM < 配送距离 ≤ 5KM</span>
										<div class="pull-right">
											<span class="pull-left mr10">运费</span>
											<div class="input-group w148 pull-left">
												<input id="shipping_distance3" class="form-control" type="text" name="shipping_distance3">
												<span class="input-group-addon">元</span>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="form-group mb10">
								<label class="control-label col-md-2 col-sm-3">&nbsp;</label>
								<div class="col-md-10 col-sm-9">
									<div class="distance-item clearfix">
										<span class="pull-left">5KM < 配送距离</span>
										<div class="pull-right">
											<span class="pull-left mr10">运费</span>
											<div class="input-group w148 pull-left">
												<input id="shipping_distance4" class="form-control" type="text" name="shipping_distance4">
												<span class="input-group-addon">元</span>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">&nbsp;</label>
								<div class="col-md-10 col-sm-9">
									<label class="checkbox-inline pull-left mr10">
										<span class="u-checkbox">
											<input type="checkbox" name="dispatch" value="满">
											<span class="checkbox-icon"></span>
										</span>满
									</label>
									<div class="input-group w148 pull-left">
										<input id="full_dispatchPrice" class="form-control" type="text" name="full_dispatchPrice">
										<span class="input-group-addon">元</span>
									</div>
									<span class="pull-left ml20" style="line-height: 36px;">免配送费</span>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">服务半径：</label>
								<div class="col-md-10 col-sm-9">
									<div class="input-group w360">
										<input id="shop_radius" class="form-control" type="text" name="shop_radius" >
										<span class="input-group-addon">KM</span>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">预计送达时间：</label>
								<div class="col-md-10 col-sm-9">
									<div class="input-group w360">
										<input id="shop_sendTime" class="form-control" type="text" name="shop_sendTime" >
										<span class="input-group-addon">分钟</span>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">配送区域：</label>
								<div class="col-md-10 col-sm-9">
									<input id="shop_area" class="form-control w360" type="text" name="shop_area" >
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">备货时间：</label>
								<div class="col-md-10 col-sm-9">
									<div class="input-group w360">
										<input id="prepare_time" class="form-control" type="text" name="prepare_time" >
										<span class="input-group-addon">分钟</span>
									</div>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">营业时间：</label>
								<div class="col-md-10 col-sm-9">
									<div id="timeList"></div>
									<div><a class="btn btn-default" href="javascript:;" onclick="addTime()">增加时段</a></div>
								</div>
							</div>
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">门店图片：</label>
								<div class="col-md-10 col-sm-9">
									<div id="logoTbody"></div>
									<p class="help-block">建议尺寸400*400，大小不超过1M，最多3张</p>
								</div>
							</div>
	            <div class="form-group">
	              <label class="control-label col-md-2 col-sm-3">门店公告：</label>
	              <div class="col-md-10 col-sm-9">
	                <textarea id="notice" class="form-control w360" type="text" name="notice" cols="2" placeholder="门店公告不得超过140个字符"></textarea>
	              </div>
	            </div>
							<div class="form-group">
								<label class="control-label col-md-2 col-sm-3">自动接单：</label>
								<div class="col-md-10 col-sm-9">
									<div style="padding-top: 7px;">
										<label class="u-switch">
											<input type="checkbox" name="dispatch" value="自动接单" onclick="changeStatus(this, '0')" checked>
											<div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
										</label>
									</div>
								</div>
							</div>
	            <div class="form-group make_order" >
	              <label class="col-md-2 col-sm-3 control-label">自动打单：</label>
	              <div class="col-md-10 col-sm-9">
	                <div style="padding-top: 7px;">
	                  <label class="u-switch">
	                    <input type="checkbox" name="dispatch" value="自动打单" onclick="isMakeOrder(this, '0')" checked>
	                    <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
	                  </label>
	                </div>
	              </div>
	            </div>
            </div>
						<div class="form-group">
							<label class="control-label col-md-2 col-sm-3"></label>
							<div class="btn-box col-md-10 col-sm-9">
								<a class="btn btn-default" onclick="Return()">取消</a>
								<button id="btn-confirm" class="btn btn-primary">保存</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<script id="timeTpl" type="text/html">
		<% if (list.length > 0) { %>
			<% for(var i = 0; i < list.length; i++) { %>
				<div class="clearfix mb10 time-view">
					<div class="w157 pull-left">
						<input id="datetimeStart<%:=i %>" class="form-control" type="text" name="shop_fromTime<%:=i %>" value="<%:=list[i].startTime %>" >
					</div>
					<span class="pull-left ml20 mr20" style="line-height: 36px">-</span>
					<div class="w157 pull-left">
						<input id="datetimeEnd<%:=i %>" class="form-control" type="text" name="shop_endTime<%:=i %>" value="<%:=list[i].endTime %>" >
					</div>
          <div class="w157 pull-left">
            <a  class="btn btn-danger ml10" onclick="delTime(<%:=[i]%>)">删除</a>
          </div>
				</div>
			<% } %>
		<% } %>
	</script>
	<script id="logoTpl" type="text/html">
		<% if (list.length > 0) { %>
			<ul class="logo-list clearfix">
				<% for(var i = 0; i < list.length; i++) { %>
					<% if (list[i].pic) { %>
						<li class="logo-item logo-item-good">
							<a class="btn-delete" href="javascript:;" onclick="delLogo(this,<%:=i %>)"></a>
							<div id="upload-logos-container<%:=i %>" class="m-upload m-upload-good-card">
								<span class="upload-plus"></span>
								<img class="upload-pic" src="<?=UPLOAD_URL?><%:=list[i].pic %>" alt="">
								<a class="upload-again" href="javascript:;" style="display: none;">重新上传</a>
								<input id="shop_logo<%:=i %>" class="shop_logos" name="shop_logos" type="text"  value="<%:=list[i].pic %>">
								<a id="upload-logo<%:=i %>" class="upload-input upload-inputs" href="javascript:;"></a>
							</div>
						</li>
					<% } else {%>
						<li class="logo-item">
							<a class="btn-delete" href="javascript:;" onclick="delLogo(this,<%:=i %>)"></a>
							<div id="upload-logos-container<%:=i %>" class="m-upload m-upload-good-card">
								<span class="btn-plus upload-plus"></span>
								<img class="upload-pic" src="" alt="">
								<a class="upload-again" href="javascript:;" style="display: none;">重新上传</a>
								<input id="shop_logo<%:=i %>" class="shop_logos"  type="text"  value="<%:=list[i].pic %>">
								<a id="upload-logo<%:=i %>" class="upload-input upload-inputs" href="javascript:;"></a>
							</div>
						</li>
					<% } %>
				<% } %>
			</ul>
		<% } %>
	</script>
	<?php $this->load->view('inc/global_footer'); ?>
	<?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
	<?=static_original_url('libs/datetimepicker/js/jquery.datetimepicker.full.js');?>
	<?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
	<?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
	<?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
	<?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
	<?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
	<?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
	<script src="https://map.qq.com/api/js?v=2.exp"></script>
	<?=static_original_url('djadmin/js/areapicker.min.js');?>
	<?=static_original_url('djadmin/js/main.min.js');?>
	<?=static_original_url('djadmin/mshop/js/shop.min.js');?>
</body>
</html>

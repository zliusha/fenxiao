<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>桌位详情 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .user-info {
      margin-bottom: 10px;
    }
    .user-avatar {
      width: 40px;
      height: 40px;
      margin-right: 10px;
    }
    .user-time {
      margin-left: 60px;
    }
    .user-info .label {
      display: inline-block;
      margin-top: 9px;
    }
    .m-empty-box {
      padding: 40px 20px;
    }
  </style>
</head>
<body>
  <input id="shop" type="hidden" value="<?=$shop_id?>">
	<div id="main">
		<div class="container-fluid">
      <ol class="breadcrumb">
				<li><a href="<?=DJADMIN_URL?>mshop/shop_area/table/<?=$shop_id?>">桌位管理</a></li>
				<li class="active">桌位详情</li>
			</ol>
			<div class="main-body">
				<div class="main-body-inner">
          <div id="table-detail" class="table-detail">
            <div class="m-empty-box">
              <p>加载中...</p>
            </div>
          </div>
          <div id="order-detail" class="order-detail"></div>
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
	<div id="delTableModal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-sm" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
					<h4 class="modal-title">桌位删除后不可恢复，确定要删除吗？</h4>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
					<button type="button" class="btn btn-danger" id="del-confirm">删除</button>
				</div>
			</div>
		</div>
  </div>
  <div id="auditModal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-sm" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
					<h4 class="modal-title">审核订单</h4>
				</div>
				<div class="modal-footer" style="text-align: center;">
					<button type="button" class="btn btn-danger" id="audit-cancel">拒单</button>
					<button type="button" class="btn btn-primary" style="margin-left: 20px;" id="audit-confirm">接单</button>
				</div>
			</div>
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
  <script id="tableDetailTpl" type="text/html">
    <div class="row">
      <div class="col-xs-4 col-sm-4 col-md-4">
        <div style="width: 240px;" class="text-center">
          <img class="table-qrcode" src="<%:=table_info.qr_img%>" alt="<%:=table_info.name%>">
          <p>
            <% if(table_info.is_down == '1') { %>
              <span class="text-danger">已下载</span>
            <% } else { %>
              <span class="text-danger">未下载</span>
            <% } %>
          </p>
          <div class="mt10">
            <a class="btn btn-primary" href="javascript:;" onclick="downloadQrcode('<%:=table_info.id%>')">下载二维码</a>
            <a class="btn btn-primary ml10" href="javascript:;" onclick="refreshQrcode('<%:=table_info.id%>')">刷新二维码</a>
          </div>
        </div>
      </div>
      <div class="col-xs-6 col-sm-6 col-md-6 order-detail">
        <dl class="dl-horizontal mt40">
          <dt>桌位名称：</dt>
          <dd><%:=table_info.name%></dd>
        </dl>
        <dl class="dl-horizontal mt20">
          <dt>用餐区域：</dt>
          <dd><%:=table_info.shop_area_name%></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>建议人数：</dt>  
          <dd><span><%:=table_info.number%></span>人</dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>桌位状态：</dt>  
          <dd>
            <% if(table_info.status == '0') { %>
              <span class="text-dark">空桌</span>
            <% } else if(table_info.status == '1') { %>
              <span class="text-dark">点餐中</span>
            <% } else if(table_info.status == '2') { %>
              <span class="text-dark">就餐中</span>
            <% } else if(table_info.status == '3') { %>
              <span class="text-dark">已结算</span>
            <% } else { %>
              <span class="text-dark">未知状态</span>
            <% } %>
          </dd>
        </dl>
        <div class="mt10">
        <a class="btn btn-primary" href="javascript:;" onclick="editTable('<%:=table_info.id%>', '<%:=table_info.shop_area_id%>', '<%:=table_info.name%>', '<%:=table_info.number%>')"><span class="iconfont icon-edit"></span>编辑桌位</a>
          <% if(table_info.status == '0') { %>
            <a class="btn btn-danger ml10" href="javascript:;" onclick="delTable('<%:=table_info.id%>')"><span class="iconfont icon-delete"></span>删除桌位</a>
          <% } %>
        </div>
      </div>
    </div>
    <p class="text-muted mt10">如果您店内的桌位二维码存在被人拍走进行恶意点菜骚扰的情况，您可以刷新二维码，并打印贴到对应的餐桌上。</p>
    <% if(order_info && table_info.status != '0') { %>
      <hr style="margin-bottom: 0;">
      <h3 class="main-title">订单信息</h3>
      <div class="order-detail mb20">
        <dl class="dl-horizontal">
          <dt>开单时间：</dt>
          <dd>
            <span><%:=order_info.start_time%></span>
            <span class="text-danger" style="font-size: 16px;margin-left: 200px;">共 <%:=order_info.done_order.total_num + order_info.complete_order.total_num%> 件，合计：￥<%:=parseFloat(order_info.done_order.total_money + order_info.complete_order.total_money).toFixed(2)%></span>
          </dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>用餐人数：</dt>  
          <dd><%:=order_info.order_table.number%>人</dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>备注：</dt>
          <dd><%:=order_info.order_table.remark%></dd>
        </dl>
      </div>
      <% if(order_info.audit_order && order_info.audit_order.list.length > 0) { %>
        <% for(var i = 0, l = order_info.audit_order.list.length; i < l; i++) { %>
          <div class="user-info clearfix">
            <div class="pull-left">
              <img class="user-avatar" src="<%:=order_info.audit_order.list[i].headimg%>">
              <span class="user-nick"><%:=order_info.audit_order.list[i].nickname%></span>
              <span class="user-time">下单时间：<%:=order_info.audit_order.list[i].time.alias%></span>
            </div>
            <div class="pull-right">
              <a class="btn btn-primary btn-sm mr10" href="javascript:;" onclick="auditOrder('<%:=order_info.audit_order.list[i].tid%>', '<%:=order_info.audit_order.list[i].order_table_id%>')">审核</a>
              <span class="label label-warning"><%:=order_info.audit_order.list[i].status.alias%></span>
            </div>
          </div>
          <% if(order_info.audit_order.list[i].order_ext && order_info.audit_order.list[i].order_ext.length > 0) { %>
            <div class="table-con table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>商品名称</th>
                    <th>单价</th>
                    <th>数量</th>
                    <th>小计</th>
                  </tr>
                </thead>
                <tbody>
                  <% for(var j = 0, k = order_info.audit_order.list[i].order_ext.length; j < k; j++) { %>
                    <tr>
                      <td><%:=order_info.audit_order.list[i].order_ext[j].goods_title%></td>
                      <td>￥<%:=order_info.audit_order.list[i].order_ext[j].price%></td>
                      <td><%:=order_info.audit_order.list[i].order_ext[j].num%></td>
                      <td>￥<%:=order_info.audit_order.list[i].order_ext[j].order_money%></td>
                    </tr>
                  <% } %>
                </tbody>
              </table>
            </div>
          <% } %>
        <% } %>
      <% } %>
      <% if(order_info.cancel_order && order_info.cancel_order.list.length > 0) { %>
        <% for(var i = 0, l = order_info.cancel_order.list.length; i < l; i++) { %>
          <div class="user-info clearfix">
            <div class="pull-left">
              <img class="user-avatar" src="<%:=order_info.cancel_order.list[i].headimg%>">
              <span class="user-nick"><%:=order_info.cancel_order.list[i].nickname%></span>
              <span class="user-time">下单时间：<%:=order_info.cancel_order.list[i].time.alias%></span>
            </div>
            <div class="pull-right">
              <span class="label label-danger"><%:=order_info.cancel_order.list[i].status.alias%></span>
            </div>
          </div>
          <% if(order_info.cancel_order.list[i].order_ext && order_info.cancel_order.list[i].order_ext.length > 0) { %>
            <div class="table-con table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>商品名称</th>
                    <th>单价</th>
                    <th>数量</th>
                    <th>小计</th>
                  </tr>
                </thead>
                <tbody>
                  <% for(var j = 0, k = order_info.cancel_order.list[i].order_ext.length; j < k; j++) { %>
                    <tr>
                      <td><%:=order_info.cancel_order.list[i].order_ext[j].goods_title%></td>
                      <td>￥<%:=order_info.cancel_order.list[i].order_ext[j].price%></td>
                      <td><%:=order_info.cancel_order.list[i].order_ext[j].num%></td>
                      <td>￥<%:=order_info.cancel_order.list[i].order_ext[j].order_money%></td>
                    </tr>
                  <% } %>
                </tbody>
              </table>
            </div>
          <% } %>
        <% } %>
      <% } %>
      <% if(order_info.done_order && order_info.done_order.list.length > 0) { %>
        <% for(var i = 0, l = order_info.done_order.list.length; i < l; i++) { %>
          <div class="user-info clearfix">
            <div class="pull-left">
              <img class="user-avatar" src="<%:=order_info.done_order.list[i].headimg%>">
              <span class="user-nick"><%:=order_info.done_order.list[i].nickname%></span>
              <span class="user-time">下单时间：<%:=order_info.done_order.list[i].time.alias%></span>
            </div>
            <div class="pull-right">
              <span class="label label-success"><%:=order_info.done_order.list[i].status.alias%></span>
            </div>
          </div>
          <% if(order_info.done_order.list[i].order_ext && order_info.done_order.list[i].order_ext.length > 0) { %>
            <div class="table-con table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>商品名称</th>
                    <th>单价</th>
                    <th>数量</th>
                    <th>小计</th>
                  </tr>
                </thead>
                <tbody>
                  <% for(var j = 0, k = order_info.done_order.list[i].order_ext.length; j < k; j++) { %>
                    <tr>
                      <td><%:=order_info.done_order.list[i].order_ext[j].goods_title%></td>
                      <td>￥<%:=order_info.done_order.list[i].order_ext[j].price%></td>
                      <td><%:=order_info.done_order.list[i].order_ext[j].num%></td>
                      <td>￥<%:=order_info.done_order.list[i].order_ext[j].order_money%></td>
                    </tr>
                  <% } %>
                </tbody>
              </table>
            </div>
          <% } %>
        <% } %>
      <% } %>
      <% if(order_info.complete_order && order_info.complete_order.list.length > 0) { %>
        <% for(var i = 0, l = order_info.complete_order.list.length; i < l; i++) { %>
          <div class="user-info clearfix">
            <div class="pull-left">
              <img class="user-avatar" src="<%:=order_info.complete_order.list[i].headimg%>">
              <span class="user-nick"><%:=order_info.complete_order.list[i].nickname%></span>
              <span class="user-time">下单时间：<%:=order_info.complete_order.list[i].time.alias%></span>
            </div>
            <div class="pull-right">
              <span class="label label-success"><%:=order_info.complete_order.list[i].status.alias%></span>
            </div>
          </div>
          <% if(order_info.complete_order.list[i].order_ext && order_info.complete_order.list[i].order_ext.length > 0) { %>
            <div class="table-con table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>商品名称</th>
                    <th>单价</th>
                    <th>数量</th>
                    <th>小计</th>
                  </tr>
                </thead>
                <tbody>
                  <% for(var j = 0, k = order_info.complete_order.list[i].order_ext.length; j < k; j++) { %>
                    <tr>
                      <td><%:=order_info.complete_order.list[i].order_ext[j].goods_title%></td>
                      <td>￥<%:=order_info.complete_order.list[i].order_ext[j].price%></td>
                      <td><%:=order_info.complete_order.list[i].order_ext[j].num%></td>
                      <td>￥<%:=order_info.complete_order.list[i].order_ext[j].order_money%></td>
                    </tr>
                  <% } %>
                </tbody>
              </table>
            </div>
          <% } %>
        <% } %>
      <% } %>
    <% } %>
  </script>
	<?php $this->load->view('inc/global_footer'); ?>
	<?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
	<?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
	<?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/shop_area_table_detail.min.js');?>
</body>
</html>

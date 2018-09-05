<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>区域管理 - 微外卖</title>
	<?php $this->load->view('inc/global_header'); ?>
</head>
<body>
	<div id="main">
    <input id="shop" type="hidden" value="<?=$shop_id?>">
		<div class="container-fluid">
			<?php $this->load->view('inc/nav_shop_area');?>
			<div class="main-body">
				<div class="main-body-inner">
          <div class="mb20">
            <a href="javascript:;" class="btn btn-primary" onclick="addArea()"><span class="iconfont icon-add"></span>添加区域</a> 
          </div>
					<div id="areaCon" class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th>区域名称</th>
									<th>操作</th>
								</tr>
							</thead>
							<tbody id="areaTbody">
								<tr>
									<td class="text-center" colspan="2">加载中...</td>
								</tr>
							</tbody>
						</table>
						<div id="areaPage" class="m-pager"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
  <div id="editAreaModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="area-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">添加 / 编辑区域</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-3 control-label">区域名称：</label>
              <div class="col-md-9"><input id="name" class="form-control" type="text" name="name" placeholder="请输入区域名称"></div>
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
	<div id="delAreaModal" class="modal fade" tabindex="-1" role="dialog">
		<div class="modal-dialog modal-sm" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
					<h4 class="modal-title">区域删除后不可恢复，确定要删除吗？</h4>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
					<button type="button" class="btn btn-danger" id="del-confirm">删除</button>
				</div>
			</div>
		</div>
	</div>
	<script id="areaTpl" type="text/html">
		<% if (rows.length > 0) { %>
			<% for(var i = 0; i < rows.length; i++) { %>
				<tr>
					<td><%:=rows[i].name%></td>
					<td>
						<a class="btn-link" href="javascript:;" onclick="editArea('<%:=rows[i].id%>', '<%:=rows[i].name%>')">编辑</a>
						<a class="btn-link btn-danger" href="javascript:;" onclick="delArea('<%:=rows[i].id%>')">删除</a>
					</td>
				</tr>
			<% } %>
		<%} else {%>
			<tr>
				<td class="text-center" colspan="2">暂无数据</td>
			</tr>
		<% } %>
	</script>
	<?php $this->load->view('inc/global_footer'); ?>
	<?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
	<?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
	<?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
	<?=static_original_url('djadmin/js/main.min.js');?>
	<?=static_original_url('djadmin/mshop/js/shop_area.min.js');?>
</body>
</html>

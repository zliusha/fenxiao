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
</head>
<body>
<div id="main">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="active">小程序版本</li>
    </ol>
    <div class="main-body">
      <div class="main-body-inner">
        <div class="clearfix mb20">
            <!--
          <div class="form-inline pull-left">
            <div class="form-group">
              <input id="searchVal" class="form-control searchVal" type="text" name="searchVal" placeholder="输入标题">
            </div>
            <a href="javascript:;" id="btn-search" class="btn btn-primary"><span class="iconfont icon-search"></span>查询</a>
          </div>
          -->
          <div class="btn-box pull-right">
            <a href="<?=ADMIN_URL?>wm_main_version/add" class="btn btn-primary"><span class="iconfont icon-add"></span>新增版本</a>
          </div>
        </div>
        <div id="shopCon">
          <table class="table">
            <thead>
            <tr>
              <th>ID</th>
              <th>设备类型</th>
                <th>版本号</th>
                <th>系统类型</th>
                <th>强制更新</th>
                <th>版本描述</th>
              <th>状态</th>
                <th>时间</th>
              <th>操作</th>
            </tr>
            </thead>
            <tbody id="ydbTbody">
              <tr>
                <td class="text-center" colspan="9">加载中...</td>
              </tr>
            </tbody>
          </table>
          <div id="ydbPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="delYdbModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
        <h4 class="modal-title">删除后不可恢复，确定要删除吗？</h4>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
        <button type="button" class="btn btn-danger" id="del-confirm">删除</button>
      </div>
    </div>
  </div>
</div>
<script id="ydbTpl" type="text/html">
  <% if(rows.length > 0) { %>
    <% for(var i = 0; i < rows.length; i++) { %>
      <tr>
        <td><%:= rows[i].id%></td>
          <td>
              <%:= rows[i].device_type%>
              <% if(rows[i].device_type == '0') { %>
              <span>T1</span>
              <% } else { %>
              <span>暂无</span>
              <% } %>
          </td>
          <td><%:= rows[i].version%></td>
          <td>
              <% if(rows[i].type == '1') { %>
              <span>ios</span>
              <% } else { %>
              <span>android</span>
              <% } %>
          </td>
          <td>
              <% if(rows[i].is_must == '1') { %>
              <span>是</span>
              <% } else { %>
              <span>否</span>
              <% } %>
          </td>
          <td><%:= rows[i].remark%></td>
        <td>
          <% if(rows[i].status == '1') { %>
          <span>发布</span>
          <% } else { %>
          <span>未发布</span>
          <% } %>
        </td>
          <td><%:= rows[i].time%></td>
        <td>
<!--          <a class="btn-link btn-primary" href="--><?//=ADMIN_URL?><!--wm_main_version/detail/<%:= rows[i].id%>">查看</a>-->
          <a class="btn-link btn-primary" href="<?=ADMIN_URL?>wm_main_version/edit/<%:= rows[i].id%>">编辑</a>
          <a class="btn-link btn-danger" href="javascript:;" onclick="delYdb(<%:= rows[i].id%>)">删除</a>
          <% if(rows[i].status == '1') { %>
          <a class="btn-link btn-primary" href="javascript:;" onclick="releaseNotice(<%:= rows[i].id%>,0)">取消发布</a>
          <% } else { %>
          <a class="btn-link btn-primary" href="javascript:;" onclick="releaseNotice(<%:= rows[i].id%>,1)">发布</a>
          <% } %>
        </td>
      </tr>
    <% } %>
  <% } else { %>
    <tr>
      <td class="m-empty-box" colspan="9">
        <p>暂无版本</p>
      </td>
    </tr>
  <% } %>
</script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('admin/js/main.min.js');?>
  <?=static_original_url('admin/vshop/js/ydb_version_list.js');?>
</body>
</html>
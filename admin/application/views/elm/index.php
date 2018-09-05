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
    <?php $this->load->view('inc/global_header'); ?>
    <?= static_original_url('admin/css/main.min.css'); ?>
    <?= static_original_url('libs/bootstrap-validator/2.0/css/bootstrapValidator.min.css'); ?>
</head>
<body>
<div id="main">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="active">商家查询</li>
        </ol>
        <div class="main-body-left">
            <div class="main-body-inner">
                <div class="mb20">
                    <div class="form-inline" style="display: inline-block;width: 100%">
                        <div class="form-group">
                            <input id="searchVal" class="form-control searchVal" type="text" name="searchVal"
                                   placeholder="请输入地址">
                        </div>
                        <a href="javascript:;" id="btn-search" class="btn btn-primary">
                            <span class="iconfont icon-search"></span>查询
                        </a>
                    </div>
                    <div class="btn-link btn-danger" style="padding-top: 10px">请不要多次爬取同一位置信息！请不要频繁开启爬虫！</div>
                </div>
                <div id="ele">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>地址</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody id="eleTbody">
                        <tr>
                            <td class="text-center" colspan="2">暂无信息</td>
                        </tr>
                        </tbody>
                    </table>
                    <div id="elePage" class="m-pager"></div>
                </div>
            </div>
        </div>
        <div class="main-body-right">
            <div id="shopCon">
                <table class="table">
                    <thead>
                    <tr>
                        <th>地址</th>
                        <th>爬虫状态</th>
                        <th>导出状态</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody id="crawlerTbody">
                    <tr>
                        <td class="text-center" colspan="4">暂无信息</td>
                    </tr>
                    </tbody>
                </table>
                <div id="CrawlerPage" class="m-pager"></div>
            </div>
        </div>
    </div>
</div>
<div id="changeShopModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
        <h4 class="modal-title">确定发起爬虫吗？</h4>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
        <button type="button" class="btn btn-danger" id="btn-confirm">确定</button>
      </div>
    </div>
  </div>
</div>
<script id="crawlerTpl" type="text/html">
    <% if(data.length > 0) { %>
    <% for(var i = 0; i < data.length; i++) { %>
    <tr>
        <td><%:= data[i].address%></td>
        <td>
            <% if(data[i].pc_status == '-1') { %>
          <span>失败</span>
          <% } else if(data[i].pc_status == '0') { %>
          <span>爬取中</span>
          <% } else if(data[i].pc_status == '1') { %>
          <span>爬取成功</span>
          <% } else { %>
          <span>使用其他爬虫</span>
          <% } %>
        </td>
        <td>
           <% if(data[i]. dc_status == '0') { %>
          <span>未导出</span>
          <% } else { %>
          <span>已导出</span>
          <% } %>
        </td>
        <td>
            <% if(data[i].pc_status == '1') { %>
                <a href="<?=ADMIN_URL?>elm_api/export_business_info?id=<%:= data[i].id%>" class="btn-link btn-primary">导出</a>
            <% } else if(data[i].pc_status == '2') { %>    
            <a href="<?=ADMIN_URL?>elm_api/export_business_info?id=<%:= data[i].connect_pc%>" class="btn-link btn-primary">导出</a>
            <% } else { %>
                <span>不可导出</span>
            <% } %>
        </td>
    </tr>
    <% } %>
    <% } else { %>
    <tr>
        <td class="text-center" colspan="4">
            暂无信息
        </td>
    </tr>
    <% } %>
</script>
<script id="eleTpl" type="text/html">
    <% if(data.length > 0) { %>
        <% for(var i = 0; i < data.length; i++) { %>
        <tr>
            <td><%:= data[i].name%></td>
            <td>
                <a href="javascript:;" class="btn-link btn-primary" onclick="changeShop('<%:= data[i].name%>', <%:= data[i].latitude%>, <%:=data[i].longitude%>)">选择</a>
            </td>
        </tr>
        <% } %>
    <% } else { %>
    <tr>
        <td class="text-center" colspan="2">
            暂无信息
        </td>
    </tr>
    <% } %>
</script>
<?php $this->load->view('inc/global_footer'); ?>
<?= static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
<?= static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
<?= static_original_url('libs/template_js/0.7.1/template.min.js'); ?>
<?= static_original_url('libs/laypage/1.3/laypage.min.js'); ?>
<?= static_original_url('admin/js/main.min.js'); ?>
<?= static_original_url('admin/vshop/js/elm.js'); ?>
</body>
</html>
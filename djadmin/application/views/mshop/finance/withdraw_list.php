<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>提现列表 - 挖到后台</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_finance');?>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="form-inline search-form mb20">
            <div class="form-group">
              <select id="shop" class="form-control" name="shop_id">
                <?php if($this->is_zongbu): ?>
                  <option value="">全部门店</option>
                <?php endif; ?>
                <?php if(!empty($shop_list)):?>
                  <?php foreach($shop_list as $shop):?>
                    <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                  <?php endforeach;?>
                <?php endif;?>
              </select>
            </div>
            <div class="form-group">
              <select id="status" class="form-control" name="status">
                <option value="">全部状态</option>
                <option value="0">未打款</option>
                <option value="1">已打款</option>
              </select>
            </div>
          </div>
          <div class="withdraw-con table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>门店</th>
                  <th>提现金额</th>
                  <th>申请日期</th>
                  <th>申请状态</th>
                  <?php if($this->is_zongbu): ?>
                    <th>操作</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody id="withdrawTbody">
                <tr>
                  <?php if($this->is_zongbu): ?>
                    <td class="text-center" colspan="5">加载中...</td>
                  <?php else:?>
                    <td class="text-center" colspan="4">加载中...</td>
                  <?php endif; ?>
                </tr>
              </tbody>          
            </table>
          </div>
          <div id="withdrawPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="remitModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">请确认是否已经通过线下打款给分店？</h4>
        </div>
        <div class="modal-footer">
          <a href="javascript:;" class="btn btn-default" data-dismiss="modal" aria-label="Close">还未打款</a>
          <button id="btn-remited" type="button" class="btn btn-primary">已经打款</button>
        </div>
      </div>
    </div>
  </div>
  <script id="withdrawTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].shop_name%></td>
          <td>￥<%:=rows[i].money%></td>
          <td><%:=rows[i].time%></td>
          <td>
            <% if(rows[i].status == '1') { %>
              <span class="label label-success">已打款</span>
            <% } else { %>
              <span class="label label-warning">未打款</span>
            <% } %>
          </td>
          <?php if($this->is_zongbu): ?>
            <td>
              <% if(rows[i].status == '1') { %>
                <span class="text-muted" style="cursor: not-allowed;">打款</span>
              <% } else { %>
                <a class="btn-link" href="javascript:;" onclick="openRemitModal('<%:=rows[i].id%>')">打款</a>
              <% } %>
            </td>
          <?php endif; ?>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <?php if($this->is_zongbu): ?>
          <td class="text-center" colspan="5">暂无数据</td>
        <?php else:?>
          <td class="text-center" colspan="4">暂无数据</td>
        <?php endif; ?>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/withdraw_list.js');?>
</body>
</html>

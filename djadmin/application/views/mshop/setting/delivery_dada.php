<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>达达配送 - 配送配置</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    #source_id {
      width: 180px;
    }

    .dl-horizontal-delivery {
      margin-bottom: 0;
    }

    .dl-horizontal-delivery .u-switch {
      margin-top: 0;
    }

    @media (min-width: 768px) {
      .dl-horizontal-delivery dt {
        float:left;
        width: 90px;
        clear: left;
        text-align: right;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .dl-horizontal-delivery dd {
        margin-left: 100px;
      }
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <?php $this->load->view('inc/nav_delivery');?>
          <div class="form-horizontal m-form-horizontal">
            <div class="form-group">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label" style="padding-top: 2px;">开启配送：</label>
                </dt>
                <dd>
                  <label class="u-switch">
                    <input id="status" type="checkbox" name="status">
                    <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
                  </label>
                </dd>
              </dl>
            </div>
            <div id="source-form-group" class="form-group" style="display: none;">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label">商户ID：</label>
                </dt>
                <dd>
                  <input id="source_id" class="form-control pull-left mr10" type="text" name="source_id" disabled>
                  <a id="edit-source" class="btn btn-primary pull-left" href="javascript:;">编辑</a>
                  <span id="source-btn-group" class="pull-left" style="display: none;">
                    <a id="cancel-source" class="btn btn-default" href="javascript:;">取消</a>
                    <a id="confirm-source" class="btn btn-primary ml10" href="javascript:;">保存</a>
                  </span>
                </dd>
              </dl>
            </div>
            <div id="shop-form-group" class="form-group" style="display: none;">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label">门店信息：</label>
                </dt>
                <dd>
                  <p><a class="btn btn-primary" href="javascript:;" onclick="editShop()">添加门店</a></p>
                  <table class="table">
                    <thead>
                      <tr>
                        <th>微商城门店</th>
                        <th>达达门店编号</th>
                        <th>订单配送区域</th>
                        <th>操作</th>
                      </tr>
                    </thead>
                    <tbody id="dadaShopTbody">
                      <tr>
                        <td class="text-center" colspan="4">加载中...</td>
                      </tr>
                    </tbody>
                  </table>
                  <div id="dadaShopPage" class="m-pager"></div>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="editShopModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="shop-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 id="shop-modal-title" class="modal-title">添加门店</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group">
              <label class="col-md-4 control-label">微商城门店：</label>
              <div class="col-md-8">
                <select id="shop_id" class="form-control" name="shop_id">
                  <option value="">请选择</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-4 control-label">达达门店编号：</label>
              <div class="col-md-8">
                <input id="shop_no" class="form-control" type="text" name="shop_no" placeholder="请输入达达门店编号">
              </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-4 control-label">订单配送区域：</label>
              <div class="col-md-8">
                <select id="city_code" class="form-control" name="city_code">
                  <option value="">请选择</option>
                </select>
              </div>
            </div>         
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="confirm-shop" class="btn btn-primary">确定</button>
        </div>
      </form>
    </div>
  </div>
  <script id="shopTpl" type="text/html">
    <option value="">请选择</option>
    <% if(list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <option value="<%:=list[i].id%>"><%:=list[i].shop_name%></option>
      <% } %>
    <% } %>
  </script>
  <script id="cityTpl" type="text/html">
    <option value="">请选择</option>
    <% if(rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <option value="<%:=rows[i].city_code%>"><%:=rows[i].city_name%></option>
      <% } %>
    <% } %>
  </script>
  <script id="dadaShopTpl" type="text/html">
    <% if(rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].shop_name%></td>
          <td><%:=rows[i].shop_no%></td>
          <td><%:=rows[i].city_name%></td>
          <td>
            <a class="btn-link" href="javascript:;" onclick="editShop('<%:=rows[i].shop_id%>', '<%:=rows[i].shop_no%>', '<%:=rows[i].city_code%>')">编辑</a>
          </td>
        </tr>
      <% } %>
    <% } else { %>
      <tr>
        <td class="text-center" colspan="4">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
  <?= static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?= static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?= static_original_url('djadmin/js/main.min.js'); ?>
  <?= static_original_url('djadmin/mshop/js/setting_delivery_dada.min.js'); ?>
</body>
</html>

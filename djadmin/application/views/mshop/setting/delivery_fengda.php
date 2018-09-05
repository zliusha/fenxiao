<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>风达配送 - 配送配置</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
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
            <div class="form-group delivery-con" style="display: none;">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label" style="padding-top: 0;">账户余额：</label>
                </dt>
                <dd class="pr">
                  <p><strong id="account-balance" style="font-size: 16px;">-.--</strong> 元</p>
                  <div><a id="btn-recharge" class="btn btn-primary" href="javascript:;">充值</a></div>
                </dd>
              </dl>
            </div>
            <div class="form-group delivery-con" style="display: none;">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label">账户明细：</label>
                </dt>
                <dd>
                  <table class="table">
                    <thead>
                      <tr>
                        <th>流水号</th>
                        <th>创建时间</th>
                        <th>类型</th>
                        <th>金额</th>
                      </tr>
                    </thead>
                    <tbody id="accountTbody">
                      <tr>
                        <td class="text-center" colspan="4">加载中...</td>
                      </tr>
                    </tbody>
                  </table>
                  <div id="accountPage" class="m-pager"></div>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="rechargeModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">账户充值</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group">
              <label class="col-md-3 control-label">充值方式：</label>
              <div class="col-md-9">
                <label class="radio-inline" >
                  <span class="u-radio">
                    <input type="radio" name="recharge_type" value="1" checked>
                    <span class="radio-icon"></span>
                  </span>微信
                </label>
                <label class="radio-inline" >
                  <span class="u-radio">
                    <input type="radio" name="recharge_type" value="2">
                    <span class="radio-icon"></span>
                  </span>支付宝
                </label>
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">充值金额：</label>
              <div class="col-md-9">
                <input id="recharge-money" class="form-control" type="text" placeholder="最小充值金额为1元" style="width: 240px;">
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">&nbsp;</label>
              <div class="col-md-9">
                <button id="confirm-recharge" class="btn btn-primary">去支付</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="payModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">充值二维码</h4>
        </div>
        <div class="modal-body">
          <div class="text-center">
            <div id="pay-type" style="margin-bottom: 5px;"></div>
            <div id="pay-amount" class="text-danger" style="font-size: 18px;"></div>
            <img id="pay-qrcode" src="" alt="" width="200" height="200">
          </div>
        </div>
      </div>
    </div>
  </div>
  <script id="accountTpl" type="text/html">
    <% if(rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].orderNum%></td>
          <td><%:=rows[i].createDate%></td>
          <td>
            <% if (rows[i].type == '1') { %>
              <span class="label label-success">充值</span>
            <% } else { %>
              <span class="label label-danger">扣减</span>
            <% } %>
          </td>
          <td>
            <% if (rows[i].type == '1') { %>
              <span class="text-success"><%:=rows[i].amount%></span>
            <% } else { %>
              <span class="text-danger"><%:=rows[i].amount%></span>
            <% } %>
          </td>
        </tr>
      <% } %>
    <% } else { %>
      <tr>
        <td class="text-center" colspan="4">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/setting_delivery_fengda.min.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>点我达配送 - 配送配置</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
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

    .recharge-action {
      position: absolute;
      right: 10px;
      top: 12px;
    }

    .alert-recharge {
      margin-bottom: 0;
      color: #F96768;
      background-color: #FEF0F0;
      border: none;
      border-radius: 0;
    }

    .alert-recharge ol {
      padding-left: 16px;
      margin-bottom: 0;
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
                  <p><strong id="account-balance" style="font-size: 16px;">*.**</strong>元</p>
                  <p class="text-danger">请保证余额在20元以上，以免影响发单</p>
                  <div class="recharge-action">
                    <a id="btn-recharge" class="btn btn-primary" href="javascript:;">充值</a>
                    <a id="btn-recharge-record" class="btn btn-default btn-outline ml10" href="javascript:;" style="padding: 7px 16px;">充值记录</a>
                  </div>
                </dd>
              </dl>
            </div>
          </div>
          <div class="delivery-con" style="display: none;">
            <div class="btn-group mb20" data-toggle="buttons">
              <label class="btn btn-default active">
                <input type="radio" name="tab-type" value="shop-info" autocomplete="off" checked>门店信息
              </label>
              <label class="btn btn-default">
                <input type="radio" name="tab-type" value="order-record" autocomplete="off">订单报表
              </label>
            </div>
            <div id="shop-info" class="tab-content">
              <p><a class="btn btn-primary" href="javascript:;" onclick="editShop()">添加门店</a></p>
              <table class="table">
                <thead>
                  <tr>
                    <th>微商城门店</th>
                    <th>配送区域</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody id="dwdShopTbody">
                  <tr>
                    <td class="text-center" colspan="3">加载中...</td>
                  </tr>
                </tbody>
              </table>
              <div id="dwdShopPage" class="m-pager"></div>
            </div>
            <div id="order-record" class="tab-content" style="display: none;">
              <div class="clearfix mb20">
                <div class="form-inline search-form">
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
                    <div class="form-control-time">
                      <input id="create_time" class="form-control" type="text" name="create_time" placeholder="输入时间" readonly>
                      <span class="iconfont icon-rili"></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th>时间</th>
                      <th>门店</th>
                      <th>发布订单量</th>
                      <th>完成订单量</th>
                      <th>取消订单量</th>
                      <th>运费账户消耗</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody id="orderReportTbody">
                    <tr>
                      <td class="text-center" colspan="7">加载中...</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div id="orderReportPage" class="m-pager"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="rechargeModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <form id="recharge-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">充值</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group">
              <label class="col-md-2 control-label">充值对象：</label>
              <div class="col-md-10">
                <p class="form-control-static">浙江云店宝科技有限公司</p>
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-2 control-label">充值金额：</label>
              <div class="col-md-10">
                <input id="recharge-money" class="form-control" type="text" placeholder="最小充值金额为1元" style="width: 200px;">
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-2 control-label">&nbsp;</label>
              <div class="col-md-10">
                <a id="confirm-recharge" class="btn btn-primary" href="javascript:;">下一步</a>
              </div>
            </div>
          </div>
          <div class="alert alert-danger alert-recharge" role="alert">
            <p>温馨提示：</p>
            <ol>
              <li>充值最小金额为分，即精确到小数点后2位。</li>
              <li>充值成功后，5分钟内转入账户。如有问题，请咨询客服。</li>
              <li>充值支持支付宝或者微信支付。</li>
              <li>充值成功后，请前往【系统设置】-【配送设置】查看账户余额。</li>
            </ol>
          </div>
        </div>
      </form>
    </div>
  </div>
  <div id="rechargeRecordModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">充值记录</h4>
        </div>
        <div class="modal-body">
          <ol style="padding-left: 16px;margin-top: -15px;margin-bottom: 20px;">
            <li>该页面主要显示账户余额及充值记录</li>
            <li>如需查看消费记录，请通过订单报表查看</li>
          </ol>
          <table class="table">
            <thead>
              <tr>
                <th>充值时间</th>
                <th>充值单号</th>
                <th>充值金额</th>
                <th>交易备注</th>
              </tr>
            </thead>
            <tbody id="rechargeRecordTbody">
              <tr>
                <td class="text-center" colspan="4">加载中...</td>
              </tr>
            </tbody>
          </table>
          <div id="rechargeRecordPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="rechargeResultModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">提示</h4>
        </div>
        <div class="modal-body">
          <p>请确认你的充值结果</p>
        </div>
        <div class="modal-footer" style="padding-top: 0;text-align: center;">
          <a id="btn-recharge-success" class="btn btn-primary mr10" href="javascript:;">支付成功</a>
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消支付</a>
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
  <script id="dwdShopTpl" type="text/html">
    <% if(rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].shop_name%></td>
          <td><%:=rows[i].city_name%></td>
          <td>
            <a class="btn-link" href="javascript:;" onclick="editShop('<%:=rows[i].shop_id%>', '<%:=rows[i].city_code%>')">编辑</a>
          </td>
        </tr>
      <% } %>
    <% } else { %>
      <tr>
        <td class="text-center" colspan="3">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="orderReportTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].date%></td>
          <td><%:=rows[i].shop_name%></td>
          <td><%:=rows[i].push_num%></td>
          <td><%:=rows[i].finish_num%></td>
          <td><%:=rows[i].cancel_num%></td>
          <td>￥<%:=rows[i].cost_amount%></td>
          <td>
            <a class="text-primary" href="javascript:;" onclick="downloadOrderDetail('<%:=rows[i].date%>', '<%:=rows[i].shop_id%>')">下载门店订单明细</a>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="7">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="rechargeRecordTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].time%></td>
          <td><%:=rows[i].code%></td>
          <td>￥<%:=rows[i].amount%></td>
          <td><%:=rows[i].remark%></td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="4">暂无记录</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?= static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?= static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
  <?= static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?= static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?= static_original_url('djadmin/js/main.min.js'); ?>
  <?= static_original_url('djadmin/mshop/js/setting_delivery_dianwoda.js'); ?>
</body>
</html>

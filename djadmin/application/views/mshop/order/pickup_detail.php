<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>订单详情 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <input id="tradeno" type="hidden" value="<?=$order_tid?>">
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li><a href="<?=DJADMIN_URL?>mshop/order/pickup">自提订单</a></li>
        <li class="active">订单详情</li>
      </ol>
      <div class="main-body">
        <div id="orderDetail" class="main-body-inner order-detail">
          <div class="m-empty-box">
            <p>加载中...</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="refundModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">退款申请</h4>
        </div>
        <div class="modal-body">
          <div id="refundCon" class="form-horizontal"></div>
        </div>
        <div id="refund-footer" class="modal-footer">
          <a id="btn-refuse" class="btn btn-default" href="javascript:;">拒绝</a>
          <button id="confirm-refund" class="btn btn-primary">同意</button>
        </div>
      </div>
    </div>
  </div>
  <div id="refuseModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <form id="refuse-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">拒绝退款</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-2 control-label">拒绝理由：</label>
              <div class="col-md-10">
                <textarea id="refuse_reason" class="form-control" name="refuse_reason" rows="3" placeholder="请输入拒绝理由"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="confirm-refuse" class="btn btn-primary">确定</button>
        </div>
      </form>
    </div>
  </div>
  <script id="orderDetailTpl" type="text/html">
    <h3 class="main-title" style="margin-top: -10px;">基本信息</h3>
    <hr>
    <dl class="dl-horizontal">
      <dt>订单状态：</dt>
      <dd>
        <% if(status.code == '1010' || status.code == '2040' || status.code == '2050') { %>
          <span class="label label-primary"><%:=status.alias %></span>
        <% } else if(status.code == '2020') { %>
          <span class="label label-info"><%:=status.alias %></span>
        <% } else if(status.code == '2030' || status.code == '2035' ||status.code == '2060' || status.code == '4020' || status.code == '4030' || status.code == '4040' || status.code == '4050' || status.code == '4060') { %>
          <span class="label label-warning"><%:=status.alias %></span>
        <% } else if(status.code == '6060' || status.code == '6061') { %>
          <span class="label label-success"><%:=status.alias %></span>
        <% } else { %>
          <span class="label label-danger"><%:=status.alias %></span>
        <% } %>
        <% if(afsno != '0') { %>
          <% if(is_afs_finished == '1') { %>
            <a class="ml10" href="javascript:;" onclick="refundOrder()">查看退款</a>
          <% } else { %>
            <a class="ml10" href="javascript:;" onclick="refundOrder()">查看退款申请</a>
          <% } %>
        <% } %>
      </dd>
    </dl>
    <% if(type == '1') { %>
      <dl class="dl-horizontal">
        <dt>预订人：</dt>
        <dd><%:=receiver_name%> <%:=receiver_phone%></dd>
        <dd><%:=receiver_site%> <%:=receiver_address%></dd>
      </dl>
      <dl class="dl-horizontal">
        <dt>骑手信息：</dt>
        <dd><%:=logistics_status%></dd>
        <% if(logistics_rider_name || logistics_rider_mobile) { %>
          <dd><%:=logistics_rider_name%> <%:=logistics_rider_mobile%></dd>
        <% } %>
      </dl>
      <dl class="dl-horizontal">
        <dt>送达时间：</dt>
        <dd><%:=update_time.alias%></dd>
      </dl>
    <% } else { %>
      <dl class="dl-horizontal">
        <dt>取货码：</dt>
        <dd><%:=logistics_code%></dd>
      </dl>
      <dl class="dl-horizontal">
        <dt>取货手机号：</dt>
        <dd><%:=logistics_detail && logistics_detail.pick_phone%></dd>
      </dl>
      <dl class="dl-horizontal">
        <dt>取货时间：</dt>
        <dd><%:=(logistics_detail && logistics_detail.pick_time) ? formatTime(logistics_detail.pick_time) : ''%></dd>
      </dl>
    <% } %>
    <dl class="dl-horizontal">
      <dt>配送方式：</dt>
      <dd><%:=logistics_type.alias%></dd>
    </dl>
    <dl class="dl-horizontal">
      <dt>下单时间：</dt>
      <dd><%:=time.alias%></dd>
    </dl>
    <dl class="dl-horizontal">
      <dt>订单编号：</dt>
      <dd><%:=tid%></dd>
    </dl>
    <dl class="dl-horizontal">
      <dt>订单备注：</dt>
      <dd><%:=remark ? remark : '暂无'%></dd>
    </dl>
    <h3 class="main-title">菜品信息</h3>
    <hr>
    <% if(type === '3') { %>
      <% if(order_ext.length > 0) { %>
        <% for(var i = 0; i < order_ext.length; i++) { %>
          <p style="color: #000;">订餐人：<%:=order_ext[i].pindan_user.nickname%></p>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>商品信息</th>
                  <th>单价</th>
                  <th>数量</th>
                  <th>小计</th>
                </tr>
              </thead>
              <tbody>
                <% for(var j = 0; j < order_ext[i].items.length; j++) { %>
                  <tr>
                    <td width="40%">
                      <div class="good-info">
                        <span class="good-title"><%:=order_ext[i].items[j].goods_title%>
                          <% if(order_ext[i].items[j].sku_str) { %>
                            <span class="text-muted"><%:=order_ext[i].items[j].sku_str%></span>
                          <% } %>
                          <% if(order_ext[i].items[j].pro_attr) { %>
                            <span class="text-muted">(<%:=order_ext[i].items[j].pro_attr%>)</span>
                          <% } %>
                        </span>
                      </div>
                    </td>
                    <td>
                      <% if(+order_ext[i].items[j].discount_type != 0) { %>
                        ￥<%:=order_ext[i].items[j].discount_price%><del class="ml10">￥<%:=order_ext[i].items[j].price%></del>
                      <% } else { %>
                        ￥<%:=order_ext[i].items[j].price%>
                      <% } %>
                    </td>
                    <td><%:=order_ext[i].items[j].num%></td>
                    <td>
                      ￥<%:=(parseFloat(order_ext[i].items[j].order_money) - parseFloat(order_ext[i].items[j].discount_money)).toFixed(2)%>
                    </td>
                  </tr>
                <% } %>
              </tbody>
            </table>
          </div>
        <% } %>
      <% } %>
    <% } else { %>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>商品信息</th>
            <th>单价</th>
            <th>数量</th>
            <th>小计</th>
          </tr>
        </thead>
        <tbody>
          <% if(order_ext.length > 0) { %>
            <% for(var i = 0; i < order_ext.length; i++) { %>
              <tr>
                <td width="40%">
                  <div class="good-info">
                    <span class="good-title"><%:=order_ext[i].goods_title%>
                      <% if(order_ext[i].sku_str) { %>
                        <span class="text-muted"><%:=order_ext[i].sku_str%></span>
                      <% } %>
                      <% if(order_ext[i].pro_attr) { %>
                        <span class="text-muted">(<%:=order_ext[i].pro_attr%>)</span>
                      <% } %>
                    </span>
                  </div>
                </td>
                <td>
                  <% if(+order_ext[i].discount_type != 0) { %>
                    ￥<%:=order_ext[i].discount_price%><del class="ml10">￥<%:=order_ext[i].price%></del>
                  <% } else { %>
                    ￥<%:=order_ext[i].price%>
                  <% } %>
                </td>
                <td><%:=order_ext[i].num%></td>
                <td>
                  ￥<%:=(parseFloat(order_ext[i].order_money) - parseFloat(order_ext[i].discount_money)).toFixed(2)%>
                </td>
              </tr>
            <% } %>
          <% } else { %>
            <tr>
              <td class="text-align" colspan="4">暂无商品</td>
            </tr>
          <% } %>
        </tbody>
      </table>
    </div>
    <% } %>
    <p class="text-right">共<%:=total_num%>件，合计：<span>￥<%:=parseFloat(ext_total_money).toFixed(2)%></span></p>
    <hr>
    <p class="text-muted">其他</p>
    <p class="clearfix">
      <span class="pull-left">餐盒费</span>
      <span class="pull-right">￥<%:=package_money%></span>
    </p>
    <p class="clearfix">
      <span class="pull-left">配送费</span>
      <span class="pull-right">￥<%:=freight_money%></span>
    </p>
    <hr>
    <% if(discount_detail && ((discount_detail.coupon && +discount_detail.coupon.amount > 0) || (discount_detail.card && +discount_detail.card.amount > 0) || (discount_detail.manjian && +discount_detail.manjian.reduce_price > 0) || +discount_detail.xinren > 0 || +discount_detail.huiyuan > 0)) { %>
      <p class="text-muted">优惠</p>
      <% if(discount_detail.coupon && +discount_detail.coupon.amount > 0) { %>
        <p class="clearfix">
          <span class="pull-left"><span class="label label-warning label-coupon mr10">券</span>优惠券</span>
          <span class="pull-right text-danger">-￥<%:=discount_detail.coupon.amount%></span>
        </p>
      <% } %>
      <% if(discount_detail.card && +discount_detail.card.amount > 0) { %>
        <p class="clearfix">
          <span class="pull-left"><span class="label label-warning label-coupon mr10">券</span>代金券</span>
          <span class="pull-right text-danger">-￥<%:=discount_detail.card.amount%></span>
        </p>
      <% } %>
      <% if(discount_detail.manjian && +discount_detail.manjian.reduce_price > 0) { %>
        <p class="clearfix">
          <span class="pull-left"><span class="label label-danger label-coupon mr10">减</span>在线支付立减</span>
          <span class="pull-right text-danger">-￥<%:=discount_detail.manjian.reduce_price%></span>
        </p>
      <% } %>
      <% if(+discount_detail.xinren > 0) { %>
        <p class="clearfix">
          <span class="pull-left"><span class="label label-success label-coupon mr10">新</span>门店新客立减</span>
          <span class="pull-right text-danger">-￥<%:=discount_detail.xinren%></span>
        </p>
      <% } %>
      <% if(+discount_detail.huiyuan > 0) { %>
        <p class="clearfix">
          <span class="pull-left"><span class="label label-warning label-coupon mr10">折</span>会员折扣</span>
          <span class="pull-right text-danger">-￥<%:=discount_detail.huiyuan%></span>
        </p>
      <% } %>
      <hr>
    <% } %>
    <p class="text-right">实付：<span class="text-danger" style="font-size: 20px;">￥<%:=pay_money%></span></p>
    <hr>
    <div class="order-footer-btn">
      <% if(status.code == '2020') { %>
        <a class="btn btn-primary" href="javascript:;" onclick="agreeOrder('<%:=tid%>')">接单</a>
        <a class="btn btn-default ml10" href="javascript:;" onclick="refuseOrder('<%:=tid%>')">拒接</a>
      <% } else if(status.code == '2030' || status.code == '2035') { %>
        <a class="btn btn-primary" href="javascript:;" onclick="confirmDelivered('<%:=tid%>')">确认取货</a>
      <% } else if(status.code == '2040') { %>
        <a class="btn btn-primary" href="javascript:;" onclick="printOrder('<%:=tid%>')">打印</a>
        <% if(logistics_type.value == '2' || logistics_type.value == '3' || logistics_type.value == '5' || logistics_type.value == '6') { %>
          <a class="btn btn-default ml10" href="javascript:;" onclick="rePushOrder('<%:=tid%>')">重新派单</a>
        <% } %>
        <a class="btn btn-primary ml10" href="javascript:;" onclick="turnSellerDelivery('<%:=tid%>')">商家派送</a>
      <% } %>
      <% if(afsno != '0') { %>
        <% if(is_afs_finished == '1') { %>
          <a class="btn btn-default" href="javascript:;" onclick="refundOrder()">查看退款</a>
        <% } else { %>
          <a class="btn btn-primary" href="javascript:;" onclick="refundOrder()">退款</a>
        <% } %>
      <% } %>
    </div>
  </script>
  <script id="refundTpl" type="text/html">
    <div class="form-group">
      <label class="col-md-2 control-label">退款状态：</label>
      <div class="col-md-10">
          <p class="form-control-static"><%:=status.alias%></p>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-2 control-label">退款类型：</label>
      <div class="col-md-10">
        <% if (type == '1') { %>
          <p class="form-control-static">全额退款</p>
        <% } else { %>
          <p class="form-control-static">部分退款</p>
        <% } %>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-2 control-label">退款金额：</label>
      <div class="col-md-10">
      <p class="form-control-static">￥<%:=tk_money%></p>
      </div>
    </div>
    <% if(afs_detail && type == '2') { %>
    <div class="form-group">
      <label class="col-md-2 control-label">退款商品：</label>
      <div class="col-md-10">
        <table class="table" style="margin-bottom: 0;">
          <tbody>
            <% for(var i = 0; i < JSON.parse(afs_detail).length; i++) { %>
              <tr>
                <td><%:=JSON.parse(afs_detail)[i].goods_title%></td>
                <td>x<%:=JSON.parse(afs_detail)[i].tk_quantity%></td>
                <td class="text-right">￥<%:=JSON.parse(afs_detail)[i].tk_money%></td>
              </tr>
            <% } %>
          </tbody>
        </table>
      </div>
    </div>
    <% } %>
    <div class="form-group" style="margin-bottom: 0;">
      <label class="col-md-2 control-label">退款理由：</label>
      <div class="col-md-10">
        <p class="form-control-static"><%:=reason%></p>
        <% if(remark) { %>
        <p class="form-control-static"><%:=remark%></p>
        <% } %>
      </div>
    </div>
    <% if(refuse_reason) { %>
    <div class="form-group" style="margin-bottom: 0;">
      <label class="col-md-2 control-label">拒绝理由：</label>
      <div class="col-md-10">
        <p class="form-control-static"><%:=refuse_reason%></p>
      </div>
    </div>
    <% } %>
  </script>
  <script id="orderPrintTpl" type="text/html">
    <div style="width: 94%;margin: 0;font-family: '宋体', Arial;font-weight: 400;font-size: 9pt;overflow: hidden;">
      <div style="margin: 5pt 0;text-align: center;font-size: 16pt;line-height: 1;margin-top: 8pt;"><span style="font-size: 8pt;">**</span>云店宝订单<span style="font-size: 7pt;">**</span></div>
      <div style="margin-bottom: 8pt;text-align: center;">* <%:=shop_name%> *</div>
      <div>下单时间：<%:=time.alias%></div>
      <div style="margin-top: 3pt;text-align: center;white-space: nowrap;overflow: hidden;">*****************************</div>
      <% if(remark) { %>
        <div style="font-size: 14pt;font-weight: 600;margin-top: 3pt;margin-bottom: 5pt;">
          备注：<%:=remark%>
        </div>
      <% } %>
      <div style="text-align: center;margin: 2pt 0;white-space: nowrap;overflow: hidden;">-------------1号口袋------------</div>
      <table style="width: 100%;font-size: 9pt;border-spacing: 0;border-collapse: collapse;" cellspacing="0">
        <% if(order_ext.length > 0) { %>
          <% for(var i = 0; i < order_ext.length; i++) { %>
            <tr>
              <td style="width: 60%;vertical-align: top;"><%:=order_ext[i].goods_title%>
                <% if(order_ext[i].sku_str) { %>
                  <span style="font-size: 8pt;"><%:=order_ext[i].sku_str%></span>
                <% } %>
                <% if(order_ext[i].pro_attr) { %>
                  <span style="font-size: 8pt;">(<%:=order_ext[i].pro_attr%>)</span>
                <% } %>
              </td>
              <td style="width: 15%;text-align: left;vertical-align: top;">x <%:=order_ext[i].num%></td>
              <td style="width: 25%;text-align: right;vertical-align: top;"><%:=(parseFloat(order_ext[i].order_money) - parseFloat(order_ext[i].discount_money)).toFixed(2)%></td>
            </tr>
          <% } %>
        <% } %>
      </table>
      <div style="text-align: center;margin: 2pt 0;white-space: nowrap;overflow: hidden;">--------------其它-------------</div>
      <table style="width: 100%;font-size: 9pt;border-spacing: 0;border-collapse: collapse;" cellspacing="0">
        <tr>
          <td style="width: 60%;">餐盒费</td>
          <td style="width: 40%;text-align: right;" colspan="2"><%:=package_money%></td>
        </tr>
        <tr>
          <td style="width: 60%;">配送费</td>
          <td style="width: 40%;text-align: right;" colspan="2"><%:=freight_money%></td>
        </tr>
        <% if(discount_detail && ((discount_detail.coupon && +discount_detail.coupon.amount > 0) || (discount_detail.card && +discount_detail.card.amount > 0) || (discount_detail.manjian && +discount_detail.manjian.reduce_price > 0) || +discount_detail.xinren > 0 || +discount_detail.huiyuan > 0)) { %>
          <% if(discount_detail.coupon && +discount_detail.coupon.amount > 0) { %>
            <tr>
              <td style="width: 60%;">优惠券</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.coupon.amount%></td>
            </tr>
          <% } %>
          <% if(discount_detail.card && +discount_detail.card.amount > 0) { %>
            <tr>
              <td style="width: 60%;">代金券</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.card.amount%></td>
            </tr>
          <% } %>
          <% if(discount_detail.manjian && +discount_detail.manjian.reduce_price > 0) { %>
            <tr>
              <td style="width: 60%;">在线支付立减</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.manjian.reduce_price%></td>
            </tr>
          <% } %>
          <% if(+discount_detail.xinren > 0) { %>
            <tr>
              <td style="width: 60%;">门店新客立减</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.xinren%></td>
            </tr>
          <% } %>
          <% if(+discount_detail.huiyuan > 0) { %>
            <tr>
              <td style="width: 60%;">会员折扣</td>
              <td style="width: 40%;text-align: right;" colspan="2">-￥<%:=discount_detail.huiyuan%></td>
            </tr>
          <% } %>
        <% } %>
      </table>
      <div style="margin: 2pt 0;text-align: center;white-space: nowrap;overflow: hidden;">*****************************</div>
      <div style="text-align: right;">（用户在线支付）<span style="font-size: 12pt;"><%:=pay_money%>元</span></div>
      <div style="text-align: center;margin: 0 0 2pt;white-space: nowrap;overflow: hidden;">--------------------------------------------</div>
      <div style="font-size: 15pt;">
        <%:=receiver_site%> <%:=receiver_address%>
      </div>
      <div style="margin-bottom: 15pt;font-size: 13pt;">
        <%:=receiver_phone%></br>
        <%:=receiver_name%>
      </div>
    </div>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/js/LodopFuncs.min.js');?>
  <?=static_original_url('djadmin/mshop/js/order_detail.min.js');?>
</body>
</html>

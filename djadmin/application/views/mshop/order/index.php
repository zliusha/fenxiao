<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>外卖订单 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_order');?>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="clearfix mb20">
            <div class="form-inline search-form pull-left">
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
                <span class="text-primary ml10 mr40" style="display: none;">营业中</span>
              </div>
              <div class="form-group">
                <select id="status" class="form-control" name="status">
                  <option value="">订单状态</option>
                  <?php foreach($map['status'] as $key => $row): ?>
                    <?php if(empty($key)) continue; ?>
                    <option value="<?=$row?>"><?=$key?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <div class="form-control-time">
                  <input id="create_time" class="form-control" type="text" name="create_time" placeholder="输入下单时间" readonly>
                  <span class="iconfont icon-rili"></span>
                </div>
              </div>
              <div class="form-group">
                <input id="tradeno" class="form-control" type="text" name="tradeno" placeholder="订单编号" style="width: 150px;">
              </div>
              <div class="form-group">
                <input id="name" class="form-control" type="text" name="name" placeholder="预订人姓名" style="width: 130px;">
              </div>
              <div class="form-group">
                <input id="phone" class="form-control" type="text" name="phone" placeholder="预订人手机号" style="width: 130px;">
              </div>
              <button id="btn-search" class="btn btn-primary"><span class="iconfont icon-search"></span>搜索</button>
            </div>
            <div class="pull-right">
              <a id="export-order" class="btn btn-primary" href="javascript:;" onclick="exportOrder()"><span class="iconfont icon-shangjia"></span>导出订单</a>
              <a id="btn-refresh" class="btn btn-primary ml10" href="javascript:;"><span class="iconfont icon-refresh"></span>刷新</a>
            </div>
          </div>
          <div class="order-con table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>订单号 / 门店</th>
                  <th>预订人</th>
                  <th>下单时间</th>
                  <th>订单状态</th>
                  <th>订单金额</th>
                  <th>配送方式</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="orderTbody">
                <tr>
                  <td class="text-center" colspan="7">加载中...</td>
                </tr>
              </tbody>          
            </table>
          </div>
          <div id="orderPage" class="m-pager"></div>
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
          <div id="refundCon" class="form-horizontal">
            <div class="m-empty-box">
              <p>加载中...</p>
            </div>
          </div>
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
  <script id="orderTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td>
            <p style="margin-bottom: 5px;"><%:=rows[i].tid %></p>
            <p><%:=rows[i].shop_name %></p>
          </td>
          <td>
            <p style="margin-bottom: 5px;"><%:=rows[i].receiver_name %> <%:=rows[i].receiver_phone %></p>
            <p><%:=rows[i].receiver_site %> <%:=rows[i].receiver_address%></p>
          </td>
          <td><%:=rows[i].time.alias %></td>
          <td>
            <% if(rows[i].status.code == '1010' || rows[i].status.code == '2040' || rows[i].status.code == '2050') { %>
              <span class="label label-primary"><%:=rows[i].status.alias %></span>
            <% } else if(rows[i].status.code == '2020') { %>
              <span class="label label-info"><%:=rows[i].status.alias %></span>
            <% } else if(rows[i].status.code == '2030' || rows[i].status.code == '2060' || rows[i].status.code == '4020' || rows[i].status.code == '4030' || rows[i].status.code == '4040' || rows[i].status.code == '4050' || rows[i].status.code == '4060') { %>
              <span class="label label-warning"><%:=rows[i].status.alias %></span>
            <% } else if(rows[i].status.code == '6060' || rows[i].status.code == '6061') { %>
              <span class="label label-success"><%:=rows[i].status.alias %></span>
            <% } else { %>
              <span class="label label-danger"><%:=rows[i].status.alias %></span>
            <% } %>
          </td>
          <td>￥<%:=rows[i].pay_money %></td>
          <td><%:=rows[i].logistics_type.alias %></td>
          <td>
            <a class="btn-link" href="<?=DJADMIN_URL?>mshop/order/detail/<%:=rows[i].tid %>">详情</a>
            <% if(rows[i].status.code == '2020') { %>
              <a class="btn-link" href="javascript:;" onclick="agreeOrder('<%:=rows[i].tid %>')">接单</a>
              <a class="btn-link" href="javascript:;" onclick="refuseOrder('<%:=rows[i].tid %>')">拒接</a>
            <% } else if(rows[i].status.code == '2030') { %>
              <a class="btn-link" href="javascript:;" onclick="confirmDelivered('<%:=rows[i].tid %>')">确认送达</a>
            <% } else if(rows[i].status.code == '2040') { %>
              <a class="btn-link" href="javascript:;" onclick="printOrder('<%:=rows[i].tid %>')">打印</a>
              <% if(rows[i].logistics_type.value == '2' || rows[i].logistics_type.value == '3' || rows[i].logistics_type.value == '5' || rows[i].logistics_type.value == '6') { %>
                <a class="btn-link" href="javascript:;" onclick="rePushOrder('<%:=rows[i].tid %>')">重新派单</a>
              <% } %>
              <a class="btn-link" href="javascript:;" onclick="turnSellerDelivery('<%:=rows[i].tid %>')">商家派送</a>
            <% } %>
            <% if(rows[i].afsno != '0') { %>
              <% if(rows[i].is_afs_finished == '1') { %>
                <a class="btn-link" href="javascript:;" onclick="refundOrder('<%:=rows[i].afsno %>', '<%:=rows[i].is_afs_finished %>')">查看退款</a>
              <% } else { %>
                <a class="btn-link" href="javascript:;" onclick="refundOrder('<%:=rows[i].afsno %>', '<%:=rows[i].is_afs_finished %>')">退款</a>
              <% } %>
            <% } %>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="7">暂无数据</td>
      </tr>
    <% } %>
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
  <?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/js/LodopFuncs.min.js');?>
  <?=static_original_url('djadmin/mshop/js/order_list.min.js');?>
</body>
</html>

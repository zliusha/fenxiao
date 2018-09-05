<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>营业统计 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
  <?=static_original_url('djadmin/mshop/css/statistics.min.css');?>
  <style>
    .m-tooltip-content {
      min-width: 230px;
      text-align: left;
    }
    .m-tooltip .icon-help {
      font-size: 16px;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_statistics');?>
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
              <?php $this->load->view('inc/select_service');?>
            </div>
            <div class="form-group">
              <div class="form-control-time">
                <input id="time" class="form-control" type="text" name="time" placeholder="输入时间" readonly style="width: 200px;">
                <span class="iconfont icon-rili"></span>
              </div>
            </div>
          </div>
          <div class="w-data-list">
            <div class="row">
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <div>营业额
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content">
                        <h3>说明</h3>
                        <p>订单实收金额</p>
                      </div>
                    </div>
                  </div>
                  <p class="w-data-num">￥<span id="pay_order_money">-.--</span></p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <div>优惠折扣
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content">
                        <h3>说明</h3>
                        <p>订单优惠抵扣金额，包括：满减、满折、优惠券、折扣等</p>
                      </div>
                    </div>
                  </div>
                  <p class="w-data-num">￥<span id="total_discount_money">-.--</span></p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <div>储值卡消费额
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content">
                        <h3>说明</h3>
                        <p>使用礼品卡或会员储值支付的订单金额</p>
                      </div>
                    </div>
                  </div>
                  <p class="w-data-num">￥<span id="member_order_money">-.--</span></p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <p></p>
                  <div>退款金额
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content" style="right: 0;">
                        <h3>说明</h3>
                        <p>订单退款成功的金额</p>
                      </div>
                    </div>
                  </div>
                  <p class="w-data-num">￥<span id="tk_order_money">-.--</span></p>
                </div>
              </div>
            </div>
          </div>
          <div class="w-data-list">
            <div class="row">
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <div>订单数
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content">
                        <h3>说明</h3>
                        <p>全部订单数</p>
                      </div>
                    </div>
                  </div>
                  <p id="total_count" class="w-data-num">--</p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <div>付款订单数
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content">
                        <h3>说明</h3>
                        <p>付款成功的订单数（堂食及零售一笔流水算一笔订单）</p>
                      </div>
                    </div>
                  </div>
                  <p id="pay_order_count" class="w-data-num">--</p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <div>退款订单数
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content">
                        <h3>说明</h3>
                        <p>发起退款的订单数（堂食及零售根据流水来算）</p>
                      </div>
                    </div>
                  </div>
                  <p id="tk_order_count" class="w-data-num">--</p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <div>无效订单数
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content" style="right: 0;">
                        <h3>说明</h3>
                        <p>配送异常、取消订单、商家拒接、全额退款</p>
                      </div>
                    </div>
                  </div>
                  <p id="invalid_order_count" class="w-data-num">--</p>
                </div>
              </div>
            </div>
          </div>
          <h3 class="main-title">七天趋势图</h3>
          <div id="trend-chart" class="mb30" style="width: 100%; height: 360px;"></div>
          <?php if($this->is_zongbu): ?>
            <h3 class="main-title clearfix">
              <span class="pull-left">门店排行</span>
              <div class="form-inline pull-left ml20">
                <div class="form-group">
                  <select id="rank_type" class="form-control">
                    <option value="1">营业额</option>
                    <option value="2">订单数</option>
                  </select>
                </div>
                <div class="form-group">
                  <div class="form-control-time">
                    <input id="rank_time" class="form-control" type="text" name="rank_time" placeholder="输入时间" readonly style="width: 200px;">
                    <span class="iconfont icon-rili"></span>
                  </div>
                </div>
              </div>
            </h3>
            <div id="tradeCon" class="mt20">
              <table class="table">
                <thead>
                  <tr>
                    <th>排名</th>
                    <th>门店</th>
                    <th>
                      <div class="sort-ctrl sort-up"><span id="type-txt">营业额</span></div>
                    </th>
                  </tr>
                </thead>
                <tbody id="tradeTbody">
                  <tr>
                    <td class="text-center" colspan="3">加载中...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <script id="tradeShopRankTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=i+1%></td>
          <td><%:=rows[i].shop_name%></td>
          <td>
            <% if(rank_type == 1) { %>
              <%:=rows[i].pay_order_money%>
            <% } else { %>
              <%:=rows[i].pay_order_count%>
            <% } %>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="3">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?=static_original_url('libs/echarts/3.7.1/echarts.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/statistics_trade.min.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>资金结算 - 挖到后台</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
  <style>
    #withdraw-num {
      position: absolute;
      display: none;
      margin-top: -9px;
      margin-left: -10px;
      vertical-align: top;
    }
    .withdraw-box {
      position: relative;
      display: inline-block;
      font-size: 14px;
    }
    .withdraw-form {
      position: absolute;
      top: 0;
      left: 50px;
      z-index: 9;
      display: none;
      width: 380px;
    }
    .dl-horizontal dt {
      width: 38px;
    }
    .dl-horizontal dd {
      margin-left: 50px;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_finance');?>
      <div class="main-body">
        <div class="main-body-inner">
          <dl class="dl-horizontal">
            <dt>提示:</dt>
            <dd>
              <ol class="text-muted" style="padding-left: 16px;margin-bottom: 0;">
                <li>本页面只统计门店非现金收支的订单流水；</li>
                <li>门店非现金收支的订单金额理论上都是到了总店的账户，门店需要向总店申请提现。</li>
              </ol>
            </dd>
          </dl>
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
          </div>
          <div class="row w-data-list pr">
            <?php if($this->is_zongbu): ?>
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <p>门店可提现余额</p>
                  <p class="w-data-num">￥<span id="account-money">-.--</span></p>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 w-data-item">
                <a href="<?=DJADMIN_URL?>mshop/finance/withdraw_list?status=0">
                  <p>提现中</p>
                  <p class="w-data-num">￥<span id="on-money">-.--</span></p>
                </a>
              </div>
            <?php else:?>
              <div class="col-md-3 col-sm-3 w-data-item">
                <div>
                  <p>可提现余额</p>
                  <div class="w-data-num">￥<span><?=$withdraw_money?></span>
                    <span class="text-primary ml10" style="font-size: 16px;position: absolute;margin-top: 7px;">提现中￥<span><?=$on_money?></span></span>
                  </div>
                  <div>
                    <div class="withdraw-box mt10">
                      <a id="btn-withdraw" class="btn btn-primary" href="javascript:;">提现</a>
                      <div id="withdraw-form" class="form-inline withdraw-form">
                        <div class="form-group">
                          <input id="withdraw-money" class="form-control" type="text" name="money" placeholder="可提现金额￥<?=$withdraw_money?>" max="<?=$withdraw_money?>">
                        </div>
                        <button id="confirm-withdraw" class="btn btn-primary">确定</button>
                        <button id="cancel-withdraw" class="btn">取消</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 col-sm-3 w-data-item" style="height: 106px;">
                <div>
                  <p>待结算余额</p>
                  <div class="w-data-num">￥<span><?=$settlement_money?></span></div>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <h3 class="main-title">余额流水<span class="text-muted ml10">(提现中及待结算不在此列表展示)</span></h3>
          <div class="clearfix">
            <div class="form-inline pull-left">
              <div class="form-group">
                <select id="type" class="form-control" name="type">
                  <option value="">全部类型</option>
                  <option value="1">收入</option>
                  <option value="0">支出</option>
                </select>
              </div>
              <div class="form-group">
                <div class="form-control-time">
                  <input id="time" class="form-control" type="text" name="time" placeholder="输入时间" readonly style="width: 200px;">
                  <span class="iconfont icon-rili"></span>
                </div>
              </div>
            </div>
            <a id="export-finance" class="btn btn-primary pull-right" href="javascript:;">导出流水</a>
          </div>
          <div class="mt20">
            <div class="finance-con table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>时间</th>
                    <th>类型</th>
                    <th>交易描述</th>
                    <th>金额</th>
                  </tr>
                </thead>
                <tbody id="financeTbody">
                  <tr>
                    <td class="text-center" colspan="4">加载中...</td>
                  </tr>
                </tbody>          
              </table>
            </div>
            <div id="financePage" class="m-pager"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script id="financeTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].time%></td>
          <td>
            <% if(rows[i].pay_type == '1') { %>
              收入
            <% } else { %>
              支出
            <% } %>
          </td>
          <td><%:=rows[i].des%></td>
          <td>￥<%:=rows[i].money%></td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="4">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/finance.min.js');?>
</body>
</html>

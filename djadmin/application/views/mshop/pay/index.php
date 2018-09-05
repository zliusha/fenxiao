<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>支付 - 微外卖</title>
	<?php $this->load->view('inc/global_header'); ?>
  <style>
    .header-logo {
      display: block;
      height: 50px;
    }

    .header-logo>img {
      margin-top: 14px;
    }

    .alert {
      color: #475669;
      border-radius: 0;
    }

    .alert .iconfont {
      margin-right: 10px;
      font-size: 18px;
      color: #45be89;
    }

    .alert-warning {
      background-color: #fefad7;
      border-color: #ffec35;
    }

    .main-title {
      height: 50px;
      margin: 0;
      font-size: 16px;
      color: #202d3d;
      line-height: 50px;
    }

    .pay-box {
      padding: 20px;
      margin-bottom: 20px;
      background-color: #fbfdff;
      border: 1px dashed #c0ccda;
    }

    .pay-money {
      font-size: 18px;
      color: #f96768;
    }

    .pay-type-box .radio-inline {
      padding-left: 24px;
    }

    .pay-type-box .radio-inline .u-radio {
      top: 10px;
    }
  </style>
</head>
<body class="has-header" style="background-color: #fff;">
  <header class="w-header">
    <nav class="navbar navbar-inverse">
      <div class="container">
        <a class="header-logo" href="javascript:;">
          <img src="<?=STATIC_URL?>djadmin/img/logo-ydb.png" alt="浙江云店宝科技有限公司">
        </a>
      </div>
    </nav>
  </header>
  <section id="main" class="w-content">
    <div class="container">
      <div class="alert alert-warning">
        <p><span class="iconfont icon-warning"></span>剩余支付时间<strong class="text-danger">12分12秒</strong>,逾期订单自动取消。</p>
      </div>
      <h3 class="main-title">支付信息</h3>
      <div class="pay-box">
        <p>开放平台商家ID：1299</p>
        <p>开放平台商家名称：把愚便当</p>
        <p style="margin-bottom: 0;">支付金额：<strong class="pay-money">1111</strong>元</p>
      </div>
      <h3 class="main-title">支付方式</h3>
      <div class="pay-type-box">
        <label class="radio-inline">
          <span class="u-radio">
            <input type="radio" name="pay_type" value="1" checked><span class="radio-icon"></span>
          </span>
          <img src="<?=STATIC_URL?>djadmin/img/alipay.png" alt="支付宝">
        </label>
        <label class="radio-inline">
          <span class="u-radio">
            <input type="radio" name="pay_type" value="1"><span class="radio-icon"></span>
          </span>
          <img src="<?=STATIC_URL?>djadmin/img/wxpay.png" alt="微信支付">
        </label>
      </div>
      <hr>
      <div>
        <a class="btn btn-primary" href="javascript:;" data-toggle="modal" data-target="#wxpayModal">确定支付</a>
      </div>
    </div>
  </section>
  <div id="wxpayModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">微信扫码支付</h4>
        </div>
        <div class="modal-body text-center">
          <h4>支付金额: <strong class="text-danger">1111</strong>元</h4>
          <p class="mt20 mb20"><img src="<?=STATIC_URL?>djadmin/img/ydb-t1-qrcode.png" alt=""></p>
          <p><span class="text-danger">10分10秒</span>后二维码过期</p>
        </div>
      </div>
    </div>
  </div>
	<?php $this->load->view('inc/global_footer'); ?>
	<?=static_original_url('djadmin/js/main.min.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>支付宝配置 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <h3 class="main-title" style="margin-top: -10px;">支付宝支付</h3>
          <p>暂时仅适用于当面付场景<br><span class="text-primary">注意：支付宝配置适用于所有门店，所有门店共同使用一个支付宝账号</span></p>
          <hr>
          <form id="pay-form" class="form-horizontal m-form-horizontal pay-form">
            <fieldset>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">应用ID：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="app_id" class="form-control w360" type="text" name="app_id" placeholder="输入应用ID">
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">密钥类型：</label>
                <div class="col-md-10 col-sm-9">
                  <label class="radio-inline" >
                    <span class="u-radio">
                      <input type="radio" name="sign_type" value="1">
                      <span class="radio-icon"></span>
                    </span>RSA
                  </label>
                  <label class="radio-inline" >
                    <span class="u-radio">
                      <input type="radio" name="sign_type" value="2">
                      <span class="radio-icon"></span>
                    </span>RSA2
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">支付宝公钥：</label>
                <div class="col-md-10 col-sm-9">
                  <textarea id="alipay_public_key" class="form-control w360" name="alipay_public_key" rows="6" placeholder="输入支付宝公钥"></textarea>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">商户私钥：</label>
                <div class="col-md-10 col-sm-9">
                  <textarea id="merchant_private_key" class="form-control w360" name="merchant_private_key" rows="10" placeholder="输入商户私钥"></textarea>
                </div>
              </div>
            </fieldset>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">&nbsp;</label>
              <div class="col-md-10 col-sm-9">
                <a id="btn-edit-pay" class="btn btn-primary" href="javascript:;" style="display: none;">编辑</a>
                <span id="pay-action-box">
                  <a id="btn-cancel-pay" class="btn btn-default" href="javascript:;">取消</a>
                  <button id="btn-save-pay" class="btn btn-primary ml10">保存</button>
                </span>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
  <?= static_original_url('djadmin/js/main.min.js'); ?>
  <?= static_original_url('djadmin/mshop/js/setting_alipay.min.js'); ?>
</body>
</html>

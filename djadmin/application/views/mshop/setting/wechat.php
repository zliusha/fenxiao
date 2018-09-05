<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>公众号配置 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .form-tip-group {
      width: 100%;
      padding: 10px 0;
      margin-bottom: 20px;
      background-color: #fbfdff;
      border: 1px dashed #c0ccda;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <h3 class="main-title" style="margin-top: -10px;">微信授权</h3>
          <hr>
          <form id="auth-form" class="form-horizontal m-form-horizontal pay-form">
            <div class="form-tip-group thirdparty-box" style="display: none">
              <div class="form-group" style="margin-bottom: 0;">
                <label class="col-md-2 col-sm-3 control-label">业务域名：</label>
                <div class="col-md-10 col-sm-9 pr">
                  https://<span id="yw-domain-url" class="form-control-static mr40"><?=$domain?></span>
                  <a class="btn btn-default btn-sm btn-copy" data-clipboard-action="copy" data-clipboard-target="#yw-domain-url">复制</a>
                </div>
              </div>
            </div>
            <div class="form-tip-group developer-box" style="display: none">
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">业务域名：</label>
                <div class="col-md-10 col-sm-9 pr">
                  https://<span id="yw-domain" class="form-control-static mr40"><?=$domain?></span>
                  <a class="btn btn-default btn-sm btn-copy" data-clipboard-action="copy" data-clipboard-target="#yw-domain">复制</a>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">JS接口安全域名：</label>
                <div class="col-md-10 col-sm-9 pr">
                  https://<span id="jk-domain" class="form-control-static mr40"><?=$domain?></span>
                  <a class="btn btn-default btn-sm btn-copy" data-clipboard-action="copy" data-clipboard-target="#jk-domain">复制</a>
                </div>
              </div>
              <div class="form-group" style="margin-bottom: 0;">
                <label class="col-md-2 col-sm-3 control-label">网页授权域名：</label>
                <div class="col-md-10 col-sm-9 pr">
                  https://<span id="sq-domain" class="form-control-static mr40"><?=$domain?></span>
                  <a class="btn btn-default btn-sm btn-copy" data-clipboard-action="copy" data-clipboard-target="#sq-domain">复制</a>
                </div>
              </div>
            </div>
            <fieldset>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">授权方式：</label>
                <div class="col-md-10 col-sm-9">
                  <label class="radio-inline" >
                    <span class="u-radio">
                      <input type="radio" name="login_type" value="1">
                      <span class="radio-icon"></span>
                    </span>第三方授权
                  </label>
                  <label class="radio-inline" >
                    <span class="u-radio">
                      <input type="radio" name="login_type" value="0">
                      <span class="radio-icon"></span>
                    </span>开发者授权
                  </label>
                </div>
              </div>
              <div class="thirdparty-box" style="display: none">
                <div class="form-group">
                  <label class="col-md-2 col-sm-3 control-label">公众号名称：</label>
                  <div class="col-md-10 col-sm-9">
                    <p id="app_nick_name" class="form-control-static">--</p>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-md-2 col-sm-3 control-label">AppID(公众号ID)：</label>
                  <div class="col-md-10 col-sm-9">
                    <p id="app_id_thirdparty" class="form-control-static">--</p>
                  </div>
                </div>
              </div>
              <div class="developer-box" style="display: none">
                <div class="form-group">
                  <label class="col-md-2 col-sm-3 control-label">AppID(公众号ID)：</label>
                  <div class="col-md-10 col-sm-9">
                    <input id="app_id" class="form-control w360" type="text" name="app_id" placeholder="请输入AppID">
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-md-2 col-sm-3 control-label">AppSecret(公众号密钥)：</label>
                  <div class="col-md-10 col-sm-9">
                    <input id="app_secret" class="form-control w360" type="text" name="app_secret" placeholder="请输入AppSecret">
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-md-2 col-sm-3 control-label">网页授权文件：</label>
                  <div id="path-container" class="col-md-10 col-sm-9 pr">
                    <a id="btn-verify-file" class="btn btn-default btn-sm" href="javascript:;">上传文件</a>
                    <span id="verify_file_name" class="ml10"></span>
                    <input id="verify_file_path" type="hidden">
                  </div>
                </div>
              </div>
            </fieldset>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">&nbsp;</label>
              <div class="col-md-10 col-sm-9">
                <a id="btn-edit-auth" class="btn btn-primary" href="javascript:;" style="display: none;">编辑</a>
                <span id="auth-action-box">
                  <a id="btn-cancel-auth" class="btn btn-default" href="javascript:;">取消</a>
                  <button id="btn-save-auth" class="btn btn-primary ml10">保存</button>
                </span>
                <!-- <a id="btn-auth-wechat" class="btn btn-default ml10" href="<?=$scrm_url?>" target="_blank" style="display: none;">授权公众号</a> -->
              </div>
            </div>
          </form>
          <h3 class="main-title">微信支付</h3>
          <p>同时适用于在线支付、当面付场景<br><span class="text-primary">注意：微信支付配置适用于所有门店，所有门店共同使用一个微信支付账号</span></p>
          <hr>
          <form id="pay-form" class="form-horizontal m-form-horizontal pay-form">
            <div class="form-tip-group">
              <div class="form-group" style="margin-bottom: 0;">
                <label class="col-md-2 col-sm-3 control-label">支付授权目录：</label>
                <div class="col-md-10 col-sm-9 pr">
                  https://<span id="sq-directory" class="form-control-static mr20"><?=$domain?>/order/</span>
                  <a class="btn btn-default btn-sm btn-copy" data-clipboard-action="copy" data-clipboard-target="#sq-directory">复制</a>
                </div>
              </div>
            </div>
            <fieldset>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">商户号：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="mch_id" class="form-control w360" type="text" name="mch_id" placeholder="输入商户号">
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">API密钥：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="key" class="form-control w360" type="text" name="key" placeholder="输入API密钥">
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
          <h3 class="main-title clearfix">
            <span class="pull-left">微信原路退款</span>
            <div class="pull-right">
              <label class="u-switch">
                <input type="checkbox" name="auto_refund">
                <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
              </label>
            </div>
          </h3>
          <form id="refund-form" class="form-horizontal m-form-horizontal pay-form" style="display: none;">
            <hr style="margin-top: 0;">
            <fieldset>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">apiclient_cert.pem(证书)：</label>
                <div class="col-md-10 col-sm-9 pr">
                  <a id="btn-apiclient-cert" class="btn btn-default btn-sm" href="javascript:;">上传证书</a>
                  <span id="apiclient_cert" class="ml10"></span>
                  <input id="apiclient_cert_path" type="hidden">
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">apiclient_key.pem(证书密钥)：</label>
                <div class="col-md-10 col-sm-9 pr">
                  <a id="btn-apiclient-key" class="btn btn-default btn-sm" href="javascript:;">上传密钥</a>
                  <span id="apiclient_key" class="ml10"></span>
                  <input id="apiclient_key_path" type="hidden">
                </div>
              </div>
            </fieldset>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">&nbsp;</label>
              <div class="col-md-10 col-sm-9">
                <a id="btn-edit-refund" class="btn btn-primary" href="javascript:;" style="display: none;">编辑</a>
                <span id="refund-action-box">
                  <a id="btn-cancel-refund" class="btn btn-default" href="javascript:;">取消</a>
                  <button id="btn-save-refund" class="btn btn-primary ml10">保存</button>
                </span>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <div id="authModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">选择第三方授权方式需要先授权公众号</h4>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <a id="btn-auth-wechat" class="btn btn-primary ml10" href="<?=$scrm_url?>" target="_blank">授权公众号</a>
        </div>
      </div>
    </div>
  </div>
  <div id="authResultModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">确定是否授权成功？</h4>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">授权失败</a>
          <a id="btn-auth-success" class="btn btn-primary ml10" href="javascript:;">授权成功</a>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
  <?= static_original_url('libs/clipboard/1.6.1/clipboard.min.js');?>
  <?= static_original_url('libs/plupload/2.3.1/moxie.js'); ?>
  <?= static_original_url('libs/plupload/2.3.1/plupload.full.min.js'); ?>
  <?= static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js'); ?>
  <?= static_original_url('libs/qiniu/1.0.21/qiniu.min.js'); ?>
  <?= static_original_url('djadmin/js/main.min.js'); ?>
  <?= static_original_url('djadmin/mshop/js/setting_wechat.min.js'); ?>
</body>
</html>

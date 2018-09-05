<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>点餐小程序授权 - 挖到后台</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('djadmin/mshop/css/xcx.min.css');?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <div class="main-body">
        <div class="main-body-inner">
          <h3 class="main-title" style="margin-top: -10px;">拥有微信小程序只需要以下三步：</h3>
          <div class="row xcx-stepper">
            <div class="col-md-4 col-sm-4 xcx-stepper-item xcx-stepper-item-auth">
              <img class="xcx-stepper-pic" src="<?= STATIC_URL ?>djadmin/img/xcx/xcx_1.png">
            </div>
            <div class="col-md-4 col-sm-4 xcx-stepper-item xcx-stepper-item-check">
              <img class="xcx-stepper-pic" src="<?= STATIC_URL ?>djadmin/img/xcx/xcx_2.png">
            </div>
            <div class="col-md-4 col-sm-4 xcx-stepper-item xcx-stepper-item-finish">
              <img class="xcx-stepper-pic" src="<?= STATIC_URL ?>djadmin/img/xcx/xcx_3.png">
            </div>
          </div>
          <div id="xcx-info"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="qrcodeModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">扫码体验小程序</h4>
        </div>
        <div class="modal-body text-center">
          <img id="experience-qrcode" src="" width="240" height="240">
        </div>
      </div>
    </div>
  </div>
  <div id="authModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">确定是否授权成功？</h4>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">授权失败</button>
          <button type="button" class="btn btn-primary" id="btn-confirm-auth">授权成功</button>
        </div>
      </div>
    </div>
  </div>
  <div id="generateModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">确定要生成/升级小程序吗？</h4>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="button" class="btn btn-primary" id="btn-confirm-generate">确定</button>
        </div>
      </div>
    </div>
  </div>
  <script id="xcxTpl" type="text/html">
    <% if (!is_authorized) { %>
      <div class="xcx-block">
        <h3 class="main-title">您还没有授权微信小程序，点击下方按钮授权。</h3>
        <p style="font-size: 16px;margin-top: -15px;margin-bottom: 20px;">如果已将小程序授权给其它第三方平台，请先前往微信小程序管理后台停止授权，再将全部权限授权给我们</p>
        <a class="btn btn-primary" href="<?=$scrm_url?>" target="_blank" onclick="authXcx()">授权小程序</a>
        <h3 class="main-title">如果您还没有注册微信小程序，点击下方按钮注册。注册成功后，再授权给云店宝即可</h3>
        <a class="btn btn-default" href="https://mp.weixin.qq.com/" target="_blank">注册小程序</a>
      </div>
    <% } %>
    <% if (xcx_server_info) { %>
      <div class="xcx-block">
        <h3 class="xcx-title">服务器小程序信息</h3>
        <dl class="dl-horizontal">
          <dt>最新版本：</dt>
          <dd><%:=xcx_server_info.user_version%></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>版本描述：</dt>
          <dd><%:=xcx_server_info.user_desc%></dd>
        </dl>
      </div>
    <% } %>
    <% if (!!info) { %>
      <div class="xcx-block">
        <h3 class="xcx-title">用户小程序信息</h3>
        <dl class="dl-horizontal">
          <dt>小程序名称：</dt>
          <dd><%:=app_nick_name%></dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>线上版本：</dt>
          <% if (info.last_success_version) { %>
            <dd><%:=info.last_success_version%></dd>
          <% } else { %>
            <dd>--</dd>
          <% } %>
        </dl>
        <dl class="dl-horizontal">
          <dt>更新时间：</dt>
          <dd>
            <% if (info.update_time) { %>
              <span><%:=formateTime(+info.update_time)%></span>
            <% } %>
          </dd>
        </dl>
        <dl class="dl-horizontal">
          <dt>更新版本：</dt>
          <dd>
            <% if (+info.audit_status == 0) { %>
              <p><%:=info.user_version%>版本<span class="ml10">微信审核通过</span></p>
            <% } else if (+info.audit_status == 1) { %>
              <p><%:=info.user_version%>版本<span class="ml10">微信审核失败</span></p>
            <% } else if (+info.audit_status == 2) { %>
              <p><%:=info.user_version%>版本<span class="ml10">正在等待微信审核</span></p>
              <p class="xcx-desc">微信审核通过后，你的小程序即可升级到最新版本。</p>
            <% } %>
          </dd>
        </dl>
        <% if (+info.audit_status == 1) { %>
          <dl class="dl-horizontal">
            <dt>失败原因：</dt>
            <dd><%:=info.audit_reason%></dd>
          </dl>
        <% } %>
      </div>
    <% } %>
    <% if (use_status == 1) { %>
      <div class="xcx-block">
        <h3 class="xcx-title">请先点击生成小程序按钮。</h3>
        <p><button class="btn btn-primary" onclick="generateXcx(this)">生成小程序</button></p>
      </div>
    <% } else if (use_status == 2) { %>
      <div class="xcx-block">
        <h3 class="xcx-title"><button class="btn btn-primary" onclick="generateXcx(this)">升级小程序</button></h3>
        <p class="xcx-desc mt20">小程序升级后，系统会自动将最新的小程序提交给微信</p>
      </div>
    <% } %>
    <% if (info && +info.audit_status == 2) { %>
      <div class="xcx-block">
        <h3 class="xcx-title"><button class="btn btn-primary" onclick="experienceXcx(this)">体验二维码</button><span class="text-muted ml10">小程序管理员可以直接扫码体验</span></h3>
      </div>
    <% } %>
    <% if (!!info) { %>
      <div class="xcx-block">
        <h3 class="xcx-title">小程序支付配置</h3>
        <p class="xcx-desc">正常情况下，请不要轻易修改小程序对应微信支付的商户号和商户密钥，配置不正确将导致小程序微信支付异常</h3>
        <form id="config-form" class="form-horizontal m-form-horizontal">
          <div class="form-group">
            <label class="col-md-2 col-sm-3 control-label">AppID(小程序ID)：</label>
            <div class="col-md-10 col-sm-9">
              <input id="app_id" class="form-control w360" type="text" name="app_id" placeholder="请输入appid" value="<%:=info.app_id%>" disabled>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 col-sm-3 control-label">AppSecret(小程序秘钥)：</label>
            <div class="col-md-10 col-sm-9">
              <input id="app_secreat" class="form-control w360" type="text" name="app_secreat" placeholder="请输入appsecrer" value="<%:=info.app_secreat%>">
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 col-sm-3 control-label">&nbsp;</label>
            <div class="col-md-10 col-sm-9">
              <button id="btn-save-config" class="btn btn-primary">保存</button>
            </div>
          </div>
        </form>
      </div>
      <div class="xcx-block">
        <h3 class="xcx-title">配置二维码规则</h3>
        <form id="verify-form" class="form-horizontal m-form-horizontal">
          <div class="form-group">
            <label class="col-md-2 col-sm-3 control-label">二维码规则：</label>
            <div class="col-md-10 col-sm-9 pr">
              <span id="verify-rule" class="form-control-static"><%:=domain + 'meal/'%></span>
              <a class="btn btn-default btn-sm btn-copy" data-clipboard-action="copy" data-clipboard-target="#verify-rule">复制</a>
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 col-sm-3 control-label">校验文件：</label>
            <div class="col-md-10 col-sm-9 pr">
              <a id="btn-upload-verify" class="btn btn-default btn-sm" href="javascript:;">上传文件</a><span id="verify_file_name" class="ml10"><%:=info.verify_file_name%></span>
              <input id="verify_file_path" type="hidden" name="verify_file_path" value="<%:=info.verify_file_path%>">
            </div>
          </div>
          <div class="form-group">
            <label class="col-md-2 col-sm-3 control-label">&nbsp;</label>
            <div class="col-md-10 col-sm-9">
              <button id="btn-save-verify" class="btn btn-primary">保存</button>
            </div>
          </div>
        </form>
      </div>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?= static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
  <?= static_original_url('libs/template_js/0.7.1/template.min.js'); ?>
  <?= static_original_url('libs/plupload/2.3.1/moxie.js'); ?>
  <?= static_original_url('libs/plupload/2.3.1/plupload.full.min.js'); ?>
  <?= static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js'); ?>
  <?= static_original_url('libs/qiniu/1.0.21/qiniu.min.js'); ?>
  <?= static_original_url('libs/clipboard/1.6.1/clipboard.min.js');?>
  <?= static_original_url('djadmin/js/main.min.js'); ?>
  <?= static_original_url('djadmin/mshop/js/xcx_meal.min.js'); ?>
</body>
</html>

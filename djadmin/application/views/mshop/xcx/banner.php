<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>外卖小程序装修 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('djadmin/mshop/css/decorate.min.css');?>
  <?=static_original_url('djadmin/mshop/css/xcx.min.css');?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_xcx');?>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="decorate-box">
            <div class="phone-box">
              <div class="phone-preview">
                <div class="phone-header">
                  <h2 id="phone-title" class="phone-header-title"></h2>
                </div>
                <div id="phone-body" class="phone-body">
                  <img class="xcx-user-list" src="<?=STATIC_URL?>djadmin/img/xcx/xcx_user_list.jpg">
                  <img id="xcx-banner" class="xcx-banner" src="<?=STATIC_URL?>djadmin/img/ydb-placeholder_750_528.png">
                  <img class="xcx-menu" src="<?=STATIC_URL?>djadmin/img/xcx/xcx_menu.jpg">
                </div>
              </div>
            </div>
            <div id="decorate-ctrl" class="shop-ctrl decorate-right" style="min-height: 645px;">
              <div class="ctrl-module form-horizontal">
                <div class="ctrl-module-box" style="padding-top: 15px">
                  <div class="form-group">
                    <label class="control-label col-md-3 col-sm-3">banner图：</label>
                    <div class="col-md-9 col-sm-9">
                      <div id="upload-banner-container" class="m-upload m-upload-banner">
                        <span id="upload-plus" class="btn-plus upload-plus"></span>
                        <img id="upload-pic" class="upload-pic" src="">
                        <a class="upload-again" href="javascript:;" style="display: none;">重新上传</a>
                        <input id="banner" type="hidden" name="banner" value="">
                        <input id="upload-banner" class="upload-input" type="file">
                      </div>
                      <p class="help-block">图片格式为jpg或png，建议尺寸750x528，大小不得超过1M</p>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="control-label col-md-3 col-sm-3">标题：</label>
                    <div class="col-md-9 col-sm-9">
                      <input id="title" class="form-control w360" type="text" name="title" disabled>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="control-label col-md-3 col-sm-3"></label>
                    <div class="btn-box col-md-9 col-sm-9">
                      <a href="javascript:;" id="btn-edit" class="btn btn-primary">编辑</a>
                      <span class="action-box" style="display: none;">
                        <a id="btn-cancel" class="btn btn-default" href="javascript:;">取消</a>
                        <button id="btn-save" class="btn btn-primary ml10">保存</button>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
  <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
  <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/xcx_decorate.min.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>商城设置 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <h3 class="main-title" style="margin-top: -10px;">分享设置</h3>
          <hr>
          <form id="setting-form" class="form-horizontal m-form-horizontal">
            <fieldset>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">分享图片：</label>
                <div class="col-md-10 col-sm-9">
                  <div id="upload-share-container" class="m-upload m-upload-share-card" style="width: 100px; height: 100px;line-height: 100px">
                    <span class="btn-plus upload-plus"></span>
                    <img class="upload-pic" src="" alt="">
                    <a class="upload-again" href="javascript:;" style="display: none;">重新上传</a>
                    <input id="share_img" type="hidden" name="share_img" value="">
                    <a id="upload-share" class="upload-input" href="javascript:;"></a>
                  </div>
                  <p class="form-tips">建议尺寸200*200</p>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">分享标题：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="share_title" class="form-control w360" name="share_title" placeholder="请输入分享标题" />
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">分享描述：</label>
                <div class="col-md-10 col-sm-9">
                  <textarea id="share_desc" class="form-control w360" name="share_desc" placeholder="最多60个字符" rows="2"></textarea>
                </div>
              </div>
            </fieldset>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">自定义域名：</label>
              <div class="col-md-10 col-sm-9">
                <div class="input-group w360">
                  <input id="domain" class="form-control" type="text" name="domain" placeholder="请输入自定义域名">
                  <span class="input-group-addon">.m.waimaishop.com</span>
                </div>
                <div class="form-inline" style="margin:-36px 0  0 370px;">
                  <div class="pr">
                    <label class="sr-only">域名链接</label>
                    <input id="full_domain" class="form-control" type="text" style="position: absolute;top:-99999px;" readonly>
                  </div>
                  <a class="btn btn-default" onclick="copyUrl(this)">复制</a>
                </div>
                <p class="form-tips">例如：yixiu.m.waimaishop.com  保存后不可修改</p>
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">&nbsp;</label>
              <div class="btn-box col-md-10 col-sm-9">
                <a id="btn-edit" class="btn btn-primary" href="javascript:;" style="display: none;">编辑</a>
                <span id="action-box">
                  <a id="btn-cancel" class="btn btn-default" href="javascript:;">取消</a>
                  <button id="btn-save" class="btn btn-primary ml10">保存</button>
                </span>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>           
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
  <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
  <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
  <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/setting.min.js');?>
</body>
</html>

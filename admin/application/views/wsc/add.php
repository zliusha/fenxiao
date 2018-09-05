<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>微商城 - 管理后台</title>
  <?php $this->load->view('inc/global_header');?>
  <?=static_original_url('admin/css/main.min.css');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/css/bootstrapValidator.min.css');?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li><a href="<?=ADMIN_URL?>wm_notice/index">首页</a></li>
        <li class="active">微商城公告</li>
      </ol>
      <div class="main-body">
        <div class="main-title-box">
          <h3 class="main-title">基本信息</h3>
        </div>
        <div class="main-body-inner">
          <div class="clearfix mb20">
          </div>
          <form id="wsc-form" class="form-horizontal board-form">
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label"><span class="text-danger">*</span>公告标题：</label>
              <div class="col-md-10 col-sm-9">
                <input id="wsc_title" class="form-control w360" type="text" name="wsc_title" placeholder="">
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">公告标签：</label>
              <div class="col-md-10 col-sm-9 serviceView">
                <label class="radio-inline" data-id="1">
                  <span class="u-radio">
                    <input type="radio" name="wsc_type" value="1" checked>
                    <span class="radio-icon"></span>
                  </span>新手上路
                </label>
                <label class="radio-inline" data-id="2">
                  <span class="u-radio">
                    <input type="radio" name="wsc_type" value="2">
                    <span class="radio-icon"></span>
                  </span>系统公告
                </label>
                <label class="radio-inline" data-id="3">
                  <span class="u-radio">
                    <input type="radio" name="wsc_type" value="3">
                    <span class="radio-icon"></span>
                  </span>产品动态
                </label>
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label"><span class="text-danger">*</span>内容设置：</label>
              <div class="btn-box col-md-10 col-sm-9">
                <script id="wscDetail" type="text/plain" style="height:360px;"></script>
                </div>
                </div>
                <div class="form-group">
                  <div class="col-md-2 col-sm-3">&nbsp;</div>
                <div class="btn-box col-md-10 col-sm-9">
                  <button id="btn-release" class="btn btn-primary">发布</button>
                  <button id="btn-confirm" class="btn btn-primary">保存</button>
                  <a href="<?=ADMIN_URL?>wsc_main_article/index" class="btn btn-default">取消</a>
                </div>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/chosen/1.7.0/chosen.jquery.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
  <?=static_original_url('libs/bootstrap-slider/js/bootstrap-slider.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
  <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
  <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('libs/jquery-tagsinput/1.3.3/js/jquery.tagsinput.min.js');?>
  <script src="<?=ADMIN_URL?>static/qiniu_ueditor_1.4.3/ueditor.config.js"></script>
  <script src="<?=ADMIN_URL?>static/qiniu_ueditor_1.4.3/ueditor.all.min.js"></script>
  <script src="<?=ADMIN_URL?>static/qiniu_ueditor_1.4.3/lang/zh-cn/zh-cn.js"></script> 
  <?=static_original_url('libs/cropbox/cropbox.js');?>
  <?=static_original_url('admin/js/main.min.js');?>
  <?=static_original_url('admin/vshop/js/wsc.js');?>
</body>
</html>

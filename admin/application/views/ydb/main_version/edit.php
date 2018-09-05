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
  <style>
    .w-data-list {
      padding: 20px 0;
    }
    .w-data-list .w-data-title {
      padding: 0 20px;
      margin-top: -10px;
      margin-bottom: 10px;
      border: none;
    }
    .w-data-block {
      min-height: 202px;
      padding: 20px;
      background-color: #fbfdff;
      border: 1px dashed #c0ccda;
    }
    .w-coupon-item {
      margin-bottom: 0;
      line-height: 24px;
    }
    .w-coupon-item+.w-coupon-item {
      margin-top: 10px;
    }
    .m-empty-box {
      margin-top: 40px;
    }
  </style>
</head>
<body>
  <div id="main">
  <input id="ydb_id" type="hidden" value="<?=$id?>">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li><a href="<?=ADMIN_URL?>wm_main_version/index">首页</a></li>
        <li class="active">小程序版本</li>
      </ol>
      <div class="main-body">
        <div class="main-title-box">
          <h3 class="main-title">基本信息</h3>
        </div>
        <div class="main-body-inner">
          <div class="clearfix mb20">
          </div>
          <form id="ydb-form" class="form-horizontal board-form">
              <div class="form-group">
                  <label class="col-md-2 col-sm-3 control-label"><span class="text-danger">*</span>设备类型：</label>
                  <div class="col-md-3 col-sm-9">
                      <select id="device_type" class="form-control  w360" name="device_type" >
                          <option value="0">T1</option>
                      </select>
                  </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label"><span class="text-danger">*</span>系统类型：</label>
                <div class="col-md-3 col-sm-9">
                  <label class="radio-inline">
                    <span class="u-radio">
                      <input type="radio" name="type" value="0" checked="">
                      <span class="radio-icon"></span>
                    </span>安卓
                  </label>
                  <label class="radio-inline">
                    <span class="u-radio">
                      <input type="radio" name="type" value="1">
                      <span class="radio-icon"></span>
                    </span>ios
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label"><span class="text-danger">*</span>是否强制更新：</label>
                <div class="col-md-3 col-sm-9">
                  <label class="radio-inline">
                    <span class="u-radio">
                      <input type="radio" name="is_must" value="0" checked="">
                      <span class="radio-icon"></span>
                    </span>否
                  </label>
                  <label class="radio-inline">
                    <span class="u-radio">
                      <input type="radio" name="is_must" value="1">
                      <span class="radio-icon"></span>
                    </span>是
                  </label>
                </div>
              </div>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label"><span class="text-danger">*</span>版本号：</label>
              <div class="btn-box col-md-10 col-sm-9">
                  <input id="version" class="form-control w360" type="text" name="version" placeholder="">
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label"><span class="text-danger">*</span>安装包：</label>
              <div class="col-md-10 col-sm-9 pr">
                <a id="apk-path" class="btn btn-default btn-sm" href="javascript:;">上传安装包</a>
                <span id="apkkey"></span>
              </div>
            </div>
            <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label"><span class="text-danger">*</span>版本描述：</label>
                <div class="btn-box col-md-3 col-sm-9">
                    <textarea id="remark" class="form-control  w360" name="remark"></textarea>
                </div>
            </div>
            <div class="form-group">
              <div class="col-md-2 col-sm-3">&nbsp;</div>
              <div class="btn-box col-md-10 col-sm-9">
                <button id="btn-confirm" class="btn btn-primary">保存</button>
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
  <?=static_original_url('libs/cropbox/cropbox.js');?>
  <?=static_original_url('admin/js/main.min.js');?>
  <?=static_original_url('admin/vshop/js/ydb_version.js');?>
</body>
</html>

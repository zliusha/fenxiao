<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>打印机 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .form-tip-box {
      padding: 20px;
      margin-bottom: 20px;
      background-color: #fbfdff;
      border: 1px dashed #c0ccda;
    }
    .form-tip-box h3 {
      margin-top: 0;
      font-size: 16px;
      color: #202d3d;
    }
    .form-tip-box ol {
      padding-left: 16px;
      margin-bottom: 0;
    }
    .m-form-horizontal .form-group {
      position: relative;
      padding-left: 80px;
      margin-left: 0;
      margin-right: 0;
    }
    .m-form-horizontal .control-label {
      position: absolute;
      top: 0;
      left: 0;
      width: 80px;
      padding-left: 0;
      padding-right: 0;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body" style="padding: 20px;">
          <div class="form-tip-box">
            <h3>温馨提示</h3>
            <ol>
              <li>支持365WIFI打印机和USB打印机</li>
              <li>只能选择一种打印机</li>
              <li>USB打印机需要按操作说明进行相关配置</li>
              <li>如有问题请联系客服：0571-28121938</li>
            </ol>
          </div>
          <form id="printer-form" class="form-horizontal m-form-horizontal">
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">设备启用：</label>
              <div class="col-md-10 col-sm-9">
                <label class="radio-inline" >
                  <span class="u-radio">
                    <input type="radio" name="type" value="1">
                    <span class="radio-icon"></span>
                  </span>365WIFI打印机
                </label>
                <label class="radio-inline" >
                  <span class="u-radio">
                    <input type="radio" name="type" value="2">
                    <span class="radio-icon"></span>
                  </span>USB打印机
                </label>
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">打印联数：</label>
              <div class="col-md-10 col-sm-9">
                <label class="radio-inline" >
                  <span class="u-radio">
                    <input type="radio" name="times" value="1">
                    <span class="radio-icon"></span>
                  </span>1联
                </label>
                <label class="radio-inline" >
                  <span class="u-radio">
                    <input type="radio" name="times" value="2">
                    <span class="radio-icon"></span>
                  </span>2联
                </label>
              </div>
            </div>
            <div id="wifi-print-box" style="display: none;">
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">设备名称：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="print_name" class="form-control w360" type="text" name="print_name" placeholder="输入设备名称">
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">设备号码：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="print_deviceno" class="form-control w360" type="text" name="print_deviceno" placeholder="输入设备底部机器号">
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">设备秘钥：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="print_key" class="form-control w360" type="text" name="print_key" placeholder="输入设备秘钥">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-2 col-sm-3 control-label">&nbsp;</label>
              <div class="col-md-10 col-sm-9">
                <button id="btn-save" class="btn btn-primary">保存</button>
                <a id="usb-print-test" href="javascript:;" class="btn btn-default ml10" style="display: none;">打印测试</a>
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
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('djadmin/js/LodopFuncs.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/setting_printer.min.js');?>
</body>
</html>

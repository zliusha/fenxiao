<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>堂食装修 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('djadmin/mshop/css/decorate.min.css');?>
</head>
<body>
  <div id="main">
  <input id="shop_id" type="hidden" value="<?=$shop_id?>">
    <div class="container-fluid">
      <?php if($this->is_zongbu):?>
        <ol class="breadcrumb">
          <li><a href="<?=DJADMIN_URL?>mshop/shop">门店管理</a></li>
          <li class="active">堂食装修</li>
        </ol>
      <?php endif;?>
      <div class="main-body">
        <div class="main-title-box">
          <h3 class="main-title">支付后营销</h3>
        </div>
        <div class="main-body-inner">
          <div class="decorate-box">
            <div class="phone-box">
              <div class="phone-preview">
                <div class="phone-header">
                  <h2 id="phone-title" class="phone-header-title">支付成功</h2>
                </div>
                <div id="phone-body" class="saoma-body">
                  <div class="saoma-box">
                    <div class="saoma-content">
                      <span class="iconfont icon-success"></span>
                      <p class="saoma-content-title">支付成功</p>
                      <p class="saoma-content-price">￥29.90</p>
                    </div>
                    <div class="saoma-bs">
                      <div class="saoma-bs-title">
                        <p>点击下图参与本店互动优惠活动</p>
                      </div>
                      <div class="saoma-bs-img">
                        <img class="saoma-logo" src="">
                        <span class="saoma-img-p">上传海报</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div id="decorate-ctrl" class="shop-ctrl decorate-right" style="min-height: 645px">
              <div class="ctrl-module form-horizontal">
                <div class="ctrl-module-box" style="padding-top: 15px">
                  <p class="help-block">（暂不支持小程序）</p>
                  <div class="form-group">
                    <label class="control-label col-md-3 col-sm-3">设置广告：</label>
                    <div class="col-md-9 col-sm-9">
                      <label class="radio-inline" onclick="changeType(this)" for="saomaType">
                        <span class="u-radio">
                          <input id="saomaType1" type="radio" name="saomaType" value="1" checked="">
                          <span class="radio-icon"></span>
                        </span>
                        开启海报
                      </label>
                      <label class="radio-inline" onclick="changeType(this)" for="saomaType">
                        <span class="u-radio">
                          <input id="saomaType2" type="radio" name="saomaType" value="2">
                          <span class="radio-icon"></span>
                        </span>
                        开启小游戏
                      </label> 
                      <label class="radio-inline" onclick="changeType(this)" for="saomaType">
                        <span class="u-radio">
                          <input id="saomaType3" type="radio" name="saomaType" value="0">
                          <span class="radio-icon"></span>
                        </span>
                        不设置
                      </label>                    
                    </div>
                  </div>
                  <div class="saoma-one">
                    <div class="form-group">
                      <label class="control-label col-md-3 col-sm-3">海报图片：</label>
                      <div class="col-md-9 col-sm-9">
                        <div id="upload-logo-container" class="m-upload m-upload-logo-banner" style="margin-right: 20px;height: 200px;line-height: 200px">
                          <span id="upload-plus" class="btn-plus upload-plus"></span>
                          <img id="upload-pic" class="upload-pic" src="" alt="">
                          <a class="upload-again" href="javascript:;" style="display: none;">重新上传</a>
                          <input id="good_logo" type="text"  name="good_logo" value="">
                          <input id="upload-logo" type="file" class="upload-input" value="">
                        </div>
                        <p class="help-block">图片格式为 jpg 或 png，尺寸不得小于 750 x 528 像素，图片大小不得超过 2M</p>
                      </div>
                    </div>
                  </div>  
                  <div class="saoma-two">
                    <div class="form-group">
                      <label class="control-label col-md-3 col-sm-3">互动游戏：</label>
                      <div class="col-md-9 col-sm-9">
                        <select id="select-game" class="form-control" name="game" style="width: 200px">
                        </select>
                        <p class="help-block">您可以在互动游戏模块创建小游戏，<a class="btn-link" id="goGame" href="<?=DJADMIN_URL?>/hd/home">立刻去创建</a></p>
                      </div>
                    </div>
                  </div>     
                  <div class="form-group">
                    <label class="control-label col-md-3 col-sm-3"></label>
                    <div class="btn-box col-md-9 col-sm-9">
                      <a href="javascript:;" id="btn-confirm" class="btn btn-primary" onclick="edit()">保存</a>
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
  <script id="gameTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <option value="<%:=rows[i].activity_url %>" data-img="<%:=rows[i].list_small_pic%>"><%:=rows[i].title %></option>
      <% } %>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
  <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
  <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/decorate_saoma.js');?>
</body>
</html>

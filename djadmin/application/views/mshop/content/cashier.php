<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>外卖收银 - 挖到后台</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .cashier-download {
      padding: 10px 20px;
      margin-bottom: 20px;
      background-color: #fff;
      -webkit-box-shadow: 0 1px 0 #d1dbe5;
    }
    .cashier-block-label {
      font-size: 16px;
      color: #202d3d;
    }
    .cashier-logo {
      width: 80px;
      height: 80px;
    }
    .cashier-title {
      margin-top: 0;
      margin-bottom: 0;
      color: #1999ff;
    }
    .cashier-desc {
      margin-top: 12px;
      margin-bottom: 0;
      font-size: 14px;
      color: #c2cdeb;
    }
    .cashier-media .media-body {
      padding: 0 20px;
    }
    .cashier-app {
      width: 200px;
      overflow: hidden;
    }
    .cashier-app-item {
      float: left;
      text-align: center;
    }
    .cashier-app-item+.cashier-app-item {
      margin-left: 20px;
    }
    .cashier-app-item>p {
      margin-top: 0;
      margin-bottom: 5px;
      color: #99a9c0;
    }
    .cashier-qr {
      width: 90px;
      width: 90px;
      border: 1px dashed #d2d2d2;
    }
    .w-data-label {
      margin-top: 5px;
      height: 40px;
    }
    .cashier-block {
      padding: 20px;
      background-color: #fbfdff;
      border: 1px dashed #c0ccda;
    }
    .cashier-block+.cashier-block {
      border-top: none;
    }
    .icon-cashier {
      display: inline-block;
      width: 48px;
      height: 48px;
      background-image: url("<?=STATIC_URL?>djadmin/mshop/img/cashier/icon-cashier-func.png");
      background-repeat: no-repeat;
    }
    .icon-cashier-foreground {
      background-position: 0 0;
    }
    .icon-cashier-background {
      background-position: -52px 0;
    }
    .cashier-func-title {
      margin-top: 10px;
      margin-bottom: 0;
      font-size: 20px;
      color: #202d3d;
    }
    .cashier-func-item {
      margin-bottom: 0;
    }
    .cashier-func-item+.cashier-func-item {
      margin-top: 10px;
    }
    .icon-func-cicle {
      display: inline-block;
      width: 8px;
      height: 8px;
      margin-right: 10px;
      background-color: #ccc;
      border-radius: 8px;
    }
    .icon-func-primary {
      background-color: #59a2e7;
    }
    .icon-func-danger {
      background-color: #f96768;
    }
    .icon-func-success {
      background-color: #45be89;
    }
    .icon-func-warning {
      background-color: #ffb465;
    }
    .icon-func-info {
      background-color: #c46fff;
    }
    .cashier-industry {
      padding: 0;
    }
    .cashier-industry-list {
      margin-bottom: 30px;
    }
    .cashier-industry-item {
      margin-top: 30px;
      text-align: center;
    }
    .cashier-industry-pic {
      display: inline-block;
      width: 116px;
      height: 76px;
      text-indent: -9999px;
    }
    .cashier-industry-name {
      margin-top: 10px;
      font-size: 18px;
      color: #333;
    }
    .w-data-num {
      font-size: 20px;
      font-weight: normal;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <div class="cashier-download">
        <p class="cashier-block-label">下载体验</p>
        <div class="cashier-media media">
          <div class="media-left media-middle">
            <img class="cashier-logo" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/cashier-logo.png" alt="32卷POS打印纸">
          </div>
          <div class="media-body media-middle">
            <h3 class="cashier-title">云店宝收银记账</h3>
            <p class="cashier-desc">云店宝收银  伴您一路前行</p>
          </div>
          <div class="media-right">
            <div class="cashier-app">
              <div class="cashier-app-item">
                <p>IOS下载</p>
                <img class="cashier-qr" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/cashier-ios.png" alt="云店宝收银记账IOS客户端">
              </div>
              <div class="cashier-app-item">
                <p>Android下载</p>
                <img class="cashier-qr" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/cashier-android.png" alt="云店宝收银记账Android客户端">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="main-body">
        <div class="main-body-inner">
          <h2 class="main-title">外卖收银台的优势</h2>
          <div class="row w-data-list">
            <div class="col-md-3 col-sm-3 w-data-item">
              <div>
                <p class="w-data-num">聚合收银</p>
                <p class="w-data-label">多主体的聚合收银，适<br>合直营加盟混合管理</p>
              </div>
            </div>
            <div class="col-md-3 col-sm-3 w-data-item">
              <div>
                <p class="w-data-num">后台管理</p>
                <p class="w-data-label">可统一进行线上与线下<br>商品管理和库存管理</p>
              </div>
            </div>
            <div class="col-md-3 col-sm-3 w-data-item">
              <div>
                <p class="w-data-num">订单管理</p>
                <p class="w-data-label">多种订单管理：堂食、<br>外卖订单均可管理。</p>
              </div>
            </div>
            <div class="col-md-3 col-sm-3 w-data-item">
              <div>
                <p class="w-data-num">储值卡管理</p>
                <p class="w-data-label">支持电子储值卡的核销，<br>也可支持一单多票</p>
              </div>
            </div>
          </div>
          <h2 class="main-title">外卖收银的功能介绍</h2>
          <div class="row mb20">
            <div class="col-md-6 col-sm-6">
              <div class="cashier-block text-center">
                <p><span class="icon-cashier icon-cashier-foreground"></span></p>
                <p class="cashier-func-title">收银台（前台管理）</p>
              </div>
              <div class="cashier-block">
                <p class="cashier-func-item"><span class="icon-func-cicle icon-func-danger"></span>聚合收银，支持微信、 支付宝、现金支付，统一或多个收银主体都可</p>
                <p class="cashier-func-item"><span class="icon-func-cicle icon-func-warning"></span>独立外卖订单处理界面， 支持收银界面完成外卖订单的处理</p>
                <p class="cashier-func-item"><span class="icon-func-cicle icon-func-success"></span>支持堂食点单</p>
                <p class="cashier-func-item"><span class="icon-func-cicle icon-func-primary"></span>支持电子储值卡的核销及管理</p>
                <p class="cashier-func-item"><span class="icon-func-cicle icon-func-info"></span>支持一单多票（外卖小票、厨房小票、对账小票）</p>
              </div>
            </div>
            <div class="col-md-6 col-sm-6">
              <div class="cashier-block text-center">
                <p><span class="icon-cashier icon-cashier-background"></span></p>
                <p class="cashier-func-title">后台管理</p>
              </div>
              <div class="cashier-block" style="min-height: 181px;">
                <p class="cashier-func-item"><span class="icon-func-cicle icon-func-danger"></span>可统一进行线上与线下商品的建立与管理</p>
                <p class="cashier-func-item"><span class="icon-func-cicle icon-func-warning"></span>对线上与线下所有的商品库存进行进销存管理</p>
              </div>
            </div>
          </div>
          <h2 class="main-title">支持多个行业</h2>
          <div class="cashier-block cashier-industry mb20">
            <div class="cashier-industry-list row">
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/kcbd.jpg" alt="快餐便当">
                <p class="cashier-industry-name">快餐便当</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/gssx.jpg" alt="果蔬生鲜">
                <p class="cashier-industry-name">果蔬生鲜</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/scxc.jpg" alt="四川湘菜">
                <p class="cashier-industry-name">四川湘菜</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/lmxh.jpg" alt="浪漫鲜花">
                <p class="cashier-industry-name">浪漫鲜花</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/yyjk.jpg" alt="医药健康">
                <p class="cashier-industry-name">医药健康</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/hbst.jpg" alt="汉堡薯条">
                <p class="cashier-industry-name">汉堡薯条</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/bzzd.jpg" alt="包子粥店">
                <p class="cashier-industry-name">包子粥店</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/dfcx.jpg" alt="地方菜系">
                <p class="cashier-industry-name">地方菜系</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/psym.jpg" alt="披萨意面">
                <p class="cashier-industry-name">披萨意面</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/xskc.jpg" alt="西式快餐">
                <p class="cashier-industry-name">西式快餐</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/tpyp.jpg" alt="甜品饮品">
                <p class="cashier-industry-name">甜品饮品</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/xcyx.jpg" alt="小吃夜宵">
                <p class="cashier-industry-name">小吃夜宵</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/scbl.jpg" alt="商超便利">
                <p class="cashier-industry-name">商超便利</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/nltc.jpg" alt="能量套餐">
                <p class="cashier-industry-name">能量套餐</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/tsms.jpg" alt="特色面食">
                <p class="cashier-industry-name">特色面食</p>
              </div>
              <div class="cashier-industry-item col-lg-2 col-md-3 col-sm-4">
                <img class="cashier-industry-pic" src="<?=STATIC_URL?>djadmin/mshop/img/cashier/msyh.jpg" alt="麻辣诱惑">
                <p class="cashier-industry-name">麻辣诱惑</p>
              </div>
            </div>
          </div>
          <h2 class="main-title">支持多种设备</h2>
          <div class="row w-data-list">
            <div class="col-md-6 col-sm-6 w-data-item">
              <div>
                <p><img src="<?=STATIC_URL?>djadmin/mshop/img/cashier/t1.png" alt=""></p>
                <p class="w-data-num">云店宝T1</p>
                <p class="w-data-label">收银一体机内置热敏打印机收款机点菜奶茶餐饮连锁收银系统</p>
              </div>
            </div>
            <div class="col-md-6 col-sm-6 w-data-item">
              <div>
                <p><img src="<?=STATIC_URL?>djadmin/mshop/img/cashier/v1.png" alt=""></p>
                <p class="w-data-num">云店宝V1</p>
                <p class="w-data-label">外卖自动接单打印机移动扫码收银机，简洁机身，精湛工艺，实力派也能用颜值说话</p>
              </div>
            </div>
          </div>
          <h2 class="main-title">完善的服务体系</h2>
          <div class="row w-data-list">
            <div class="col-md-3 col-sm-3 w-data-item">
              <div>
                <p class="w-data-num">全天候客服</p>
                <p class="w-data-label">7*24小时一对一沟通</p>
              </div>
            </div>
            <div class="col-md-3 col-sm-3 w-data-item">
              <div>
                <p class="w-data-num">专业指导</p>
                <p class="w-data-label">免费指导带你玩转强大功能</p>
              </div>
            </div>
            <div class="col-md-3 col-sm-3 w-data-item">
              <div>
                <p class="w-data-num">极速响应</p>
                <p class="w-data-label">随时待命，为您解决各种难题</p>
              </div>
            </div>
            <div class="col-md-3 col-sm-3 w-data-item">
              <div>
                <p class="w-data-num">免费维修</p>
                <p class="w-data-label">专业团队为您保驾护航</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
</body>
</html>

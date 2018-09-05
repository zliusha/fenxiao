<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>支付成功 - 云店宝</title>
	<?php $this->load->view('inc/global_header'); ?>
  <style>
    .header-logo {
      display: block;
      height: 50px;
    }

    .header-logo>img {
      margin-top: 14px;
    }
  </style>
</head>
<body class="has-header" style="background-color: #fff;">
  <header class="w-header">
    <nav class="navbar navbar-inverse">
      <div class="container">
        <a class="header-logo" href="<?=DJADMIN_URL?>">
          <img src="<?=STATIC_URL?>djadmin/img/logo-ydb.png" alt="浙江云店宝科技有限公司">
        </a>
      </div>
    </nav>
  </header>
  <section id="main" class="w-content">
    <div class="container">
      <div class="text-center" style="padding-top: 160px;">
        <p style="margin: 0 0 10px;"><span class="iconfont icon-success" style="font-size: 68px;color: #19cc3c;"></span></p>
        <h3>恭喜您，支付成功</h3>
        <p class="mt20"><a class="btn btn-primary" href="<?=DJADMIN_URL?>">返回首页</a></p>
      </div>
    </div>
  </section>
	<?php $this->load->view('inc/global_footer'); ?>
	<?=static_original_url('djadmin/js/main.min.js');?>
</body>
</html>

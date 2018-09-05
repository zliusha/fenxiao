<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>错误</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <div class="main-body">
        <div class="main-body-inner">
          <div class="text-center" style="padding: 100px 0;">
            <p><span class="iconfont icon-danger text-danger" style="font-size: 40px;"></span></p>
            <p style="font-size: 16px;"><?=$msg?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('djadmin/js/main.min.js');?>
</body>
</html>

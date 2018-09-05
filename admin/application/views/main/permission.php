<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>权限限制 - 挖到后台</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .main-body-inner p {
      font-size: 18px;
    }
    .mt100 {
      margin-top: 100px;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <div class="main-body">
        <div class="main-body-inner">
          <div class="text-center mt100">
            <p><?=$msg?></p>
            <p>客服电话：0571-26201018</p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('djadmin/js/main.min.js');?>
</body>
</html>

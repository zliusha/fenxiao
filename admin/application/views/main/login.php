<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>账号登录 - 管理后台</title>
  <?php $this->load->view('inc/global_header');?>
  <?=static_original_url('admin/css/account.min.css');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/css/bootstrapValidator.min.css');?>
  <script>
    if(window.top != window.self){
      window.top.location = __BASEURL__ + 'passport/login';
    }  
  </script>
</head>
<body class="bg-bright">
  <header class="w-account-header clearfix">
    <div class="pull-left">
      <a class="header-logo" href="<?=ADMIN_URL?>">
        <img src="<?=STATIC_URL?>admin/img/logo-primary.png" alt="挖到">
      </a>
      <span class="header-desc">账户登录</span>
    </div>
  </header>
  <section id="main" class="w-account-content">
    <?php echo form_open('', array("class"=>"form account-form","id"=>"login-form"))?>
      <div class="form-group">
        <label class="control-label">帐号：</label>
        <input id="account" class="form-control" type="text" name="account" placeholder="请输入帐号" autofocus>
      </div>
      <div class="form-group" style="margin-bottom: 10px;">
        <label class="control-label">密码：</label>
        <input id="password" class="form-control" type="password" name="password" placeholder="请输入密码">
      </div>
      <button id="btn-login" class="btn btn-primary btn-block btn-lg mt40">登录</button>
    </form>
  </section>
  <footer class="w-account-footer">
    <p>增值电信业务许可证： 浙B2-20160727 &nbsp;&nbsp; 浙公网安备33010602003879号 &nbsp;&nbsp; 浙ICP备16019390号</p>
    <p>@2016 wadao.com杭州挖到科技有限公司</p>
  </footer>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('admin/js/main.min.js');?>
  <?=static_original_url('admin/js/account.min.js');?>
</body>
</html>

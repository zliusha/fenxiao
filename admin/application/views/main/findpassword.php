<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>找回密码 - 挖到后台</title>
  <?php $this->load->view('inc/global_header');?>
  <?=static_original_url('djadmin/css/account.min.css');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/css/bootstrapValidator.min.css');?>
  <script>
    if (window.top != window.self) {
      window.top.location = __BASEURL__ + 'passport/findpassword';
    }
  </script>
</head>
<body class="bg-bright">
  <header class="w-account-header clearfix">
    <div class="pull-left">
      <a class="header-logo" href="<?=SITE_URL?>">
        <img src="<?=STATIC_URL?>djadmin/img/logo-primary.png" alt="挖到">
      </a>
      <span class="header-desc">找回密码</span>
    </div>
    <div class="pull-right">
      <a href="<?=DJADMIN_URL?>passport/login">登录</a>
    </div>
  </header>
  <section id="main" class="w-account-content">
    <?php echo form_open('', array("class" => "form account-form", "id" => "findpassword-form")) ?>
      <div class="form-group">
        <label class="control-label">手机号：</label>
        <input id="mobile" class="form-control" type="text" name="mobile" placeholder="请输入手机号" autofocus>
      </div>
      <div class="form-group" style="position: relative;">
        <label class="control-label">短信验证：</label>
        <input id="code" class="form-control" type="text" name="code" placeholder="请输入短信验证码">
        <button id="get-findpassword-code" class="btn btn-link btn-get-code">获取验证码</button>
      </div>
      <div class="form-group">
        <label class="control-label">设置密码：</label>
        <input id="password" class="form-control" type="password" name="password" placeholder="请设置登录密码">
      </div>
      <button id="btn-findpassword" class="btn btn-primary btn-block btn-lg mt40">确定修改</button>
    </form>
  </section>
  <footer class="w-account-footer">
    <p>增值电信业务许可证： 浙B2-20160727 &nbsp;&nbsp; 浙公网安备33010602003879号 &nbsp;&nbsp; 浙ICP备16019390号</p>
    <p>@2016 wadao.com杭州挖到科技有限公司</p>
  </footer>
  <?php $this->load->view('inc/global_footer');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/js/account.min.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>微商城 - 挖到</title>
  <?php $this->load->view('inc/global_header');?>
  <?=static_original_url('admin/css/account.min.css');?>
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
    .textBack{
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 30px;
      min-height: 400px;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li class="active">管理后台首页</li>
      </ol>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="row textBack">
            <span id="username"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('admin/js/main.min.js');?>
  <?=static_original_url('admin/js/index.min.js');?>
</body>
<script type="text/javascript">
  $(function () {
    function getAccountInfo() {
      $.getJSON(__BASEURL__ + "/sys_account_api/info", {},
        function (data) {
          if (data.success) {
            $('#username').html('欢迎回来，'+data.data.m_account.username);
          } else {
            new Msg({
              type: "danger",
              msg: data.msg
            });
          }
        }
      );
    }
    getAccountInfo();
  });
</script>
</html>

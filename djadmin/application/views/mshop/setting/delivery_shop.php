<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>门店配送 - 配送配置</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    #source_id {
      width: 180px;
    }

    .dl-horizontal-delivery {
      margin-bottom: 0;
    }

    .dl-horizontal-delivery .u-switch {
      margin-top: 0;
    }

    @media (min-width: 768px) {
      .dl-horizontal-delivery dt {
        float:left;
        width: 90px;
        clear: left;
        text-align: right;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .dl-horizontal-delivery dd {
        margin-left: 100px;
      }
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <?php $this->load->view('inc/nav_delivery');?>
          <div class="form-horizontal m-form-horizontal">
            <div class="form-group">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label" style="padding-top: 2px;">开启配送：</label>
                </dt>
                <dd>
                  <label class="u-switch">
                    <input id="status" type="checkbox" name="status">
                    <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
                  </label>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?= static_original_url('djadmin/js/main.min.js'); ?>
  <script>
    $(function(){
      var $status = $("#status");

      getDeliveryMethod();
      
      // 获取配送方式
      function getDeliveryMethod(){
        $.get(__BASEURL__ + 'mshop/setting_api/shipping_method', function (data) {
          if (data.success) {
            var status = +data.data.shipping;

            if (status == 1) {
              $status.prop("checked", true).prop("disabled", true);
            } else {
              $status.prop("checked", false);
            }
          }
        });
      }

      // 改变配送开启动态
      $status.on("change", function () {
        var status = +$(this).prop("checked");

        if (status == 0) {
          $status.prop("checked", true).prop("disabled", true);
          return;
        }

        $.post(
          __BASEURL__ + "mshop/setting_api/update_shipping_method",
          autoCsrf({
            shipping: status
          }),
          function (data) {
            if (data.success) {
              new Msg({
                type: "success",
                msg: "修改成功",
                delay: 1
              });

            } else {
              new Msg({
                type: "danger",
                msg: data.msg
              });

              $status.prop("checked", !status);
            }
          }
        );
      });
    });
  </script>
</body>
</html>

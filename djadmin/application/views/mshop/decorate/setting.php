<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>配送方式 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('djadmin/mshop/css/decorate.min.css');?>
  <style type="text/css">
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
        margin-left: 95px;
      }
    }
  </style>
</head>
<body>
  <div id="main">
  <input id="shop_id" type="hidden" value="<?=$shop_id?>">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_decorate');?>
      <div class="main-body">
        <div class="main-body-inner row">
          <div class="form-horizontal m-form-horizontal">
            <div class="form-group">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label" style="padding-top: 2px;">外卖配送：</label>
                </dt>
                <dd>
                  <label class="u-switch">
                    <input id="status-waimai" type="checkbox" name="status" value="1">
                    <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
                  </label>
                </dd>
              </dl>
            </div>
            <div class="form-group">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label" style="padding-top: 2px;">到店自取：</label>
                </dt>
                <dd>
                  <label class="u-switch">
                    <input id="status" type="checkbox" name="status" value="2">
                    <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
                  </label>
                </dd>
              </dl>
              <p class="text-danger mt10" style="margin-left: 95px;">到店自取开启后，顾客可以在外卖端下单，到店后凭取货码取货</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>  
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
  <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
  <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <script type="text/javascript">
    $(function(){
      var $status = $("#status"),
        $statusWaimai = $('#status-waimai'),
        shop_id = $('#shop_id').val();

      getDeliveryMethod();
      
      // 获取配送方式
      function getDeliveryMethod(){
        $.get(__BASEURL__ + 'mshop/shop_api/info', {
          shop_id: shop_id
        },function (data) {
          if (data.success) {
            var status = +data.data.info.shpping_switch;

            if (status == 1) {
              $statusWaimai.prop("checked", true);
              $status.prop("checked", false);
            } else if(status == 2) {
              $status.prop("checked", true);
              $statusWaimai.prop("checked", false);
            } else if(status == 3) {
              $status.prop("checked", true);
              $statusWaimai.prop("checked", true);
            }
          }
        });
      }

      // 改变配送开启动态
      $status.on("change", function () {
        if(!$status.is(':checked') && !$statusWaimai.is(':checked')){
          $status.prop('checked',true)
          return false
        }

        var value = 0;

        $('[name="status"]:checked').each(function(index, ele){
          value += Number(ele.value);
        })

        $.post(
          __BASEURL__ + "mshop/shop_api/update_shpping_switch",
          autoCsrf({
            shop_id: shop_id,
            value: value
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

              $status.prop("checked", value & 2);
            }
          }
        );
      });

      // 改变配送开启动态
      $statusWaimai.on("change", function () {
        if(!$status.is(':checked') && !$statusWaimai.is(':checked')){
          $statusWaimai.prop('checked',true)
          return false
        }

        var value = 0;

        $('[name="status"]:checked').each(function(index, ele){
          value += Number(ele.value);
        })
        
        $.post(
          __BASEURL__ + "mshop/shop_api/update_shpping_switch",
          autoCsrf({
            shop_id: shop_id,
            value: value
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

              $statusWaimai.prop("checked", value & 1);
            }
          }
        );
      });
    });
  </script>
</body>
</html>

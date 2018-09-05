<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>堂食配置 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .meal-radio {
      padding-left: 0;
      padding-top: 0!important;
    }
    .meal-radio + .meal-radio {
      margin-left: 60px;
    }
    .meal-radio .u-radio {
      top: auto!important;
      left: 50%;
      bottom: 0;
      margin-left: -11px;
    }
    .meal-show-type {
      display: block;
      width: 284px;
      height: 558px;
      padding-top: 518px;
      margin-bottom: 40px;
      text-align: center;
      background-repeat: no-repeat;
      background-position: center;
    }
    .meal-show-type1 {
      background-image: url("<?=STATIC_URL?>djadmin/mshop/img/dinein_show_type1.png");
    }
    .meal-show-type2 {
      background-image: url("<?=STATIC_URL?>djadmin/mshop/img/dinein_show_type2.png");
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <h3 class="main-title" style="margin-top: -10px;">商品详情设置</h3>
          <hr>
          <div id="meal-form">
            <div class="form-group">
              <label class="radio-inline meal-radio">
                <span class="meal-show-type meal-show-type2">在菜单列表页直接加购物车</span>
                <span class="u-radio">
                  <input type="radio" name="show_type" value="0">
                  <span class="radio-icon"></span>
                </span>
              </label>
              <label class="radio-inline meal-radio">
                <span class="meal-show-type meal-show-type1">在菜单详情页加购物车</span>
                <span class="u-radio">
                  <input type="radio" name="show_type" value="1">
                  <span class="radio-icon"></span>
                </span>
              </label>
            </div>
            <div class="form-group">
              <button id="btn-save" class="btn btn-primary">保存</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>           
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <script>
    $(function(){
      var $btnSave = $('#btn-save');
      
      // 获取堂食设置
      $.get(__BASEURL__ + 'mshop/meal_setting_api/get_meal_setting', function(res){
        if (res.success) {
          $('[name="show_type"][value="' + res.data.show_type + '"]').prop("checked", true);
        } else {
          new Msg({
            type: 'danger',
            msg: res.msg
          })
        }
      })
      
      // 修改堂食设置
      $btnSave.on('click', function(){
        var show_type = $('[name="show_type"]:checked').val();
        
        $btnSave.prop('disabled', true).text('保存中...');

        $.post(__BASEURL__ + 'mshop/meal_setting_api/save_meal_setting', autoCsrf({
          show_type: show_type
        }), function(res){
          $btnSave.prop('disabled', false).text('保存');

          if (res.success) {
            new Msg({
              type: 'success',
              msg: '修改成功'
            })
          } else {
            new Msg({
              type: 'danger',
              msg: res.msg
            })
          }
        })
      })
    });
  </script>
</body>
</html>

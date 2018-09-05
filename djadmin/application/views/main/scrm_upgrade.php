<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>互动营销 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <div class="main-body" style="padding: 20px;">
        <h3 style="font-size: 18px;color: #000;margin-top: 10px;margin-bottom: 20px;">终于等到了互动营销全新升级改版，还好你没放弃！</h3>
        <p>功能简介：</p>
        <ol style="padding-left: 20px;line-height: 1.8">
          <li>从门店维度管理客户；从公众号维度管理粉丝；从会员维度识别客户和粉丝真身。</li>
          <li>强大的标签管理功能，你可以对会员进行人群细分，更精准的与会员互动。</li>
          <li>丰富的会员权益：会员储值、会员卡、优惠券、积分。</li>
          <li>短信跳微信突破微信限制，商家可自由触达粉丝。</li>
          <li>微信实时消息、互动群发等功能让你及时响应粉丝。</li>
          <li>场景化的短信营销功能使用起来更得心应手。</li>
        </ol>
        <p>总之，从此以后你可以花样百出的进行拉新、留存、促活、转化。</p>
        <p class="text-danger">提示：升级后，请在“互动营销-设置”模块重新授权公众号。（如果你有小程序的话，小程序也请重新授权）</p>
        <p class="mt20"><button class="btn btn-primary" onclick="upgradeScrm()">立即升级</button></p>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <script>
    function upgradeScrm() {
      $.get(__BASEURL__ + 'scrm_api/upgrade', function(res) {
        if (res.success) {
          window.location.reload();
        } else {
          new Msg({
            type: 'danger',
            msg: res.msg
          })
        }
      })
    }
  </script>
</body>
</html>

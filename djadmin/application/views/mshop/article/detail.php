<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>公告详情 - 云店宝</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .m-article-title {
      margin-top: 10px;
      margin-bottom: 20px;
      font-size: 18px;
      font-weight: normal;
      color: #333;
      text-align: center;
    }
  </style>
</head>
<body>
  <input id="article_id" type="hidden" value="<?=$article_id?>">
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
				<li><a href="<?=DJADMIN_URL?>mshop/article">公告消息</a></li>
				<li class="article-title active">...</li>
      </ol>
      <div class="main-body">
				<div class="main-body-inner">
          <h1 class="m-article-title article-title"></h1>
          <div id="article-content" class="m-article-content">
            <div class="m-empty-box">
              <p>加载中...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <script>
    $(function () {
      getArticleDetail();

      // 获取文章详情
      function getArticleDetail() {
        $.getJSON(__BASEURL__ + 'mshop/notice_api/get_info/'+$('#article_id').val(), function (data) {
          if (data.success) {
            $('.article-title').text(data.data.m_notice.title);
            $('#article-content').html(data.data.m_notice.content);
          }else{
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }
        });
      }
    });
  </script>
</body>
</html>

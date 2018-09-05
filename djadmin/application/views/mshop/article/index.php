<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>公告消息 - 云店宝</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li class="active">公告消息</li>
      </ol>
      <div class="main-body">
        <div class="main-body-inner">
          <div id="articleCon" class="m-article">
            <div class="m-empty-box">
              <p>加载中...</p>
            </div>
          </div>
          <div id="articlePage" class="m-pager mt20"></div>
        </div>
      </div>
    </div>
  </div>
  <script id="articleTpl" type="text/html">
    <% if(rows.length > 0) { %>
      <ul class="m-article-list">
        <% for(var i = 0; i < rows.length; i++) { %>
          <li class="m-article-item has-time">
            <a href="<?=DJADMIN_URL?>mshop/article/detail/<%:=rows[i].id%>"><%:=(cur_page-1)*page_size+i+1%>、<%:=rows[i].title%><span class="m-article-time"><%:=rows[i].time%></span></a>
          </li>
        <% } %>
      </ul>
    <% } else { %>
      <div class="m-empty-box">
        <p class="text-center">暂无公告消息</p>
      </div>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <script>
    $(function () {
      var cur_page = 1,
        page_size = 10,
        articleTpl = document.getElementById('articleTpl').innerHTML;

      getNoticeList(cur_page);

      // 获取门店列表
      function getNoticeList(curr) {
        $.getJSON(__BASEURL__ + 'mshop/notice_api/get_list', {
          current_page: curr || 1,
          page_size: page_size
        }, function (data) {
          if (data.success) {
            data.data.cur_page = cur_page;
            data.data.page_size = page_size;
            var pages = Math.ceil(+data.data.total / page_size);

            $('#articleCon').html(template(articleTpl, data.data));

            laypage({
              cont: 'articlePage',
              pages: pages,
              curr: curr || 1,
              skin: '#5aa2e7',
              first: 1,
              last: pages,
              skip: true,
              prev: "&lt",
              next: "&gt",
              jump: function (obj, first) {
                if (!first) {
                  getNoticeList(obj.curr);
                  cur_page = obj.curr;
                }
              }
            });
          }
        });
      }
    });
  </script>
</body>
</html>

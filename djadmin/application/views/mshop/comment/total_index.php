<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>顾客评价 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
  <?=static_original_url('djadmin/mshop/css/comment.css');?>
  <style type="text/css">
    .order-con .table p{
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li class="active">顾客评价</li>
      </ol>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="clearfix mb20">
            <div class="form-inline pull-left form-label">
              <div class="form-group">
                <select id="select-shop" class="form-control" name="shop_id" onchange="changeShop()">
                  <?php if($this->is_zongbu): ?>
                    <option value="">全部门店</option>
                  <?php endif; ?>
                  <?php if(!empty($shop_list)):?>
                    <?php foreach($shop_list as $shop):?>
                      <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                    <?php endforeach;?>
                  <?php endif;?>
                </select>
              </div>
              <div class="form-group">
                <div class="form-control-time">
                  <input id="create_time" class="form-control" type="text" name="create_time" placeholder="输入下单时间" readonly>
                  <span class="iconfont icon-rili"></span>
                </div>
              </div>
            </div>
          </div>
          <div class="order-con table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>
                    <label class="radio-inline"><span class="u-radio"><input type="radio" name="commentTpye" value="0" checked=""><span class="radio-icon"></span></span>全部
                    </label>
                    <label class="radio-inline"><span class="u-radio"><input type="radio" name="commentTpye" value="1"><span class="radio-icon"></span></span>未回复差评
                    </label>
                  </th>
                  <th style="text-align: right;">
                    <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="hasContent" value="1"><span class="checkbox-icon"></span></span>只看有内容评
                    </label>
                  </th>
                </tr>
              </thead>
              <tbody id="commentTbody">
                <tr>
                  <td class="text-center" colspan="2">加载中...</td>
                </tr>
              </tbody>          
            </table>
          </div>
          <div id="commentPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="editReplyModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="reply-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">回复</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group" style="margin: 0">
              <div style="text-align: center;"><textarea style="height: 200px;" id="reply_name" class="form-control" type="text" name="reply_name" placeholder="请输入回复内容"></textarea></div>
            </div>       
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="edit-confirm" class="btn btn-primary">确定</button>
        </div>
      </form>
    </div>
  </div>
  <script id="commentTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td>
            <ul class="star-list">
            <% if(rows[i].comments[0].score==0) { %>
              <li class="star-item"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>
            <%} else if(rows[i].comments[0].score==1) {%>
              <li class="star-item active"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>            
            <%} else if(rows[i].comments[0].score==2) {%>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>            
            <%} else if(rows[i].comments[0].score==3) {%>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item"></li>
              <li class="star-item"></li>            
            <%} else if(rows[i].comments[0].score==4) {%>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item"></li>              
            <%} else {%>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item active"></li>
              <li class="star-item active"></li>              
            <% } %>  
            </ul>
            <p  style="word-break: break-all;word-wrap: break-word;white-space: pre-wrap;"><%:=rows[i].comments[0].content %></p>
            <% if(rows[i].comments[0].picarr.length>0) { %>
            <ul class="comment-img-list">
              <% for(var k = 0; k < rows[i].comments[0].picarr.length; k++) { %>
              <li class="comment-img-item">
                <img class="comment-img-logo" src="<%:=rows[i].comments[0].picarr[k] %>">
              </li>
              <% } %>
            </ul>
            <% } %>
            <p style="margin: 0">
              <% for(var j = 0; j < rows[i].comments[0].tags.length; j++) { %>
                <% if(rows[i].comments[0].tags[j]=='1') { %>
                <span class="label label-primary">干净卫生</span>
                <%} else if(rows[i].comments[0].tags[j]=='2') { %>
                <span class="label label-primary">食材新鲜</span>
                <%} else if(rows[i].comments[0].tags[j]=='3') { %>
                <span class="label label-primary">分量足</span>
                <%} else if(rows[i].comments[0].tags[j]=='4') { %>
                <span class="label label-primary">味道好</span>
                <%} else if(rows[i].comments[0].tags[j]=='5') { %>
                <span class="label label-primary">包装精美</span>
                <%} else if(rows[i].comments[0].tags[j]=='6') { %>
                <span class="label label-primary">非常实惠</span>
                <%} else if(rows[i].comments[0].tags[j]=='7') { %>
                <span class="label label-primary">主动联系</span>
                <%} else if(rows[i].comments[0].tags[j]=='8') { %>
                <span class="label label-primary">态度很好</span>
                <%} else if(rows[i].comments[0].tags[j]=='9') { %>
                <span class="label label-primary">衣着整洁</span>
                <%} else if(rows[i].comments[0].tags[j]=='10') { %>
                <span class="label label-primary">餐品完好</span>
                <%} else if(rows[i].comments[0].tags[j]=='11') { %>
                <span class="label label-primary">准时到达</span>
                <%} else if(rows[i].comments[0].tags[j]=='12') { %>
                <span class="label label-primary">服务态度好</span>
                <%} else if(rows[i].comments[0].tags[j]=='13') { %>
                <span class="label label-primary">送餐快</span>
                <%} else if(rows[i].comments[0].tags[j]=='14') { %>
                <span class="label label-primary">穿着专业</span>
                <% } %>
              <% } %>
            </p>
            <p>
            <% for(var j = 0; j < rows[i].comments.length; j++) { %>
              <% if(rows[i].comments[j].type==='0') { %>
              <p style="margin-bottom: 0;color: #5aa2e7"><%:=rows[i].comments[j].ext %>：<span class="f-ellipsis" style="color: #475669;"><%:=rows[i].comments[j].content %></span></p>
              <% } %>
            <% } %>  
            </p>
            <% if(rows[i].reply) { %>
            <p class="f-ellipsis" style="color: #99a9c0;">商家回复：<%:=rows[i].reply.content %></p>
            <% } %> 
          </td>
          <td class="w360" style="text-align: right;">
            <p><%:=rows[i].shop_name %>&nbsp;|&nbsp;<%:=rows[i].time %></p>
            <p>
              <span style="line-height: 24px;margin-right: 5px;color: #99a9c0">隐藏差评</span>
              <label class="u-switch">
              <% if(rows[i].comments[0].is_hide=='0') { %>
                <input  type="checkbox" name="hide" onclick="changeHide(<%:=rows[i].comments[0].order_id%>,'1')">
              <%} else {%> 
                <input  type="checkbox" name="hide" onclick="changeHide(<%:=rows[i].comments[0].order_id%>,'0')" checked>
              <% } %> 
                <span class="u-switch-checkbox" data-on="隐藏" data-off="显示"></span>
              </label>
            </p>
            <% if(rows[i].reply) { %>
            <a class="btn btn-primary" href="javascript:;" disabled>已回复</a>
            <%} else {%>
            <a class="btn btn-primary" href="javascript:;" onclick="reply(<%:=rows[i].comments[0].order_id%>)">回复</a>
            <% } %> 
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="2">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/comment_total_list.js');?>
</body>
</html>

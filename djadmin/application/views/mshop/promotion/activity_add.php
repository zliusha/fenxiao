<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>创建活动 - 满减活动</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
  <?=static_original_url('libs/bootstrap-datetimepicker/2.0/css/bootstrap-datetimepicker.min.css');?>
</head>
<body>
<div id="main">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li><a href="<?=DJADMIN_URL?>mshop/promotion/activity_list">满减活动</a></li>
      <li class="active">创建活动</li>
    </ol>
    <div class="main-body">
      <div class="main-body-inner row">
        <form id="discount-form" class="form-horizontal m-form-horizontal">
          <div class="form-group">
            <label class="control-label col-md-2 col-sm-3">选择门店：</label>
            <div class="col-md-10 col-sm-9">
              <select id="shop" class="form-control w360" name="shop_id">
                <?php if($this->is_zongbu):?>
                  <option value="0">全部门店</option>
                <?php endif;?>
                <?php if(!empty($shop_list)):?>
                  <?php foreach($shop_list as $shop):?>
                    <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                  <?php endforeach;?>
                <?php endif;?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-md-2 col-sm-3">活动名称：</label>
            <div class="col-md-10 col-sm-9">
              <input id="active_name" class="form-control w360" type="text" name="active_name" placeholder="活动名称不得超过30个字符">
            </div>
          </div>
          <div class="form-group form-inline">
            <label class="control-label col-md-2 col-sm-3">活动时间：</label>
            <div class="col-md-10 col-sm-9" style="position: relative">
              <input size="16"  id="start_time" type="text" value="" readonly class="form_datetime form-control"  style="width: 170px;background-color: transparent" name="start_time">
              <label>至</label>
              <input size="16" id="end_time" type="text" value="" readonly class="form_datetime form-control" style="width: 170px;background-color: transparent" name="end_time">
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-md-2 col-sm-3">优惠设置：</label>
            <div class="col-md-10 col-sm-9">
              <div class="mb10"><a class="btn btn-default" href="javascript:;" onclick="addGood()">添加一级优惠</a></div>
              <table class="table" style="margin-bottom: 0;">
                <thead>
                  <tr>
                    <th>订单金额</th>
                    <th>减免金额</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody id="discountTbody">
                  <tr>
                    <td class="text-center" colspan="3">加载中...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-md-2 col-sm-3"></label>
            <div class="btn-box col-md-10 col-sm-9">
              <a class="btn btn-default" href="<?=DJADMIN_URL?>mshop/promotion/activity_list">取消</a>
              <button id="btn-confirm" class="btn btn-primary">保存</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<div id="addGoodModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
        <h4 class="modal-title">添加商品</h4>
      </div>
      <div class="modal-body">
        <div class="mb20 clearfix">
          <div class="form-inline pull-left">
            <div class="form-group">
              <input id="searchModalVal" class="form-control" type="text" name="searchModalVal" placeholder="商品名称">
            </div>
            <a href="javascript:;" class="btn btn-primary" onclick="searchModalVal()"><span class="iconfont icon-search"></span>搜索</a>
          </div>
        </div>
        <table class="table">
          <thead>
            <tr>
              <th>商品</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody id="goodModal">
            <tr>
              <td class="text-center" colspan="2">加载中...</td>
            </tr>
          </tbody>
        </table>
        <div id="goodModalPage" class="m-pager"></div>
      </div>
    </div>
  </div>
</div>
<script id="setDiscountTpl" type="text/html">
  <% if (list.length > 0) { %>
    <% for(var i = 0; i < list.length; i++) { %>
      <tr>
        <td>
          <input type="text" class="form-control" id="order_price<%:=[i]%>" name="order_price" value="<%:=list[i].price %>" style="width: 100px;" onchange="orderPriceChange(this,<%:=[i]%>)">
        </td>
        <td >
          <input type="text" class="form-control" id="red_price<%:=[i]%>" name="red_price" value="<%:=list[i].red_price %>" style="width: 100px;" onchange="redPriceChange(this,<%:=[i]%>)">
        </td>
        <td>
          <a class="btn-link btn-danger" href="javascript:;" onclick="delPrice(this,<%:=[i]%>)">删除</a>
        </td>
      </tr>
    <% } %>
  <%} else {%>
    <tr>
      <td class="text-center" colspan="3">暂无数据</td>
    </tr>
  <% } %>
</script>
<?php $this->load->view('inc/global_footer'); ?>
<?= static_original_url('libs/laydate/laydate.js'); ?>
<?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
<?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
<?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.js');?>
<?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
<?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
<?=static_original_url('djadmin/js/areapicker.min.js');?>
<?=static_original_url('djadmin/js/main.min.js');?>
<?=static_original_url('djadmin/mshop/js/activity_add.js');?>
</body>
</html>

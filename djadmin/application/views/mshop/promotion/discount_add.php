<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>创建活动 - 限时折扣</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('libs/bootstrap-daterangepicker/2.1.25/bootstrap-daterangepicker.min.css');?>
  <?=static_original_url('libs/bootstrap-datetimepicker/2.0/css/bootstrap-datetimepicker.min.css');?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li><a href="<?=DJADMIN_URL?>mshop/promotion/discount_list">限时折扣</a></li>
        <li class="active">创建活动</li>
      </ol>
      <div class="main-body">
        <div class="main-body-inner row">
          <form id="discount-form" class="form-horizontal m-form-horizontal">
            <div class="form-group">
              <label class="control-label col-md-2 col-sm-3"><span class="text-danger">*</span>选择门店：</label>
              <div class="col-md-10 col-sm-9">
                <select id="shop" class="form-control w360" name="shop_id" >
                  <?php if($this->is_zongbu): ?>
                    <option value="0">全部门店</option>
                  <?php endif; ?>
                  <?php if(!empty($shop_list)):?>
                    <?php foreach($shop_list as $shop):?>
                      <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                    <?php endforeach;?>
                  <?php endif;?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-md-2 col-sm-3"><span class="text-danger">*</span>活动名称：</label>
              <div class="col-md-10 col-sm-9">
                <input id="active_name" class="form-control w360" type="text" name="active_name" placeholder="活动名称不得超过30个字符">
              </div>
            </div>
            <div class="form-group form-inline">
              <label class="control-label col-md-2 col-sm-3"><span class="text-danger">*</span>活动时间：</label>
              <div class="col-md-10 col-sm-9" style="position: relative">
                <input size="16"  id="start_time" type="text" value="" readonly class="form_datetime form-control "  style="width: 170px;background-color: transparent" name="start_time">
                <label>至</label>
                <input size="16" id="end_time" type="text" value="" readonly class="form_datetime form-control " style="width: 170px;background-color: transparent" name="end_time">
              </div>
            </div>
            <div class="form-group form-inline">
              <label class="control-label col-md-2 col-sm-3">用户参与次数：</label>
              <div class="col-md-10 col-sm-9">
                <div class="clearfix">
                  <label class="radio-inline pull-left" >
                    <span class="u-radio">
                      <input type="radio" name="limit_type" value="不限次数" checked onclick="limitType(this)">
                      <span class="radio-icon"></span>
                    </span>不限次数
                  </label>
                  <label class="radio-inline pull-left mr10" >
                    <span class="u-radio">
                      <input type="radio" name="limit_type" value="仅限1次" onclick="limitType(this)">
                      <span class="radio-icon"></span>
                    </span>仅限1次
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group form-inline">
              <label class="control-label col-md-2 col-sm-3">每单限购数量：</label>
              <div class="col-md-10 col-sm-9">
                <div class="clearfix">
                  <label class="radio-inline pull-left" >
                    <span class="u-radio">
                      <input type="radio" name="discount_type" value="不限购" checked onclick="discountType(this)">
                      <span class="radio-icon"></span>
                    </span>不限购
                  </label>
                  <label class="radio-inline pull-left mr10" >
                    <span class="u-radio">
                      <input type="radio" name="discount_type" value="限购" onclick="discountType(this)">
                      <span class="radio-icon"></span>
                    </span>每种商品前
                  </label>
                  <input id="discount_num" class="form-control" style="width: 80px" type="number" name="discount_num">
                  <span >件享受优惠</span>
                </div>
              </div>
            </div>
            <div class="form-group form-inline">
              <label class="control-label col-md-2 col-sm-3">商品标签：</label>
              <div class="col-md-10 col-sm-9">
                <div class="clearfix">
                  <label class="radio-inline pull-left" >
                    <span class="u-radio">
                      <input type="radio" name="good_type" value="打折" checked onclick="goodType(this)">
                      <span class="radio-icon"></span>
                    </span>打折
                  </label>
                  <label class="radio-inline pull-left mr10" >
                    <span class="u-radio">
                      <input type="radio" name="good_type" value="减价" onclick="goodType(this)">
                      <span class="radio-icon"></span>
                    </span>减价
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-md-2 col-sm-3">活动商品：</label>
              <div class="col-md-10 col-sm-9">
                <div class="mb10"><a class="btn btn-default" href="javascript:;" onclick="addGood()">添加商品</a></div>
                <table class="table" style="margin-bottom: 0;">
                  <thead>
                    <tr>
                      <th>商品名称</th>
                      <th>原价</th>
                      <th>优惠</th>
                      <th>优惠后</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody id="shopTbody">
                    <tr>
                      <td class="text-center" colspan="7">加载中...</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-md-2 col-sm-3">&nbsp;</label>
              <div class="btn-box col-md-10 col-sm-9">
                <button id="btn-confirm" class="btn btn-primary">完成</button>
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
            <button class="btn btn-primary pull-right" onclick="batchAddGood(this)"><span class="iconfont icon-add"></span>批量添加</button>
          </div>
          <table class="table">
            <thead>
              <tr>
                <th class="w40">
                  <label class="u-checkbox"><input type="checkbox" name="selectAll"><span class="checkbox-icon"></span></label>
                </th>
                <th>商品</th>
                <th class="text-right">操作</th>
              </tr>
            </thead>
            <tbody id="goodModal">
              <tr>
                <td class="text-center" colspan="3">加载中...</td>
              </tr>
            </tbody>
          </table>
          <div id="goodModalPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
  <script id="addGoodTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td class="w40">
            <label class="u-checkbox">
              <% if (rows[i].promo_id == 0) { %>
                <input type="checkbox" name="selectItem" data-price="<%:= rows[i].inner_price%>" data-path='<?=UPLOAD_URL?><%:=rows[i].pict_url %>' data-title="<%:=rows[i].title%>" value="<%:= rows[i].goods_id%>">
              <%} else {%>
                <input type="checkbox" name="selectItem" data-price="<%:= rows[i].inner_price%>" data-path='<?=UPLOAD_URL?><%:=rows[i].pict_url %>' data-title="<%:=rows[i].title%>" value="<%:= rows[i].goods_id%>" disabled>
              <% } %>
              <span class="checkbox-icon"></span>
            </label>
          </td>
          <td >
            <img src="<?=UPLOAD_URL?><%:=rows[i].pict_url %>" width="50" height="50" style="display: inline-block;margin-top: -30px;">
            <div style="display: inline-block">
              <p><%:=rows[i].title%></p>
              <p>¥ <%:=rows[i].inner_price %></p>
            </div>
          </td>
          <td class="text-right">
            <% if (rows[i].promo_id == 0) { %>
              <a  class="btn btn-default" onclick="selectGood(<%:=rows[i].goods_id%>,'<%:=rows[i].inner_price%>','<?=UPLOAD_URL?><%:=rows[i].pict_url %>','<%:=rows[i].title%>')" style="cursor: pointer">选择</a>
            <%} else {%>
              <span >已参加活动</span>
            <% } %>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="3">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="goodsTpl" type="text/html">
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <tr>
          <td  >
            <img src="<%:=list[i].src %>" width="50" height="50" style="display:inline-block;">
            <p style="display: inline-block;"><%:=list[i].name %></p>
          </td>
          <td>¥ <%:=list[i].price %></td>
          <td>
            <span style="display: inline-block"><%:=list[i].discount_name %></span>
            <% if (list[i].discount_name == "打折") { %>
            <input type="text" class="form-control dec_input" data-id="<%:=list[i].id%>"
                   value="<%:=list[i].dec_input*10%>" onchange="editPrice(this,<%:=list[i].id%>,<%:=[i] %>,'<%:=list[i].price%>','<%:=list[i].discount_name%>')" style="width: 100px;display: inline-block">
            <%} else {%>
            <input type="text" class="form-control dec_input" data-id="<%:=list[i].id%>"
                   value="<%:=list[i].dec_input%>" onchange="editPrice(this,<%:=list[i].id%>,<%:=[i] %>,'<%:=list[i].price%>','<%:=list[i].discount_name%>')" style="width: 100px;display: inline-block">
            <% } %>
          </td>
          <td style="position: relative">
            <% if (list[i].dec_price == 0) { %>
              ¥<span class="edit_price"><%:=list[i].price %></span>
            <%} else {%>
              ¥<span class="edit_price"><%:=list[i].dec_price %></span>
            <% } %>
            <% if (list[i].sku_list.length> 1) { %>
            <br>
            <a class="sku_hover<%:=[i]%>" style="cursor: pointer" onclick="onshowTable(<%:=[i]%>)" onMouseOut="onMouseOutSku(<%:=[i]%>)" onMouseOver="onMouseSku(<%:=[i]%>)">
              <%:=list[i].sku_list.length %>个规格<%:=list[i].discount_name %>
            </a>
            <%} else {%>
            <a style="display: none"><%:=list[i].discount_type %></a>
            <% } %>
            <table class="table hover_table<%:=[i]%>" style="position: absolute;top:60px;display: none;z-index:50;width: 400px;left:-160px">
              <thead>
              <tr>
                <th>规格</th>
                <th>原价</th>
                <th><%:=list[i].discount_name %><%:=list[i].dec_input %></th>
              </tr>
              </thead>
              <tbody >
              <% for(var j = 0; j < list[i].sku_list.length; j++) { %>
                <tr>
                  <td><%:=list[i].sku_list[j].attr_names %></td>
                  <td><%:=list[i].sku_list[j].sale_price %></td>
                  <td><%:=list[i].sku_list[j].decPrice %></td>
                </tr>
              <% } %>
              </tbody>
            </table>
          </td>
          <td><a class="btn-link btn-danger" onclick="delgood(<%:=[i] %>)"> 删除</a></td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="6">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?= static_original_url('libs/laydate/laydate.js'); ?>
  <?=static_original_url('libs/moment/2.18.1/moment.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?= static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/discount_add.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>商品库 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style type="text/css">
    #shopModal .checkbox-inline{
      margin-right: 20px;
      margin-left: 0;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_good');?>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="clearfix mb20">
            <div class="form-inline pull-left">
              <div class="form-group">
                <input id="searchVal" class="form-control" type="text" name="searchVal" placeholder="输入商品标题">
              </div>
              <a href="javascript:;" class="btn btn-primary" onclick="searchVal()"><span class="iconfont icon-search"></span>搜索</a>                     
            </div>
            <div class="btn-box pull-right">
              <a href="<?=DJADMIN_URL?>mshop/store_goods/add" class="btn btn-primary"><span class="iconfont icon-add"></span>添加商品</a>
              <a href="javascript:;" class="btn btn-primary" onclick="openGoodModal()"><span class="iconfont icon-refresh"></span>商品同步</a>
              <a href="javascript:;" class="btn btn-link" onclick="openSyncModal()">同步记录</a>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table good-table">
              <thead>
                <tr>
                  <th>商品名称</th>
                  <th>所属分类</th>
                  <th>价格</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="goodTbody">
                <tr>
                  <td class="text-center" colspan="4">加载中...</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div id="goodPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="delGoodModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">商品删除后无法正常售卖，确定要删除吗？</h4>
          <div class="col-md-10 col-sm-9" style="margin-top: 10px">
            <label class="radio-inline"><span class="u-radio"><input type="radio" name="delType" value="0" checked> <span class="radio-icon"></span></span>仅删除商品库商品
            </label>
          </div>
          <div class="col-md-10 col-sm-9" style="margin-top: 10px">
            <label class="radio-inline"><span class="u-radio"><input type="radio" name="delType" value="1"> <span class="radio-icon"></span></span>删除门店和商品库商品
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="button" class="btn btn-danger" id="del-confirm">删除</button>
        </div>
      </div>
    </div>
  </div>
  <div id="addGoodModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">同步部分商品到分店</h4>
        </div>
        <div class="modal-body">
          <div class="mb20 clearfix">
            <button class="btn btn-primary pull-right" onclick="batchAddGood(this)"><span class="iconfont icon-add"></span>添加到分店</button>
            <div class="form-inline pull-left">
            <div class="form-group">
              <input id="searchModalVal" class="form-control" type="text" name="searchModalVal" placeholder="输入商品标题">
            </div>
            <a href="javascript:;" class="btn btn-primary" onclick="searchModalVal()"><span class="iconfont icon-search"></span>搜索</a>
            </div>  
          </div>
          <table class="table">
            <thead>
              <tr>
                <th class="w40">
                  <label class="u-checkbox"><input type="checkbox" name="selectAll"><span class="checkbox-icon"></span></label>
                </th>
                <th>商品名称</th>
                <th class="text-right">&nbsp;</th>
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
  <div id="addshopModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">同步部分商品到分店</h4>
        </div>
        <div class="modal-body">
          <h4 style="font-weight: 600;margin-top: 0;font-size: 16px;">选择门店</h4>
          <p style="margin-bottom: 20px;">已经选择<span id="shop-number">0</span>分店</p>
            <div id="shopModal"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
            <button type="button" class="btn btn-primary" id="sync-btn" onclick="batchAddShop()">确认</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="addSyncModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">同步记录</h4>
        </div>
        <div class="modal-body">
          <table class="table">
            <thead>
              <tr>
                <th>操作</th>
                <th>内容</th>
                <th>操作时间</th>
              </tr>
            </thead>
            <tbody id="syncModal">
              <tr>
                <td class="text-center" colspan="5">加载中...</td>
              </tr>
            </tbody>
          </table>
          <div id="syncModalPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>   
  <script id="goodTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
      <tr>
        <td>
          <% if (rows[i].sku_type == 1 && rows[i].sku_list.length > 1) { %>
            <div class="good-info">
              <img class="good-pic" src="<?=UPLOAD_URL?><%:=rows[i].pict_url %>">
              <span class="J_VIEW_MORE_GOOD good-title"><span class="good-more-arrow iconfont icon-arrow-down"></span><%:= rows[i].title %></span>
            </div>
            <div class="good-more-info" style="padding-left: 60px">
              <% for(var j = 0; j < rows[i].sku_list.length; j++) { %>
                <p><%:=rows[i].sku_list[j].attr_names %></p>
              <% } %>  
            </div>
          <%} else {%>
            <div class="good-info">
              <img class="good-pic" src="<?=UPLOAD_URL?><%:=rows[i].pict_url %>">
              <span class="good-title"><%:=rows[i].title %></span>
            </div>
          <% } %>
        </td>
        <td>
          <% if(rows[i].cate_names && rows[i].cate_names.split(',')) { %>
            <% if(rows[i].cate_names && rows[i].cate_names.split(',').length > 0) { %>
              <% for(var j = 0; j < rows[i].cate_names.split(',').length; j++) { %>
                <span class="label label-primary"><%:=rows[i].cate_names.split(',')[j] %></span>
              <% } %>
            <% } else { %>
              --
            <% } %>
          <% } else { %>
              --
          <% } %>
        </td>
        <td>
          <p>￥<%:=rows[i].inner_price %></p>
          <% if (rows[i].sku_list.length >1) { %>
            <div class="good-more-info">
              <% for(var j = 0; j < rows[i].sku_list.length; j++) { %>
              <p>￥<%:=rows[i].sku_list[j].sale_price %></p>
              <% } %>  
            </div>
          <% } %>
        </td>
        <td>
          <a class="btn-link" href="<?=DJADMIN_URL?>mshop/store_goods/edit/<%:=rows[i].id%>">编辑</a>
          <a class="btn-link btn-danger" href="javascript:;" onclick="delGood(<%:=rows[i].id %>)">删除</a>
        </td>
      </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="4">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="classTpl" type="text/html">
    <select id="select-class" class="form-control" name="category" onchange="changeClass()">
      <option value="">选择分类</option>
      <% if (list.length > 0) { %>
        <% for(var i = 0; i < list.length; i++) { %>
          <option value="<%:=list[i].id %>"><%:=list[i].cate_name %></option>
        <% } %>
      <% } %>
    </select>
  </script>
  <script id="shopTpl" type="text/html">
    <select id="select-shop" class="form-control" name="shopgory" onchange="changeShop()">
      <% if (list.length > 0) { %>
        <% for(var i = 0; i < list.length; i++) { %>
          <option value="<%:=list[i].id %>"><%:=list[i].shop_name %></option>
        <% } %>
      <% } %>
    </select>
  </script>  
  <script id="modalTpl" type="text/html">
  <% if (sku_list.length >1) { %>
    <table class="table" style="margin-bottom: 0;">
      <thead>
        <tr>
          <th>规格</th>
          <th class="text-center">价格</th>
        </tr>
      </thead>
      <tbody>
        <% for(var i = 0; i < sku_list.length; i++) { %>
          <tr>
            <td><%:=sku_list[i].attr_names %></td>
            <td>
              <div class="form-group" style="margin-bottom: 0">
              <input class="form-control" value="<%:=sku_list[i].sale_price %>" type="text" name="price" oninput="changePrice(this,<%:=i %>)">
              <input class="form-control" value="<%:=sku_list[i].id %>" type="hidden">
              </div>
            </td>
          </tr>
        <% } %>
      </tbody>
    </table>
    <%} else {%>
      <div class="form-horizontal">
        <% for(var i = 0; i < sku_list.length; i++) { %>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="col-md-2 control-label">价格：</label>
            <div class="col-md-10">
              <div class="form-group" style="margin-bottom: 0">
              <input class="form-control" value="<%:=sku_list[i].sale_price %>" type="text" name="price" oninput="changePrice(this,<%:=i %>)">
              <input class="form-control" value="<%:=sku_list[i].id %>" type="hidden">
              </div>
            </div>
          </div>
        <% } %>
      </div>
    <% } %>
  </script>
  <script id="syncModalTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td class="w90"><%:= rows[i].title%></td>
          <td><p style="width: 300px;"><%:= rows[i].desc%></p></td>
          <td><%:= rows[i].time%></td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="5">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="goodModalTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td class="w40">
            <label class="u-checkbox">
            <% if(rows[i].is_check) { %>
            <input type="checkbox" name="selectItem" value="<%:= rows[i].id%>" checked>
            <%} else {%>
            <input type="checkbox" name="selectItem" value="<%:= rows[i].id%>">
            <% } %>
            <span class="checkbox-icon"></span></label>
          </td>
          <td>
            <div class="good-info">
              <img class="good-pic" src="<%:= __UPLOADURL__+rows[i].pict_url%>" alt="<%:= rows[i].title%>">
              <span class="good-title"><%:= rows[i].title%></span>
            </div>
          </td>
          <td class="text-right">
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="3">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="shopModalTpl" type="text/html">
  <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="shopAll"><span class="checkbox-icon"></span></span>全部分店</label>
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <label class="checkbox-inline" onclick="addShopItem()"><span class="u-checkbox"><input type="checkbox" name="shopItem" value="<%:=list[i].id %>"><span class="checkbox-icon"></span></span><%:=list[i].shop_name %></label>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="3">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/store_good_list.min.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>商品列表 - 微外卖</title>
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
                <select id="shop_id" class="form-control" name="shop_id">
                  <?php if(!empty($shop_list)):?>
                    <?php foreach($shop_list as $shop):?>
                      <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                    <?php endforeach;?>
                  <?php endif;?>
                </select>
              </div>
              <div class="form-group">
                <select id="cate_id" class="form-control" name="cate_id">
                  <option value="">全部分类</option>
                </select>
              </div>
              <div class="form-group">
                <select id="status" class="form-control" name="status">
                  <option value="">全部状态</option>
                  <option value="1">已上架</option>
                  <option value="0">已下架</option>
                </select>
              </div>
              <div class="form-group">
                <input id="title" class="form-control" type="text" name="title" placeholder="输入商品标题" style="width: 200px;">
              </div>
              <button id="btn-search" class="btn btn-primary"><span class="iconfont icon-search"></span>搜索</button>                   
            </div>
            <div class="btn-box pull-right">
              <button id="batchShelvesUp" class="btn btn-primary"><span class="iconfont icon-shangjia"></span>批量上架</button>
              <button id="batchShelvesDown" class="btn btn-primary"><span class="iconfont icon-xiajia"></span>批量下架</button>
              <button id="btn-add-good" class="btn btn-primary"><span class="iconfont icon-add"></span>添加商品</button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table good-table">
              <thead>
                <tr>
                  <th class="w40">
                    <label class="u-checkbox"><input type="checkbox" name="selectAll"><span class="checkbox-icon"></span></label>
                  </th>
                  <th>商品名称</th>
                  <th>所属分类</th>
                  <th>来源</th>
                  <th>价格</th>
                  <th>商品库存</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="goodTbody">
                <tr>
                  <td class="text-center" colspan="7">加载中...</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div id="goodPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="editInventoryModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="inventory-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">修改库存</h4>
        </div>
        <div class="modal-body">
          <div id="inventoryTable"><p class="text-center">加载中...</p></div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="save-inventory" class="btn btn-primary">保存</button>
        </div>
      </form>
    </div>
  </div>
  <div id="delGoodModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">商品删除后无法正常售卖，确定要删除吗？</h4>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="button" class="btn btn-danger" id="del-confirm">删除</button>
        </div>
      </div>
    </div>
  </div>
  <script id="goodTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td class="w40">
            <label class="u-checkbox">
              <input type="checkbox" name="selectItem" value="<%:= rows[i].id %>">
              <span class="checkbox-icon"></span>
            </label>
          </td>
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
            <% if(rows[i].cate_name.length > 0) { %>
              <% for(var j = 0; j < rows[i].cate_name.length; j++) { %>
                <span class="label label-primary"><%:=rows[i].cate_name[j] %></span>
              <% } %>
            <% } else { %>
              --
            <% } %>
          </td>
          <td>
            <% if (rows[i].store_goods_id >0) { %>
              <p>同步商品</p>
            <% } else { %>
              <p>非同步商品</p>
            <% } %>
          </td>
          <td>
            <p>￥<%:=rows[i].inner_price %></p>
            <% if (rows[i].sku_list.length > 1) { %>
              <div class="good-more-info">
                <% for(var j = 0; j < rows[i].sku_list.length; j++) { %>
                  <p>￥<%:=rows[i].sku_list[j].sale_price %></p>
                <% } %>  
              </div>
            <% } %>
          </td>
          <td>
            <p>
              <% if (rows[i].is_num_hide && rows[i].use_stock_num >= 0) { %>
                --
              <% } else if(rows[i].use_stock_num < 0) { %>
                不限库存
              <% } else { %>
                <%:= parseFloat(rows[i].use_stock_num) || 0 %>
              <% } %>
              <a class="iconfont icon-edit ml10" onclick="editInventory('<%:= rows[i].id %>', '<%:= rows[i].sku_type %>', '<%:= rows[i].measure_type %>')" href="javascript:;"></a>
            </p>
            <% if (rows[i].sku_list.length > 1) { %>
              <div class="good-more-info">
                <% for(var j = 0; j < rows[i].sku_list.length; j++) { %>
                  <% if (rows[i].sku_list[j].use_stock_num < 0) { %>
                    <p>不限库存</p>
                  <% } else { %>
                    <p><%:=parseFloat(rows[i].sku_list[j].use_stock_num) || 0 %></p>
                  <% } %>
                <% } %>  
              </div>
            <% } %>
          </td>
          <td>
            <% if(rows[i].status == '0') { %>
              <a class="btn-link" href="javascript:;" onclick="shelvesUp('<%:=rows[i].id %>')">上架</a>
            <% } else { %>
              <a class="btn-link" href="javascript:;" onclick="shelvesDown('<%:=rows[i].id %>')">下架</a>
            <% } %>
            <a class="btn-link" href="<?=DJADMIN_URL?>mshop/items/edit/<%:=rows[i].id%>">编辑</a>
            <a class="btn-link btn-danger" href="javascript:;" onclick="delGood('<%:=rows[i].id %>')">删除</a>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="7">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="cateTpl" type="text/html">
    <option value="">全部分类</option>
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <option value="<%:=list[i].id %>"><%:=list[i].cate_name %></option>
      <% } %>
    <% } %>
  </script>
  <script id="inventoryTableTpl" type="text/html">
    <% if(sku_type == '1') { %>
      <div class="table-responsive" style="margin-bottom: 0;">
        <table class="table" style="margin-bottom: 0;">
          <thead>
            <tr>
              <th class="text-center">规格</th>
              <th>可用库存</th>
            </tr>
          </thead>
          <tbody>
            <% if(data.length > 0) { %>
              <% for(var i = 0; i < data.length; i++) { %>
                <tr>
                  <td class="text-center"><%:=data[i].attr_names %></td>
                  <td class="form-group">
                    <input class="form-control use_stock_num" type="text" name="use_stock_num" data-id="<%:=data[i].id %>" value="<%:=parseFloat(data[i].use_stock_num) ||0%>">
                  </td>
                </tr>
              <% } %>
            <% } else { %>
              <tr>
                <td class="text-center" colspan="2">暂无数据</td>
              </tr>
            <% } %>
          </tbody>
        </table>
        <div class="mt10 text-muted" style="margin-top: 10px">负数表示不限库存</div>
      </div>
    <% } else { %>
      <% if(data.length > 0) { %>
        <div class="form-horizontal">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="col-md-3 control-label">可用库存：</label>
            <div class="col-md-9">
              <input class="form-control use_stock_num" type="text" name="use_stock_num" data-id="<%:= data[0].id %>" value="<%:=parseFloat(data[0].use_stock_num) || 0%>">
            </div>
          </div>
        </div>
      <% } %>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/good_list.min.js');?>
</body>
</html>

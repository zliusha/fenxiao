<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>商品分类 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_good');?>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="clearfix mb20">
            <?php if($this->is_zongbu):?>
              <a class="btn btn-primary pull-left" href="javascript:;" onclick="addCate()"><span class="iconfont icon-add"></span>添加分类</a>
            <?php else:?>
              <a class="btn btn-primary pull-right" href="javascript:;" onclick="addCate()"><span class="iconfont icon-add"></span>添加分类</a>
            <?php endif;?>
            <form class="form-inline pull-left">
              <div class="form-group" style="margin-right: 10px">
                <?php if($this->is_zongbu):?>
                  <select id="shop" class="form-control" name="shop_id" style="display: none;">
                    <option value="0">全部门店</option>
                  </select>
                <?php else:?>
                  <?php if(!empty($shop_list)):?>
                    <select id="shop" class="form-control" name="shop_id">
                      <?php foreach($shop_list as $shop):?>
                        <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                      <?php endforeach;?>
                    </select>
                  <?php endif;?>
                <?php endif;?>
              </div>
            </form>
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>分类名称</th>
                  <th>分类排序
                    <div class="m-tooltip">
                      <span class="iconfont icon-help"></span>
                      <div class="m-tooltip-content" style="min-width: 168px;padding: 10px 15px;">
                        <p>数字越大排序越靠前</p>
                      </div>
                    </div>
                  </th>
                  <th>操作</th>
                </tr>
              </thead> 
              <tbody id="cateTbody">
                <tr>
                  <td class="text-center" colspan="3">加载中...</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div id="catePage" class="m-pager"></div>
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
            <button class="btn btn-default pull-left" onclick="batchAddGood(this)">批量添加</button>
            <div class="form-inline pull-right">
              <div class="form-group">
                <input id="title" class="form-control" type="text" name="title" placeholder="输入商品标题">
              </div>
              <a id="btn-search" href="javascript:;" class="btn btn-primary"><span class="iconfont icon-search"></span>搜索</a>
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
            <tbody id="goodTbody">
              <tr>
                <td class="text-center" colspan="3">加载中...</td>
              </tr>
            </tbody>
          </table>
          <div id="goodPage" class="m-pager"></div>
        </div>
      </div>
    </div>
  </div>  
  <div id="editCateModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="cate-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">添加 / 编辑分类</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group">
              <label class="col-md-3 control-label">分类名称：</label>
              <div class="col-md-9"><input id="cate_name" class="form-control" type="text" name="cate_name" placeholder="请输入分类名称"></div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-3 control-label">分类排序：</label>
              <div class="col-md-9"><input id="cate_sort" class="form-control" type="number" name="cate_sort" placeholder="请输入分类排序"></div>
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
  <div id="delCateModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">确定要删除该分类吗？</h4>
        </div>
        <div class="modal-footer">
          <button class="btn btn-default" data-dismiss="modal">取消</button>
          <button id="del-confirm" class="btn btn-danger">删除</button>
        </div>
      </div>
    </div>
  </div>
  <script id="cateTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].cate_name %></td>
          <td><%:=rows[i].sort %></td>
          <td>
            <a class="btn-link" href="javascript:;" onclick="editCate('<%:=rows[i].id %>', '<%:=rows[i].cate_name %>','<%:=rows[i].sort %>')">编辑</a>
            <a class="btn-link btn-danger" href="javascript:;" onclick="delCate('<%:=rows[i].id %>')">删除</a>
            <a class="btn-link" href="javascript:;" onclick="openGoodModal('<%:=rows[i].id %>')">添加商品</a>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="3">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="goodTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td class="w40">
            <label class="u-checkbox">
              <% if(rows[i].cate_ids) { %>
                <% if(rows[i].cate_ids.split(',').indexOf(cate_id) > -1) { %>
                  <input type="checkbox" name="selectItem" value="<%:= rows[i].id%>" disabled>
                <% } else { %>
                  <% if(rows[i].is_check) { %>
                  <input type="checkbox" name="selectItem" value="<%:= rows[i].id%>" checked>
                  <% } else { %>
                  <input type="checkbox" name="selectItem" value="<%:= rows[i].id%>">
                  <% } %>
                <% } %>
              <% } else { %>
                <% if(rows[i].is_check) { %>
                <input type="checkbox" name="selectItem" value="<%:= rows[i].id%>" checked>
                <% } else { %>
                <input type="checkbox" name="selectItem" value="<%:= rows[i].id%>">
                <% } %>
              <% } %>
              <span class="checkbox-icon"></span>
            </label>
          </td>
          <td>
            <div class="good-info">
              <img class="good-pic" src="<%:= __UPLOADURL__+rows[i].pict_url%>" alt="<%:= rows[i].title%>">
              <span class="good-title"><%:= rows[i].title%></span>
            </div>
          </td>
          <td class="text-right">
            <% if(rows[i].cate_ids) { %>
              <% if(rows[i].cate_ids.split(',').indexOf(cate_id) > -1) { %>
                <button class="btn btn-default btn-sm" onclick="delGood(this, <%:= rows[i].id%>)">移除</button>
              <% } else { %>
                <button class="btn btn-primary btn-sm" onclick="addGood(this, <%:= rows[i].id%>)">添加</button>
              <% } %>
            <% } else { %>
              <button class="btn btn-primary btn-sm" onclick="addGood(this, <%:= rows[i].id%>)">添加</button>
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
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/good_cate.min.js');?>
</body>
</html>

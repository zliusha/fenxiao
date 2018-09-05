<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>限时折扣 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_promotion');?>
      <div class="main-body">
        <div class="main-body-inner">
          <?php if($this->is_zongbu):?>
            <div class="clearfix mb20">
              <div class="form-inline pull-left">
                <div class="form-group">
                  <select id="shop" class="form-control" name="shop_id" >
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
                  <select id="active_state" class="form-control active_state" name="active_state">
                    <option value="0">活动状态</option>
                    <option value="1">未开始</option>
                    <option value="2">进行中</option>
                    <option value="3">已结束</option>
                  </select>
                </div>
                <div class="form-group">
                  <input id="searchVal" class="form-control searchVal" type="text" name="searchVal" placeholder="输入活动标题">
                </div>
                <a href="javascript:;" id="btn-search" class="btn btn-primary"><span class="iconfont icon-search"></span>搜索</a>
              </div>
              <div class="btn-box pull-right">
                <a href="<?=DJADMIN_URL?>mshop/promotion/discount_add" class="btn btn-primary"><span class="iconfont icon-add"></span>创建活动</a>
              </div>
            </div>
          <?php else:?>
            <!--分店-->
            <div class="clearfix mb20">
              <div class="form-inline pull-left">
                <div class="form-group" style="display: none">
                  <select id="shop" class="form-control" name="shop_id">
                    <?php if(!empty($shop_list)):?>
                      <?php foreach($shop_list as $shop):?>
                        <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                      <?php endforeach;?>
                    <?php endif;?>
                  </select>
                </div>
                <a href="<?=DJADMIN_URL?>mshop/promotion/discount_add" class="btn btn-primary"><span class="iconfont icon-add"></span>创建活动</a>
              </div>
              <div class="form-inline pull-right">
                <div class="form-group">
                  <select id="active_state" class="form-control active_state" name="active_state" >
                    <option value="0">活动状态</option>
                    <option value="1">进行中</option>
                    <option value="2">未开始</option>
                    <option value="3">已结束</option>
                  </select>
                </div>
                <div class="form-group">
                  <input id="searchVal" class="form-control searchVal" type="text" name="searchVal" placeholder="输入活动标题">
                </div>
                <a href="javascript:;"  id="btn-search" class="btn btn-primary pull-right"   style="margin-left: 5px"><span class="iconfont icon-search"></span>搜索</a>
              </div>
            </div>
          <?php endif;?>
          <div id="shopCon">
            <table class="table">
              <thead>
                <tr>
                  <th>活动名称</th>
                  <th>门店</th>
                  <th>状态</th>
                  <th>优惠方式</th>
                  <th>规则</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody id="activeTbody">
                <tr>
                  <td class="text-center" colspan="6">加载中...</td>
                </tr>
              </tbody>
            </table>
            <div id="activePage" class="m-pager"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
  <div id="endActiveModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">活动结束后不可恢复，确定要结束吗？</h4>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="button" class="btn btn-danger" id="end-confirm">结束</button>
        </div>
      </div>
    </div>
  </div>
  <div id="delActiveModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">活动删除后不可恢复，确定要删除吗？</h4>
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
          <h4 class="modal-title">添加商品</h4>
        </div>
        <div class="modal-body">
          <div class="mb20">
            <button class="btn btn-default" onclick="batchAddGood(this)">批量添加</button>
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
  <script id="activeTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:= rows[i].title%></td>
          <td><%:= rows[i].shop.shop_name%></td>
          <td>
            <%:= rows[i].status.alias%><br>
            <%:= rows[i].start_time.alias%> 至 <%:= rows[i].end_time.alias%>
          </td>
          <td><%:= rows[i].discount_type.alias%></td>
          <td>每天00:00--23:00<br>
            <% if (rows[i].limit_buy == 0) { %>
              不限购
            <%} else {%>
              每人每种商品前<%:= rows[i].limit_buy%>件享受优惠
            <% } %>
          </td>
          <td>
            <a class="btn-link" href="<?=DJADMIN_URL?>mshop/promotion/discount_edit/<%:=rows[i].id%>">编辑</a>
            <% if (rows[i].status.alias == "已结束") { %>
              <span class="text-muted ml10 mr10">已结束</span>
              <a class="btn-link btn-danger delBtn<%:= [i]%>" href="javascript:;" onclick="delActive(this,<%:=rows[i].id%>)">删除</a>
            <%} else {%>
              <a class="btn-link endBtn<%:= [i]%>" href="javascript:;" onclick="endActive(this,<%:=[i]%>,<%:=rows[i].id%>)">结束</a>
            <% } %>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="6">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
  <?=static_original_url('libs/bootstrap-switch/js/switch.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/discount_list.min.js');?>
</body>
</html>
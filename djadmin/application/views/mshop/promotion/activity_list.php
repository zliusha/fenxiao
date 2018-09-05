<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>满减活动 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_promotion');?>
      <div class="main-body">
        <div class="main-body-inner">
          <?php if($this->is_zongbu):?>
            <!--总店-->
            <div class="clearfix mb20">
              <div class="form-inline pull-left">
                <div class="form-group">
                  <select id="shop" class="form-control" name="shop_id">
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
                  <select id="active_state" class="form-control" name="active_state">
                    <option value="0">活动状态</option>
                    <option value="1">未开始</option>
                    <option value="2">进行中</option>
                    <option value="3">已结束</option>
                  </select>
                </div>
                <div class="form-group">
                  <input id="searchVal" class="form-control" type="text" name="searchVal" placeholder="输入活动标题">
                </div>
                <a href="javascript:;" id="btn-search" class="btn btn-primary"><span class="iconfont icon-search"></span>搜索</a>
              </div>
              <div class="btn-box pull-right">
                <a href="<?=DJADMIN_URL?>mshop/promotion/activity_add" class="btn btn-primary"><span class="iconfont icon-add"></span>创建活动</a>
              </div>
            </div>
          <?php else:?>
            <!--分店-->
            <div class="clearfix mb20">
              <div class="form-inline pull-left">
                <div class="form-group" style="display: none">
                  <select id="shop" class="form-control" name="shop_id" >
                    <?php if($this->is_zongbu): ?>
                    <?php endif; ?>
                    <?php if(!empty($shop_list)):?>
                      <?php foreach($shop_list as $shop):?>
                        <option value="<?=$shop['id']?>"><?=$shop['shop_name']?></option>
                      <?php endforeach;?>
                    <?php endif;?>
                  </select>
                </div>
                <a href="<?=DJADMIN_URL?>mshop/promotion/activity_add" class="btn btn-primary"><span class="iconfont icon-add"></span>创建活动</a>
              </div>
              <div class="form-inline pull-right">
                <div class="form-group">
                  <select id="active_state" class="form-control" name="active_state">
                    <option value="0">活动状态</option>
                    <option value="1">进行中</option>
                    <option value="2">未开始</option>
                    <option value="3">已结束</option>
                  </select>
                </div>
                <div class="form-group">
                  <input id="searchVal" class="form-control" type="text" name="searchVal" placeholder="输入活动标题">
                </div>
                <a href="javascript:;" class="btn btn-primary pull-right" id="btn-search" style="margin-left: 5px" ><span class="iconfont icon-search"></span>搜索</a>
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
                <th>订单优惠</th>
                <th>操作</th>
              </tr>
              </thead>
              <tbody id="activeTbody">
              <tr>
                <td class="text-center" colspan="5">加载中...</td>
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
  <script id="shopTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td><%:=rows[i].shop_name %></td>
          <td><%:=rows[i].time %></td>
          <td class="lineEdit">
            <a class="btn-link" style="margin-top: -20px" href="<?=DJADMIN_URL?>mshop/shop/edit/<%:=rows[i].id%>">编辑</a>
            <a class="btn-link btn-danger" href="javascript:;" onclick="delShop('<%:=rows[i].id %>')">删除</a>
            <div class="dropdown inline-block">
              <a class="btn-link" href="javascript:;" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">链接</a>
              <div class="dropdown-menu dropdown-menu-right link-box" aria-labelledby="dLabel">
                <div class="form-inline">
                  <div class="form-group">
                    <label class="sr-only">门店链接</label>
                    <input class="form-control" type="text" value="<%:=rows[i].qr_url%>" readonly>
                  </div>
                  <button class="btn btn-primary" onclick="copyUrl(this)">复制</button>
                </div>
              </div>
            </div>
            <div class="dropdown inline-block">
              <a class="btn-link" href="javascript:;" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">二维码</a>
              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dLabel">
                <img class="shop-qrcode" src="<?=DJADMIN_URL?>qr_api/index?s=6&d=<%:=rows[i].qr_url%>" alt="二维码">
                <p class="text-center">扫一扫访问门店</p>
              </div>
            </div>
            <label class="u-switch" title="是否开启自动接单">
              <% if(rows[i].status == '1') { %>
              <input type="checkbox" onclick="changeStatus('<%:=rows[i].id %>', '0')">
              <% } else { %>
              <input type="checkbox" onclick="changeStatus('<%:=rows[i].id %>', '1')" checked>
              <% } %>
              <div class="u-switch-checkbox" data-on="营业" data-off="停业"></div>
            </label>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="3">暂无数据</td>
      </tr>
    <% } %>
  </script>
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
          <td class="activity_td">
            <% for(var j = 0; j < rows[i].discountList.length; j++) { %>
            <span>满<%:= rows[i].discountList[j].price%>减<%:= rows[i].discountList[j].red_price%>,</span>
            <% } %>
          </td>
          <td>
            <a class="btn-link" href="<?=DJADMIN_URL?>mshop/promotion/activity_edit/<%:=rows[i].id%>">编辑</a>
            <% if (rows[i].status.alias == "已结束") { %>
              <span class="text-muted ml10 mr10">已结束</span>
              <a class="btn-link btn-danger delBtn<%:=[i]%>" href="javascript:;" onclick="delActive(this,<%:=rows[i].id%>)">删除</a>
            <%} else {%>
              <a class="btn-link endBtn<%:=[i]%>" href="javascript:;" onclick="endActive(this,<%:=[i]%>,<%:=rows[i].id%>)">结束</a>
            <% } %>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="5">暂无数据</td>
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
  <?=static_original_url('djadmin/mshop/js/activity_list.min.js');?>
</body>
</html>

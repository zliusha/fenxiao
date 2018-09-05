<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>新用户优惠 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <input id="is_admin" type="hidden" value="<?=$this->is_zongbu?>">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_promotion');?>
      <div class="main-body">
        <div class="main-body-inner">
          <?php if ($this->is_zongbu): ?>
            <div class="mb20">
              <a onclick="manyClick()" class="btn btn-primary" id="manySet"><span class="iconfont icon-pingjia"></span>批量设置</a>
            </div>
            <div id="shopCon">
              <table class="table">
                <thead>
                  <tr>
                    <th class="w40">
                      <label class="u-checkbox"><input type="checkbox" name="selectAll"><span class="checkbox-icon"></span></label>
                    </th>
                    <th>门店名称</th>
                    <th>优惠金额</th>
                    <th>操作</th>
                  </tr>
                </thead>
                <tbody id="shopTbody">
                  <tr>
                    <td class="text-center" colspan="4">加载中...</td>
                  </tr>
                </tbody>
              </table>
              <div id="shopPage" class="m-pager"></div>
            </div>
          <?php else: ?>
            <form id="user_from" class="form-horizontal m-form-horizontal bv-form">
              <div class="form-group">
                <label class="control-label col-md-2 col-sm-3">选择门店：</label>
                <div class="col-md-10 col-sm-9">
                  <select id="shop" class="form-control" name="shop_id" style="width: 200px">
                    <?php if ($this->is_zongbu): ?>
                    <?php endif; ?>
                    <?php if (!empty($shop_list)): ?>
                      <?php foreach ($shop_list as $shop): ?>
                        <option value="<?= $shop['id'] ?>"><?= $shop['shop_name'] ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                  <div class="text-muted mt10">开启门店优惠后，新用户下单会立减相应的金额，并且不受其他优惠活动的限制。</div>
                </div>
              </div>
              <div class="form-group form-inline">
                <label class="control-label col-md-2 col-sm-3">开启活动：</label>
                <div class="col-md-10 col-sm-9">
                  <div class="clearfix">
                    <div class="form-inline form-group">
                      <div style="padding-top: 7px;" class="pull-left form-group">
                        <label class="u-switch">
                          <input type="checkbox" name="dispatch" value="开启活动" onclick="turnOrOff(this)">
                          <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-group minus_view" style="display: none">
                <label class="control-label col-md-2 col-sm-3">下单立减：</label>
                <div class="col-md-10 col-sm-9">
                  <div class="input-group" style="width: 200px">
                    <input id="price" class="form-control" type="text" name="price">
                    <span class="input-group-addon">元</span>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="control-label col-md-2 col-sm-3"></label>
                <div class="btn-box col-md-10 col-sm-9">
                  <button class="btn btn-primary" id="save_btn">保存</button>
                </div>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php if ($this->is_zongbu): ?>
  <div id="editInventoryModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="inventory-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">修改优惠金额</h4>
        </div>
        <div class="modal-body">
          <div id="inventoryTable">
            <div class="form-horizontal">
              <div class="form-group" style="margin-bottom: 0;">
                <div class="col-md-12">
                  <div class="input-group">
                    <input class="form-control priceInput" type="text" name="use_stock_num">
                    <span class="input-group-addon">元</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="save-inventory" class="btn btn-primary">保存</button>
        </div>
      </form>
    </div>
  </div>
  <div id="manyShowModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="many-form" class="modal-content" >
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">批量设置</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group form-inline">
              <label class="control-label col-md-3 col-sm-5">开启活动：</label>
              <div class="col-md-9 col-sm-7">
                <div class="clearfix">
                  <div class="form-inline form-group">
                    <div style="padding-top: 7px;" class="pull-left form-group">
                      <label class="u-switch">
                        <input type="checkbox" name="dispatch" value="开启活动" onclick="turnOrOffMany(this)">
                        <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="form-group minus_view" style="margin-bottom: 0;">
              <label class="control-label col-md-3 col-sm-5">下单立减：</label>
              <div class="col-md-9 col-sm-7">
                <div class="input-group" style="width: 220px">
                  <input id="manyPrice" class="form-control" type="text" name="manyPrice">
                  <span class="input-group-addon">元</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="button" class="btn btn-primary" id="many-confirm">确定</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
  <div id="delShopModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">门店删除后不可恢复，确定要删除吗？</h4>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="button" class="btn btn-danger" id="del-confirm">删除</button>
        </div>
      </div>
    </div>
  </div>
  <script id="shopTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td>
            <label class="u-checkbox">
              <input type="checkbox" name="selectItem" value="" data-id="<%:=rows[i].id %>"/>
              <span class="checkbox-icon"></span>
            </label>
          </td>
          <td><%:=rows[i].shop_name%></td>
          <td>
            <p>
              ￥<%:=rows[i].newbie_coupon%>
              <a class="iconfont icon-edit ml10" href="javascript:;" onclick="editInventory(<%:=rows[i].id %>,<%:= i %>,<%:=rows[i].newbie_coupon %>,<%:=rows[i].is_newbie_coupon %>)"></a>
            </p>
          </td>
          <td class="lineEdit">
            <label class="u-switch" title="是否开启活动">
              <% if(rows[i].is_newbie_coupon == '1') { %>
                <input type="checkbox" onclick="changeStatus('<%:=rows[i].id %>', '<%:=rows[i].newbie_coupon %>','<%:=rows[i].is_newbie_coupon %>',this)" checked>
              <% } else { %>
                <input type="checkbox" onclick="changeStatus('<%:=rows[i].id %>', '<%:=rows[i].newbie_coupon %>','<%:=rows[i].is_newbie_coupon %>',this)">
              <% } %>
              <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
            </label>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="4">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="goodTpl" type="text/html">
    <% if (rows.length > 0) { %>
      <% for(var i = 0; i < rows.length; i++) { %>
        <tr>
          <td class="w40">
            <label class="u-checkbox"><input type="checkbox" name="selectItem" value="<%:= rows[i].id%>"><span class="checkbox-icon"></span></label>
          </td>
          <td>
            <div class="good-info">
              <img class="good-pic" src="<%:= __UPLOADURL__+rows[i].pict_url%>" alt="<%:= rows[i].title%>">
              <span class="good-title"><%:= rows[i].title%></span>
            </div>
          </td>
          <td class="text-right">
            <button class="btn btn-default btn-sm" onclick="addGood(this,<%:= rows[i].id%>)">添加</button>
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
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js'); ?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js'); ?>
  <?=static_original_url('djadmin/js/main.min.js'); ?>
  <?=static_original_url('djadmin/mshop/js/new_user.min.js'); ?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>裂变优惠券 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_promotion');?>
      <div class="main-body">
        <div class="main-body-inner">
          <?php $this->load->view('inc/nav_coupon');?>
          <div id="couponView">
            <div class="clearfix mb20 changeDiscount">
              <div class="form-group form-inline">
                <label class="control-label pull-left" style="line-height: 32px">开启活动：</label>
                <div class="pull-left">
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
            </div>
            <p><a class="btn btn-primary" href="<?=DJADMIN_URL?>mshop/promotion/coupon_record?coupon_type=1">领取记录</a></p>
            <div style="color:#c0ccda;">您可以设置顾客下单后获得的优惠券，顾客需分享给好友后才能领取，从而拉来更多的顾客。<br>如：设置可分享的优惠券数为10张，顾客下单后，分享给好友的同时从中领取1张，最多可有9位好友领取</div>
            <hr>
          </div>
          <form id="coupon-form" class="form-horizontal m-form-horizontal" style="display: none">
            <div class="form-group form-inline status_view" style="display: none;">
              <label class="control-label col-md-2 col-sm-3">活动状态：</label>
              <div class="col-md-10 col-sm-9">
                <p id="status_text" class="form-control-static"></p>
              </div>
            </div>
            <div class="form-group form-inline" style="">
              <label class="control-label col-md-2 col-sm-3">活动时间：</label>
              <div class="col-md-10 col-sm-9">
                <input size="16" id="changeFrom" type="text" value="" style="background-color: transparent" readonly class="form_datetime form-control" name="changeFrom">
                <label>至</label>
                <input size="16" id="changeTo" type="text" value="" style="background-color: transparent" readonly class="form_datetime form-control" name="changeTo">
              </div>
            </div>
            <div class="form-group form-inline" style="">
              <label class="control-label col-md-2 col-sm-3">获取数量：</label>
              <div class="col-md-10 col-sm-9">
                <select id="active_num" class="form-control" name="active_num" onchange="changeActiveNum(this)">
                  <option value="1">1</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
                  <option value="6">6</option>
                  <option value="7">7</option>
                  <option value="8">8</option>
                  <option value="9">9</option>
                  <option value="10">10</option>
                </select>
                <div class="mt10" style="color:#c0ccda;">顾客单次下单后获得的可分享的优惠券数量</div>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-md-2 col-sm-3">优惠券名称：</label>
              <div class="col-md-10 col-sm-9">
                <div class="form-control-time " style="width: 365px">
                  <input type="text" name="discount_name" class="from form-control" id="discount_name" placeholder="限制10个汉字">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="control-label col-md-2 col-sm-3">优惠券金额：</label>
              <div class="col-md-10 col-sm-9">
                <!--固定金额-->
                <div class="clearfix mb10">
                  <label class="radio-inline pull-left">
                    <span class="u-radio">
                      <input type="radio" name="price_type" value="fixedPrice" checked onclick="usePriceType(this)">
                      <span class="radio-icon"></span>
                    </span>固定金额
                  </label>
                  <div class="input-group pull-left" style="width: 270px;margin-left: 20px">
                    <input id="price" class="form-control" type="text" name="price">
                    <span class="input-group-addon">元</span>
                  </div>
                </div>
                <!--随机金额-->
                <div class="clearfix">
                  <label class="radio-inline pull-left">
                    <span class="u-radio">
                      <input type="radio" name="price_type" value="randomPrice"  onclick="usePriceType(this)">
                      <span class="radio-icon"></span>
                    </span>随机金额
                  </label>
                  <div class="input-group pull-left" style="width: 125px;margin-left: 20px">
                    <input id="minPrice" class="form-control" type="text" name="minPrice">
                    <span class="input-group-addon">元</span>
                  </div>
                  <div class="input-group pull-left" style="line-height: 28px;padding-left: 10px">-</div>
                  <div class="input-group pull-left" style="width: 120px;margin-left: 10px">
                    <input id="maxPrice" class="form-control" type="text" name="maxPrice">
                    <span class="input-group-addon">元</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="form-group form-inline">
              <label class="control-label col-md-2 col-sm-3">使用限制：</label>
              <div class="col-md-10 col-sm-9">
                <div class="clearfix">
                  <label class="radio-inline pull-left">
                    <span class="u-radio">
                      <input type="radio" name="use_type" value="金额限制" checked onclick="useType(this)">
                      <span class="radio-icon"></span>
                    </span>订单金额限制
                  </label>
                  <div class="pull-left" style="line-height: 36px;margin-top: -2px;margin-left: 90px;">
                    <span class="pull-left">订单满</span>
                    <div class="input-group pull-left ml10 mr10" style="width: 120px;">
                      <input id="discount_num" class="form-control" type="text" name="discount_num">
                      <span class="input-group-addon">元</span>
                    </div>
                    <span class="pull-left">可用</span>
                  </div>
                  <div style="clear: both">
                    <label class="radio-inline mr10">
                      <span class="u-radio">
                        <input type="radio" name="use_type" value="减价" onclick="useType(this)">
                        <span class="radio-icon"></span>
                      </span>原价购买时可用(不能同时享受满减活动)
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div class="form-group form-inline">
              <label class="control-label col-md-2 col-sm-3">使用时间：</label>
              <div class="col-md-10 col-sm-9">
                <input size="16" id="accountFrom" type="text" style="background-color: transparent" readonly class="use_datetime form-control" name="accountFrom">
                <label>至</label>
                <input size="16" id="accountTo" type="text" style="background-color: transparent" readonly class="use_datetime form-control" name="accountTo">
                <div class="mt10" style="color: #c0ccda;">优惠券创建后即可被领取，这里设置的「开始时间」是指优惠券可被使用的时间</div>
              </div>
            </div>
            <div class="form-group form-inline">
              <label class="control-label col-md-2 col-sm-3"></label>
              <div class="col-md-10 col-sm-9">
                <button id="btn-confirm" class="btn btn-primary">保存</button>
                <a id="editActive" class="btn btn-primary" onclick="editActive()">编辑</a>
                <a id="endActive" class="btn btn-primary ml10" style="display: none" onclick="endActive(this,'0','4')">结束活动</a>
                <a id="delActive" class="btn btn-default ml10" onclick="delActive()" style="display: none">删除</a>
              </div>
            </div>
          </form>
          <!-- 活动-->
          <div id="coupon_active" style="display: none"></div>
        </div>
      </div>
    </div>
  </div>
  <div id="endDiscountModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span>
          </button>
          <h4 class="modal-title">优惠券删除后不可恢复，确定要删除吗？</h4>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="button" class="btn btn-danger" id="delDiscount">删除</button>
        </div>
      </div>
    </div>
  </div>
  <div id="endActiveModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span>
          </button>
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
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span>
          </button>
          <h4 class="modal-title">活动删除后不可恢复，确定要删除吗？</h4>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
          <button type="button" class="btn btn-danger" id="del-confirm">删除</button>
        </div>
      </div>
    </div>
  </div>
  <script id="discountTpl" type="text/html">
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <tr>
          <td><%:=list[i].name %> <br>¥<%:=list[i].price %></td>
          <td><span class="activeTableStatus"><%:=list[i].status %></span><br>
            <%:=list[i].fromTime %> 至 <%:=list[i].toTime %>
          </td>
          <td>已领:2 <br>已用:8</td>
          <td>无领取限制<br>仅原价购买可用</td>
          <td class="lastMake btn-box">
            <a class="btn-link btn-primary" onclick="editDiscount('<%:=list[i].name %>',<%:=list[i].price%>,'<%:=list[i].fromTime %>','<%:=list[i].toTime %>',<%:=list[i].num %>)">编辑</a>
            <a class="btn-link btn-danger ml10" onclick="endDiscount(<%:=[i]%>)">删除</a>
          </td>
        </tr>
      <% } %>
    <%} else {%>
      <tr>
        <td class="text-center" colspan="5">暂无数据</td>
      </tr>
    <% } %>
  </script>
  <script id="activeTpl" type="text/html">
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <div class="form-group form-inline">
          <label class="control-label col-md-2 col-sm-3">活动状态：</label>
          <div class="col-md-10 col-sm-9">
            <p class="form-control-static"><%:=list[i].status %></p>
          </div>
        </div>
        <div class="form-group form-inline">
          <label class="control-label col-md-2 col-sm-3">活动时间：</label>
          <div class="col-md-10 col-sm-9">
            <input size="16" id="changeFrom" type="text" value="" readonly class="form_datetime form-control" name="changeFrom">
            <label>至</label>
            <input size="16" id="changeTo" type="text" value="" readonly class="form_datetime form-control" name="changeTo">
          </div>
        </div>
        <div class="form-group form-inline">
          <label class="control-label col-md-2 col-sm-3">获取数量：</label>
          <div class="col-md-10 col-sm-9">
            <select id="active_num" class="form-control" name="active_num" onchange="changeActiveNum(this)">
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">4</option>
              <option value="5">5</option>
              <option value="6">6</option>
              <option value="7">7</option>
              <option value="8">8</option>
              <option value="9">9</option>
              <option value="10">10</option>
            </select>
            <div class="mt10" style="color:#c0ccda;">顾客单次下单后获得的可分享的优惠券数量</div>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-2 col-sm-3">优惠券名称：</label>
          <div class="col-md-10 col-sm-9">
            <div class="form-control-time w360">
              <input type="text" name="discount_name" class="from form-control" id="discount_name" placeholder="限制10个汉字">
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="control-label col-md-2 col-sm-3">面额：</label>
          <div class="col-md-10 col-sm-9">
            <div class="input-group" style="width: 200px">
              <input id="price" class="form-control" type="text" name="price">
              <span class="input-group-addon">元</span>
            </div>
          </div>
        </div>
        <div class="form-group form-inline">
          <label class="control-label col-md-2 col-sm-3">使用限制：</label>
          <div class="col-md-10 col-sm-9">
            <div class="clearfix">
              <label class="radio-inline pull-left">
                <span class="u-radio">
                  <input type="radio" name="use_type" value="金额限制" checked onclick="useType(this)">
                  <span class="radio-icon"></span>
                </span>订单金额限制
              </label>
              <div class="pull-left" style="line-height: 36px;margin-top: -2px;margin-left: 90px;">
                <span class="pull-left">订单满</span>
                <div class="input-group pull-left ml10 mr10" style="width: 120px;">
                  <input id="discount_num" class="form-control" type="text" name="discount_num">
                  <span class="input-group-addon">元</span>
                </div>
                <span class="pull-left">可用</span>
              </div>
              <label class="radio-inline pull-left mr10" style="margin-left: 10px">
                <span class="u-radio">
                  <input type="radio" name="use_type" value="减价" onclick="useType(this)">
                  <span class="radio-icon"></span>
                </span>原价购买时可用(不能同时享受满减活动)
              </label>
            </div>
          </div>
        </div>
        <div class="form-group form-inline">
          <label class="control-label col-md-2 col-sm-3">使用时间：</label>
          <div class="col-md-10 col-sm-9">
            <input size="16" id="accountFrom" type="text" value="" readonly class="form_datetime form-control" name="accountFrom">
            <label>至</label>
            <input size="16" id="accountTo" type="text" value="" readonly class="form_datetime form-control" name="accountTo">
            <div class="mt10" style="color: #c0ccda;">优惠券创建后即可被领取，这里设置的「开始时间」是指优惠券可被使用的时间</div>
          </div>
        </div>
        <div class="form-group form-inline">
          <label class="control-label col-md-2 col-sm-3"></label>
          <div class="col-md-10 col-sm-9">
            <button id="btn-confirm" class="btn btn-primary">保存</button>
            <a id="endActive" class="btn btn-primary ml10" style="display: none" onclick="endActive(this,'0','4')">结束活动</a>
            <a id="delActive" class="btn btn-default ml10" onclick="delActive()" style="display: none">删除</a>
          </div>
        </div>
      <% } %>
    <%} else {%>
      <div>暂无数据</div>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/moment/2.18.1/moment.min.js'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js'); ?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js'); ?>
  <?=static_original_url('libs/laydate/laydate.js'); ?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js'); ?>
  <?=static_original_url('djadmin/js/main.min.js'); ?>
  <?=static_original_url('djadmin/mshop/js/coupon_list.min.js'); ?>
</body>
</html>

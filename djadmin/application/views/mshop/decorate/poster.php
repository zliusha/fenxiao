<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>门店海报 - 微外卖</title>
  <?php $this->load->view('inc/global_header');?>
  <?=static_original_url('libs/bootstrap-datetimepicker/2.0/css/bootstrap-datetimepicker.min.css');?>
  <?=static_original_url('djadmin/mshop/css/decorate.min.css');?>
  <style type="text/css">
    .m-upload-logo-banner {
      width: 270px;
      height: 80px;
      line-height: 80px;
    }
  </style>
</head>
<body>
  <div id="main">
  <input id="shop_id" type="hidden" value="<?=$shop_id?>">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_decorate');?>
      <div class="main-body">
        <div class="main-body-inner row">
          <div class="decorate-box">
            <div class="phone-box">
              <div class="phone-preview">
                <div class="phone-header">
                  <h2 id="phone-title" class="phone-header-title"></h2>
                </div>
                <div id="phone-body">
                  <div  class="store-detail-header">
                    <div class="store-detail-bottom">
                      <img class="store-detail-bottom-logo" src="">
                    </div>
                    <div class="store-detail-zoom"></div> 
                    <div class="store-shop-info">
                      <img class="store-shop-logo" id="shop-logo" src="">
                      <h3 class="store-shop-name" id="shop-name"></h3>
                      <p>门店配送.约<span id="arrive_time">0</span>分钟</p>
                      <p class="store-shop-notice" id="shop-notice"></p>
                      <div class="store-shop-bs">
                        <span class="store-shop-bs-item">
                          <img class="store-shop-bs-logo" src="<?=STATIC_URL?>djadmin/mshop/img/search.png">
                        </span>
                        <span class="store-shop-bs-item">
                          <img class="store-shop-bs-logo" src="<?=STATIC_URL?>djadmin/mshop/img/order.png">
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="m-shop-activity-item">
                    <div id="m-shop-activity-more" class="m-shop-activity-more" style="">
                        <p class="f-clearfix">
                          <span class="f-fl"><span class="u-label u-label-success u-label-success-gradient">新</span>
                          门店活动
                          </span> 
                        <span class="f-fr"><span>1</span>个活动</span>
                      </p>
                    </div>
                  </div>
                  <div class="m-shop-nav">
                    <div class="m-shop-nav-box">
                      <ul class="m-nav-tab">
                        <li class="m-nav-item z-active"><a href="javascript:;" class="m-nav-link">商品</a></li> 
                        <li class="m-nav-item"><a href="javascript:;" class="m-nav-link">评价</a></li>
                        <li class="m-nav-item"><a href="javascript:;" class="m-nav-link">商家</a></li>
                      </ul>
                    </div> 
                  </div>
                  <div class="recommend-good">
                    <div id="show-select-good">
                      
                    </div>
                  </div>
                  <div class="goods-box">
                    <div class="goods">
                      <div class="menu-wrapper">
                        <ul class="menu-list" id="menu-list">
                          
                        </ul>
                      </div>

                      <div class="goods-box-right">
                        <div id="poster-tbody" class="poster-wrapper">
                        </div>
                        <div class="goods-wrapper">

                        </div>
                      </div>
                    </div>
                  </div>
                  <footer class="g-footer m-order-submit">
                    <img src="<?=STATIC_URL?>mshop/img/shop-footer.png">
                  </footer>
                </div>
              </div>
            </div>
            <div class="shop-ctrl decorate-right">
              <div class="ctrl-module form-horizontal">
                <h3 class="title">通过运营位推荐新品、打造爆款商品，更可设置多个不同时间生效的海报。</h3>
                <div class="ctrl-module-box">
                  <div id="start-decorate" class="btn-center" style="padding: 50px 0">
                    <a href="javascript:;" class="btn btn-primary" onclick="startDecorate()">增加海报</a>
                  </div>
                  <div class="edit-decorate" id="edit-decorate">
                    
                  </div>
                  <div class="decorate-ctrl" id="decorate-ctrl">
                    <div class="banner-item">
                      <div class="clearfix">
                        <div class="upload-box">
                          <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3">海报图片：</label>
                            <div class="col-md-9 col-sm-9">
                              <div class="">
                                <div id="upload-logo-container" class="m-upload m-upload-logo-banner" style="margin-right: 20px">
                                  <span id="upload-plus" class="btn-plus upload-plus"></span>
                                  <img id="upload-pic" class="upload-pic" src="" alt="">
                                  <a class="upload-again" href="javascript:;" style="display: none;">重新上传</a>
                                  <input id="good_logo" type="text"  name="good_logo" value="">
                                  <input id="upload-logo" type="file" class="upload-input" value="">
                                </div>
                                <div class="upload-bs">
                                <!--   <a href="javascript:;" class="btn-link">替换</a> -->
                                  <a href="javascript:;" class="btn-link" onclick="delImg()">删除</a>
                                </div>
                              </div>
                              <p class="help-block">图片格式为 jpg 或 png，尺寸不得小于 750 x 250 像素，图片大小不得超过 2M</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="control-label col-md-3 col-sm-3">名称：</label>
                      <div class="col-md-9 col-sm-9">
                        <input id="poster-title" class="form-control w360" type="text"  name="poster_title">
                      </div>
                    </div>
                    <div class="form-group form-inline">
                      <label class="control-label col-md-3 col-sm-3">起止日期：</label>
                      <div class="col-md-9 col-sm-9" style="position: relative">
                        <input size="16" id="start_time" type="text" value="" readonly="" class="form_datetime form-control" style="width: 170px;background-color: transparent" name="start_time">
                        <label>至</label>
                        <input size="16" id="end_time" type="text" value="" readonly="" class="form_datetime form-control" style="width: 170px;background-color: transparent" name="end_time">
                        <div class="from-group" style="margin-top: 10px">
                          <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="date" value="1" ><span class="checkbox-icon"></span></span>周一</label>
                          <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="date" value="2" ><span class="checkbox-icon"></span></span>周二</label>
                          <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="date" value="3" ><span class="checkbox-icon"></span></span>周三</label>
                          <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="date" value="4" ><span class="checkbox-icon"></span></span>周四</label>
                          <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="date" value="5" ><span class="checkbox-icon"></span></span>周五</label>
                          <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="date" value="6" ><span class="checkbox-icon"></span></span>周六</label>
                          <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="date" value="7" ><span class="checkbox-icon"></span></span>周七</label>
                        </div>
                      </div>
                    </div>
                    <div class="form-group form-inline">
                      <label class="control-label col-md-3 col-sm-3">生效时段：</label>
                      <div class="col-md-9 col-sm-9" style="position: relative">
                        <div class="clearfix mb10 time-view">
                          <input id="datetimeStart" class="form-control" type="text" name="shop_fromTime" value="" style="width: 170px;">
                          <label>至</label>
                          <input id="datetimeEnd" class="form-control" type="text" name="shop_endTime" value="" style="width: 170px;">
                        </div>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="control-label col-md-3 col-sm-3">关联商品：</label>
                      <div class="col-md-9 col-sm-9">
                        <div class="sure-add-good" style="float: left;line-height: 34px">
                          <a  href="javascript:;" class="btn-link" onclick="changeGood()" style="margin-left: 5px">添加</a>
                        </div>
                        <div class="sureGood-box" style="display: none;">
                          <div id="sureGoodTbody" class="form-control w360" style="display: inline-block;float: left;height: inherit;min-height: 36px;"></div>
                       <!--    <input class="form-control w360" type="text"  style="display: inline-block;"> -->
                          <div style="float: left;line-height: 36px">
                            <a href="javascript:;" class="btn-link" onclick="changeGood()" style="margin-left: 5px">修改</a>  
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="form-group">
                      <label class="control-label col-md-3 col-sm-3"></label>
                      <div class="btn-box col-md-9 col-sm-9">
                        <a href="javascript:;" class="btn btn-default" onclick="preview()">预览</a>
                        <a href="javascript:;" id="btn-confirm" class="btn btn-primary" onclick="release()">确定发布</a>
                        <a href="javascript:;" class="btn btn-primary" onclick="Return()">返回</a>
                    </div>
                  </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
    <div id="addGoodModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">关联商品</h4>
        </div>
        <div class="modal-body">
          <div class="add-good-box clearfix">
            <div class="add-good-left">
              <div id="add-good-tbody">
              </div>
            </div>
            <div class="add-good-right">
              <div id="select-good-tbody"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-default" data-dismiss="modal" onclick="closeSelect()">取消</button>
          <button id="save-confirm" class="btn btn-primary" onclick="sureSelect()">保存</button>
        </div>
      </div>
    </div>
  </div>
    <script id="addGoodTpl" type="text/html">
    <% if (list.length > 0) { %>
    <div class="w-aside">
      <ul class="nav">
      <% for(var i = 0; i < list.length; i++) { %>
        <li>
          <a class="J_TOGGLE_SUBNAV" href="javascript:;">
            <% if (list[i].goods_list.length > 0) { %>
            <span class="iconfont icon-arrow-down"></span>
            <% } %>
            <!-- <span class="u-checkbox"><input type="checkbox" name="selectMenu"><span class="checkbox-icon"></span></span> -->
            <span><%:=list[i].cate_name %></span>
          </a>
          <% if (list[i].goods_list.length > 0) { %>
          <ul class="subnav">
            <% for(var j = 0; j < list[i].goods_list.length; j++) { %>
              <li class="J_NAV_ITEM">
                <label class="checkbox-inline"><span class="u-checkbox"><input type="checkbox" name="selectGood" value="<%:=list[i].goods_list[j].id %>" data-title="<%:=list[i].goods_list[j].title %>" data-price="<%:=list[i].goods_list[j].sku_list[0].sale_price %>" data-img="<%:=list[i].goods_list[j].pict_url %>"><span class="checkbox-icon"></span></span><%:=list[i].goods_list[j].title %></label>
              </li>
            <% } %>
          </ul>
          <% } %>
        </li>       
      <% } %>
      </ul>
    </div>
    <% } %>
  </script>
<div id="previewModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
        </div>
        <div class="modal-body">
          <div class="phone-preview-box">
            <div class="phone-preview">
                <div class="phone-header">
                  <h2 id="phone-title2" class="phone-header-title"></h2>
                </div>
                <div id="phone-body">
                  <div  class="store-detail-header">
                    <div class="store-detail-bottom">
                      <img class="store-detail-bottom-logo" src="">
                    </div>
                    <div class="store-detail-zoom"></div> 
                    <div class="store-shop-info">
                      <img class="store-shop-logo" id="shop-logo2" src="">
                      <h3 class="store-shop-name" id="shop-name2"></h3>
                      <p>门店配送.约<span id="arrive_time">0</span>分钟</p>
                      <p class="store-shop-notice" id="shop-notice2"></p>
                      <div class="store-shop-activity">
                        <p class="clearfix">
                          <span class="fl"><span class="u-label u-label-success u-label-success-gradient">新</span>
                          门店活动
                          </span> 
                          <span class="fr"><span>1</span>个活动</span>
                        </p>
                      </div>
                      <div class="store-shop-bs">
                        <span class="store-shop-bs-item">
                          <img class="store-shop-bs-logo" src="<?=STATIC_URL?>djadmin/mshop/img/search.png">
                        </span>
                        <span class="store-shop-bs-item">
                          <img class="store-shop-bs-logo" src="<?=STATIC_URL?>djadmin/mshop/img/order.png">
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="m-shop-activity-item">
                    <div id="m-shop-activity-more" class="m-shop-activity-more" style="">
                        <p class="f-clearfix">
                          <span class="f-fl"><span class="u-label u-label-success u-label-success-gradient">新</span>
                          新用户下单立减12元
                          </span> 
                        <span class="f-fr"><span>1</span>个活动</span>
                      </p>
                    </div>
                  </div>
                  <div class="m-shop-nav">
                    <div class="m-shop-nav-box">
                      <ul class="m-nav-tab">
                        <li class="m-nav-item z-active"><a href="javascript:;" class="m-nav-link">商品</a></li> 
                        <li class="m-nav-item"><a href="javascript:;" class="m-nav-link">评价</a></li>
                        <li class="m-nav-item"><a href="javascript:;" class="m-nav-link">商家</a></li>
                      </ul>
                    </div> 
                  </div>
                  <div class="recommend-good-no" style="display: none;">暂无推荐商品</div>
                  <div class="recommend-good">
                    <div id="show-select-good2">
                      
                    </div>
                  </div>
                  <div class="goods-box">
                    <div class="goods">
                      <div class="menu-wrapper">
                        <ul class="menu-list" id="menu-list2">
                          
                        </ul>
                      </div>

                      <div class="goods-box-right">
                        <div id="poster-tbody2" class="poster-wrapper">
                        </div>
                        <div class="goods-wrapper">

                        </div>
                      </div>
                    </div>
                  </div>
                  <footer class="g-footer m-order-submit">
                    <img src="<?=STATIC_URL?>mshop/img/shop-footer.png">
                  </footer>
                </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="text-align: center;padding-top: 0">
          <button class="btn btn-default active" onclick="changePhoneType(this)">主流机型</button>
          <button class="btn btn-default" onclick="changePhoneType(this)">iPhone X</button>
        </div>
        <div class="modal-footer" style="text-align: center;padding-top: 0">
          <button class="btn btn-warn" onclick="release()">发布</button>
        </div>
      </div>
    </div>
  </div>  
  <script id="menuTpl" type="text/html">
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <% if (i == 0) { %>
        <li class="menu-item menu-item-selected"><%:=list[i].cate_name %></li>
        <%} else {%>
        <li class="menu-item"><%:=list[i].cate_name %></li>
        <% } %>
      <% } %>
    <% } %>
  </script>
    <script id="selectGoodTpl" type="text/html">
    <h4 class="title">已经选择商品<%:=list.length %>个（最多20个）</h4>
    <% if (list.length > 0) { %>
    <div class="w-aside">
      <ul class="nav">
      <% for(var i = 0; i < list.length; i++) { %>
        <li>
          <a class="J_TOGGLE_SUBNAV" href="javascript:;">
            <span class="iconfont icon-delete" onclick="delSelectGood(<%:=i %>,<%:=list[i].id %>)"></span>
            <%:=list[i].title %>
          </a>
        </li>       
      <% } %>
      </ul>
    </div>
    <% } %>
  </script>
  <script id="sureGoodTpl" type="text/html">
    <% if (list.length > 0) { %>
      <% for(var i = 0; i < list.length; i++) { %>
        <p><%:=list[i].title %></p>       
      <% } %>
    <% } %>
  </script>
  <script id="posterTpl" type="text/html">
   <% if (list.length > 0) { %>
    <ul class="poster-list active">
      <% for(var i = 0; i < list.length; i++) { %>
      <li class="poster-item">
        <img class="poster-item-logo" src="<%:=list[i].module_data.img %>">
      </li>
      <% } %>
    </ul>
    <%} else {%>
    <div class="poster-no active">上传海报</div>
    <% } %>    
  </script>
  <script id="editPostTpl" type="text/html">
  <% for(var i = 0; i < list.length; i++) { %>
    <div class="edit-banner-item">
      <div class="form-inline clearfix">
        <div class="m-upload m-upload-logo-banner">
          <img class="upload-pic" src="<%:=list[i].module_data.img %>" alt="">
        </div>
        <div class="upload-bs">
          <h4 class="bs-title"><%:=list[i].module_data.title %>（<%:=list[i].module_data.list.length %>个商品）
            <span class="label label-primary">已上线</span>
          </h4>
          <p>生效日期： <%:=list[i].module_data.start_day %> 至 <%:=list[i].module_data.end_day %></p>
          <p>生效时段： 周<%:=list[i].module_data.week %> <%:=list[i].module_data.start_time %>-<%:=list[i].module_data.end_time %></p>
        </div>
      </div>
      <div class="edit-banner-btn">
        <a href="javascript:;" class="btn-link btn-poster-detail">
          详情
          <div class="poster-detail">
            <h3 class="good-title">关联商品</h3>
            <ul class="good-list">
            <% for(var j = 0; j < list[i].module_data.list.length; j++) { %>
              <li class="good-item"><%:=list[i].module_data.list[j].title %></li>
            <% } %>
            </ul>
          </div>
        </a>
        <a href="javascript:;" class="btn-link" onclick="editDecorate(<%:=list[i].id %>,<%:=[i] %>)">编辑</a>
        <a href="javascript:;" class="btn-link" onclick="deleteDecorate(<%:=list[i].id %>)">作废</a>
      </div>
    </div>
    <% } %> 
  </script>
  <script id="showSelectTpl" type="text/html">
  <% if (list.length > 0) { %>
    <h4 id="show-recommend-title"><%:=title %></h4>
    <ul class="recommend-good-list">
    <% for(var i = 0; i < list.length; i++) { %>
      <li class="recommend-good-item">
        <img class="recommend-good-logo" src="<%:=list[i].pict_url %>">
        <p class="recommend-good-title"><%:=list[i].title %></p>
        <p class="recommend-good-p">
          <span class="recommend-good-price">￥<%:=list[i].sku_list[0].sale_price %></span>
          <% if (list[i].sku_type=='0') { %>
          <span class="u-cart-add u-btn-primary-gradient f-fr"></span>
          <%} else {%>
          <span class="cart-sku f-fr">规格</span>
          <% } %>
        </p>
      </li>
    <% } %>  
    </ul>
    <%} else {%>
    <div class="recommend-good-no">暂无推荐商品</div>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/bootstrap-datetimepicker/2.0/js/bootstrap-datetimepicker.min.js'); ?>
  <?=static_original_url('libs/bootstrap-datetimepicker/2.0/js/locales/bootstrap-datetimepicker.zh-CN.js');?>
  <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
  <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
  <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/decorate_poster.js');?>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>门店招牌 - 微外卖</title>
  <?php $this->load->view('inc/global_header'); ?>
  <?=static_original_url('djadmin/mshop/css/decorate.min.css');?>
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
                  <div  class="store-detail-header active">
                    <div class="store-detail-bottom">
                      <img class="store-detail-bottom-logo" src="">
                    </div>
                    <div class="store-detail-zoom"></div> 
                    <div class="store-shop-info">
                      <img class="store-shop-logo" id="shop-logo" src="">
                      <h3 class="store-shop-name" id="shop-name"></h3>
                      <p>门店配送.约<span id="arrive_time">0</span>分钟</p>
                      <p class="store-shop-notice" id="shop-notice"></p>
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
                        <div class="poster-wrapper" id="poster-tbody">
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
                <h3 class="title">有效的树立你的店铺的品牌形象，使您的品牌深入人心，从而提高用户对你的信任和喜爱。</h3>
                <div class="ctrl-module-box">
                  <div id="start-decorate" class="btn-center" style="display: none;">
                    <a href="javascript:;" class="btn btn-primary" onclick="startDecorate()">开始装修</a>
                  </div>
                  <div class="edit-decorate" id="edit-decorate">
                    <div class="edit-banner-item">
                      <div class="m-upload m-upload-logo-banner">
                        <img id="edit-banner-logo" class="upload-pic" src="" alt="">
                      </div>
                      <div class="upload-bs">
                        <a href="javascript:;" class="btn btn-primary" onclick="editDecorate()">编辑</a>
                      </div>
                    </div>
                  </div>
                  <div class="decorate-ctrl" id="decorate-ctrl">
                    <div class="banner-item">
                      <div class="clearfix">
                        <div class="upload-box">
                          <div class="form-group">
                            <label class="control-label col-md-3 col-sm-3">招牌图片：</label>
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
                                  <!-- <a href="javascript:;" class="btn-link">替换</a> -->
                                  <a href="javascript:;" class="btn-link" onclick="delImg()">删除</a>
                                </div>
                              </div>
                              <p class="help-block">图片格式为 jpg 或 png，尺寸不得小于 750 x 170 像素，图片大小不得超过 2M</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                      <div class="form-group">
                        <label class="control-label col-md-3 col-sm-3"></label>
                        <div class="btn-box col-md-9 col-sm-9">
                          <a href="javascript:;" class="btn btn-default" onclick="preview()">预览</a>
                          <a href="javascript:;" id="btn-confirm" class="btn btn-primary" onclick="release()">确定发布</a>
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
                  <div  class="store-detail-header active">
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
                  <div class="m-shop-nav">
                    <div class="m-shop-nav-box">
                      <ul class="m-nav-tab">
                        <li class="m-nav-item z-active"><a href="javascript:;" class="m-nav-link">商品</a></li> 
                        <li class="m-nav-item"><a href="javascript:;" class="m-nav-link">评价</a></li>
                        <li class="m-nav-item"><a href="javascript:;" class="m-nav-link">商家</a></li>
                      </ul>
                    </div> 
                  </div>
                  <div class="recommend-good" style="display: block;">
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
                        <div class="poster-wrapper" id="poster-tbody2">
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
  <script id="posterTpl" type="text/html">
   <% if (list.length > 0) { %>
    <ul class="poster-list">
      <% for(var i = 0; i < list.length; i++) { %>
      <li class="poster-item">
        <img class="poster-item-logo" src="<%:=list[i].module_data.img %>">
      </li>
      <% } %>
    </ul>
    <%} else {%>
    <div class="poster-no">暂无海报</div>
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
    <div class="recommend-good-no">暂无商品</div>
    <% } %>
  </script>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
    <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
  <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
  <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
  <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
  <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/mshop/js/decorate_index.js');?>
</body>
</html>

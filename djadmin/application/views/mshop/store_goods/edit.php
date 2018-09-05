<?php
defined('BASEPATH') or exit('No direct script access allowed');
?>
  <!DOCTYPE html>
  <html lang="zh-CN">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>添加商品 - 微外卖</title>
    <?php $this->load->view('inc/global_header'); ?>
    <?=static_original_url('djadmin/css/good.min.css');?>
    <?=static_original_url('libs/chosen/1.7.0/chosen.min.css');?>
    <?=static_original_url('libs/bootstrap-slider/css/bootstrap-slider.min.css');?>
    <?=static_original_url('libs/jquery-tagsinput/1.3.3/css/jquery.tagsinput.min.css');?>
  </head>
  <body>
    <div id="main">
      <input id="goods_id" type="hidden" value="<?=$goods_id?>">
      <div class="container-fluid">
        <ol class="breadcrumb">
          <li>
            <a href="<?=DJADMIN_URL?>mshop/store_goods">商品库</a>
          </li>
          <li class="active">添加商品</li>
        </ol>
        <div class="main-body">
          <div class="main-title-box">
            <h3 class="main-title">基本信息</h3>
          </div>
          <div class="main-body-inner">
            <form id="good-form" class="form-horizontal good-form">
              <div class="form-group">
                <label class="control-label col-md-2 col-sm-3">商品图片：</label>
                <div class="col-md-10 col-sm-9">
                  <div id="logoTbody"></div>
                  <p class="help-block">建议使用方形图，图片大小不超过1M。最多可上传5张，第一张默认为商品主图</p>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">
                  <span class="text-danger">*</span>商品标题：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="good_title" class="form-control w360" type="text" name="good_title" placeholder="">
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">商品条码：</label>
                <div class="col-md-10 col-sm-9">
                  <input id="good_code" class="form-control w360" type="text" name="good_code" placeholder="">
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">商品描述：</label>
                <div class="col-md-10 col-sm-9">
                  <textarea class="form-control w360" name="good_detail" id="good_detail" style="height: 120px;"></textarea>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">商品标签：</label>
                <div class="col-md-10 col-sm-9">
                  <label class="radio-inline">
                    <span class="u-radio">
                      <input type="radio" name="goodLabel" value="0" checked>
                      <span class="radio-icon"></span>
                    </span>无
                  </label>
                  <label class="radio-inline">
                    <span class="u-radio">
                      <input type="radio" name="goodLabel" value="1">
                      <span class="radio-icon"></span>
                    </span>招牌
                  </label>
                  <label class="radio-inline">
                    <span class="u-radio">
                      <input type="radio" name="goodLabel" value="2">
                      <span class="radio-icon"></span>
                    </span>新品
                  </label>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">计重方式：</label>
                <div class="col-md-10 col-sm-9">
                  <label class="radio-inline" for="goodMetering">
                    <span class="u-radio">
                      <input type="radio" name="goodMetering" onclick="changeMetering(this)" value="1" checked disabled>
                      <span class="radio-icon"></span>
                    </span>计件
                  </label>
                  <label class="radio-inline" for="goodMetering">
                    <span class="u-radio">
                      <input type="radio" name="goodMetering" onclick="changeMetering(this)" value="2" disabled>
                      <span class="radio-icon"></span>
                    </span>计重
                  </label>
                  <select id="count-weight" class="form-control w90 count-weight" name="count_weight" disabled style="display: none" onchange="changeWeightType(this)">
                    <option value="1">kg</option>
                    <option value="2">g</option>
                    <option value="3">千克</option>
                    <option value="4">克</option>
                    <option value="5">斤</option>
                    <option value="6">两</option>
                  </select>
                  <p class="help-block" style="margin-bottom:0">计量方式和重量单位保存之后不能编辑</p>
                </div>
              </div>
              <!-- <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">会员价：</label>
                <div class="col-md-10 col-sm-9" style="margin-top: 7px">
                  <label class="u-switch">
                    <input type="checkbox" name="goods_member" onclick="changeMember(this)">
                    <span class="u-switch-checkbox" data-on="开启" data-off="关闭"></span>
                  </label>
                </div>
              </div> -->
              <div id="only-type-box">
                <div class="form-group add-group">
                  <label class="col-md-2 col-sm-3 control-label">商品规格：</label>
                  <div class="btn-box col-md-10 col-sm-9">
                    <a class="btn btn-link" href="javascript:;" onclick="addMany()" style="padding-left: 0;padding-top: 6px;">添加规格</a>
                  </div>
                </div>
                <div class="form-group price-group">
                  <label class="col-md-2 col-sm-3 control-label">
                    <span class="text-danger">*</span>商品价格：</label>
                  <div class="col-md-10 col-sm-9">
                    <div class="input-group w360">
                      <input id="good_price" class="form-control" type="text" name="good_price" placeholder="">
                      <span class="input-group-addon input-group-weight">元</span>
                    </div>
                  </div>
                </div>
                <!-- <div class="form-group member-group">
                  <label class="col-md-2 col-sm-3 control-label">
                    <span class="text-danger">*</span>会员价：</label>
                  <div class="col-md-10 col-sm-9">
                    <div class="input-group w360">
                      <input id="good_member" class="form-control good_member" type="text" name="good_member" placeholder="" disabled>
                      <span class="input-group-addon input-group-weight">元</span>
                    </div>
                  </div>
                </div> -->
                <div class="form-group box-group">
                  <label class="col-md-2 col-sm-3 control-label">餐盒费：</label>
                  <div class="col-md-10 col-sm-9">
                    <div class="input-group w360">
                      <input id="good_box" class="form-control" type="text" name="good_box" placeholder="">
                      <span class="input-group-addon">元</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="group clearfix" id="many" style="display: none;margin-bottom: 20px;margin-left: -10px;margin-right: -10px;">
                <label class="col-md-2 col-sm-3 control-label">商品规格：</label>
                <div class="col-md-10 col-sm-9">
                  <table class="table">
                    <thead>
                      <tr>
                        <th><span class="text-danger">*</span>规格名称</th>
                        <th>规格条码</th>
                        <th><span class="text-danger">*</span>价格（元）</th>
                        <!-- <th><span class="text-danger">*</span>会员价（元）</th> -->
                        <th>餐盒费（元）</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody id="manyTbody"></tbody>
                  </table>
                  <a class="btn btn-link" href="javascript:;" onclick="addMany()" style="padding-left: 0;padding-top: 6px;">添加规格</a>
                </div>
              </div>
              <div class="form-group prop-group">
                <label class="col-md-2 col-sm-3 control-label">属性：</label>
                <div class="btn-box col-md-10 col-sm-9">
                  <a class="btn btn-link" href="javascript:;" onclick="addProp()" style="padding-left: 0;padding-top: 6px;">添加商品属性</a>
                </div>
              </div>
              <div class="group clearfix" id="prop" style="display: none;margin-bottom: 20px;margin-left: -10px;margin-right: -10px;">
                <label class="col-md-2 col-sm-3 control-label">属性：</label>
                <div class="col-md-10 col-sm-9">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>属性名称</th>
                        <th>属性内容</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody id="propTbody"></tbody>
                  </table>
                  <a class="btn btn-link" href="javascript:;" onclick="addProp()" style="padding-left: 0;padding-top: 4px;">添加商品属性</a>
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 col-sm-3 control-label">商品分类：</label>
                <div class="col-md-10 col-sm-9">
                  <div class="clearfix">
                    <div id="classTbody" style="display:inline-block;float: left;margin-top: 4px;"></div>
                    <a class="btn btn-link" href="javascript:;" onclick="addCate()" style="float: left;padding-left: 0;padding-top:8px;">添加分类</a>
                  </div>
                  <p class="help-block">商品选择分类后才会展示给顾客，支持多选</p>
                </div>
              </div>
              <div class="form-group">
                <div class="col-md-2 col-sm-3">&nbsp;</div>
                <div class="btn-box col-md-10 col-sm-9">
                  <a href="<?=DJADMIN_URL?>mshop/items/index" class="btn btn-default">取消</a>
                  <button id="btn-confirm" class="btn btn-primary">保存</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <div id="editImgModal" class="modal fade" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-sm" role="document">
        <form id="cate-form" class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" style="text-align: center;">裁剪商品图片</h4>
          </div>
          <div class="modal-body" style="text-align: center;">
            <div class="imageBox">
              <div class="thumbBox"></div>
              <div class="spinner" style="display: none">Loading...</div>
            </div>
            <div class="image-footer" style="text-align: center;">
              <input id="ex1" data-slider-id='ex1Slider' type="text" data-slider-min="0" data-slider-max="20" data-slider-step="1" data-slider-value="0"
              />
              <br>
              <div class="clearfix" style="width: 210px;display: inline-block;margin-top: 10px;">
                <span style="float: left;">缩小</span>
                <span style="float: right;">放大</span>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
            <input type="button" id="btnCrop" class="btn btn-primary" value="裁剪">
          </div>
        </form>
      </div>
    </div>
    <script id="classTpl" type="text/html">
      <% if (rows.length > 0) { %>
        <% for(var i = 0; i < rows.length; i++) { %>
          <span class="label label-large cate-label" data-value="<%:=rows[i].id %>" onclick="changeCate(this)"><%:=rows[i].cate_name %></span>
        <% } %>
      <%} else {%>
      <span class="help-block" style="margin-right: 10px">暂无分类</span>
      <% } %>
    </script>
    <script id="manyTpl" type="text/html">
      <% if (list.length > 0) { %>
        <% for(var i = 0; i < list.length; i++) { %>
          <tr>
            <td class="form-group">
              <input class="form-control" type="text" name="sku_name" value="<%:=list[i].sku_name %>" oninput="changeSku(this,<%:=i %>)">
            </td>
            <td class="form-group">
              <input class="form-control" type="text" name="good_sku_code" value="<%:=list[i].sku_code %>" oninput="changeSkuCode(this,<%:=i %>)">
            </td>
            <td class="form-group">
              <input class="form-control w90" type="text" name="good_price" value="<%:=list[i].price %>" oninput="changePrice(this,<%:=i %>)">
            </td>
            <!-- <td class="form-group">
              <input class="form-control w90 good_member" type="text" name="good_member" value="<%:=list[i].member_price %>" oninput="changeMemberPrice(this,<%:=i %>)">
            </td> -->
            <td class="form-group">
              <input class="form-control w90" type="text" name="good_box" value="<%:=list[i].box %>" oninput="changeBox(this,<%:=i %>)">
            </td>
            <td class="form-group">
              <a class="btn-link btn-danger" href="javascript:;" onclick="delManyItem(<%:=i %>)">删除</a>
            </td>
          </tr>
        <% } %>
      <% } %>
    </script>
    <script id="propTpl" type="text/html">
      <% if (list.length > 0) { %>
        <% for(var i = 0; i < list.length; i++) { %>
          <tr>
            <td class="form-group">
              <input class="form-control" type="text" name="prop_name" value="<%:=list[i].name %>" oninput="changeProp(this,<%:=i %>)">
            </td>
            <td class="form-inline">
              <% for(var j = 0; j < list[i].value.length; j++) { %>
              <div class="form-group">
                  <input class="form-control w90" style="margin-right:10px" type="text" name="prop_value" value="<%:=list[i].value[j] %>" oninput="changePropValue(this,<%:=i %>, <%:=j %>)">
              </div>
              <% } %>
              <a href="javascript:;" class="btn btn-default btn-prop" onclick="addPropValue(<%:=i %>)" style="margin-left: 0">+</a>
              <a href="javascript:;" class="btn btn-default btn-prop" onclick="delPropValue(<%:=i %>)">-</a>
            </td>
            <td class="form-group">
              <a class="btn-link btn-danger" href="javascript:;" onclick="delPropItem(<%:=i %>)">删除</a>
            </td>
          </tr>
        <% } %>
      <% } %>
    </script>
    <script id="logoTpl" type="text/html">
      <% if (list.length > 0) { %>
        <ul class="logo-list">
          <% for(var i = 0; i < list.length; i++) { %>
            <% if (list[i].pic) { %>
              <li class="logo-item logo-item-good" style="display: inline-block;">
                <a class="btn-delete" href="javascript:;" onclick="delLogo(this,<%:=i %>)"></a>
                <div id="upload-logo-container<%:=i %>" class="m-upload m-upload-good-card">
                  <span class="btn-plus upload-plus"></span>
                  <img class="upload-pic" src="<?=UPLOAD_URL?><%:=list[i].pic %>" alt="">
                  <a class="upload-again" href="javascript:;" style="display: inline;">重新上传</a>
                  <input id="good_logo<%:=i %>" class="good-logo" name="good_logo<%:=i %>" type="text" value="<%:=list[i].pic %>">
                  <input id="upload-logo<%:=i %>" type="file" class="upload-input" value="">
                </div>
              </li>
            <%} else {%>
              <li class="logo-item" style="display: inline-block;">
                <div id="upload-logo-container<%:=i %>" class="m-upload m-upload-good-card">
                  <span class="btn-plus upload-plus"></span>
                  <img class="upload-pic" src="" alt="">
                  <a class="upload-again" href="javascript:;" style="display: none;">重新上传</a>
                  <input id="good_logo<%:=i %>" class="good-logo" name="good_logo<%:=i %>" type="text" value="<%:=list[i].pic %>">
                  <input id="upload-logo<%:=i %>" type="file" class="upload-input" value="">
                </div>
              </li>
            <% } %>
          <% } %>
        </ul>
      <% } %>
    </script>
    <?php $this->load->view('inc/global_footer'); ?>
    <?=static_original_url('libs/chosen/1.7.0/chosen.jquery.min.js');?>
    <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
    <?=static_original_url('libs/bootstrap-validator/2.0/js/language/zh_CN.js');?>
    <?=static_original_url('libs/bootstrap-slider/js/bootstrap-slider.min.js');?>
    <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
    <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
    <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
    <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
    <?=static_original_url('libs/template_js/0.7.1/template.min.js');?>
    <?=static_original_url('libs/laypage/1.3/laypage.min.js');?>
    <?=static_original_url('libs/jquery-tagsinput/1.3.3/js/jquery.tagsinput.min.js');?>
    <?=static_original_url('libs/cropbox/cropbox.js');?>
    <?=static_original_url('djadmin/js/main.min.js');?>
    <?=static_original_url('djadmin/mshop/js/store_good.js');?>
  </body>
  </html>
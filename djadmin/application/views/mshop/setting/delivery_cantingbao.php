<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>餐厅宝配送 - 配送配置</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    #source_id {
      width: 180px;
    }

    .dl-horizontal-delivery {
      margin-bottom: 0;
    }

    .dl-horizontal-delivery .u-switch {
      margin-top: 0;
    }

    @media (min-width: 768px) {
      .dl-horizontal-delivery dt {
        float:left;
        width: 90px;
        clear: left;
        text-align: right;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }

      .dl-horizontal-delivery dd {
        margin-left: 100px;
      }
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <?php $this->load->view('inc/nav_setting');?>
      <div class="main-body">
        <div class="main-body-inner">
          <?php $this->load->view('inc/nav_delivery');?>
          <div class="form-horizontal m-form-horizontal">
            <div class="form-group" style="margin-bottom: 10px;">
              <dl class="dl-horizontal-delivery">
                <dt>
                  <label class="control-label" style="padding-top: 2px;">开启配送：</label>
                </dt>
                <dd>
                  <label class="u-switch">
                    <input id="status" type="checkbox" name="status">
                    <div class="u-switch-checkbox" data-on="开启" data-off="关闭"></div>
                  </label>
                  <div class="mt20">提示：请将回调地址及门店管理中的门店ID提供给餐厅宝，并索要其商户Key和商户秘钥</p>
                </dd>
              </dl>
            </div>
            <div id="cantingbao-box" style="display: none;">
              <div class="form-group">
                <dl class="dl-horizontal-delivery">
                  <dt>
                    <label class="control-label">回调地址：</label>
                  </dt>
                  <dd>
                    <span><?=$notify_url?></span>
                    <div class="form-inline" style="display: inline-block;margin-left: 20px;">
                      <div class="pr">
                        <label class="sr-only">回调地址</label>
                        <input class="form-control" type="text" style="position: absolute;top:-99999px;" readonly value="<?=$notify_url?>">
                      </div>
                      <a class="btn btn-default" onclick="copyUrl(this)">复制</a>
                    </div>
                  </dd>
                </dl>
              </div>
              <div class="form-group">
                <dl class="dl-horizontal-delivery">
                  <dt>
                    <label class="control-label">商户Key：</label>
                  </dt>
                  <dd>
                    <input id="app_key" class="form-control w360" type="text" name="app_key" placeholder="请输入商户Key" disabled>
                  </dd>
                </dl>
              </div>
              <div class="form-group">
                <dl class="dl-horizontal-delivery">
                  <dt>
                    <label class="control-label">商户秘钥：</label>
                  </dt>
                  <dd>
                    <input id="app_secret" class="form-control w360" type="text" name="app_secret" placeholder="请输入商户秘钥" disabled>
                  </dd>
                </dl>
              </div>
              <div class="form-group">
                <dl class="dl-horizontal-delivery">
                  <dt>&nbsp;</dt>
                  <dd>
                    <a id="edit-cantingbao" class="btn btn-primary" href="javascript:;">编辑</a>
                    <span id="cantingbao-btn-group" style="display: none;">
                      <a id="cancel-cantingbao" class="btn btn-default" href="javascript:;">取消</a>
                      <button id="confirm-cantingbao" class="btn btn-primary ml10">保存</button>
                    </span>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?= static_original_url('djadmin/js/main.min.js'); ?>
  <?= static_original_url('djadmin/mshop/js/setting_delivery_cantingbao.min.js'); ?>
</body>
</html>

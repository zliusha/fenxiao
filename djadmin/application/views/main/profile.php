<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>账户信息 - 挖到后台</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .profile-list {
      margin: -20px 0;
    }

    .profile-item {
      padding: 20px 0;
      margin: 0;
      line-height: 36px;
    }

    .profile-item + .profile-item {
      border-top: 1px solid #d1dbe5;
    }

    .profile-item > div {
      padding: 0;
    }

    .profile-tip {
      line-height: 1.5;
      color: #99a9bf;
    }

    .profile-label {
      width: 60px;
    }

    .profile-item p {
      margin-bottom: 0;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <ol class="breadcrumb">
        <li class="active">账户信息</li>
      </ol>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="profile-list">
            <div class="profile-item row">
              <div class="profile-label col-md-1 col-xs-2">头像：</div>
              <div class="col-md-7 col-xs-6">
                <p><img id="profile-avatar" class="w-avatar" src="<?=STATIC_URL?>djadmin/img/avatar.jpg" alt="头像"></p>
                <p class="profile-tip mt10">建议尺寸200*200</p>
              </div>
              <div id="upload-avatar-container" class="col-md-4 col-xs-4 text-right pull-right">
                <button id="upload-avatar" class="btn btn-default">修改头像</button>
              </div>
            </div>
            <div class="profile-item row">
              <div class="profile-label col-md-1 col-xs-2">账户：</div>
              <div class="col-md-7 col-xs-6">
                <p id="profile-phone">-</p>
                <p class="profile-tip">手机用于登录及登录密码的找回、修改，同时接收重要提醒。</p>
              </div>
              <div class="col-md-4 col-xs-4 text-right pull-right">&nbsp;</div>
            </div>
            <div class="profile-item row">
              <div class="profile-label col-md-1 col-xs-2">昵称：</div>
              <div class="col-md-7 col-xs-6">
                <p id="profile-nick">-</p>
              </div>
              <div class="col-md-4 col-xs-4 text-right pull-right">
                <button id="edit-nick" class="btn btn-default">修改昵称</button>
              </div>
            </div>
            <div class="profile-item row">
              <div class="profile-label col-md-1 col-xs-2">密码：</div>
              <div class="col-md-7 col-xs-6">
                <p>******</p>
                <p class="profile-tip">本密码用于账号登录，登录后可进行所有操作，请妥善保管。</p>
              </div>
              <div class="col-md-4 col-xs-4 text-right pull-right">
                <button id="edit-password" class="btn btn-default">修改密码</button>
              </div>
            </div>
            <div class="profile-item row">
              <div class="profile-label col-md-1 col-xs-2">性别：</div>
              <div class="col-md-7 col-xs-6">
                <p id="profile-sex">-</p>
              </div>
              <div class="col-md-4 col-xs-4 text-right pull-right">
                <button id="edit-sex" class="btn btn-default">修改性别</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="nickModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="nick-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">修改昵称</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-2 control-label">昵称：</label>
              <div class="col-md-10">
                <input id="nick" class="form-control" type="text" name="nick" placeholder="请输入昵称">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="confirm-nick" class="btn btn-primary">确定</button>
        </div>
      </form>
    </div>
  </div>
  <div id="passwordModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="password-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">修改密码</h4>
        </div>
        <div class="modal-body">
          <div class="form-horizontal">
            <div class="form-group">
              <label class="col-md-3 control-label">旧密码：</label>
              <div class="col-md-9">
                <input id="old_password" class="form-control" type="password" name="old_password" placeholder="请输入旧密码">
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">新密码：</label>
              <div class="col-md-9">
                <input id="new_password" class="form-control" type="password" name="new_password" placeholder="请输入新密码">
              </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
              <label class="col-md-3 control-label">确认密码：</label>
              <div class="col-md-9">
                <input id="re_password" class="form-control" type="password" name="re_password" placeholder="请再次输入新密码">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="confirm-password" class="btn btn-primary">确定</button>
        </div>
      </form>
    </div>
  </div>
  <div id="sexModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
      <form id="sex-form" class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span class="modal-close" aria-hidden="true"></span></button>
          <h4 class="modal-title">修改性别</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="radio-inline"><span class="u-radio"><input type="radio" name="sex" value="保密"><span class="radio-icon"></span></span> 保密</label>
            <label class="radio-inline"><span class="u-radio"><input type="radio" name="sex" value="男"><span class="radio-icon"></span></span> 男</label>
            <label class="radio-inline"><span class="u-radio"><input type="radio" name="sex" value="女"><span class="radio-icon"></span></span> 女</label>
          </div>
        </div>
        <div class="modal-footer">
          <a class="btn btn-default" href="javascript:;" data-dismiss="modal">取消</a>
          <button id="confirm-sex" class="btn btn-primary">确定</button>
        </div>
      </form>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?=static_original_url('libs/bootstrap-validator/2.0/js/bootstrapValidator.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/moxie.js');?>
  <?=static_original_url('libs/plupload/2.3.1/plupload.full.min.js');?>
  <?=static_original_url('libs/plupload/2.3.1/i18n/zh_CN.js');?>
  <?=static_original_url('libs/qiniu/1.0.21/qiniu.min.js');?>
  <?=static_original_url('djadmin/js/main.min.js');?>
  <?=static_original_url('djadmin/js/profile.min.js');?>
</body>
</html>

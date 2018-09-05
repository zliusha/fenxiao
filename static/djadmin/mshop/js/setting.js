/**
 * setting.js
 * by jinmu
 * date: 2017-10-27
 */
$(function () {
  var $shareImg = $('#share_img'),
    $shareTitle = $("#share_title"),
    $shareDesc = $("#share_desc"),
    $domain = $("#domain"),
    $fullDomain = $("#full_domain"),
    $uploadShareContainer = $('#upload-share-container'),
    $settingForm = $("#setting-form"),
    $btnSave = $('#btn-save'),
    $btnEdit = $("#btn-edit"),
    $btnCancel = $("#btn-cancel"),
    $actionBox = $('#action-box');

  var domain = '';

  validatorSettingForm();
  getShopInfo();

  // 上传分享图片
  uploadFile('main_header', {
    browse_button: 'upload-share',
    container: 'upload-share-container',
    drop_element: 'upload-share-container',
    max_file_size: '1mb',
    chunk_size: '1mb',
    init: {
      'FileUploaded': function (up, file, info) {
        var res = JSON.parse(info.response);
        var halfpath = res.key;
        var fullpath = up.getOption('domain') + halfpath;

        $shareImg.val(halfpath).blur();
        $uploadShareContainer.find('.upload-again').show();
        $uploadShareContainer.find('.upload-plus').hide();
        $uploadShareContainer.find('.upload-pic').attr('src', fullpath);
      }
    }
  });

  // 进入商城设置可编辑状态
  function showSettingAction() {
    $uploadShareContainer.find('.upload-again').show();
    $settingForm.find('fieldset').prop('disabled', false);
    !domain && $domain.prop('disabled', false);
    $btnEdit.hide();
    $actionBox.show();
  }

  // 进入商城设置不可编辑状态
  function hideSettingAction() {
    $uploadShareContainer.find('.upload-again').hide();
    $settingForm.find('fieldset').prop('disabled', true);
    $domain.prop('disabled', true);
    $btnEdit.show();
    $actionBox.hide();
  }

  // 编辑商城设置
  $btnEdit.on('click', function() {
    resetSettingForm();
    showSettingAction();
  })

  // 取消编辑商城设置
  $btnCancel.on('click', function() {
    resetSettingForm();
    hideSettingAction();
  })

  // 重置
  function resetSettingForm() {
    $settingForm.data('bootstrapValidator').destroy();
    $settingForm.data('bootstrapValidator', null);
    validatorSettingForm();
  }

  // 验证设置表单
  function validatorSettingForm() {
    $settingForm
      .bootstrapValidator({
        excluded: [':disabled'],
        fields: {
          share_img: {
            validators: {
              notEmpty: {
                message: "分享图片不能为空"
              }
            }
          },
          share_title: {
            validators: {
              notEmpty: {
                message: "分享标题不能为空"
              }
            }
          },
          share_desc: {
            validators: {
              notEmpty: {
                message: "分享描述不能为空"
              },
              stringLength: {
                max: 60,
                message: '分享描述不能超过60个字符'
              }
            }
          },
          domain: {
            validators: {
              notEmpty: {
                message: "自定义域名不能为空"
              },
              regexp: {
                regexp: /^[a-zA-Z][a-zA-Z\d]{4,}$/,
                message: "自定义域名需以字母开头，可包含字母和数字，至少4个字符"
              }
            }
          }
        }
      }).on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var share_img = $shareImg.val(),
          share_title = $shareTitle.val(),
          share_desc = $shareDesc.val(),
          cus_domain = $domain.val(),
          postData = {
            share_img: share_img,
            share_title: share_title,
            share_desc: share_desc
          };

        if (!domain) {
          postData.domain = cus_domain;
        }

        $btnSave.prop('disabled', true).text('保存中...');

        $.post(
          __BASEURL__ + "mshop/setting_api/save_mall_setting",
          autoCsrf(postData),
          function (data) {
            if (data.success) {
              resetSettingForm();
              hideSettingAction();
              
              new Msg({
                type: "success",
                msg: "保存成功",
                delay: 1
              });
            } else {
              new Msg({
                type: "danger",
                msg: data.msg
              });
            }

            $btnSave.prop('disabled', false).text('保存');
          }
        );
      });
  }

  // 填充完整域名
  $domain.on('input', function() {
    $fullDomain.val('https://' + $(this).val() + '.m.waimaishop.com');
  });

  // 获取商城设置信息
  function getShopInfo() {
    $.get(__BASEURL__ + 'mshop/setting_api/get_mall_setting', function (data) {
      if (data.success) {
        var info = data.data;

        if (!info) {
          showSettingAction();
          return;
        }

        info.share_title && (info.share_title !== 'null') && $shareTitle.val(info.share_title);
        info.share_desc && (info.share_desc !== 'null') && $shareDesc.val(info.share_desc);

        if (info.share_img && info.share_img !== 'null') {
          $shareImg.val(info.share_img);
          $uploadShareContainer.find('.upload-again').show();
          $uploadShareContainer.find('.upload-plus').hide();
          $uploadShareContainer.find('.upload-pic').attr('src', __UPLOADURL__ + info.share_img);
        }

        domain = info.domain;
        if (domain && domain !== 'null') {
          $domain.val(domain);
          $fullDomain.val('https://' + domain + '.m.waimaishop.com');
        } else {
          $domain.val(info.aid);
          $fullDomain.val('https://' + info.aid + '.m.waimaishop.com');
        }

        if (info.share_title && (info.share_title !== 'null') && info.share_desc && (info.share_desc !== 'null') && info.share_img && (info.share_img !== 'null') && info.domain && (info.domain !== 'null')) {
          hideSettingAction();
        } else {
          showSettingAction();
        }
      } else {
        showSettingAction();

        new Msg({
          type: "danger",
          msg: data.msg
        });
      }
    });
  }
});
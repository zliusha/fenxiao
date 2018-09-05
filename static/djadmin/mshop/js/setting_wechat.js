$(function () {
  var $loginType = $('[name="login_type"]'),
    $appNickName = $("#app_nick_name"),
    $appId = $("#app_id"),
    $appIdThirdparty = $("#app_id_thirdparty"),
    $appSecret = $("#app_secret"),
    $verifyFileName = $('#verify_file_name'),
    $verifyFilePath = $('#verify_file_path'),
    $mchId = $("#mch_id"),
    $key = $("#key"),
    $autoRefund = $('[name="auto_refund"]'),
    $apiclientCert = $('#apiclient_cert'),
    $apiclientCertPath = $('#apiclient_cert_path'),
    $apiclientKey = $('#apiclient_key'),
    $apiclientKeyPath = $('#apiclient_key_path'),
    $developerBox = $('.developer-box'),
    $thirdpartyBox = $('.thirdparty-box'),
    $authForm = $('#auth-form'),
    $payForm = $('#pay-form'),
    $refundForm = $('#refund-form'),
    $authModal = $('#authModal'),
    $authResultModal = $('#authResultModal'),
    $btnAuthWechat = $('#btn-auth-wechat'),
    $btnAuthSuccess = $('#btn-auth-success'),
    $btnEditAuth = $("#btn-edit-auth"),
    $btnCancelAuth = $("#btn-cancel-auth"),
    $btnSaveAuth = $("#btn-save-auth"),
    $btnEditPay = $("#btn-edit-pay"),
    $btnCancelPay = $("#btn-cancel-pay"),
    $btnSavePay = $("#btn-save-pay"),
    $btnEditRefund = $('#btn-edit-refund'),
    $btnCancelRefund = $('#btn-cancel-refund'),
    $btnSaveRefund = $('#btn-save-refund'),
    $authActionBox = $('#auth-action-box'),
    $payActionBox = $('#pay-action-box'),
    $refundActionBox = $('#refund-action-box');

  var gzhInfo = null;

  initClipboard();
  initUploadVerifyFile();
  initUploadApiclientCert();
  initUploadApiclientKey();
  validatorAuthForm();
  validatorPayForm();
  validatorRefundForm();
  getAuthConfig();

  // 进入授权登录可编辑状态
  function showAuthAction() {
    $authForm.find('fieldset').prop('disabled', false);
    $btnEditAuth.hide();
    $authActionBox.show();
  }

  // 进入授权登录不可编辑状态
  function hideAuthAction() {
    $authForm.find('fieldset').prop('disabled', true);
    $btnEditAuth.show();
    $authActionBox.hide();
  }

  // 编辑授权登录
  $btnEditAuth.on('click', function() {
    resetAuthForm();
    showAuthAction();
  })

  // 取消编辑授权登录
  $btnCancelAuth.on('click', function() {
    resetAuthForm();
    hideAuthAction();
  })

  // 进入支付配置可编辑状态
  function showPayAction() {
    $payForm.find('fieldset').prop('disabled', false);
    $btnEditPay.hide();
    $payActionBox.show();
  }

  // 进入支付配置不可编辑状态
  function hidePayAction() {
    $payForm.find('fieldset').prop('disabled', true);
    $btnEditPay.show();
    $payActionBox.hide();
  }

  // 编辑支付配置
  $btnEditPay.on('click', function() {
    resetPayForm();
    showPayAction();
  })

  // 取消编辑支付配置
  $btnCancelPay.on('click', function() {
    resetPayForm();
    hidePayAction();
  })

  // 进入原路退款可编辑状态
  function showRefundAction() {
    $refundForm.find('fieldset').prop('disabled', false);
    $btnEditRefund.hide();
    $refundActionBox.show();
  }

  // 进入原路退款不可编辑状态
  function hideRefundAction() {
    $refundForm.find('fieldset').prop('disabled', true);
    $btnEditRefund.show();
    $refundActionBox.hide();
  }

  // 编辑原路退款
  $btnEditRefund.on('click', function() {
    resetRefundForm();
    showRefundAction();
  });

  // 取消编辑原路退款
  $btnCancelRefund.on('click', function() {
    resetRefundForm();
    hideRefundAction();
  })

  // 初始化复制
  function initClipboard() {
    var clipboard = new Clipboard(".btn-copy");

    clipboard.on("success", function (e) {
      new Msg({
        type: "success",
        msg: "复制成功",
        delay: 1
      });
    });

    clipboard.on("error", function (e) {
      new Msg({
        type: "danger",
        msg: "复制失败",
        delay: 1
      });
    });
  }

  // 初始化网页授权文件上传
  function initUploadVerifyFile() {
    var uploader = new plupload.Uploader({
      browse_button: 'btn-verify-file', //触发文件选择对话框的按钮，为那个元素id
      url: __BASEURL__ + 'common_file/upload?type=inc&http_url=1', //服务器端的上传页面地址
      flash_swf_url: __STATICURL__ + 'libs/plupload/2.3.1/Moxie.swf', //swf文件，当需要使用swf方式进行上传时需要配置该参数
      file_data_name: 'imgFile',
      auto_start: true,
      multi_selection: false, //true:ctrl多文件上传, false 单文件上传
      domain: __UPLOADURL__,
      filters: {
        mime_types: [{
          title: "Txt files",
          extensions: "txt"
        }]
      },
      init: {
        'FileUploaded': function (up, file, info) {
          var res = JSON.parse(info.response);

          $verifyFileName.text(file.name);
          $verifyFilePath.val(res.data.new_filepath);
        }
      }
    });

    uploader.init();
    uploader.bind('FilesAdded', function (up, files) {
      uploader.start();
    });
  }

  // 初始化apiclient证书上传
  function initUploadApiclientCert() {
    var uploader = new plupload.Uploader({
      browse_button: 'btn-apiclient-cert', //触发文件选择对话框的按钮，为那个元素id
      url: __BASEURL__ + 'common_file/upload?type=inc&http_url=1', //服务器端的上传页面地址
      flash_swf_url: __STATICURL__ + 'libs/plupload/2.3.1/Moxie.swf', //swf文件，当需要使用swf方式进行上传时需要配置该参数
      file_data_name: 'imgFile',
      auto_start: true,
      multi_selection: false, //true:ctrl多文件上传, false 单文件上传
      domain: __UPLOADURL__,
      filters: {
        mime_types: [{
          title: "Pem files",
          extensions: "pem"
        }]
      },
      init: {
        'FileUploaded': function (up, file, info) {
          var res = JSON.parse(info.response);

          $apiclientCert.text(file.name);
          $apiclientCertPath.val(res.data.new_filepath);
        }
      }
    });

    uploader.init();
    uploader.bind('FilesAdded', function (up, files) {
      uploader.start();
    });
  }

  // 初始化apiclient证书密钥上传
  function initUploadApiclientKey() {
    var uploader = new plupload.Uploader({
      browse_button: 'btn-apiclient-key', //触发文件选择对话框的按钮，为那个元素id
      url: __BASEURL__ + 'common_file/upload?type=inc&http_url=1', //服务器端的上传页面地址
      flash_swf_url: __STATICURL__ + 'libs/plupload/2.3.1/Moxie.swf', //swf文件，当需要使用swf方式进行上传时需要配置该参数
      file_data_name: 'imgFile',
      auto_start: true,
      multi_selection: false, //true:ctrl多文件上传, false 单文件上传
      domain: __UPLOADURL__,
      filters: {
        mime_types: [{
          title: "Pem files",
          extensions: "pem"
        }]
      },
      init: {
        'FileUploaded': function (up, file, info) {
          var res = JSON.parse(info.response);

          $apiclientKey.text(file.name);
          $apiclientKeyPath.val(res.data.new_filepath);
        }
      }
    });

    uploader.init();
    uploader.bind('FilesAdded', function (up, files) {
      uploader.start();
    });
  }

  // 重置授权登录表单
  function resetAuthForm() {
    $authForm.data('bootstrapValidator').destroy();
    $authForm.data('bootstrapValidator', null);
    validatorAuthForm();
  }

  // 授权登录表单
  function validatorAuthForm() {
    $authForm
      .bootstrapValidator({
        fields: {
          login_type: {
            validators: {
              notEmpty: {
                message: '请选择授权方式'
              }
            }
          },
          app_id: {
            validators: {
              notEmpty: {
                message: 'AppID(公众号ID)不能为空'
              }
            }
          },
          app_secret: {
            validators: {
              notEmpty: {
                message: 'AppSecret(公众号密钥)不能为空'
              }
            }
          },
          verify_file_name: {
            validators: {
              notEmpty: {
                message: '网页授权文件不能为空'
              }
            }
          },
          verify_file_path: {
            validators: {
              notEmpty: {
                message: '网页授权文件不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var login_type = +$('[name="login_type"]:checked').val(),
          app_id = $appId.val(),
          app_secret = $appSecret.val(),
          verify_file_name = $verifyFileName.text(),
          verify_file_path = $verifyFilePath.val(),
          post_data = {
            login_type: login_type
          };

        if (login_type === 0 && (!verify_file_name || !verify_file_path)) {
          new Msg({
            type: 'danger',
            msg: '网页授权文件不能为空'
          })

          return;
        }

        if (login_type === 0) {
          post_data.app_id = app_id;
          post_data.app_secret = app_secret;
          post_data.verify_file_name = verify_file_name;
          post_data.verify_file_path = verify_file_path;
        }

        $btnSaveAuth.prop('disabled', true).text('保存中...');

        $.post(__BASEURL__ + 'mshop/gzh_config_api/login_edit', autoCsrf(post_data), function (data) {
          $btnSaveAuth.prop('disabled', false).text('保存');

          if (data.success) {
            resetAuthForm();
            hideAuthAction();

            new Msg({
              type: 'success',
              msg: data.msg
            })
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            })
          }
        });
      });
  }

  // 重置授权登录表单
  function resetPayForm() {
    $payForm.data('bootstrapValidator').destroy();
    $payForm.data('bootstrapValidator', null);
    validatorPayForm();
  }

  // 支付配置表单
  function validatorPayForm() {
    $payForm
      .bootstrapValidator({
        fields: {
          mch_id: {
            validators: {
              notEmpty: {
                message: 'MCHID(商户ID)不能为空'
              }
            }
          },
          key: {
            validators: {
              notEmpty: {
                message: 'key(商户支付秘钥)不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var mch_id = $mchId.val(),
          key = $key.val();

        $btnSavePay.prop('disabled', true).text('保存中...');

        $.post(__BASEURL__ + 'mshop/gzh_config_api/pay_edit', autoCsrf({
          mch_id: mch_id,
          key: key
        }), function (data) {
          $btnSavePay.prop('disabled', false).text('保存');

          if (data.success) {
            resetPayForm();
            hidePayAction();

            new Msg({
              type: 'success',
              msg: data.msg
            })
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            })
          }
        });
      });
  }

  // 重置原路退款表单
  function resetRefundForm() {
    $refundForm.data('bootstrapValidator').destroy();
    $refundForm.data('bootstrapValidator', null);
    validatorRefundForm();
  }

  // 原路退款表单
  function validatorRefundForm() {
    $refundForm
      .bootstrapValidator({
        fields: {
          apiclient_cert_path: {
            validators: {
              notEmpty: {
                message: 'apiclient_cert(证书)不能为空'
              }
            }
          },
          apiclient_key_path: {
            validators: {
              notEmpty: {
                message: 'apiclient_key(证书密钥)不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var apiclient_cert_path = $apiclientCertPath.val(),
          apiclient_key_path = $apiclientKeyPath.val();

        if (!apiclient_cert_path) {
          new Msg({
            type: 'danger',
            msg: 'apiclient_cert(证书)不能为空'
          })

          return;
        }

        if (!apiclient_key_path) {
          new Msg({
            type: 'danger',
            msg: 'apiclient_key(证书密钥)不能为空'
          })

          return;
        }

        $btnSaveRefund.prop('disabled', true).text('保存中...');

        $.post(__BASEURL__ + 'mshop/gzh_config_api/return_edit', autoCsrf({
          apiclient_cert_path: apiclient_cert_path,
          apiclient_key_path: apiclient_key_path
        }), function (data) {
          $btnSaveRefund.prop('disabled', false).text('保存');

          if (data.success) {
            resetRefundForm();
            hideRefundAction();

            new Msg({
              type: 'success',
              msg: data.msg
            })
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            })
          }
        });
      });
  }

  // 设置默认授权方式
  function setDefaultLoginType() {
    $('[name="login_type"][value="0"').prop('checked', true);
    toggleLoginType(0);
  }

  // 获取公众号配置
  function getAuthConfig() {
    $.getJSON(__BASEURL__ + 'mshop/gzh_config_api/info', function (data) {
      if (data.success) {
        var info = data.data.info;
        gzhInfo = data.data.gzh_info;

        if (!info) {
          showAuthAction();
          showPayAction();
          showRefundAction();
          setDefaultLoginType();
          return;
        }

        // 填充授权方式
        if (info.login_type && info.login_type !== 'null') {
          $('[name="login_type"][value=' + info.login_type + ']').prop('checked', true);
          toggleLoginType(+info.login_type);
        } else {
          setDefaultLoginType();
        }

        // 填充公众号信息
        info.app_id && (info.app_id !== 'null') && $appId.val(info.app_id);
        info.app_secret && (info.app_secret !== 'null') && $appSecret.val(info.app_secret);

        // 判断是否绑定过公众号
        if (gzhInfo) {
          gzhInfo.appid && (gzhInfo.appid !== 'null') && $appIdThirdparty.text(gzhInfo.appid);
          gzhInfo.app_nick_name && (gzhInfo.app_nick_name !== 'null') && $appNickName.text(gzhInfo.app_nick_name);
        }

        // 填充网页授权文件
        info.verify_file_name && (info.verify_file_name !== 'null') && $verifyFileName.text(info.verify_file_name);
        info.verify_file_path && (info.verify_file_path !== 'null') && $verifyFilePath.val(info.verify_file_path);

        if (info.app_id && (info.app_id !== 'null') && info.app_secret && (info.app_secret !== 'null') && (info.verify_file_name !== 'null') && info.verify_file_path && (info.verify_file_path !== 'null')) {
          hideAuthAction();
        } else {
          showAuthAction();
        }

        // 填充商户ID和商户支付密钥
        info.mch_id && (info.mch_id !== 'null') && $mchId.val(info.mch_id);
        info.key && (info.key !== 'null') && $key.val(info.key);

        if (info.mch_id && (info.mch_id !== 'null') && info.key && (info.key !== 'null')) {
          hidePayAction();
        } else {
          showPayAction();
        }

        // 填充是否原路退款
        if (info.is_auto_return !== 'null') {
          toggleAutoRefund(+info.is_auto_return);
          if (+info.is_auto_return == 1) {
            $autoRefund.prop('checked', true);
          } else {
            $autoRefund.prop('checked', false);
          }
        }

        // 填充退款证书
        if (info.apiclient_cert_path && info.apiclient_cert_path !== 'null') {
          var apiclient_cert_path = info.apiclient_cert_path;
          var apiclient_cert = apiclient_cert_path.substr(apiclient_cert_path.lastIndexOf('/') + 1, apiclient_cert_path.length);
          $apiclientCertPath.val(apiclient_cert_path);
          $apiclientCert.text(apiclient_cert);
        }
        
        // 填充退款证书密钥
        if (info.apiclient_key_path && info.apiclient_key_path !== 'null') {
          var apiclient_key_path = info.apiclient_key_path;
          var apiclient_key = apiclient_key_path.substr(apiclient_key_path.lastIndexOf('/') + 1, apiclient_key_path.length);
          $apiclientKeyPath.val(apiclient_key_path);
          $apiclientKey.text(apiclient_key);
        }

        if (info.apiclient_cert_path && (info.apiclient_cert_path !== 'null') && info.apiclient_key_path && (info.apiclient_key_path !== 'null')) {
          hideRefundAction();
        } else {
          showRefundAction();
        }

        if (+info.login_type === 1 && (!gzhInfo || !gzhInfo.appid)) {
          $authModal.modal('show');
        }
      } else {
        showAuthAction();
        showPayAction();
        showRefundAction();
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    });
  }

  // 获取第三方授权的公众号信息
  function getGzhInfo() {
    $.getJSON(__BASEURL__ + 'mshop/gzh_config_api/info', function (data) {
      if (data.success) {
        gzhInfo = data.data.gzh_info;
        
        if (gzhInfo && gzhInfo.appid && (gzhInfo.appid !== 'null')) {
          $('[name="login_type"][value="1"').prop('checked', true);
          toggleLoginType(1);
          $appIdThirdparty.text(gzhInfo.appid);
          $appNickName.text(gzhInfo.app_nick_name);
          $btnSaveAuth.click();

          new Msg({
            type: 'success',
            msg: '授权成功'
          })
        } else {
          new Msg({
            type: 'danger',
            msg: '暂无授权信息'
          })
        }
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    });
  }

  // 切换授权方式
  function toggleLoginType(loginType) {
    if (loginType === 1) {
      // 第三方授权
      $developerBox.hide();
      $thirdpartyBox.show();
    } else {
      // 开发者授权
      $developerBox.show();
      $thirdpartyBox.hide();
    }
    resetAuthForm();
  }

  // 改变授权方式
  $loginType.on('change', function () {
    var login_type = +$(this).val();

    if (login_type === 1) {
      if (gzhInfo && gzhInfo.appid && (gzhInfo.appid !== 'null')) {
        toggleLoginType(login_type);
      } else {
        setDefaultLoginType();
        $authModal.modal('show');
      }
    } else {
      toggleLoginType(login_type);
    }
  })

  // 切换原路退款
  function toggleAutoRefund(is_auto_return) {
    if (is_auto_return == 1) {
      $refundForm.show();
    } else {
      $refundForm.hide();
    }
  }

  // 切换自动退款
  $autoRefund.on('change', function () {
    var is_auto_return = +$autoRefund.is(':checked');

    toggleAutoRefund(is_auto_return);

    $autoRefund.prop('disabled', true);

    $.post(__BASEURL__ + 'mshop/gzh_config_api/set_return', autoCsrf({
      is_auto_return: is_auto_return
    }), function (data) {
      $autoRefund.prop('disabled', false);

      if (data.success) {
        new Msg({
          type: 'success',
          msg: data.msg
        })
      } else {
        $autoRefund.prop('checked', false);

        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    });
  })

  // 授权公众号
  $btnAuthWechat.on('click', function () {
    $authModal.modal('hide');
    $authResultModal.modal('show');
  })

  // 公众号授权成功
  $btnAuthSuccess.on('click', function () {
    $authResultModal.modal('hide');
    getGzhInfo();
  })
});
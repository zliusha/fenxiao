$(function () {
  var $appId = $("#app_id"),
    $alipayPublicKey = $("#alipay_public_key"),
    $merchantPrivateKey = $("#merchant_private_key"),
    $payForm = $('#pay-form'),
    $btnEditPay = $("#btn-edit-pay"),
    $btnCancelPay = $("#btn-cancel-pay"),
    $btnSavePay = $("#btn-save-pay"),
    $payActionBox = $('#pay-action-box');

  validatorPayForm();
  getAlipayConfig();

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

  // 重置支付配置表单
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
          app_id: {
            validators: {
              notEmpty: {
                message: '应用ID不能为空'
              }
            }
          },
          sign_type: {
            validators: {
              notEmpty: {
                message: '请选择密钥类型'
              }
            }
          },
          alipay_public_key: {
            validators: {
              notEmpty: {
                message: '支付宝公钥不能为空'
              }
            }
          },
          merchant_private_key: {
            validators: {
              notEmpty: {
                message: '商户私钥不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var app_id = $appId.val(),
          sign_type = +$('[name="sign_type"]:checked').val(),
          alipay_public_key = $alipayPublicKey.val(),
          merchant_private_key = $merchantPrivateKey.val();

        $btnSavePay.prop('disabled', true).text('保存中...');

        $.post(__BASEURL__ + 'mshop/alipay_config_api/pay_edit', autoCsrf({
          app_id: app_id,
          sign_type: sign_type,
          alipay_public_key: alipay_public_key,
          merchant_private_key: merchant_private_key
        }), function (data) {
          $btnSavePay.prop('disabled', false).text('保存');

          if (data.success) {
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

  // 获取支付宝配置
  function getAlipayConfig() {
    $.getJSON(__BASEURL__ + 'mshop/alipay_config_api/info', function (data) {
      if (data.success) {
        var info = data.data.info;

        if (!info) {
          showPayAction();
          return;
        }

        // 填充配置信息
        info.sign_type && (info.sign_type !== 'null') && $('[name="sign_type"][value=' + info.sign_type + ']').prop('checked', true);
        info.app_id && (info.app_id !== 'null') && $appId.val(info.app_id);
        info.alipay_public_key && (info.alipay_public_key !== 'null') && $alipayPublicKey.val(info.alipay_public_key);
        info.merchant_private_key && (info.merchant_private_key !== 'null') && $merchantPrivateKey.text(info.merchant_private_key);

        if (info.sign_type && (info.sign_type !== 'null') && info.app_id && (info.app_id !== 'null') && info.alipay_public_key &&(info.alipay_public_key !== 'null') && info.merchant_private_key && (info.merchant_private_key !== 'null')) {
          hidePayAction();
        } else {
          showPayAction();
        }
      } else {
        showPayAction();
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    });
  }
});
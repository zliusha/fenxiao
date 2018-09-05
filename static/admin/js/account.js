/**
 * account.js
 * by lanran
 * date: 2017-08-09
 */
$(function () {
  var $btnLogin = $('#btn-login'),
    $btnRegister = $('#btn-register'),
    $btnFindPassword = $('#btn-findpassword');

  // 获取注册验证码
  $('#get-register-code').on('click', function(e){
    // 阻止表单默认提交
    e.preventDefault();

    getAuthCode($(this), $('#mobile').val(), 'register');
  });

  // 获取找回密码验证码
  $('#get-findpassword-code').on('click', function(e){
    // 阻止表单默认提交
    e.preventDefault();

    getAuthCode($(this), $('#mobile').val(), 'update_pwd');
  });

  // 登录表单
  $("#login-form").bootstrapValidator({
    fields: {
      account: {
        validators: {
          notEmpty: {
            message: "帐号不能为空"
          }
        }
      },
      password: {
        validators: {
          notEmpty: {
            message: "密码不能为空"
          },
          stringLength: {
            min: 6,
            max: 25,
            message: '密码格式不正确'
          }
        }
      }
    }
  }).on('success.form.bv', function (e) {
    // 阻止表单默认提交
    e.preventDefault();

    var account = $("#account").val(),
      password = $("#password").val();

    $btnLogin.prop('disabled', true).text('登录中...');

    $.post(
      __BASEURL__ + "passport_api/login",
      autoCsrf({
        account: account,
        password: password
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "登录成功",
            delay: 1
          });
          window.location.href = __BASEURL__;
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }

        $btnLogin.prop('disabled', false).text('登录');
      }
    );
  });

  // 注册表单
  $("#register-form").bootstrapValidator({
    fields: {
      mobile: {
        validators: {
          notEmpty: {
            message: "手机号不能为空"
          },
          regexp: {
            regexp: PregRule.Tel,
            message: "手机号格式不正确"
          }
        }
      },
      code: {
        validators: {
          notEmpty: {
            message: "验证码不能为空"
          },
          regexp: {
            regexp: PregRule.Authcode,
            message: "输入6位验证码"
          }
        }
      },
      password: {
        validators: {
          notEmpty: {
            message: "密码不能为空"
          },
          regexp: {
            regexp: PregRule.Pwd,
            message: "请使用字母、数字和符号的密码组合，8-25个字符"
          }
        }
      },
      repassword: {
        validators: {
          notEmpty: {
            message: "密码不能为空"
          },
          identical: {
            field: "password",
            message: "两次密码不一致"
          }
        }
      }
    }
  }).on('success.form.bv', function (e) {
    // 阻止表单默认提交
    e.preventDefault();

    var mobile = $("#mobile").val(),
      password = $("#password").val(),
      repassword = $("#repassword").val(),
      code = $("#code").val();

    $btnRegister.prop('disabled', true).text('注册中...');

    $.post(
      __BASEURL__ + "passport_api/register",
      autoCsrf({
        mobile: mobile,
        code: code,
        password: password,
        repassword: repassword
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "注册成功",
            delay: 1
          });
          window.location.href = __BASEURL__ + "passport/login";
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }

        $btnRegister.prop('disabled', false).text('确定注册');
      }
    );
  });

  // 找回密码表单
  $("#findpassword-form").bootstrapValidator({
    fields: {
      mobile: {
        validators: {
          notEmpty: {
            message: "手机号不能为空"
          },
          regexp: {
            regexp: PregRule.Tel,
            message: "手机号格式不正确"
          }
        }
      },
      code: {
        validators: {
          notEmpty: {
            message: "验证码不能为空"
          },
          regexp: {
            regexp: PregRule.Authcode,
            message: "输入6位验证码"
          }
        }
      },
      password: {
        validators: {
          notEmpty: {
            message: "密码不能为空"
          },
          regexp: {
            regexp: PregRule.Pwd,
            message: "请使用字母、数字和符号的密码组合，8-25个字符"
          }
        }
      },
      repassword: {
        validators: {
          notEmpty: {
            message: "密码不能为空"
          },
          identical: {
            field: "password",
            message: "两次密码不一致"
          }
        }
      }
    }
  }).on('success.form.bv', function (e) {
    // 阻止表单默认提交
    e.preventDefault();

    var mobile = $("#mobile").val(),
      code = $("#code").val(),
      password = $("#password").val();

    $btnFindPassword.prop('disabled', true).text('修改中...');

    $.post(
      __BASEURL__ + "passport_api/findpassword",
      autoCsrf({
        mobile: mobile,
        password: password,
        repassword: password,
        code: code
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "修改成功",
            delay: 1
          });

          window.location.href = __BASEURL__ + "passport/login";
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }

        $btnFindPassword.prop('disabled', false).text('确定修改');
      }
    );
  });
});

/**
 * login.js
 * by liangya
 * date: 2017-11-08
 */
$(function () {
  var $btnRegister = $("#btn-register"),
    $btnSendCode = $("#btnSendCode"),
    $agreement = $("#agreement"),
    $formError = $(".form-error");

  function verifyMobile(mobile) {
    if (!mobile) {
      $formError.html("手机号不能为空").show();
      return false;
    } else if (!PregRule.Tel.test(mobile)) {
      $formError.html("手机号格式不正确").show();
      return false;
    } else {
      $formError.html("").hide();
      return true;
    }
  }

  function verifyCode(code) {
    if (!code) {
      $formError.html("验证码不能为空").show();
      return false;
    } else if (!PregRule.Authcode.test(code)) {
      $formError.html("验证码格式不正确").show();
      return false;
    } else {
      $formError.html("").hide();
      return true;
    }
  }

  function verifyPassword(password) {
    if (!password) {
      $formError.html("密码不能为空").show();
      return false;
    } else if (!PregRule.Pwd.test(password)) {
      $formError.html("密码格式不正确").show();
      return false;
    } else {
      $formError.html("").hide();
      return true;
    }
  }

  function verifyRePassword(repassword) {
    if (!repassword) {
      $formError.html("确定密码不能为空").show();
      return false;
    } else if (!PregRule.Pwd.test(repassword)) {
      $formError.html("确定密码格式不正确").show();
      return false;
    } else {
      $formError.html("").hide();
      return true;
    }
  }

  function verifyPasswordAgreement(password, repassword) {
    if (password !== repassword) {
      $formError.html("两次密码输入的不一致").show();
      return false;
    } else {
      $formError.html("").hide();
      return true;
    }
  }

  $btnSendCode.on('click', function () {
    var mobile = $("#mobile").val();

    if (!verifyMobile(mobile)) {
      return false;
    }

    getAuthCode($(this), $formError, mobile, "register");
  });

  $agreement.on('change', function () {
    var agreement = $(this).prop('checked');

    if (!agreement) {
      $btnRegister.prop('disabled', true);
    } else {
      $btnRegister.prop("disabled", false);
    }
  });

  $btnRegister.on("click", function () {
    var mobile = $("#mobile").val(),
      code = $('#code').val(),
      password = $("#password").val(),
      repassword = $("#repassword").val();

    if (!verifyMobile(mobile) || !verifyCode(code) || !verifyPassword(password) || !verifyRePassword(repassword) || !verifyPasswordAgreement(password, repassword)) {
      return false;
    }

    $btnRegister.prop("disabled", true).text("提交中...");

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
          window.location.href = __SAASURL__ + '?visit_id=' + data.data.visit_id;
        } else {
          $formError.html(data.msg).show();
        }
        $btnRegister.prop("disabled", false).text("提交");
      }
    );
  });

  $(document).on("keydown", function(e) {
    if (e.keyCode == 13) {
      $btnRegister.click();
    }
  });

  function show_mybox(id) {
    $('#' + id).show();
  }

  function close_mybox(id) {
    $("#" + id).hide();
  }

  window.show_mybox = show_mybox;
  window.close_mybox = close_mybox;
});
/**
 * login.js
 * by liangya
 * date: 2017-11-08
 */
$(function () {
  var $btnFindpassword = $("#btn-findpassword"),
    $btnSendCode = $("#btnSendCode"),
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
      $formError.html("新密码不能为空").show();
      return false;
    } else if (!PregRule.Pwd.test(password)) {
      $formError.html("新密码格式不正确").show();
      return false;
    } else {
      $formError.html("").hide();
      return true;
    }
  }

  function verifyRePassword(repassword) {
    if (!repassword) {
      $formError.html("确定新密码不能为空").show();
      return false;
    } else if (!PregRule.Pwd.test(repassword)) {
      $formError.html("确定新密码格式不正确").show();
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

  $btnSendCode.on("click", function () {
    var mobile = $("#mobile").val();

    if (!verifyMobile(mobile)) {
      return false;
    }

    getAuthCode($(this), $formError, mobile, "update_pwd");
  });

  $btnFindpassword.on("click", function () {
    var mobile = $("#mobile").val(),
      code = $("#code").val(),
      password = $("#password").val(),
      repassword = $("#repassword").val();

    if (!verifyMobile(mobile) ||
      !verifyCode(code) ||
      !verifyPassword(password) ||
      !verifyRePassword(repassword) ||
      !verifyPasswordAgreement(password, repassword)
    ) {
      return false;
    }

    $btnFindpassword.prop("disabled", true).text("提交中...");

    $.post(
      __BASEURL__ + "passport_api/findpassword",
      autoCsrf({
        mobile: mobile,
        code: code,
        password: password,
        repassword: repassword
      }),
      function (data) {
        if (data.success) {
          window.location.href = __BASEURL__ + "passport/login";
        } else {
          $formError.html(data.msg).show();
        }
        $btnFindpassword.prop("disabled", false).text("提交");
      }
    );
  });

  $(document).on("keydown", function(e) {
    if (e.keyCode == 13) {
      $btnFindpassword.click();
    }
  });
});
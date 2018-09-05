/**
 * login.js
 * by liangya
 * date: 2017-11-08
 */
$(function() {
  var $btnBindphone = $("#btn-bindphone"),
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

  $btnSendCode.on("click", function() {
    var mobile = $("#mobile").val();

    if (!verifyMobile(mobile)) {
      return false;
    }

    getAuthCode($(this), $formError, mobile, "normal");
  });

  $btnBindphone.on("click", function() {
    var mobile = $("#mobile").val(),
      code = $("#code").val(),
      open_id = $("#open_id").val(),
      union_id = $("#union_id").val(),
      sign = $("#sign").val();

    if (!verifyMobile(mobile) ||!verifyCode(code)) {
      return false;
    }

    $btnBindphone.prop("disabled", true).text("提交中...");

    $.post(
      __BASEURL__ + "passport_api/wx_bind",
      autoCsrf({
        mobile: mobile,
        code: code,
        open_id: open_id,
        union_id: union_id,
        sign: sign
      }),
      function(data) {
        if (data.success) {
          window.location.href = __SAASURL__;
        } else {
          $formError.html(data.msg).show();
        }
        $btnBindphone.prop("disabled", false).text("提交");
      }
    );
  });

  $(document).on("keydown", function(e) {
    if (e.keyCode == 13) {
      $btnBindphone.click();
    }
  });
});

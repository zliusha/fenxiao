$(function () {
  var $btnGetCode = $('#btn-get-code'),
    $btnConfirm = $('#btn-confirm'),
    is_bind = $('#is_bind').val();

  var redirect_url = document.referrer || __BASEURL__+'user/info';

  // 获取验证码
  $btnGetCode.on("click", function () {
    getAuthCode($(this), $('#phone').val(), 'normal', 60);
  });

  // 确定绑定手机号
  $btnConfirm.on('click', function(){
    var phone = $("#phone").val(),
      code = $("#code").val(),
      postUrl = '';

    if(!is_bind){
      postUrl = __BASEURL__ + "api/user/update_mobile";
    }else{
      postUrl = __BASEURL__ + "api/user/bind_mobile";
    }

    // 判断手机号
    if (!phone) {
      layer.open({
        content: "手机号不能为空",
        skin: "msg",
        time: 1
      });

      return false;
    } else if (!PregRule.Tel.test(phone)) {
      layer.open({
        content: "手机号格式不正确",
        skin: "msg",
        time: 1
      });

      return false;
    }

    // 判断验证码
    if(!code){
      layer.open({
        content: "验证码不能为空",
        skin: "msg",
        time: 1
      });

      return false;
    }else if(!PregRule.Authcode.test(code)) {
      layer.open({
        content: "验证码为6位的数字",
        skin: "msg",
        time: 1
      });
      
      return false;
    }

    $btnConfirm.prop('disabled', true).text('提交中...');

    $.post(
      postUrl,
      autoCsrf({
        mobile: phone,
        mobile_code: code
      }),
      function (data) {
        if (data.success) {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
          setTimeout(function(){
            window.location.replace(redirect_url);
          },1000)
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }

        $btnConfirm.prop('disabled', false).text('确定');
      }
    );
  });
});
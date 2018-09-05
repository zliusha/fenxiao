/**
 * login.js
 * by liangya
 * date: 2017-11-08
 */
$(function() {
  var $btnLogin = $("#btn-login"),
    $formError = $(".form-error");

  initAccount();

  $("#focus-qr").on('click', function(){
    $(".suo-erwei").fadeToggle();
  });

  var obj = new WxLogin({
    id: "weixin-login-container",
    appid: $("#app_id").val(),
    scope: "snsapi_login",
    redirect_uri: encodeURIComponent($("#redirect_uri").val()),
    state: $("#state").val(),
    style: "",
    href: ''
  });

  function showWeixinLogin(){
    $('.weixin-login').show();
    $('.mobile-login').hide();
  }

  function showMobileLogin() {
    $(".mobile-login").show();
    $(".weixin-login").hide();
  }

  function verifyAccount(account) {
    if (!account) {
      $formError.html("手机号不能为空").show();
      return false;
    } else if (!PregRule.Tel.test(account)) {
      $formError.html("手机号格式不正确").show();
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

  function verifyPhrase(phrase) {
    if (!phrase) {
      $formError.html("验证码不能为空").show();
      return false;
    } else {
      $formError.html("").hide();
      return true;
    }
  }

  $btnLogin.on("click", function() {
    var account = $("#account").val(),
      password = $("#password").val(),
      phrase = $("#phrase").val(),
      token = $("#token").val();

    if (!verifyAccount(account) || !verifyPassword(password) || !verifyPhrase(phrase)) {
      return false;
    }

    $btnLogin.prop("disabled", true).text("登录中...");

    $.post(
      __BASEURL__ + "passport_api/login",
      autoCsrf({
        account: account,
        password: password,
        phrase: phrase,
        token: token
      }),
      function(data) {
        if (data.success) {
          saveAccount();
          window.location.href = __SAASURL__;
        } else {
          $formError.html(data.msg).show();
        }
        $btnLogin.prop("disabled", false).text("登录云店宝");
      }
    );
  });

  $(document).on("keydown", function(e){
    if (e.keyCode == 13){
      $btnLogin.click();
    }
  });

  function initAccount(){
    var is_save = Cookie.Get("is_save"),
      account = Cookie.Get("account"),
      password = Cookie.Get("password");
    
    if(Boolean(is_save)){
      $("#saveAccount").prop("checked", true);
      $("#account").val(atob(account));
      $("#password").val(atob(password));
    }else{
      $("#saveAccount").prop("checked", false);
      $("#account").val('');
      $("#password").val('');
    }
  }

  function saveAccount(){
    var isSave = $("#saveAccount").prop('checked'),
      account = $('#account').val(),
      password = $('#password').val();

    if(isSave){
      Cookie.Set("is_save", isSave, 7);
      Cookie.Set("account", btoa(account), 7);
      Cookie.Set("password", btoa(password), 7);
    }else{
      Cookie.Set("is_save", '', 0);
      Cookie.Set("account", '', 0);
      Cookie.Set("password", '', 0);
    }
  }

  window.showWeixinLogin = showWeixinLogin;
  window.showMobileLogin = showMobileLogin;
});

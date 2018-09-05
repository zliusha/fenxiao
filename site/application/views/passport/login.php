<!DOCTYPE html>
<html lang="zh-cn">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>云店宝登录 - 国内领先外卖新零售管理系统 外卖行业新零售解决方案 外卖开店 用云店宝！</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="云店宝官网，爱聚云店宝，外卖行业新零售解决方案，外卖开店 用云店宝！外卖微商城，外卖收银台，美团外卖饿了么外卖平台对接财务进销存系统，新零售解决方案，多门店管理，外卖O2O,外卖解决方案，外卖管理系统，外卖管理软件，">
	<meta name="description" content="爱聚云店宝：国内领先外卖新零售解决方案，对接美团、饿了么等外卖平台，实时同步各平台订单与财务数据，通过外卖微商城、外卖收银台帮助商家提升到店转化，唤醒沉睡客户，以老客带新客，实现线下门店与互联网餐厅的一体化经营。">
  <?php $this->load->view('inc/global_css'); ?>
  <?=static_original_url('site/css/passport/comment.css');?>
  <style type="text/css">
    *,
    *:before,
    *:after {
      -webkit-box-sizing: border-box;
      -moz-box-sizing: border-box;
      box-sizing: border-box;
    }

    .clearfix:after {
      display: table;
      content: '';
      height: 0;
      clear: both;
    }

    .fl {
      float: left;
    }

    .fr {
      float: right;
    }

    .pr {
      position: relative;
    }

    .w1200 {
      width: 1200px;
      height: 100%;
      margin-left: auto;
      margin-right: auto;
    }

    .header-login {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      z-index: 9;
      height: 70px;
    }

    .header-login .logo {
      float: left;
      margin-top: 18px;
    }

    .header-login .logo {
      width: 133px;
      height: 34px;
    }

    .passport-txt {
      float: left;
      margin-top: 21px;
      margin-left: 20px;
      padding-left: 20px;
      line-height: 30px;
      font-size: 20px;
      color: #fff;
      border-left: 1px solid #fff;
    }

    .icon-user {
      display: inline-block;
      width: 30px;
      height: 30px;
      margin-right: 5px;
      vertical-align: bottom;
      background-image: url('<?=STATIC_URL?>site/img/passport/icon-user-light.png');
      background-repeat: no-repeat;
      background-position: center;
    }

    .login-box {
      position: absolute;
      top: 50%;
      right: 0;
      z-index: 9;
      width: 400px;
      padding: 30px;
      margin-top: -200px;
      background-color: #ecf9ff;
    }

    .form-title {
      height: 46px;
      line-height: 46px;
      font-size: 18px;
      font-weight: normal;
      color: #333;
      text-align: center;
      border-bottom: 2px solid #00a0e9;
    }

    .form-group {
      margin-top: 20px;
      border: 1px solid #cdcdcd;
      overflow: hidden;
    }

    .control-label {
      width: 40px;
      height: 40px;
      float: left;
      text-indent: -9999px;
      background-color: #e2e2e4;
    }

    .form-control {
      width: 294px;
      height: 40px;
      padding: 0 10px;
      float: left;
      font-size: 16px;
      line-height: 40px;
      border: none;
    }

    .form-helper {
      margin: 20px 0;
    }

    .form-helper,
    .form-helper a {
      font-size: 14px;
      color: #797979;
    }

    .form-error {
      margin-top: 20px;
      font-size: 14px;
      color: #f96868;
      padding-left: 10px;
      border-left: 2px solid #f96868;
    }

    .icon-phone,
    .icon-password,
    .icon-safe {
      background-repeat: no-repeat;
      background-position: center;
    }

    .icon-phone {
      background-image: url('<?=STATIC_URL?>site/img/passport/icon-phone.png');
    }

    .icon-password {
      background-image: url('<?=STATIC_URL?>site/img/passport/icon-password.png');
    }

    .icon-safe {
      background-image: url('<?=STATIC_URL?>site/img/passport/icon-safe.png');
    }

    .btn-login {
      display: block;
      width: 100%;
      margin-bottom: 20px;
      font-weight: normal;
      text-align: center;
      vertical-align: middle;
      -ms-touch-action: manipulation;
      touch-action: manipulation;
      cursor: pointer;
      background-image: none;
      border: 1px solid transparent;
      white-space: nowrap;
      padding: 6px 0;
      font-size: 18px;
      line-height: 1.5;
      border-radius: 0;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
    }

    .btn-primary {
      color: #fff;
      background-color: #00a0e9;
    }

    .btn-success {
      color: #fff;
      background-color: #07af12;
    }

    .btn-success.btn-outline {
      color: #07af12;
      background-color: transparent;
      border: 1px solid #07af12;
    }

    .toogle-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      width: 51px;
      height: 51px;
      background-repeat: no-repeat;
      background-position: top right;
      cursor: pointer;
    }

    .mobile-login .toogle-btn {
      background-image: url('<?=STATIC_URL?>site/img/passport/qr-login.png');
    }

    .weixin-login .toogle-btn {
      background-image: url('<?=STATIC_URL?>site/img/passport/mobile-login.png');
    }

    .login-tip {
      position: absolute;
      top: 6px;
      right: 65px;
      width: 147px;
      height: 27px;
      background-repeat: no-repeat;
      background-position: top right;
    }

    .mobile-login .login-tip {
      background-image: url('<?=STATIC_URL?>site/img/passport/weixin-login-tip.png');
    }

    .weixin-login .login-tip {
      background-image: url('<?=STATIC_URL?>site/img/passport/mobile-login-tip.png');
    }
  </style>
  <script>
    if(window.top != window.self){
      window.top.location = __BASEURL__ + 'passport/login';
    }  
  </script>
</head>
<body>
  <input type="hidden" id="app_id" value="<?=$app_id?>">
  <input type="hidden" id="redirect_uri" value="<?=$redirect_uri?>">
  <input type="hidden" id="state" value="<?=$state?>">
  <header class="header-login">
    <div class="w1200">
      <a class="logo" href="<?=SITE_URL?>">
        <img src="<?=STATIC_URL?>site/img/logo-light.png" alt="云店宝">
      </a>
      <div class="passport-txt">
        <span class="icon icon-user"></span>
        登录
      </div>
    </div>
  </header>
  <div class="body first accueil_page" id="body_0">
    <div class="w1200 pr">
      <div class="login-box">
        <div class="mobile-login">
          <div class="toogle-btn" onclick="showWeixinLogin()"></div>
          <span class="login-tip"></span>
          <h3 class="form-title">快捷登录</h3>
          <p class="form-error" style="display: none;"></p>
          <div class="form-group">
            <label class="control-label icon-phone">手机号：</label>
            <input id="account" class="form-control" type="text" name="account" placeholder="手机号">
          </div>
          <div class="form-group">
            <label class="control-label icon-password">密码：</label>
            <input id="password" class="form-control" type="password" name="password" placeholder="密码">
          </div>
          <div class="form-group" style="position: relative;">
            <label class="control-label icon-safe">验证码：</label>
            <input id="phrase" class="form-control" type="number" name="phrase" placeholder="验证码">
            <img id="captcha_img" src="<?=$captcha_img?>" class="captcha_img" alt="" width="100" height="40" style="position: absolute; top: 0;right: 0;">
            <input id="token" class="form-control" type="hidden" name="token" value="<?=$token?>">
          </div>
          <p class="form-helper clearfix">
            <label class="fl">
              <input id="saveAccount" type="checkbox" name="saveAccount" style="vertical-align: middle;margin-top: -3px;margin-right: 5px;">记住密码
            </label>
            <a class="fr" href="<?=SITE_URL?>passport/findpassword">忘记密码？</a>
          </p>
          <button id="btn-login" class="btn-login btn-primary">登录云店宝</button>
          <button class="btn-login btn-success btn-outline" onclick="showWeixinLogin()"><img src="<?=STATIC_URL?>site/img/passport/icon-weixin.png" alt=""> 扫描二维码登录</button>
          <p class="clearfix">
            <a class="fr" href="<?=SITE_URL?>passport/register" style="color:#399bf4;">立即注册</a>
          </p>
        </div>
        <div class="weixin-login" style="display: none;">
          <div class="toogle-btn" onclick="showMobileLogin()"></div>
          <span class="login-tip"></span>
          <div id="weixin-login-container" style="margin: 10px auto;width: 300px;height: 400px;">
            <img src="<?=STATIC_URL?>site/img/passport/ydb-qr.png" alt="">
          </div>
          <p class="clearfix">
            <a class="fr" href="<?=SITE_URL?>passport/register" style="color:#399bf4;">立即注册</a>
          </p>
        </div>
      </div>
      <div class="suo-text-container">
        <p class="suo-txt suo-txt-top">
          <i class="icon icon-1"></i>实力优品—给您品质保障，让您放心使用！</p>
        <div class="suo-txt suo-txt-bottom">
          <span>
            <i class="icon icon-2"></i>云店宝外卖管理软件—火热招商 ，共赢万亿市场！</span>
          <div class="suo-fr suo-erwei-btn">
            <div class="suo-erwei">
              <img src="<?=STATIC_URL?>site/img/passport/ydb-qr.png" alt="">
            </div>
            <i id="focus-qr" class="icon icon-3"></i>关注云店宝服务，了解更多咨询！
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="aj-scrm-loin-foot">
    <span>爱聚科技旗下： 电商宝ERP | 电商宝财务 |电商宝SCRM | 爱聚门店 | 爱聚收银记账 | 爱聚新零售 | 爱聚HR </span>
    <br>
    <span>软件证书编号：浙RC-2016-0918 | 软件著作权登记号：2016SR285973 | 浙ICP备12032625号-3 |
      <a target="_blank" href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=33010602004534" style="display:inline-block;text-decoration:none;height:20px;line-height:20px;color:#999;">
        <img src="<?=STATIC_URL?>site/img/safe.png" style="float:left;" />浙公网安备 33010602004534号</a>
    </span>
    <br>
    <span>Copyright@2012-2017 浙江云店宝科技有限公司版权所有</span>
  </div>
  <?php $this->load->view('inc/global_js'); ?>
  <script src="//res.wx.qq.com/connect/zh_CN/htmledition/js/wxLogin.js"></script>
  <?=static_original_url('libs/base64/1.0.1/base64.min.js');?>
  <?=static_original_url('site/js/login.js');?>
</body>
</html>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>云店宝找回密码 - 国内领先外卖新零售管理系统 外卖行业新零售解决方案 外卖开店 用云店宝！</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="云店宝官网，爱聚云店宝，外卖行业新零售解决方案，外卖开店 用云店宝！外卖微商城，外卖收银台，美团外卖饿了么外卖平台对接财务进销存系统，新零售解决方案，多门店管理，外卖O2O,外卖解决方案，外卖管理系统，外卖管理软件，">
	<meta name="description" content="爱聚云店宝：国内领先外卖新零售解决方案，对接美团、饿了么等外卖平台，实时同步各平台订单与财务数据，通过外卖微商城、外卖收银台帮助商家提升到店转化，唤醒沉睡客户，以老客带新客，实现线下门店与互联网餐厅的一体化经营。">
  <?php $this->load->view('inc/global_css'); ?>
  <?=static_original_url('site/css/passport/login.css');?>
  <script>
    if(window.top != window.self){
      window.top.location = __BASEURL__ + 'passport/findpassword';
    }  
  </script>
</head>
<body>
  <div class="doc_box">
    <div class="symbol_header">
    </div>
    <a style="font-size: 14px;position:absolute;left:20px;top:20px;font-weight:bold;color:#399bf4" href="<?=SITE_URL?>passport/login">返回登录页</a>
    <div class="register_content" id="retrieve_pwd">
      <div class="mybox_con_top">
        <p>云店宝找回密码</p>
      </div>
      <div class="left_area">
        <p class="form-error"></p>
        <div style="display:block;" data-view="phoneRegister" class="form_body">
          <input id="source" name="source" type="hidden" value="">
          <div class="input_item clearfix" style="display: block;position:relative;border-bottom:1px solid #eee;">
            <div class="input_box" style="margin-bottom:1px;">
              <input type="text" autocomplete="off" data-required="required" placeholder="手机号" id="mobile" name="mobile"class="input input_white phone" style="width: 350px;">
              <div class="line" style="bottom:-2px;"></div>
            </div>
          </div>
          <div class="input_item" style="display: block;border-bottom:1px solid #eee;position: relative;">
            <div class="input_group clearfix" style="margin-top:1px;">
              <div class="input_box" style="width: 174px;margin-bottom:1px;display: inline-block;">
                <input type="text" id="code" style="width: 174px;border-right:1px solid #eee;height: 22px;" name="code" MaxLength="6" autocomplete="off" data-required="required" placeholder="验证码" class="input input_white first_child kapkey">
                <div class="line" style="bottom:-2px;"></div>
              </div>
              <label id="authcode_error"></label>
              <button type="button" id="btnSendCode" class="btn-get-code">获取验证码</button>
            </div>
          </div>
          <div class="input_item clearfix" style="display: block;position:relative;border-bottom:1px solid #eee;">
            <div class="input_box" style="margin-bottom:1px;">
              <input type="password" autocomplete="off" data-required="required" placeholder="设置新密码" id="password" name="password" class="input input_white password" style="width: 350px;">
              <div class="line" style="bottom:-2px;"></div>
            </div>
          </div>
          <div class="input_item clearfix" style="display: block;position:relative;border-bottom:1px solid #eee;">
            <div class="input_box" style="margin-bottom:1px;">
              <input type="password" autocomplete="off" data-required="required" placeholder="确定新密码" id="repassword" name="repassword" class="input input_white password1" style="width: 350px;">
              <div class="line" style="bottom:-2px;"></div>
            </div>
          </div>
          <div style="display: block;margin-top: 40px;" class="input_item clearfix" id="finish_reg" data-propertyname="submit" data-controltype="Botton">
            <button id="btn-findpassword" type="button" class="btn btn_primary">提交</button>
          </div>
        </div>
      </div>
      <div class="footer-copyright">
        <span>软件证书编号：浙RC-2016-0918 | 软件著作权登记号：2016SR285973 | 浙ICP备12032625号-3 |
          <a target="_blank" href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=33010602004534"
            style="display:inline-block;text-decoration:none;height:20px;line-height:20px;color:#999;">
            <img src="<?=STATIC_URL?>site/img/safe.png" style="float:left;">浙公网安备 33010602004534号</a>
        </span>
        <br> 浙江云店宝科技有限公司版权所有2012-2017
      </div>
    </div>
  </div>
  <div id="code-modal" class="ydb-modal">
    <div class="ydb-modal__content ydb-modal__content--code">
      <div class="ydb-modal__header">
        <h4 class="ydb-modal__title">请输入验证码</h4>
        <a href="javascript:;" class="ydb-modal__close">x</a>
      </div>
      <div class="ydb-modal__body">
        <div class="ydb-form__group">
          <input id="phrase" name="phrase" class="ydb-form__control" type="text">
          <input id="token" name="token" class="ydb-form__control" type="hidden">
          <img id="captcha_img" src="//www.youzan.com/v2/common/sms/imgcaptcha?t=12" alt="" width="100" height="40">
        </div>
      </div>
      <div class="ydb-modal__footer">
        <button id="btn-getCode" type="button" class="ydb-btn ydb-btn--primary">获取验证码</button>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_js'); ?>
  <?=static_original_url('site/js/findpassword.js');?>
</body>
</html>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>云店宝注册 - 国内领先外卖新零售管理系统 外卖行业新零售解决方案 外卖开店 用云店宝！</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="云店宝官网，爱聚云店宝，外卖行业新零售解决方案，外卖开店 用云店宝！外卖微商城，外卖收银台，美团外卖饿了么外卖平台对接财务进销存系统，新零售解决方案，多门店管理，外卖O2O,外卖解决方案，外卖管理系统，外卖管理软件，">
	<meta name="description" content="爱聚云店宝：国内领先外卖新零售解决方案，对接美团、饿了么等外卖平台，实时同步各平台订单与财务数据，通过外卖微商城、外卖收银台帮助商家提升到店转化，唤醒沉睡客户，以老客带新客，实现线下门店与互联网餐厅的一体化经营。">
  <?php $this->load->view('inc/global_css'); ?>
  <?=static_original_url('site/css/passport/login.css');?>
  <script>
    // 推广Params
    var spread_visit_id  = '1';
    var spread_app_id = '4';
    var spread_id = '42';

    if(window.top != window.self){
      window.top.location = __BASEURL__ + 'passport/register';
    }  
  </script>
  <script async src='//acrm.ecbao.cn/assert/spread_tg.js'></script>
</head>
<body>
  <div class="doc_box">
    <div class="symbol_header">
    </div>
    <a style="font-size: 14px;position:absolute;left:20px;top:20px;font-weight:bold;color:#399bf4" href="<?=SITE_URL?>passport/login">返回登录页</a>
    <div class="register_content" id="retrieve_pwd">
      <div class="mybox_con_top">
        <p>云店宝注册</p>
      </div>
      <div id="showSuccessLoopTip" style="display: none;"></div>
      <div class="left_area">
        <p class="form-error"></p>
        <div style="display:block;" class="form_body">
          <input id="source" name="source" type="hidden" value="">
          <div class="input_item clearfix" style="display: block;position:relative;border-bottom:1px solid #eee;">
            <div class="input_box" style="margin-bottom:1px;">
              <input type="text" autocomplete="off" placeholder="手机号" id="mobile" name="mobile" class="input input_white phone" style="width: 350px;">
              <div class="line" style="bottom:-2px;"></div>
            </div>
          </div>
          <div class="input_item" style="display: block;border-bottom:1px solid #eee;position: relative;">
            <div class="input_group clearfix" style="margin-top:1px;">
              <div class="input_box" style="width: 174px;margin-bottom:1px;display: inline-block;">
                <input type="text" id="code" style="width: 174px;border-right:1px solid #eee;height: 22px;" name="code" MaxLength="6" autocomplete="off" placeholder="验证码" class="input input_white first_child kapkey">
                <div class="line" style="bottom:-2px;"></div>
              </div>
              <label id="authcode_error"></label>
              <button type="button" id="btnSendCode" class="btn-get-code">获取验证码</button>
            </div>
          </div>
          <div class="input_item clearfix" style="display: block;position:relative;border-bottom:1px solid #eee;">
            <div class="input_box" style="margin-bottom:1px;">
              <input type="password" autocomplete="off" placeholder="密码至少需要8位" id="password" name="password" class="input input_white password" style="width: 350px;">
              <div class="line" style="bottom:-2px;"></div>
            </div>
          </div>
          <div class="input_item clearfix" style="display: block;position:relative;border-bottom:1px solid #eee;">
            <div class="input_box" style="margin-bottom:1px;">
              <input type="password" autocomplete="off" placeholder="确定密码" id="repassword" name="repassword" class="input input_white password1" style="width: 350px;">
              <div class="line" style="bottom:-2px;"></div>
            </div>
          </div>
          <p class="clearfix" style="font-size: 14px;margin-top: 20px;">
            <label class="fl"><input id="agreement" type="checkbox" checked="checked" style="vertical-align: middle;margin-top: -1px;">已接受</label>
            <a class="fl" id="agree" href="javascript:;" style="color:#399bf4;cursor: pointer" onclick="show_mybox('user_agreement');">《云店宝用户协议》</a>
            <a class="fr" style="color:#399bf4;" href="<?=SITE_URL?>passport/login">已有账号？登录</a>
          </p>
          <div style="display: block;margin-top: 40px;" class="input_item clearfix" id="finish_reg">
            <button id="btn-register" type="button" class="btn btn_primary">提交</button>
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
  <!--用户协议弹窗-->
  <div id="user_agreement" class="mybox" style="display: none;">
    <div class="mybox_bg" onclick="close_mybox('user_agreement');"></div>
    <div class="mybox_con" style="width:700px;" id='user_agreement_content'>
      <div class="mybox_con_top">
        云店宝用户协议
        <a href="javascript:void(0)" onclick="close_mybox('user_agreement');">
          <b>×</b>
        </a>
      </div>
      <div class="mybox_con_body" id="user_agreement_body">
        <h2>【首部及导言】</h2>
        <h2>　　为协助用户有效利用云店宝资源，维护用户合法权益，特制订《云店宝用户规则》（以下简称“本规则”）。请您务必审慎阅读、充分理解各条款内容，特别是免除或者限制责任的条款，以及开通或使用某项服务的单独协议，并选择接受或不接受。限制、免责条款可能以加粗形式提示您注意。
          <br
          />　　除非您已阅读并接受本规则所有条款，否则您无权申请或成为云店宝用户。您申请或使用云店宝软件的行为即视为您已阅读并同意受本规则的约束。</h2>
        <h2>一、【规则的范围】</h2>
        <p>　　1.1 本规则是爱聚科技制定的关于获取和使用云店宝软件的相关用户规则。本规则适用于爱聚科技提供的需要注册或使用云店宝的全部软件和服务。
          <br/>　　1.2 您通过云店宝软件使用爱聚科技的软件和服务时，须同时遵守各项服务的单独协议。</p>
        <h2>二、【云店宝账号的性质】</h2>
        <p>　　云店宝账号是爱聚科技创设的用于识别用户身份的标识。云店宝账号的所有权属于爱聚科技。</p>
        <h2>三、【云店宝账号的获取方式】</h2>
        <p>　　3.1 您可以通过如下方式免费或有偿申请注册成为云店宝用户，包括但不限于：
          <br/> 　　（1）软件客户端；
          <br/> 　　（2）爱聚科技网站；
          <br/> 　　（3）参与爱聚科技开展或授权开展的活动；
          <br/> 　　（4）其他爱聚科技授权的方式。
          <br/> 　　3.2 您完成申请注册程序后，依照爱聚科技的业务规则获得该账号的使用权。
          <br/> 　　3.3 无论您通过何种方式注册成为云店宝用户，均受本规则约束。</p>
        <h2>四、【用户身份信息验证】</h2>
        <p>　　4.1 您在申请使用云店宝软件过程中，需要填写一些必要的信息，请保持这些信息的及时更新，以便爱聚科技向您提供帮助，或更好地为您提供服务。若国家法律法规有特殊规定的，您需要填写真实的身份信息。若您填写的信息不完整或不准确，则可能无法使用服务或在使用过程中受到限制。
          <br/>　　4.2 爱聚科技与用户一同致力于个人信息的保护，保护用户个人信息是爱聚科技的一项基本原则。未经您的同意，爱聚科技不会向爱聚科技以外的任何公司、组织和个人披露您的个人信息。法律法规另有规定的情况除外。</p>
        <h2>五、【云店宝账号的使用】</h2>
        <p>　　5.1 您可以按照爱聚科技的业务规则通过用户注册信息登录和使用爱聚科技提供的各种软件和服务。</p>
        <h2>六、【云店宝账户的安全问题】</h2>
        <p>
          <b>　　6.1 云店宝密码由您自行设定。您应妥善保管您的云店宝用户名与密码。</b>
          <br/> 　　6.2 爱聚科技与您共同负有维护云店宝用户安全的责任。爱聚科技会采取并不断更新技术措施，努力保护您的云店宝账号在服务器端的安全。
          <br/> 　　6.3 您需要采取特定措施保护您的账号安全，包括但不限于妥善保管云店宝用户名与密码、安装防病毒木马软件、定期更改密码等措施。</p>
        <h2>七、【云店宝账号的找回】</h2>
        <p>　　如果您的云店宝账号被盗、密码遗忘或因其他原因导致无法正常登录，您可以按照爱聚科技密码找回途径找回。</p>
        <h2>八、【用户行为规范】</h2>
        <p>
          <b>　　8.1 您不得恶意注册云店宝用户账号。爱聚科技可以对恶意注册行为进行独立判断和处理。</b>
          <br/>
          <b>　　8.2 云店宝账号使用权仅属于初始申请注册人。未经爱聚科技许可，您不得赠与、借用、租用、转让或售卖云店宝账号或者以其他方式许可非初始申请注册人使用云店宝用户。</b>
          <br/> 　　8.3 如果您当前使用的云店宝账号并不是您初始申请注册的或者通过爱聚科技提供的其他途径获得的，但您却知悉该云店宝账号当前的密码，您不得用该账号登录或进行任何操作，并请您在第一时间通知爱聚科技或者该账号的初始申请注册人。</p>
        <h2>九、【责任承担】</h2>
        <p>　　9.1 您理解并同意，作为云店宝账号的初始申请注册人和使用人，您应承担该账号项下所有活动产生的全部责任。
          <br/> 9.2 因爱聚科技原因导致您的云店宝账号被盗，爱聚科技将依法承担相应责任。非因爱聚科技原因导致的，爱聚科技不承担任何责任。
          <br/>
          <b>　　9.3 爱聚科技依照业务规则限制、冻结或终止您的云店宝账号使用，可能会给您造成一定的损失，该损失由您自行承担，爱聚科技不承担任何责任。</b>
          <br/>
          <b>　　9.4 您不得有偿或无偿转让云店宝账号，以免产生纠纷。您应当自行承担由此产生的任何责任，同时爱聚科技保留追究上述行为人法律责任的权利。</b>
        </p>
        <h2>十、【云店宝账号使用的限制、冻结或终止】</h2>
        <p>
          <b>　　10.1 如您违反法律法规、爱聚科技各服务协议或业务规则的规定，爱聚科技有权进行独立判断并随时限制、冻结或终止您对云店宝账号的使用，且根据实际情况决定是否恢复使用。</b>
          <br/>
          <b>　　10.2 如果爱聚科技发现您并非账号初始申请注册人，爱聚科技有权在未经通知的情况下终止您使用该账号。</b>
          <br/>
          <b>　　10.3 为了充分利用云店宝软件资源，如果您存在注册云店宝账号后未及时进行初次登录使用，或长期未登陆使用云店宝等情形，爱聚科技有权终止该账号的继续使用。</b>
          <br/>
          <b>　　10.4 爱聚科技按照本规则或相关法律法规，限制、冻结或终止您对云店宝账号的使用，而由此给您带来的损失由您自行承担。</b>
        </p>
        <h2>十一、【客户服务】</h2>
        <p>　　如果您对爱聚科技采取的云店宝用户使用限制措施有异议，或在使用云店宝的过程中有其他问题的，均可联系爱聚科技客户服务部门（QQ： 800036070 电话：0571-89935939 邮箱：help@iyenei.com），我们会给予您必要的帮助。</p>
        <h2>十二、【其他】</h2>
        <p>　　12.1 您申请或使用云店宝即视为您已阅读并同意受本规则的约束。爱聚科技有权在必要时修改本规则条款。您可以在爱聚科技相关页面查阅本规则的最新版本。本规则条款变更后，如果您继续使用云店宝账号，即视为您已接受修改后的规则。如果您不接受修改后的规则，应当停止使用云店宝软件。
          <br/> 　　12.2 本协议内容为爱聚科技旗下其余产品使用或服务协议不可分割的组成部分，您在使用爱聚科技其余产品或服务应当同时遵守。
          <br/>
          <b>　　12.3 本规则签订地为中华人民共和国浙江省杭州市西湖区。</b>
          <br/>
          <b>　　12.4 本规则的成立、生效、履行、解释及纠纷解决，适用中华人民共和国大陆地区法律（不包括冲突法）。</b>
          <br/>
          <b>　　12.5 若您和爱聚科技之间发生任何纠纷或争议，首先应友好协商解决；协商不成的，您同意将纠纷或争议提交本规则签订地有管辖权的人民法院管辖。</b>
          <br/> 　　12.6 本规则所有条款的标题仅为阅读方便，本身并无实际涵义，不能作为本规则涵义解释的依据。
          <br/> 　　12.7 本规则条款无论因何种原因部分无效或不可执行，其余条款仍有效，对双方具有约束力。</p>
        <p style="text-align: right;">杭州爱聚科技有限公司</p>
        <br/>
        <p style="text-align: center;">Copyright © 2015云店宝（杭州爱聚科技有限公司）www.ecbao.cn 浙ICP备12032625号</p>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_js'); ?>
  <?=static_original_url('site/js/register.js');?>
</body>
</html>
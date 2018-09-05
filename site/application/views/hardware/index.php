<!DOCTYPE html>
<html lang="zh-cn">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>爱聚云店宝-智能硬件 国内领先外卖新零售管理系统 外卖行业新零售解决方案 外卖开店 用云店宝！</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="keywords" content="云店宝官网，爱聚云店宝，外卖行业新零售解决方案，外卖开店 用云店宝！外卖微商城，外卖收银台，美团外卖饿了么外卖平台对接财务进销存系统，新零售解决方案，多门店管理，外卖O2O,外卖解决方案，外卖管理系统，外卖管理软件">
  <meta name="description" content="爱聚云店宝：国内领先外卖新零售解决方案，对接美团、饿了么等外卖平台，实时同步各平台订单与财务数据，通过外卖微商城、外卖收银台帮助商家提升到店转化，唤醒沉睡客户，以老客带新客，实现线下门店与互联网餐厅的一体化经营">
  <?php $this->load->view('inc/global_css'); ?>
  <?=static_original_url('site/css/hardware.css');?>
</head>
<body>
  <?php $this->load->view('inc/global_header'); ?>
  <div class="pos-banner">
    <div class="pos-banner-text">
      <h2>云店宝 业内外卖行业管理软件</h2>
      <h3><span>智能化收银硬件</span> —— ——让收银更高效</h3>
    </div>
  </div>
  <div class="pos-content">
    <div class="pos-block">
      <section class="pos-width1200">
        <ul class="pos-list-ul">
          <li class="item active" data-href="pos-list">
            <a href="javascript:;">
              <i class="icon pos-icon-01"></i>
              <div class="pos-line"></div>
              <h3 class="pos-h3">智能POS机</h3>
            </a>
          </li>
          <li class="item " data-href="inte-list">
            <a href="javascript:;">
              <i class="icon pos-icon-02"></i>
              <div class="pos-line"></div>
              <h3 class="pos-h3">智能收银台</h3>
            </a>
          </li>
          <li class="item " data-href="sweep-list">
            <a href="javascript:;">
              <i class="icon pos-icon-03"></i>
              <div class="pos-line"></div>
              <h3 class="pos-h3">智能扫码抢</h3>
            </a>
          </li>
          <li class="item " data-href="sweep-list">
            <a href="javascript:;">
              <i class="icon pos-icon-04"></i>
              <h3>钱箱和电子称</h3>
            </a>
          </li>
        </ul>
      </section>
    </div>
    <div class="item-content active pos-list">
      <div class="pos-img-01"></div>
      <div class="pos-img-02"></div>
      <div class="pos-img-03"></div>
      <div class="pos-img-04"></div>
      <div class="pos-img-05"></div>
      <div class="pos-img-06"></div>
      <div class="pos-img-07"></div>
      <div class="pos-img-08"></div>
      <div class="pos-img-09"></div>
    </div>
    <div class="item-content inte-list">
      <div class="inte-img-01"></div>
      <div class="inte-img-02"></div>
      <div class="inte-img-03"></div>
      <div class="inte-img-04"></div>
      <div class="inte-img-05"></div>
      <div class="inte-img-06"></div>
      <div class="inte-img-07"></div>
      <div class="inte-img-08"></div>
    </div>
    <div class="item-content sweep-list">
      <div class="sweep-img-01"></div>
      <div class="sweep-img-02"></div>
      <div class="sweep-img-03"></div>
    </div>
  </div>
  <div class="pos-foot-img">
    <h3>云店宝</h3>
    <h2>业内外卖行业管理软件</h2>
    <a class="pos-foot-btn" href="<?=SITE_URL?>passport/register" target="_blank">立即免费注册体验</a>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
  <?php $this->load->view('inc/global_js'); ?>
  <script>
    $(".dropmenu").mouseenter(function(){
      $(this).find('.a').addClass("active");
      var $container = $(this).find('.dropDownList');
      var $item = $(this).find('.menuSystemList');
      var boo_c = $container.is(":animated");
      if(!boo_c){
        $container.animate({height: $item.height()},300);
      }
    }).mouseleave(function(){
      $(this).find('.a').removeClass("active");
      var $container = $(this).find('.dropDownList');
      $container.animate({height: 0},200);
    });
    $(".pos-list-ul").on("mouseenter", '.item', function () {
      $(this).addClass("active").siblings(".active").removeClass("active");
      var cls = $(this).attr('data-href');
      $(`.${cls}`).addClass('active').siblings(".item-content").removeClass("active");
    });
  </script>
</body>
</html>

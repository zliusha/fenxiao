<?php
defined('BASEPATH') or exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>智能硬件 - 挖到后台</title>
  <?php $this->load->view('inc/global_header'); ?>
  <style>
    .hardware-banner {
      height: 130px;
      padding: 38px 0 30px;
      margin-bottom: 10px;
      text-align: center;
      background: url("<?=STATIC_URL?>djadmin/mshop/img/hardware/hardware-banner.jpg") no-repeat center/100% 100%;
    }
    .hardware-banner>h1 {
      margin-top: 0;
      font-size: 24px;
      color: #fff;
    }
    .hardware-banner>p {
      margin-top: 0;
      margin-bottom: 0;
      font-size: 20px;
      color: #ff6326;
    }
    .hardware-item {
      margin-top: 0;
      padding: 20px 0;
    }
    .hardware-item+.hardware-item {
      border-top: 1px solid #eee;
    }
    .hardware-pic {
      width: 156px;
      height: 146px;
      border: 1px dashed #d2d2d2;
    }
    .hardware-qr {
      width: 110px;
      height: 110px;
      border: 1px dashed #d2d2d2;
    }
    .hardware-title {
      margin-top: 5px;
      margin-bottom: 10px;
      font-size: 18px;
      color: #333;
    }
    .hardware-item>p {
      margin-bottom: 0;
    }
    .hardware-item .media-body {
      padding: 0 40px;
    }
    .hardware-info {
      margin-top: 20px;
      color: #666;
    }
    .media-right>p {
      margin-top: 10px;
      margin-bottom: 0;
      color: #999;
      text-align: center;
    }
  </style>
</head>
<body>
  <div id="main">
    <div class="container-fluid">
      <div class="hardware-banner">
        <h1>云店宝 业内收款外卖行业管理软件</h1>
        <p>智能化收银硬件——让收银更高效</p>
      </div>
      <div class="main-body">
        <div class="main-body-inner">
          <div class="hardware-list">
            <div class="hardware-item media">
              <div class="media-left media-middle">
                <img class="hardware-pic" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/hardware-pic1.jpg" alt="云店宝(sunmi)T1">
              </div>
              <div class="media-body">
                <h3 class="hardware-title">云店宝T1</h3>
                <p class="hardware-desc">收银一体机内置热敏打印机收款机点菜奶茶餐饮连锁收银系统</p>
                <p class="hardware-info">高端配置，精心设计；集齐商用贴心一体配置，更坚固更耐用，更广视野，更多内容，<br>根据需求自由搭配，前台功能面面俱到，各大外卖平台轻松对接，快捷点餐拒绝等待，多种支付任性收银，省时省力老板手机管店。</p>
              </div>
              <div class="media-right media-middle">
                <img class="hardware-qr" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/qr-ecbao-mall.png" alt="电商宝">
                <p>扫码购买</p>
              </div>
            </div>
            <div class="hardware-item media">
              <div class="media-left media-middle">
                <img class="hardware-pic" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/hardware-pic2.jpg" alt="云店宝V1">
              </div>
              <div class="media-body">
                <h3 class="hardware-title">云店宝V1</h3>
                <p class="hardware-desc">外卖自动接单打印机移动扫码收银机，简洁机身，精湛工艺，实力派也能用颜值说话</p>
                <p class="hardware-info">省时：多种需求，轻松搞定，稳定耐用，易上手，适配多种商业场景；<br>省钱：一机多用，高性价比，多款商业应用，高速打印，大音量喇叭，超大电量；<br>省心：高级管家，精细管理，告别手写点单，人工算账等粗放管理模式；</p>
              </div>
              <div class="media-right media-middle">
                <img class="hardware-qr" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/qr-ecbao-mall.png" alt="电商宝">
                <p>扫码购买</p>
              </div>
            </div>
            <div class="hardware-item media">
              <div class="media-left media-middle">
                <img class="hardware-pic" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/hardware-pic3.jpg" alt="2D码二维码扫码器">
              </div>
              <div class="media-body">
                <h3 class="hardware-title">2D码二维码扫码器</h3>
                <p class="hardware-desc">MD6000通用型手持影像式扫描器屏幕码</p>
                <p class="hardware-info">多种触发扫描模式：手动扫描，持续扫描，智能感应，命令触发；<br>可适用手机屏幕，纸质条码扫描；可扫一维码/二维码；<br>高灵敏扫描头，高回弹按键，防震防摔。</p>
              </div>
              <div class="media-right media-middle">
                <img class="hardware-qr" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/qr-ecbao-mall.png" alt="电商宝">
                <p>扫码购买</p>
              </div>
            </div>
            <div class="hardware-item media">
              <div class="media-left media-middle">
                <img class="hardware-pic" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/hardware-pic4.jpg" alt="405五格三档带锁钱箱">
              </div>
              <div class="media-body">
                <h3 class="hardware-title">405五格三档带锁钱箱</h3>
                <p class="hardware-desc">超市收款机收钱箱，收钱盒可独立使用</p>
                <p class="hardware-info">超宽大钞仓（支票仓），灵活的钢铁夹和存储空间，一体式拆边，<br>中性橡胶底角防滑耐用，JR11（4P）接口可连接设备，三档仓所设计；</p>
              </div>
              <div class="media-right media-middle">
                <img class="hardware-qr" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/qr-ecbao-mall.png" alt="电商宝">
                <p>扫码购买</p>
              </div>
            </div>
            <div class="hardware-item media">
              <div class="media-left media-middle">
                <img class="hardware-pic" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/hardware-pic5.jpg" alt="80*50热敏打印纸">
              </div>
              <div class="media-body">
                <h3 class="hardware-title">80*50热敏打印纸</h3>
                <p class="hardware-desc">纸超市收银收款机打印纸80mm小票纸80x50</p>
                <p class="hardware-info">采用进口木浆纸制作 ；不含可迁移性荧光增白剂；打印清晰，打印残留纸屑少；</p>
              </div>
              <div class="media-right media-middle">
                <img class="hardware-qr" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/qr-ecbao-mall.png" alt="电商宝">
                <p>扫码购买</p>
              </div>
            </div>
            <div class="hardware-item media">
              <div class="media-left media-middle">
                <img class="hardware-pic" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/hardware-pic6.jpg" alt="32卷POS打印纸">
              </div>
              <div class="media-body">
                <h3 class="hardware-title">32卷POS打印纸</h3>
                <p class="hardware-desc">超市热敏纸57x35移动刷卡机58mm小票纸银联收银纸</p>
                <p class="hardware-info">木浆原纸，打印清晰；涂布均匀，长久保存；用心包装，防潮防水；</p>
              </div>
              <div class="media-right media-middle">
                <img class="hardware-qr" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/qr-ecbao-mall.png" alt="电商宝">
                <p>扫码购买</p>
              </div>
            </div>
            <div class="hardware-item media">
              <div class="media-left media-middle">
                <img class="hardware-pic" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/hardware-pic7.jpg" alt="365小票打印机">
              </div>
              <div class="media-body">
                <h3 class="hardware-title">365小票打印机</h3>
                <p class="hardware-desc">云打印，插电即打，让订单在云间传递！</p>
                <p class="hardware-info">使用于：线上商城、餐饮外卖、酒店订房、超市收银、发货库房、物流订单、KTV预定、门票出售、加油站、医院挂号；<br>使用场景：在厨房可按部门分单；在仓库可按发货地址分单；在库房按商品分单；在市场按供货商分单；</p>
              </div>
              <div class="media-right media-middle">
                <img class="hardware-qr" src="<?=STATIC_URL?>djadmin/mshop/img/hardware/qr-ecbao-mall.png" alt="电商宝">
                <p>扫码购买</p>
              </div>
            </div>
          </div>    
        </div>
      </div>
    </div>
  </div>
  <?php $this->load->view('inc/global_footer'); ?>
</body>
</html>

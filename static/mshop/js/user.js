$(function () {
  var $viewQrcode = $('#view-qrcode'),
    $userCard = $('.m-user-card'),
    $userBarcode = $('#user-barcode'),
    $userVipNum = $('.m-user-vip'),
    $btnGetVip = $('#btn-get-vip'),
    $modalCode = $('#modal-code');

  // 页面初始化
  getUserInfo();
  getVipLink();
  getVipInfo();
  getVipBalance();
  getVipIntegral();
  getIntegralMallLink();

  // 设置会员信息
  function setVipCode(code) {
    // 格式化会员卡号
    var f_code = formatCode(code);

    $userVipNum.text(f_code);

    JsBarcode("#user-barcode", code, {
      format: "codabar",
      width: 3,
      displayValue: false
    });

    new QRCode(document.getElementById('user-qrcode'), code);
  }

  // 格式化会员卡号
  function formatCode(code) {
    return code.toString().replace(/(\d)(?=(?:\d{4})+$)/g, '$1 ');
  }

  // 获取用户信息
  function getUserInfo() {
    $.getJSON(__BASEURL__ + "api/user/info", function (data) {
      if (data.success) {
        var img = data.data.img;

        img && $(".m-user-avatar").attr('src', img);
        $(".m-user-name").text(data.data.username);
        $(".m-user-phone").text(data.data.mobile);
      } else {
        console.log(data.msg);
      }
    });
  }

  // 获取领取会员卡链接
  function getVipLink() {
    $.post(__BASEURL__ + 'api/wecard/member_card_link', autoCsrf({}), function (data) {
      if (data.success) {
        $btnGetVip.attr('href', data.data.link);
      } else {
        $btnGetVip.hide();
        $userVipNum.show();
        console.log(data.msg);
      }
    });
  }

  // 获取会员卡信息
  function getVipInfo() {
    $.post(__BASEURL__ + 'api/wecard/member_card', autoCsrf({}), function (data) {
      if (data.success) {
        var code = data.data.code;
        var dataLocal = data.data.dataLocal;
        var member_card = data.data.dataRemote.member_card;

        // 判断是否有会员卡号
        if (code) {
          $viewQrcode.show();
          $userBarcode.show();
          $btnGetVip.hide();
          $userVipNum.show();
          setVipCode(code);
          $('.m-vip-block').show();
        } else {
          $btnGetVip.show();
          $userVipNum.hide();
        }

        // 判断是否有会员卡背景颜色
        if (member_card.base_info.color) {
          $userCard.css('background-color', member_card.base_info.color);
        }

        // 判断是否有会员卡背景图片
        if (member_card.background_pic_url) {
          $userCard.css('background-image', 'url(' + member_card.background_pic_url + ')');
        }

        // 判断是否有折扣
        if (dataLocal.is_discount == '1') {
          $('#discount-item').show();
          $('#discount-num').text(dataLocal.discount);
        }

        // 判断是否有积分
        if (dataLocal.is_trade_bonus == '1') {
          $('#bonus-item').show();
        }

        // 判断是否有特权说明
        if (member_card.prerogative) {
          $('#m-benefits-info').html(member_card.prerogative);
        }
      } else {
        $btnGetVip.show();
        $userVipNum.hide();
        console.log(data.msg);
      }
    });
  }

  // 获取会员余额
  function getVipBalance() {
    $.post(__BASEURL__ + 'api/member/balance', autoCsrf({}), function (data) {
      if (data.success) {
        $('#vip-balance').text('￥' + data.data.money);
      } else {
        console.log(data.msg);
      }
    });
  }

  // 获取会员积分
  function getVipIntegral() {
    $.post(__BASEURL__ + 'api/member/integral', autoCsrf({}), function (data) {
      if (data.success) {
        $('#vip-integral').text(data.data.integral);
      } else {
        console.log(data.msg);
      }
    });
  }

  // 获取积分商城链接
  function getIntegralMallLink() {
    $.post(__BASEURL__+'api/member/integral_mall_link', autoCsrf({}), function(data){
      if(data.success){
        $('#integral-item').attr('href', data.data.integralMall);
      }else{
        console.log(data.msg);
      }
    });
  }

  // 展开会员权益详情
  $('#m-benefits-list').on('click', function () {
    var $this = $(this);

    $this.toggleClass('active');
    $('#m-benefits-info').toggle();
  });

  // 二维码弹窗关闭按钮
  $modalCode.on('click', function (e) {
    if (e.target == this) {
      hideModalCode();
    }
  });

  // 打开二维码弹窗
  function openModalCode() {
    $modalCode.addClass('active');
  }

  // 隐藏二维码弹窗
  function hideModalCode() {
    $modalCode.removeClass('active');
  }

  window.openModalCode = openModalCode;
  window.hideModalCode = hideModalCode;
});
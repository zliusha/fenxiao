/**
 * pay.js
 * by liangya
 * date: 2017-08-29
 */
$(function () {
  var $btnPay = $('#btn-pay'),
    tradeno = GetQueryString('tradeno'),
    pay_money = GetQueryString('pay_money'),
    expire = GetQueryString('expire').replace(/-/g, "/"),
    server_time = $('#server_time').val().replace(/-/g, "/"),
    difftime = new Date(server_time).getTime() - new Date().getTime();

  // 初始化支付倒计时
  initCountDown('countdown', difftime, expire, function () {
    $btnPay.prop('disabled', true);

    window.location.href = __BASEURL__ + 'order/detail?tradeno=' + tradeno;
  });

  history.replaceState(null, null, __BASEURL__ + "order/");
  
  // 页面退出事件
  window.addEventListener("beforeunload", function() {
    window.location.href = __BASEURL__ + "order/detail?tradeno=" + tradeno;
  }, false); 
  
  $('.pay_money').text(pay_money);

  // 确定支付
  $btnPay.on('click', function () {
    var type = $('[name="pay_type"]:checked').val();

    $btnPay.prop('disabled', true);

    if (type == '1') {
      // 微信支付
      $.post(__BASEURL__ + 'api/payment/weixin', autoCsrf({
        type: 'order',
        tid: tradeno
      }), function (data) {
        if (data.success) {
          wxPay(JSON.parse(data.data));
        } else {
          layer.open({
            content: data.msg,
            skin: 'msg',
            time: 1
          });
        }
        $btnPay.prop('disabled', false);
      });
    }
  });

  // 获取微信支付结果
  function getWeixinResult() {
    $.post(__BASEURL__ + 'api/payment/weixin_result', autoCsrf({
      tid: tradeno
    }), function (data) {
      if (data.success) {
        window.location.href = __BASEURL__ + 'order/detail?tradeno=' + tradeno;
      }
    });
  }

  // 调用微信支付
  function wxPay(options) {
    function jsApiCall() {
      WeixinJSBridge.invoke(
        'getBrandWCPayRequest',
        options,
        function (res) {
          WeixinJSBridge.log(res.err_msg);
          setTimeout(function () {
            getWeixinResult();
          }, 500);
        }
      );
    }

    if (typeof WeixinJSBridge == "undefined") {
      if (document.addEventListener) {
        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
      } else if (document.attachEvent) {
        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
      }
    } else {
      jsApiCall();
    }
  }
});
/**
 * index.js
 * by liangya
 * date: 2017-08-03
 */
$(function () {
  var $body = $('body'),
    $mainFrame = $('#main-frame'),
    $loadingBox = $('#loading-box');

  getProductUrl();

  // 获取产品中心URL
  function getProductUrl() {
    $.getJSON(__BASEURL__ + "/erp_api/get_login_url",
      function (data) {
        if (data.success) {
          $(".nav-item-product>a").attr('href', data.data.url);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 顶部导航跳转
  $('.J_NAVBAR_NAV_ITEM a').on('click', function (e) {
    var $this = $(this),
      type = $this.data('type'),
      target = $this.attr('href');

    e.preventDefault();

    if (!target) {
      return false;
    }

    if (type == 'qq') {
      window.open(target);
      return false;
    }

    $('.J_NAVBAR_NAV_ITEM').removeClass('active');
    $this.parent().addClass('active');

    if ($loadingBox.is(':hidden')) {
      $loadingBox.stop().fadeIn();
    }
    hideLoadingBox();

    if (type == 'index') {
      $body.addClass('has-aside');
      $('#J_NAV_HOME').click();
    } else {
      $body.removeClass('has-aside');
    }

    $mainFrame.attr('src', target);
  });

  // 侧边导航跳转
  $('.J_NAV_ITEM a').on('click', function (e) {
    var $this = $(this),
      target = $this.attr('href');

    e.preventDefault();

    if (!target) {
      return false;
    }

    $('.J_NAV_ITEM').removeClass('active');
    $this.parent().addClass('active');

    if ($loadingBox.is(':hidden')) {
      $loadingBox.stop().fadeIn();
    }
    hideLoadingBox();

    $mainFrame.attr('src', target);
  });

  // 切换子菜单
  $('.J_TOGGLE_SUBNAV').on('click', function () {
    var $this = $(this);

    $this.next().slideToggle();
    $this.parent().toggleClass('open');
  });

  // 消息框链接跳转
  $('body').on('click', 'a.message-box', function (e) {
    var target = $(this).attr('href');

    e.preventDefault();

    if (!target) {
      return false;
    }

    if ($loadingBox.is(':hidden')) {
      $loadingBox.stop().fadeIn();
    }
    hideLoadingBox();

    $mainFrame.attr('src', target);
  });

  $loadingBox.stop().fadeIn();

  $mainFrame.on('load', function () {
    $loadingBox.hide();
  });

  function hideLoadingBox() {
    setTimeout(function() {
      if (!$loadingBox.is(':hidden')) {
        $loadingBox.hide();
      }
    }, 1000);
  }
});

/* 
 * 订单消息推送
 */
var ws; // websocket实例
var wsUrl = __WSURL__;
var lockReconnect = false; // 避免重复连接
var loginData; // 连接成功需要发送的登录验证数据
var orderMusic = document.getElementById("order-music");
var orderPrintTpl = document.getElementById('orderPrintTpl').innerHTML;
var LODOP; // 声明为全局变量

// 设置心跳发送
var heartSend = {
  interval: 10000,
  intervalObj: null,
  reset: function () {
    clearTimeout(this.intervalObj);
    return this;
  },
  start: function () {
    this.intervalObj = setInterval(function () {
      if (ws && ws.readyState === 1) {
        ws.send('peng');
      }
    }, this.interval);
  }
}

getLoginData();

// 获取登录数据
function getLoginData() {
  $.get(__BASEURL__ + 'mshop/Mq_api/login', function (data) {
    if (data.success) {
      var is_shop_user = data.data.is_shop_user;

      loginData = data.data.out_data;

      // 判断是否是门店账号
      if (is_shop_user && loginData) {
        createWebSocket(wsUrl, loginData);
      }
    } else {
      console.log(data.msg);
    }
  });
}

function createWebSocket(url, data) {
  try {
    ws = new WebSocket(url);
    initEventHandle(data);
  } catch (e) {
    reconnect(url);
  }
}

function initEventHandle(data) {
  ws.onopen = function () {
    console.log("Connected");
    ws.send(data);
    // 定时发送心跳消息
    heartSend.reset().start();
  };

  ws.onmessage = function (e) {
    var data = JSON.parse(e.data);
    // console.log(data);
    handleMsg(data);
  }

  ws.onclose = function (e) {
    console.log('ERROR', e);
    reconnect(wsUrl);
  };

  ws.onerror = function () {
    console.log('ERROR', e);
    reconnect(wsUrl);
  };
}

function reconnect(url) {
  if (lockReconnect) return;

  lockReconnect = true;

  //没连接上会一直重连，设置延迟避免请求过多
  setTimeout(function () {
    loginData && createWebSocket(url, loginData);
    lockReconnect = false;
  }, 2000);
}

// 消息处理
function handleMsg(data) {
  switch (data.type) {
    case 'order_notify':
      alertOrder();
      break;
    case 'order_printer':
      (+data.print_type == 2) && printOrder(data.tid, +data.print_times);
      break;
    default:
      console.log('未知消息类型：', data.type);
      break;
  }
}

// 新订单提醒
function alertOrder() {
  new Msg({
    type: 'success',
    msg: '你有1条新的云店宝订单，请及时处理',
    url: __BASEURL__ + 'mshop/order'
  });

  orderMusic.paused && orderMusic.play();
}

// 新订单打印
function printOrder(tid, times) {
  times = times || 1;

  $.getJSON(__BASEURL__ + 'mshop/order_api/detail', {
    tradeno: tid
  }, function (data) {
    if (data.success) {
      if (!data.data) return;
      CreatePrintWebPage(data.data);
      LODOP.SET_PRINT_COPIES(times);
      LODOP.PRINT();
    } else {
      console.log(data.msg);
    }
  });
}

// 新订单打印预览
function printPreview(tid, times) {
  times = times || 1;

  $.getJSON(__BASEURL__ + 'mshop/order_api/detail', {
    tradeno: tid
  }, function (data) {
    if (data.success) {
      if (!data.data) return;
      CreatePrintWebPage(data.data);
      LODOP.SET_PRINT_COPIES(times);
      LODOP.PREVIEW();
    } else {
      console.log(data.msg);
    }
  });
}

function CreatePrintWebPage(data) {
  var html = template(orderPrintTpl, data);

  LODOP = getLodop();
  LODOP.PRINT_INIT("云店宝订单");
  LODOP.ADD_PRINT_HTM(0, 0, "100%", "100%", html);
  LODOP.SET_PRINT_PAGESIZE(3, 570, 5, "CreateCustomPage");
}

/**
 * main js
 * by liangya
 * date: 2017-10-20
 */
$(function () {
  // 全选/反选
  $('body').on('click', '[name="select_all"]', function () {
    var is_checked = $(this).is(':checked');

    $('[name="select_item"]').each(function (i, e) {
      var $this = $(this),
        disabled = $this.prop('disabled');

      if (disabled) {
        $this.prop('checked', false);
      } else {
        $this.prop('checked', is_checked);
      }
    });
  });

  // 单选
  $('body').on('click', '[name="select_item"]', function () {
    var l = $('[name="select_item"]').length,
      sl = $('[name="select_item"]:checked').length;

    if (l == sl) {
      $('[name="select_all"]').prop('checked', true);
    } else {
      $('[name="select_all"]').prop('checked', false);
    }
  });

  // ios微信返回刷新页面
  var isPageHide = false;

  window.addEventListener("pageshow", function () {
    if (isPageHide) {
      window.location.reload();
    }
  });

  window.addEventListener("pagehide", function () {
    isPageHide = true;
  });
});

/**
 * 工具库
 */
(function (w, d) {
  // 操作Cookie
  var Cookie = {
    Get: function (name) {
      var arr = d.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));

      if (arr != null) {
        return unescape(arr[2]);
      }

      return null;
    },
    Set: function (name, value, days) {
      var Days = days, //此 cookie 将被保存 days 天
        exp = new Date(); //new Date("December 31, 9998");

      exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
      d.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
    }
  };

  // 常用正则表达式
  var PregRule = {
    Email: /\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/, //邮箱
    Account: /^[a-zA-Z0-9_]{2,20}$/, // 账户
    Pwd: /^[a-zA-Z0-9_~!@#$%^&*()]{6,25}$/i, // 密码
    Tel: /^(13|14|15|16|17|18|19)[0-9]{9}$/, //手机
    IDCard: /^\d{17}[\d|X|x]|\d{15}$/, //身份证 
    Number: /^\d+$/, //数字
    Integer: /^[-\+]?\d+$/, //正负整数
    IntegerZ: /^[1-9]\d*$/, //正整数
    IntegerF: /^-[1-9]\d*$/, //负整数
    Chinese: /^[\u0391-\uFFE5]+$/,
    Zipcode: /^\d{6}$/, //邮编
    Authcode: /^\d{6}$/, //验证码
    QQ: /^\d{4,12}$/, // QQ
    Price: /^(0|[1-9]\d*)(\.\d{1,2})?$/, // 价格
    Money: /^(0|[1-9]\d*)(\.\d{1,4})?$/, // 金额
    Letter: /^[A-Za-z]+$/, //字母
    LetterU: /^[A-Z]+$/, //大写字母
    LetterL: /^[a-z]+$/, //小写字母
    Url: /^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/, // URL
    Date: /^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/, //日期
    Domain: /^[a-zA-Z0-9]{4,}$/ //自定义域名
  };

  // 请求验证
  var CSRF_ID = 'csrf_cookie_name';

  function autoCsrf(params) {
    if (params == undefined) {
      params = {};
    }

    var autoParams = {
      csrf_token_name: Cookie.Get(CSRF_ID),
      rdm: Math.random()
    };

    return $.extend(autoParams, params);
  }

  // 获取url参数
  function GetQueryString(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"),
      r = window.location.search.substr(1).match(reg);

    if (r != null) {
      return unescape(r[2]);
    }

    return null;
  }

  // 获取验证码
  function getAuthCode($obj, mobile, type, time) {
    var timer = null;

    time = time || 60;

    if (!mobile) {
      layer.open({
        content: "手机号不能为空",
        skin: "msg",
        time: 1
      });

      return false;
    } else if (!PregRule.Tel.test(mobile)) {
      layer.open({
        content: "手机号格式不正确",
        skin: "msg",
        time: 1
      });

      return false;
    }

    function countDown() {
      if (time <= 0) {
        clearTimeout(timer);
        $obj.prop('disabled', false).text('获取验证码');
        return false;
      }

      $obj.prop('disabled', true).text(time + 's后重发');
      time--;
    }

    $obj.prop('disabled', true);

    // 获取验证码
    $.post(
      __BASEURL__ + "api/mobile_api/send_code",
      autoCsrf({
        mobile: mobile,
        type: type
      }),
      function (data) {
        if (data.success) {
          layer.open({
            content: "获取成功",
            skin: "msg",
            time: 1
          });

          countDown();
          timer = setInterval(countDown, 1000);
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });

          $obj.prop('disabled', false).text('获取验证码');
        }
      },
      "json"
    );
  }

  w.Cookie = Cookie;
  w.PregRule = PregRule;
  w.autoCsrf = autoCsrf;
  w.GetQueryString = GetQueryString;
  w.getAuthCode = getAuthCode;
})(window, document);

/**
 * countdown js
 */
(function (w, d) {
  /**
   * [initCountDown description]
   * @param  {[String]}      ele        [倒计时容器id]
   * @param  {[timestamp]}   diffTime   [服务器时间-本地时间]
   * @param  {[time]}        endTime    [结束时间]
   * @param  {Function}      callback   [倒计时结束回调]
   */
  function initCountDown(ele, diffTime, endTime, callback) {
    var clock = d.getElementById(ele);

    function updateClock() {
      var t = getTimeRemaining(diffTime, endTime),
        timer = null;

      if (t.total >= 0) {
        clock.innerHTML = formatTime(t.minutes) + ":" + formatTime(t.seconds);
        timer = setTimeout(updateClock, 1000);
      } else {
        clearTimeout(timer);
        callback && callback();
      }
    }

    updateClock();
  }

  function getTimeRemaining(diffTime, endTime) {
    var total = Date.parse(endTime) - Date.parse(new Date()) - diffTime,
      days = Math.floor(total / (1000 * 60 * 60 * 24)),
      hours = Math.floor(total / (1000 * 60 * 60) % 24),
      minutes = Math.floor(total / (1000 * 60) % 60),
      seconds = Math.floor(total / 1000 % 60);

    return {
      total: total,
      days: days,
      hours: hours,
      minutes: minutes,
      seconds: seconds
    };
  }

  function formatTime(t) {
    var tt;
    if (t < 10) {
      tt = "0" + t;
    } else {
      tt = t;
    }
    return tt;
  }

  w.initCountDown = initCountDown;
})(window, document);

/**
 * Cart js
 */
(function (w, d) {
  // 购物车对象
  function Cart() {
    this.key = "SHOPCART";
  }

  // 获取购物车对象
  Cart.prototype.getCart = function () {
    var shopCart = JSON.parse(localStorage.getItem(this.key));

    // 判断是否存在购物车
    if (!shopCart) {
      shopCart = {
        goods: [],
        total_amount: 0,
        total_price: "0.00",
        total_box: "0.00"
      };
    }

    return shopCart;
  };

  // 保存购物车信息
  Cart.prototype.saveCart = function (shopCart) {
    localStorage.setItem(this.key, JSON.stringify(shopCart));
  };

  // 清空购物车
  Cart.prototype.clearCart = function () {
    var shopCart = {
      goods: [],
      total_amount: 0,
      total_price: "0.00",
      total_box: "0.00"
    };

    this.saveCart(shopCart);
  };

  // 添加商品
  Cart.prototype.addGood = function (good) {
    var shopCart = this.getCart();
    var is_has_good = false;

    for (var i = 0, l = shopCart.goods.length; i < l; i++) {
      if (shopCart.goods[i].sku_id == good.sku_id) {
        shopCart.goods[i].amount =
          parseInt(shopCart.goods[i].amount) + parseInt(good.amount);
        is_has_good = true;
      }
    }
    var dec_price = '';
    var promo_price = '';
    var number = '';
    if(good.promo_setting){
      if(good.promo_limit_buy>=good.amount){
        if(good.promo_setting.discount_name=='打折'){
          dec_price = good.price;
          number = good.promo_limit_buy;
          good.price = good.promo_setting.dec_input*good.price;
          promo_price = good.price;
        }else{
          dec_price = good.price;
          number = good.promo_limit_buy;
          good.price = good.price-good.promo_setting.dec_input;
          promo_price = good.price;
        }
      }
    }

    if (!is_has_good) {
      shopCart.goods.push({
        id: good.id,
        sku_id: good.sku_id,
        promo_setting:good.promo_setting,
        promo_limit_buy:good.promo_limit_buy,
        attr_name: good.attr_name,
        title: good.title,
        dec_price:dec_price,
        promo_price:promo_price,
        number:number,
        sku_type: good.sku_type,
        sku_number: good.sku_number,
        pict_url: good.pict_url,
        price: parseFloat(good.price).toFixed(2),
        amount: parseInt(good.amount),
        box_fee: parseFloat(good.box_fee).toFixed(2)
      });
    }

    shopCart.total_amount =
      parseInt(shopCart.total_amount) + parseInt(good.amount);
    shopCart.total_price = (parseFloat(shopCart.total_price) +
      parseInt(good.amount) * parseFloat(good.price)).toFixed(2);
    shopCart.total_box = (parseFloat(shopCart.total_box) +
      parseInt(good.amount) * parseFloat(good.box_fee)).toFixed(2);

    this.saveCart(shopCart);
  };

  // 更新商品
  Cart.prototype.updateGood = function (sku_id, amount) {
    var shopCart = this.getCart();

    for (var i = 0, l = shopCart.goods.length; i < l; i++) {
      if (shopCart.goods[i].sku_id == sku_id) {
         if(shopCart.goods[i].promo_setting){
            if(shopCart.goods[i].promo_setting.discount_name=='打折'){
              if(amount>shopCart.goods[i].amount){
                if(Number(shopCart.goods[i].promo_limit_buy)+1 == amount){
                  shopCart.goods[i].price = shopCart.goods[i].price/shopCart.goods[i].promo_setting.dec_input;
                }
              }
              if(amount<shopCart.goods[i].amount){
                if(Number(shopCart.goods[i].promo_limit_buy) == amount+1){ 
                  shopCart.goods[i].price = shopCart.goods[i].price*shopCart.goods[i].promo_setting.dec_input;
                }
              }  
            }else{
              if(amount>shopCart.goods[i].amount){
                if(Number(shopCart.goods[i].promo_limit_buy)+1 == amount){
                  shopCart.goods[i].price = shopCart.goods[i].price-shopCart.goods[i].promo_setting.dec_input;
                }
              }
              if(amount<shopCart.goods[i].amount){
                if(Number(shopCart.goods[i].promo_limit_buy) == amount+1){ 
                  shopCart.goods[i].price = shopCart.goods[i].price+shopCart.goods[i].promo_setting.dec_input;
                }
              } 
            }  
          }
          console.info(shopCart.goods[i].price)
        var dis = parseInt(amount) - parseInt(shopCart.goods[i].amount);
        var dis_price = dis * parseFloat(shopCart.goods[i].price);
        var dis_box = dis * parseFloat(shopCart.goods[i].box_fee);
        shopCart.goods[i].amount = parseInt(amount);
        shopCart.total_amount = parseInt(shopCart.total_amount) + dis;
        shopCart.total_price = (parseFloat(shopCart.total_price) +
          dis_price).toFixed(2);
        shopCart.total_box = (parseFloat(shopCart.total_box) +
          dis_box).toFixed(2);
      }
    }

    this.saveCart(shopCart);
  };

  // 删除商品
  Cart.prototype.delGood = function (sku_id) {
    var shopCart = this.getCart();
    var list = [];

    for (var i = 0, l = shopCart.goods.length; i < l; i++) {
      if (shopCart.goods[i].sku_id == sku_id) {
        shopCart.total_amount =
          parseInt(shopCart.total_amount) - parseInt(shopCart.goods[i].amount);
        shopCart.total_price = (parseFloat(shopCart.total_price) -
          parseInt(shopCart.goods[i].amount) *
          parseFloat(shopCart.goods[i].price)).toFixed(2);
        shopCart.total_box = (parseFloat(shopCart.total_box) -
          parseInt(shopCart.goods[i].amount) *
          parseFloat(shopCart.goods[i].box_fee)).toFixed(2);
      } else {
        list.push(shopCart.goods[i]);
      }
    }

    shopCart.goods = list;

    this.saveCart(shopCart);
  };

  w.Cart = Cart;
})(window, document);
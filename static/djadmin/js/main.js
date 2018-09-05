/**
 * main.js
 * by liangya
 * date: 2017-08-03
 */
$(function () {
  // 全选/反选
  $('body').on('click', '[name="selectAll"]', function () {
    var is_checked = $(this).is(':checked');

    $('[name="selectItem"]').each(function (i, e) {
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
  $('body').on('click', '[name="selectItem"]', function () {
    var l = $('[name="selectItem"]').length,
      sl = $('[name="selectItem"]:checked').length;

    if (l == sl) {
      $('[name="selectAll"]').prop('checked', true);
    } else {
      $('[name="selectAll"]').prop('checked', false);
    }
  });

  // 展开商品信息
  $('body').on('click', '.J_VIEW_MORE_GOOD', function () {
    var _this = $(this);

    _this.toggleClass('open');
    _this.parents('tr').find('.good-more-info').slideToggle();
  });
});

/**
 * 工具库
 */
(function (w) {
  // 操作Cookie
  var Cookie = {
    Get: function (name) {
      var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));

      if (arr != null) {
        return unescape(arr[2]);
      }

      return null;
    },
    Set: function (name, value, days) {
      var Days = days, //此 cookie 将被保存 days 天
        exp = new Date(); //new Date("December 31, 9998");

      exp.setTime(exp.getTime() + Days * 24 * 60 * 60 * 1000);
      document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
    }
  };

  // 常用正则表达式
  var PregRule = {
    Email: /\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/, //邮箱
    Account: /^[a-zA-Z0-9_]{2,20}$/, // 账户
    Pwd: /^[a-zA-Z0-9_~!@#$%^&*()]{8,25}$/, // 密码
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

  // 复制链接
  function copyUrl(obj) {
    $(obj).parent().find('.form-control').get(0).select();

    document.execCommand("Copy");

    new Msg({
      type: 'success',
      msg: '复制成功',
      delay: 1
    });
  }

  // 上传文件
  function uploadFile(type, options) {
    var defaults = {
      runtimes: 'html5,flash,html4',
      dragdrop: true,
      multi_selection: false,
      max_file_size: '1mb',
      chunk_size: '200kb',
      domain: __UPLOADURL__,
      flash_swf_url: __STATICURL__ + 'libs/plupload/2.3.1/Moxie.swf',
      get_new_uptoken: true,
      auto_start: true,
      filters: {
        mime_types: [{
          title: "Image files",
          extensions: "jpg,jpeg,png"
        }]
      },
      resize: {
        quality: 60,
        preserve_headers: false
      },
      uptoken_func: function () {
        var ajax = new XMLHttpRequest();

        ajax.open('GET', __BASEURL__ + 'qiniu_api/get_token?type=' + type, false);
        ajax.setRequestHeader("If-Modified-Since", "0");
        ajax.send();

        if (ajax.status === 200) {
          var res = JSON.parse(ajax.responseText);
          return res.data.up_token;
        } else {
          return '';
        }
      },
      init: {
        'FilesAdded': function (up, files) {
          plupload.each(files, function (file) {
            // 文件添加进队列后，处理相关的事情
          });
        },
        'BeforeUpload': function (up, file) {
          // 每个文件上传前，处理相关的事情
        },
        'UploadProgress': function (up, file) {
          // 每个文件上传时，处理相关的事情
        },
        'FileUploaded': function (up, file, info) {
          // 每个文件上传成功时，处理相关的事情
        },
        'Error': function (up, err, errTip) {
          //上传出错时，处理相关的事情
        },
        'UploadComplete': function () {
          //队列文件处理完毕后，处理相关的事情
        },
        'Key': function (up, file) {
          var key = "";
          var extName = file.name.split('.')[file.name.split('.').length - 1];
          key = type + '/' + new Date().getTime() + '_' + Math.floor(1000 + Math.random() * (9999 - 1000)) + '.' + extName;
          return key;
        }
      }
    };

    options = $.extend(true, {}, defaults, options);
    uploader = Qiniu.uploader(options);

    return uploader;
  };

  // 获取验证码
  function getAuthCode($obj, mobile, type, time) {
    var timer = null;

    time = time || 60;

    if (!mobile) {
      new Msg({
        type: 'danger',
        msg: '手机号不能为空',
        delay: 2
      });

      return false;
    } else if (!PregRule.Tel.test(mobile)) {
      new Msg({
        type: 'danger',
        msg: '手机号格式不正确',
        delay: 2
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

    // 获取验证码
    $.post(
      __BASEURL__ + "mobile_api/send_code",
      autoCsrf({
        mobile: mobile,
        type: type
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "获取成功",
            delay: 1
          });

          countDown();
          timer = setInterval(countDown, 1000);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });

          $obj.prop('disabled', false).text('获取验证码');
        }
      },
      "json"
    );
  }

  // 格式化数字
  function formatNumber(n) {
    n = n.toString();

    return n[1] ? n : '0' + n;
  }

  // 格式化时间
  function formatTime(timestamp) {
    var date = new Date(parseInt(timestamp));
    var year = date.getFullYear();
    var month = date.getMonth() + 1;
    var day = date.getDate();
    var hour = date.getHours();
    var minute = date.getMinutes();
    var second = date.getSeconds();
  
    return [year, month, day].map(formatNumber).join('-') + ' ' + [hour, minute, second].map(formatNumber).join(':');
  }

  // 格式化自取时间
  function formatPickTime(timestamp) {
    var date = new Date(parseInt(timestamp));
    var hours = date.getHours();
    var minutes = date.getMinutes();

    return formatNumber(hours) + ':' + formatNumber(minutes);
  }

  w.Cookie = Cookie;
  w.PregRule = PregRule;
  w.autoCsrf = autoCsrf;
  w.GetQueryString = GetQueryString;
  w.copyUrl = copyUrl;
  w.uploadFile = uploadFile;
  w.getAuthCode = getAuthCode;
  w.formatTime = formatTime;
  w.formatPickTime = formatPickTime;
})(window);

/**
 * 提示消息
 */
(function (w, $) {
  function Msg(options) {
    this.type = options.type || 'info';
    this.msg = options.msg;
    this.url = options.url;
    this.closeBtn = options.closeBtn || false;
    this.delay = options.delay || 3;
    this.callback = options.callback;

    this.init();
  }

  Msg.prototype.init = function () {
    var msgHtml, msgLabel, msgDelete, msgId, msgDelay, msgUrl;

    msgId = 'm-message' + new Date().getTime();
    msgDelay = this.delay * 1000 + 200;

    switch (this.type) {
      case 'info':
        msgLabel = '<div class="message-label message-info"><span class="iconfont icon-tips"></span></div>';
        break;
      case 'success':
        msgLabel = '<div class="message-label message-success"><span class="iconfont icon-ok"></span></div>';
        break;
      case 'warning':
        msgLabel = '<div class="message-label message-warning"><span class="iconfont icon-gantan"></span></div>';
        break;
      case 'danger':
        msgLabel = '<div class="message-label message-danger"><span class="iconfont icon-close"></span></div>';
        break;
      default:
        msgLabel = '';
    }

    if (!this.url) {
      msgUrl = 'javascript:;';
    } else {
      msgUrl = this.url;
    }

    if (!this.closeBtn) {
      msgDelete = '';
    } else {
      msgDelete = '<span class="btn-delete message-delete" href="javascript:;"></span>';
    }

    msgHtml = '<div class="m-message" id="' + msgId + '">' +
      msgLabel +
      '<a class="message-box" href="' + msgUrl + '">' +
      '<span>' + this.msg + '</span>' + msgDelete +
      '</a>' +
      '</div>';
    $('body').append(msgHtml);

    var _this = this;

    $('#' + msgId).find('.message-delete').on('click', function () {
      $('#' + msgId).removeClass('in');
      _this.callback && _this.callback();
    });

    var t = setTimeout(function () {
      $('#' + msgId).addClass('in');
    }, 200);

    var d = setTimeout(function () {
      $('#' + msgId).removeClass('in');
      _this.callback && _this.callback();
    }, msgDelay);

    var r = setTimeout(function () {
      $('#' + msgId).remove();
      clearTimeout(t);
      clearTimeout(d);
      clearTimeout(r);
    }, msgDelay * 2);
  };

  w.Msg = Msg;
}(window, jQuery));
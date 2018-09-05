/**
 * main.js
 * by liangya
 * date: 2017-11-08
 */
$(function () {
	var $nav = $(".nav");

	$(window).on('scroll', function () {
		var scrollTop = $(window).scrollTop();

		if (scrollTop >= 72) {
			$nav.addClass("fix-nav");
		} else {
			$nav.removeClass("fix-nav");
		}
	});

	$('#side-kf').hover(function () {
		$(this).addClass('active');
	}, function () {
		$(this).removeClass('active');
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
	var is_refresh = false;

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
			max_file_size: '100mb',
			chunk_size: '4mb',
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
					var extName = file.name.split('.')[1];
					key = type + '/' + new Date().getTime() + '_' + Math.floor(1000 + Math.random() * (9999 - 1000)) + '.' + extName;
					return key;
				}
			}
		};

		options = $.extend(true, {}, defaults, options);
		uploader = Qiniu.uploader(options);

		return uploader;
	};

	function getAuthCode($obj, $formError, mobile, type, time) {
		var $btnGetCode = $('#btn-getCode');
		var timer = null;

		time = time || 60;

		if (!mobile) {
			$formError.html('手机号不能为空').show();
			return false;
		} else if (!PregRule.Tel.test(mobile)) {
			$formError.html("手机号格式不正确").show();
			return false;
		} else {
			$formError.html("").hide();
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

		getCode();

		// 获取验证码
		function getCode(phrase, token) {
			var postData = {
				mobile: mobile,
				type: type
			};

			phrase && (postData.phrase = phrase);
			token && (postData.token = token);


			$obj.prop('disabled', true).text('获取中...');
			$btnGetCode.prop('disabled', true).text('获取中...');

			$.post(
				__BASEURL__ + "mobile_api/send_code",
				autoCsrf(postData),
				function (data) {
					$btnGetCode.prop('disabled', false).text('获取验证码');

					if (data.success) {
						layer.msg('发送成功');
						$('#code-modal').hide();
						$formError.html('').hide();
	
						countDown();
						timer = setInterval(countDown, 1000);
					} else {
						if (data.code == '004-3') {
							$formError.html('').hide();
							getToken(function() {
								$('#code-modal').show();
							});
						} else {
							$formError.html(data.msg).show();
						}
	
						$obj.prop('disabled', false).text('获取验证码');
					}
				},
				"json"
			);
		}

		// 获取验证码
		$btnGetCode.on('click', function () {
			var phrase = $('#phrase').val();
			var token = $('#token').val();

			if (!phrase) {
				layer.msg('请输入图片验证码！');
				return;
			}

			getCode(phrase, token);
		});
	}

	function getToken(cb) {
		is_refresh = true;

		$.post(
			__BASEURL__ + "mobile_api/token",
			autoCsrf(),
			function (data) {
				is_refresh = false;

				if (data.success) {
					$('#captcha_img').attr('src', data.data.captcha_img);
					$('#token').val(data.data.token);
					cb && cb();
				} else {
					console.log(data.msg)
				}
			},
			"json"
		);
	}

	// 刷新验证码
	$('#captcha_img').on('click', function() {
		if (!is_refresh) {
			getToken();
		}
	})

	// 关闭弹出框
	$('.ydb-modal__close').on('click', function() {
		var $modal = $(this).parents('.ydb-modal');
		$modal.hide();
	})

	function backTop() {
		$('html,body').animate({
			scrollTop: 0
		}, 300);
	}

	w.Cookie = Cookie;
	w.PregRule = PregRule;
	w.autoCsrf = autoCsrf;
	w.GetQueryString = GetQueryString;
	w.copyUrl = copyUrl;
	w.uploadFile = uploadFile;
	w.getAuthCode = getAuthCode;
	w.backTop = backTop;
})(window);
/**
 * xcx.js
 * by liangya
 * date: 2018-04-27
 */
$(function () {
  var $xcxInfo = $('#xcx-info'),
    $qrcodeModal = $("#qrcodeModal"),
    $generateModal = $("#generateModal"),
    $authModal = $('#authModal'),
    $btnConfirmGenerate = $('#btn-confirm-generate'),
    $btnConfirmAuth = $('#btn-confirm-auth'),
    xcxTpl = document.getElementById('xcxTpl').innerHTML;

  getXcxInfo();

  // 获取商城设置信息
  function getXcxInfo() {
    $.getJSON(__BASEURL__ + "mshop/xcx_config_api/info", function (data) {
      if (data.success) {
        if (!data.data) {
          return
        }

        $xcxInfo.html(template(xcxTpl, {
          is_authorized: true,
          app_nick_name: data.data.app_nick_name,
          domain: data.data.domain,
          info: data.data.info,
          use_status: data.data.use_status,
          xcx_server_info: data.data.xcx_server_info
        }));

        initConfigForm();
        initUploadVerify();
      } else {
        var info = {
          is_authorized: false,
          app_nick_name: '',
          domain: '',
          info: null,
          use_status: 0,
          xcx_server_info: null
        };

        $xcxInfo.html(template(xcxTpl, info));

        if (data.msg !== '小程序未授权或授权过期') {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    });
  }

  // 授权小程序
  function authXcx() {
    $authModal.modal('show');
  }

  // 授权成功
  $btnConfirmAuth.on('click', function(){
    $authModal.modal('hide');
    getXcxInfo();
  })

  // 生成、升级小程序
  function generateXcx() {
    $generateModal.modal("show");
  }

  // 确定生成、升级小程序
  $btnConfirmGenerate.on("click", function () {
    $btnConfirmGenerate.prop('disabled', true);

    $.getJSON(__BASEURL__ + 'mshop/xcx_config_api/generate', function (data) {
      if (data.success) {
        new Msg({
          type: "success",
          msg: data.msg,
          delay: 1,
          callback: function () {
            getXcxInfo();
          }
        });
      } else {
        new Msg({
          type: "danger",
          msg: data.msg
        });
      }

      $btnConfirmGenerate.prop('disabled', false);
      $generateModal.modal('hide');
    });
  });

  // 体验二维码
  function experienceXcx() {
    var $experienceQrcode = $('#experience-qrcode');

    $.getJSON(__BASEURL__ + 'mshop/xcx_config_api/getqrcode', function (data) {
      if (data.success) {
        $experienceQrcode.attr('src', data.data.qrurl);
        $qrcodeModal.modal('show');
      } else {
        new Msg({
          type: "danger",
          msg: data.msg
        });
      }
    });
  }

  // 初始化上传验证文件
  function initUploadVerify() {
    var uploader = new plupload.Uploader({
      browse_button: 'btn-upload-verify', // 触发文件选择对话框的按钮，为那个元素id
      url: __BASEURL__ + 'common_file/upload?type=inc&http_url=1', // 服务器端的上传页面地址
      flash_swf_url: __STATICURL__ + 'libs/plupload/2.3.1/Moxie.swf', // swf文件，当需要使用swf方式进行上传时需要配置该参数
      file_data_name: 'imgFile',
      auto_start: true,
      multi_selection: false, // true多文件上传, false 单文件上传
      domain: __UPLOADURL__,
      filters: {
        mime_types: [{
          title: "Txt files",
          extensions: "txt"
        }]
      },
      init: {
        'FileUploaded': function (up, file, info) {
          var res = JSON.parse(info.response);
          var halfpath = res.data.new_filepath;
          var fullpath = up.getOption('domain') + halfpath;
          $("#verify_file_name").text(file.name);
          $("#verify_file_path").val(halfpath);
        }
      }
    });

    uploader.init();

    uploader.bind('FilesAdded', function (up, files) {
      uploader.start();
    });
  }

  // 初始化授权配置表单
  function initConfigForm() {
    // 授权配置表单
    $('#config-form')
      .bootstrapValidator({
        fields: {
          app_secreat: {
            validators: {
              notEmpty: {
                message: 'appsecrer不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var $btnSaveConfig = $('#btn-save-config'),
          app_id = $('#app_id').val()
          app_secreat = $("#app_secreat").val();

        $btnSaveConfig.prop('disabled', true);

        $.post(__BASEURL__ + 'mshop/xcx_config_api/save_secreat', autoCsrf({
          app_id: app_id,
          app_secreat: app_secreat
        }), function (data) {
          if (data.success) {
            new Msg({
              type: "success",
              msg: data.msg,
              delay: 1,
            });
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }

          $btnSaveConfig.prop('disabled', false);
        });
      });
  }

  // 初始化验证表单
  function initVerifyForm() {
    // 验证文件表单
    $('#verify-form')
    .bootstrapValidator({
      fields: {
        verify_file_path: {
          validators: {
            notEmpty: {
              message: '校验文件不能为空'
            }
          }
        }
      }
    })
    .on('success.form.bv', function (e) {
      // 阻止表单默认提交
      e.preventDefault();

      var $btnSaveVerify = $('#btn-save-verify'),
        app_id = $('#app_id').val(),
        verify_file_name = $('#verify_file_name').text(),
        verify_file_path = $("#verify_file_path").val();

      $btnSaveVerify.prop('disabled', true);

      $.post(__BASEURL__ + 'mshop/xcx_config_api/save_verify_file', autoCsrf({
        app_id: app_id,
        verify_file_name: verify_file_name,
        verify_file_path: verify_file_path
      }), function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: data.msg,
            delay: 1
          });
        } else {
          new Msg({
            type: 'danger',
            msg: data.msg
          });
        }

        $btnSaveVerify.prop('disabled', false);
      });
    });
  }

  // 初始化复制
  function initClipboard() {
    var clipboard = new Clipboard(".btn-copy");

    clipboard.on("success", function (e) {
      new Msg({
        type: "success",
        msg: "复制成功",
        delay: 1
      });
    });

    clipboard.on("error", function (e) {
      new Msg({
        type: "danger",
        msg: "复制失败",
        delay: 1
      });
    });
  }

  // 格式化日期
  function formateTime(str) {
    var oDate = new Date(parseInt(str) * 1000),
      oYear = oDate.getFullYear(),
      oMonth = oDate.getMonth() + 1,
      oDay = oDate.getDate(),
      oHour = oDate.getHours(),
      oMin = oDate.getMinutes(),
      oSen = oDate.getSeconds(),
      oTime = oYear + '-' + getzf(oMonth) + '-' + getzf(oDay) + ' ' + getzf(oHour) + ':' + getzf(oMin) + ':' + getzf(oSen); //最后拼接时间
    return oTime;
  }

  // 补0操作
  function getzf(num) {
    if (parseInt(num) < 10) {
      num = '0' + num;
    }

    return num;
  }

  window.authXcx = authXcx;
  window.generateXcx = generateXcx;
  window.experienceXcx = experienceXcx;
  window.formateTime = formateTime;
});
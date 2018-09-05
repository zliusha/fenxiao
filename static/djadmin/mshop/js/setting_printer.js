$(function () {
  var $printName = $("#print_name"),
    $printDeviceno = $("#print_deviceno"),
    $printKey = $("#print_key"),
    $wifiPrintBox = $('#wifi-print-box'),
    $usbPrintTest = $('#usb-print-test'),
    $btnSave = $('#btn-save');

  var LODOP;

  getPrintInfo();
  validatorPrintForm();

  // 获取打印机信息
  function getPrintInfo() {
    $.get(__BASEURL__ + '/mshop/prints_api/info2', function (data) {
      if (data.success) {
        var info = data.data.info;

        if(info) {
          $('[name="type"][value="'+info.type+'"]').prop('checked', true);
          $('[name="times"][value="'+info.times+'"]').prop('checked', true);
          $printName.val(info.print_name);
          $printDeviceno.val(info.print_deviceno);
          $printKey.val(info.print_key);
          toggleType(info.type);
        }else{
          $('[name="type"][value="1"]').prop('checked', true);
          $('[name="times"][value="1"]').prop('checked', true);
          toggleType(1);
        }
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 切换类型
  function toggleType(type) {
    if (type == 1) {
      $wifiPrintBox.show();
      $usbPrintTest.hide();
    } else if (type == 2) {
      $wifiPrintBox.hide();
      $usbPrintTest.show();
    }
  }

  // 验证打印机表单
  function validatorPrintForm() {
    $('#printer-form')
      .bootstrapValidator({
        fields: {
          type: {
            validators: {
              notEmpty: {
                message: '打印机类型不能为空'
              }
            }
          },
          times: {
            validators: {
              notEmpty: {
                message: '打印联数不能为空'
              }
            }
          },
          print_name: {
            validators: {
              notEmpty: {
                message: '设备名称不能为空'
              }
            }
          },
          print_deviceno: {
            validators: {
              notEmpty: {
                message: '设备号码不能为空'
              }
            }
          },
          print_key: {
            validators: {
              notEmpty: {
                message: '设备秘钥不能为空'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var type = +$('[name="type"]:checked').val(),
          times = +$('[name="times"]:checked').val(),
          print_name = $printName.val(),
          print_deviceno = $printDeviceno.val(),
          print_key = $printKey.val(),
          post_data = {
            type: type,
            times: times
          };

        if(type == 1) {
          post_data.print_name = print_name;
          post_data.print_deviceno = print_deviceno;
          post_data.print_key = print_key;
        }

        $btnSave.prop('disabled', true);

        $.post(__BASEURL__ + 'mshop/prints_api/save', autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: data.msg
            });
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }

          $btnSave.prop('disabled', false);
        });
      });
  }

  function CreatePrintWebPage() {
    var html = $('#printer-form').html();

    LODOP = getLodop();
    LODOP.PRINT_INIT("云店宝订单");
    LODOP.ADD_PRINT_HTM(0, 0, "100%", "100%", html);
    LODOP.SET_PRINT_PAGESIZE(3, 570, 5, "CreateCustomPage");
  }

  $usbPrintTest.on('click', function(){
    CreatePrintWebPage();
    LODOP.PRINT();
  });

  // 改变打印机类型
  $('[name="type"]').on('change', function () {
    var type = +$(this).val();

    toggleType(type);
  });
});
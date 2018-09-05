/**
 * setting_delivery_cantingbao.js
 * by liangya
 * date: 2018-08-15
 */
$(function () {
  var $status = $("#status"),
    $appKey = $("#app_key"),
    $appSecret = $("#app_secret"),
    $cantingbaoBox = $("#cantingbao-box"),
    $editCantingbao = $("#edit-cantingbao"),
    $cancelCantingbao = $("#cancel-cantingbao"),
    $confirmCantingbao = $("#confirm-cantingbao"),
    $cantingbaoBtnGroup = $("#cantingbao-btn-group");

  var info = null;

  getDeliveryMethod();
  getCantingbaoInfo();

  // 获取配送方式
  function getDeliveryMethod() {
    $.get(__BASEURL__ + 'mshop/setting_api/shipping_method', function (data) {
      if (data.success) {
        var status = +data.data.shipping;

        if (status == 6) {
          $status.prop("checked", true);
          toggleStatus(true);
        } else {
          $status.prop("checked", false);
          toggleStatus(false);
        }
      } else {
        console.log(data.msg);
      }
    });
  }

  // 切换配送开启动态
  function toggleStatus(is_use) {
    if (!is_use) {
      $cantingbaoBox.hide();
    } else {
      $cantingbaoBox.show();
      $status.prop("disabled", true);
    }
  }

  // 改变配送开启动态
  $status.on("change", function () {
    var status = $(this).prop("checked");
    var shipping = !status ? 0 : 6;

    toggleStatus(status);

    $.post(
      __BASEURL__ + "mshop/setting_api/update_shipping_method",
      autoCsrf({
        shipping: shipping
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "修改成功",
            delay: 1
          });

        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });

          $status.prop("checked", !status);
        }
      }
    );
  });

  // 获取餐厅宝信息
  function getCantingbaoInfo() {
    $.get(
      __BASEURL__ + "mshop/cantingbao_api/info",
      function (data) {
        if (data.success) {
          info = data.data.config;

          if (!info) {
            return false;
          }

          info.app_key && $appKey.val(info.app_key);
          info.app_secret && $appSecret.val(info.app_secret);

          if (!info.app_key || !info.app_secret) {
            showCantingbaoBtnGroup();
          } else {
            hideCantingbaoBtnGroup();
          }
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 编辑餐厅宝
  $editCantingbao.on("click", function () {
    showCantingbaoBtnGroup();
  });

  // 显示餐厅宝操作
  function showCantingbaoBtnGroup() {
    $editCantingbao.hide();
    $cantingbaoBtnGroup.show();
    $appKey.prop("disabled", false);
    $appSecret.prop("disabled", false);
  }

  // 隐藏餐厅宝操作
  function hideCantingbaoBtnGroup() {
    $editCantingbao.show();
    $cantingbaoBtnGroup.hide();
    $appKey.prop("disabled", true);
    $appSecret.prop("disabled", true);
  }

  // 确定修改餐厅宝
  $confirmCantingbao.on("click", function () {
    var app_key = $appKey.val(),
      app_secret = $appSecret.val();

    if (!app_key) {
      new Msg({
        type: "danger",
        msg: '商户Key不能为空'
      });

      return false;
    }

    if (!app_secret) {
      new Msg({
        type: "danger",
        msg: '商户秘钥不能为空'
      });

      return false;
    }

    $confirmCantingbao.prop('disabled', true).text('保存中...');

    $.post(
      __BASEURL__ + "mshop/cantingbao_api/setting",
      autoCsrf({
        app_key: app_key,
        app_secret: app_secret
      }),
      function (data) {
        $confirmCantingbao.prop('disabled', false).text('保存');

        if (data.success) {
          hideCantingbaoBtnGroup();

          info.app_key = app_key;
          info.app_secret = app_secret;

          new Msg({
            type: "success",
            msg: "修改成功",
            delay: 1
          });
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  });

  // 取消修改餐厅宝
  $cancelCantingbao.on("click", function () {
    hideCantingbaoBtnGroup();
    $confirmCantingbao.prop('disabled', false).text('保存');

    if (info) {
      $appKey.val(info.app_key);
      $appSecret.val(info.app_secret);
    }
  });
});
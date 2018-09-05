/**
 * setting_delivery_fengda.js
 * by liangya
 * date: 2018-06-25
 */

$(function () {
  var $status = $("#status"),
    $deliveryCon = $('.delivery-con'),
    $accountBalance = $('#account-balance'),
    $accountTbody = $("#accountTbody"),
    $btnRecharge = $('#btn-recharge'),
    $confirmRecharge = $('#confirm-recharge'),
    $rechargeMoney = $('#recharge-money'),
    $rechargeModal = $('#rechargeModal'),
    $payModal = $('#payModal'),
    $payType = $('#pay-type'),
    $payAmount = $('#pay-amount'),
    $payQrcode = $('#pay-qrcode'),
    accountTpl = document.getElementById("accountTpl").innerHTML;

  var cur_page = 1,
    page_size = 10;

  getDeliveryMethod();
  getAccountList();

  // 切换配送开启动态
  function toggleStatus(status) {
    if (!status) {
      $deliveryCon.hide();
    } else {
      $deliveryCon.show();
      $status.prop("disabled", true);
    }
  }

  // 获取配送方式
  function getDeliveryMethod() {
    $.get(__BASEURL__ + 'mshop/setting_api/shipping_method', function (data) {
      if (data.success) {
        var status = +data.data.shipping;

        if (status == 5) {
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

  // 获取点我达门店列表
  function getAccountList(curr) {
    $.getJSON(
      __BASEURL__ + "mshop/fengda_api/money_list", {
        current_page: curr || 1,
        page_size: page_size
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);

          $accountBalance.html(data.data.money)
          $accountTbody.html(template(accountTpl, data.data));

          laypage({
            cont: "accountPage",
            pages: pages,
            curr: curr || 1,
            skin: "#5aa2e7",
            first: 1,
            last: pages,
            skip: true,
            prev: "&lt",
            next: "&gt",
            jump: function (obj, first) {
              if (!first) {
                getAccountList(obj.curr);
                cur_page = obj.curr;
              }
            }
          });
        }
      }
    );
  }

  // 改变配送开启状态
  $status.on("change", function () {
    var status = $(this).prop("checked");
    var shipping = !status ? 0 : 5;

    $.post(
      __BASEURL__ + "mshop/setting_api/update_shipping_method",
      autoCsrf({
        shipping: shipping
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "修改成功"
          });

          toggleStatus(status);
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

  // 打开充值弹窗
  $btnRecharge.on('click', function () {
    $('[name="recharge_type"][value=1]').prop('checked', true);
    $rechargeMoney.val('');
    $rechargeModal.modal('show');
  });

  // 输入充值金额
  $rechargeMoney.on('keyup', function () {
    var recharge_money = $rechargeMoney.val().replace(/[^\d.]/g, '').replace(/^\./g, "").replace(/\.{2,}/g, ".").replace(".", "$#$").replace(/\./g, "").replace("$#$", ".").replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3');

    $rechargeMoney.val(recharge_money);
  });

  // 确定充值
  $confirmRecharge.on('click', function () {
    var recharge_type = $('[name="recharge_type"]:checked').val(),
      recharge_money = $rechargeMoney.val();

    if (recharge_money === '') {
      new Msg({
        type: 'danger',
        msg: '请输入充值金额'
      });

      return false;
    }

    if (+recharge_money < 1) {
      new Msg({
        type: 'danger',
        msg: '充值金额不得低于1元'
      });

      return false;
    }

    $confirmRecharge.prop('disabled', true).text('正在获取二维码...');

    $.post(__BASEURL__ + 'mshop/fengda_api/recharge', autoCsrf({
      type: recharge_type,
      amount: recharge_money
    }), function (data) {
      $confirmRecharge.prop('disabled', false).text('去支付');

      if (data.success) {
        $payQrcode.attr('src', 'http://demo.waimaishop.com/djadmin/' + 'qr_api/index?s=6&d=' + data.data.info.pay_link)
        $payType.text(data.data.info.type == '1' ? '微信扫一扫' : '支付宝扫一扫');
        $payAmount.text('￥' + parseFloat(data.data.info.amount).toFixed(2));
        $rechargeModal.modal('hide');
        $payModal.modal('show');
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    })
  });
});
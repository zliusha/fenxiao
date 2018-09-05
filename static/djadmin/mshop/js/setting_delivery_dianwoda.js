/**
 * setting_delivery_dianwoda.js
 * by liangya
 * date: 2018-02-26
 */

$(function () {
  var $status = $("#status"),
    $tabType = $('[name="tab-type"]'),
    $accountBalance = $('#account-balance'),
    $deliveryCon = $('.delivery-con'),
    $shop = $('#shop'),
    $shopForm = $("#shop-form"),
    $shopModalTitle = $('#shop-modal-title'),
    $shopId = $('#shop_id'),
    $cityCode = $('#city_code'),
    $createTime = $("#create_time"),
    $btnRecharge = $('#btn-recharge'),
    $btnRechargeRecord = $('#btn-recharge-record'),
    $btnRechargeSuccess = $('#btn-recharge-success'),
    $shopFormGroup = $("#shop-form-group"),
    $editShopModal = $("#editShopModal"),
    $rechargeModal = $('#rechargeModal'),
    $rechargeRecordModal = $('#rechargeRecordModal'),
    $rechargeResultModal = $('#rechargeResultModal'),
    $rechargeMoney = $('#recharge-money'),
    $confirmRecharge = $('#confirm-recharge'),
    $confirmShop = $("#confirm-shop"),
    shopTpl = document.getElementById("shopTpl").innerHTML,
    cityTpl = document.getElementById("cityTpl").innerHTML,
    dwdShopTpl = document.getElementById("dwdShopTpl").innerHTML,
    orderReportTpl = document.getElementById("orderReportTpl").innerHTML,
    rechargeRecordTpl = document.getElementById("rechargeRecordTpl").innerHTML;

  var start = moment().subtract(29, "days"),
    end = moment();

  var cur_page = 1,
    order_cur_page = 1,
    page_size = 10,
    shop_id = $shop.val(),
    create_time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD");

  initDateRange();
  getDeliveryMethod();
  getDwdInfo();
  getShopList();
  getCityList();
  getDwdShopList();
  getOrderReport();
  validatorShopForm();

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e) {
      create_time = s.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD");

      $createTime.val(create_time);

      getOrderReport(1);
    }

    $createTime.daterangepicker({
      startDate: start,
      endDate: end,
      maxDate: end,
      applyClass: "btn-primary",
      cancelClass: "btn-default",
      locale: {
        applyLabel: "确认",
        cancelLabel: "取消",
        fromLabel: "起始时间",
        toLabel: "结束时间",
        customRangeLabel: "自定义",
        daysOfWeek: ["日", "一", "二", "三", "四", "五", "六"],
        monthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
        firstDay: 1,
        format: "YYYY-MM-DD"
      },
      ranges: {
        今日: [moment(), moment()],
        昨日: [moment().subtract(1, "days"), moment().subtract(1, "days")],
        最近7日: [moment().subtract(6, "days"), moment()],
        最近30日: [moment().subtract(29, "days"), moment()]
      }
    }, cb);
  }

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

        if (status == 3) {
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

  // 获取点我达账户信息
  function getDwdInfo() {
    $.get(__BASEURL__ + 'mshop/dianwoda_api/info', function (data) {
      if (data.success) {
        var balance = new Number(data.data.balance).toFixed(2);

        $accountBalance.html(balance);
      } else {
        console.log(data.msg);
      }
    });
  }

  // 获取微商城门店列表
  function getShopList() {
    $.get(
      __BASEURL__ + "mshop/shop_api/all_list",
      function (data) {
        if (data.success) {
          $shopId.html(template(shopTpl, data.data));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 获取点我达门店列表
  function getDwdShopList(curr) {
    $.getJSON(
      __BASEURL__ + "mshop/dianwoda_api/shop_list", {
        current_page: curr || 1,
        page_size: page_size
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);

          $("#dwdShopTbody").html(template(dwdShopTpl, data.data));

          laypage({
            cont: "dwdShopPage",
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
                getDwdShopList(obj.curr);
                cur_page = obj.curr;
              }
            }
          });
        }
      }
    );
  }

  // 获取点我达区域列表
  function getCityList() {
    $.get(
      __BASEURL__ + "mshop/dianwoda_api/city_all_list",
      function (data) {
        if (data.success) {
          $("#city_code").html(template(cityTpl, data.data));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 验证门店表单
  function validatorShopForm() {
    $shopForm
      .bootstrapValidator({
        excluded: [":disabled"],
        fields: {
          shop_id: {
            validators: {
              notEmpty: {
                message: "请选择微商城门店"
              }
            }
          },
          city_code: {
            validators: {
              notEmpty: {
                message: "请选择订单配送区域"
              }
            }
          }
        }
      })
      .on("success.form.bv", function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var shop_id = $shopId.val(),
          city_code = $cityCode.val(),
          city_name = $cityCode.find("option:selected").text();

        $confirmShop.prop("disabled", true);

        $.post(
          __BASEURL__ + "mshop/dianwoda_api/shop_edit",
          autoCsrf({
            shop_id: shop_id,
            city_code: city_code,
            city_name: city_name
          }),
          function (data) {
            if (data.success) {
              new Msg({
                type: "success",
                msg: "保存成功",
                delay: 1
              });

              getDwdShopList(1);
            } else {
              new Msg({
                type: "danger",
                msg: data.msg
              });
            }

            $confirmShop.prop("disabled", false);
            $editShopModal.modal("hide");
          }
        );
      });
  }

  // 添加、编辑门店
  function editShop(shop_id, city_code) {
    $shopForm
      .data("bootstrapValidator")
      .destroy();
    $shopForm.data("bootstrapValidator", null);
    validatorShopForm();
    $editShopModal.modal("show");

    if (!shop_id) {
      $shopModalTitle.text("添加门店");
      $shopId.val('').prop('disabled', false);
      $cityCode.val('');
    } else {
      $shopModalTitle.text("编辑门店");
      $shopId.val(shop_id).prop('disabled', true);
      $cityCode.val(city_code);
    }
  }

  // 改变配送开启状态
  $status.on("change", function () {
    var status = $(this).prop("checked");
    var shipping = !status ? 0 : 3;

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

  // 切换tab
  $tabType.on('change', function () {
    var tabType = $(this).val();

    $('.tab-content').hide();
    $('#' + tabType).show();
  });

  // 获取订单报表
  function getOrderReport(curr) {
    $.getJSON(
      __BASEURL__ + "mshop/dianwoda_api/report_list", {
        current_page: curr || 1,
        page_size: page_size,
        shop_id: shop_id,
        start_date: create_time.split(' - ')[0],
        end_date: create_time.split(' - ')[1]
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);

          $("#orderReportTbody").html(template(orderReportTpl, data.data));

          laypage({
            cont: "orderReportPage",
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
                getOrderReport(obj.curr);
                order_cur_page = obj.curr;
              }
            }
          });
        }
      }
    );
  }

  // 获取充值记录
  function getRechargeRecord(curr) {
    $.getJSON(
      __BASEURL__ + "mshop/dianwoda_api/deposit_history", {
        current_page: curr || 1,
        page_size: page_size
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);

          $("#rechargeRecordTbody").html(template(rechargeRecordTpl, data.data));

          laypage({
            cont: "rechargeRecordPage",
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
                getRechargeRecord(obj.curr);
              }
            }
          });
        }
      }
    );
  }

  // 切换门店
  $shop.on('change', function () {
    shop_id = $shop.val();

    getOrderReport(1);
  });

  // 打开充值弹窗
  $btnRecharge.on('click', function () {
    $rechargeMoney.val('');

    $rechargeModal.modal('show');
  });

  // 打开充值记录弹窗
  $btnRechargeRecord.on('click', function () {
    $rechargeRecordModal.modal('show');

    getRechargeRecord();
  });

  // 输入充值金额
  $rechargeMoney.on('keyup', function () {
    var recharge_money = $rechargeMoney.val().replace(/[^\d.]/g, '').replace(/^\./g, "").replace(/\.{2,}/g, ".").replace(".", "$#$").replace(/\./g, "").replace("$#$", ".").replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3');

    $rechargeMoney.val(recharge_money);
  });

  // 确定充值
  $confirmRecharge.on('click', function () {
    var recharge_money = $rechargeMoney.val();

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

    $rechargeModal.modal('hide');
    $rechargeResultModal.modal('show');

    // 支付宝充值
    window.parent.open(__BASEURL__ + 'alipay/dianwoda?amount=' + recharge_money);
  });

  // 支付成功
  $btnRechargeSuccess.on('click', function () {
    $rechargeResultModal.modal('hide');

    window.location.reload();
  });

  // 下载门店订单明细
  function downloadOrderDetail(date, shop_id) {
    window.open(__BASEURL__ + 'mshop/dianwoda_api/daily_statement?date=' + date + '&shop_id=' + shop_id);
  }

  window.editShop = editShop;
  window.downloadOrderDetail = downloadOrderDetail;
});
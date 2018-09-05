/**
 * order_list.js
 * by liangya
 * date: 2017-10-25
 */
$(function () {
  var $shop = $("#shop"),
    $status = $("#status"),
    $createTime = $("#create_time"),
    $tradeno = $("#tradeno"),
    $phone = $("#phone"),
    $code = $('#code'),
    $btnSearch = $("#btn-search"),
    $btnRefresh = $("#btn-refresh"),
    $btnRefuse = $("#btn-refuse"),
    $confirmRefund = $("#confirm-refund"),
    $confirmRefuse = $("#confirm-refuse"),
    $refundModal = $("#refundModal"),
    $refuseModal = $("#refuseModal"),
    orderTpl = document.getElementById("orderTpl").innerHTML,
    orderPrintTpl = document.getElementById('orderPrintTpl').innerHTML,
    refundTpl = document.getElementById("refundTpl").innerHTML;

  var start = moment().subtract(29, "days"),
    end = moment();

  var LODOP; // 声明为全局变量

  // 搜索字段
  var cur_page = 1,
    page_size = 10,
    shop_id = $shop.val(),
    status = $status.val(),
    create_time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD"),
    tradeno = $tradeno.val(),
    phone = $phone.val(),
    code = $code.val();

  getUrlStatus();
  getUrlCreateTime();
  getOrderQuery();
  initDateRange();
  getOrderList(cur_page);
  validatorRefuseForm();

  // 获取URL参数status
  function getUrlStatus() {
    status = GetQueryString('status') || '';
    $status.val(status);
  }

  // 获取URL参数time
  function getUrlCreateTime() {
    var time = GetQueryString('create_time');
    
    if(time){
      create_time = time;
      start = create_time.split(' - ')[0];
      end = create_time.split(' - ')[1];
    }
  }

  function getOrderQuery() {
    var order_query = sessionStorage.getItem('PICKUP_ORDER_QUERY') ? JSON.parse(sessionStorage.getItem('PICKUP_ORDER_QUERY')) : null;

    if (order_query) {
      cur_page = order_query.cur_page;
      page_size = order_query.page_size;
      phone = order_query.phone;
      $phone.val(phone);
      code = order_query.logistics_code;
      $code.val(code);
      tradeno = order_query.tradeno;
      $tradeno.val(tradeno);
      create_time = order_query.create_time;
      start = create_time.split(' - ')[0];
      end = create_time.split(' - ')[1];
      status = order_query.status;
      $status.find("option[value = '" + status + "']").attr("selected", true);
      shop_id = order_query.shop_id;
      $shop.find("option[value = '" + shop_id + "']").attr("selected", true);
    }
  }

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e) {
      create_time = s.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD");

      $createTime.val(create_time);

      getOrderList(1);
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

  // 获取订单列表
  function getOrderList(curr) {
    var order_query = {
      cur_page: cur_page,
      page_size: page_size,
      shop_id: shop_id,
      status: status,
      create_time: create_time,
      tradeno: tradeno,
      phone: phone,
      logistics_code: code
    }

    sessionStorage.setItem('PICKUP_ORDER_QUERY', JSON.stringify(order_query))

    $.getJSON(
      __BASEURL__ + "mshop/order_api/self_pick_up_list", {
        current_page: curr || 1,
        page_size: page_size,
        shop_id: shop_id,
        status: status,
        create_time: create_time,
        tradeno: tradeno,
        phone: phone,
        logistics_code: code
      },
      function (data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);

          $("#orderTbody").html(
            template(orderTpl, data.data)
          );

          laypage({
            cont: "orderPage",
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
                getOrderList(obj.curr);
                cur_page = obj.curr;
              }
            }
          });
        }
      }
    );
  }


  function onMouseOut(index) {
    //toggle()
    $(".hover_table"+index).hide();
  }

  function onMouse(index) {
    $(".hover_table"+index).show();
  }

  // 修改门店
  $shop.on("change", function () {
    shop_id = $(this).val();

    getOrderList(1);
  });

  // 修改订单状态
  $status.on("change", function () {
    status = $(this).val();

    getOrderList(1);
  });

  // 搜索
  $btnSearch.on("click", function () {
    tradeno = $tradeno.val();
    code = $code.val();
    phone = $phone.val();

    getOrderList(1);
  });

  // 刷新
  $btnRefresh.on("click", function () {
    window.location.reload();
  });

  // 获取售后详情
  function getAfsDetail(id) {
    $("#refundCon").html('<div class="m-empty-box"><p>加载中...</p></div>');

    $.getJSON(
      __BASEURL__ + "mshop/afs_api/detail", {
        id: id
      },
      function (data) {
        if (data.success) {
          $("#refundCon").html(
            template(refundTpl, data.data)
          );
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 验证拒绝表单
  function validatorRefuseForm() {
    $("#refuse-form")
      .bootstrapValidator({
        fields: {
          refuse_reason: {
            validators: {
              notEmpty: {
                message: "拒绝理由不能为空"
              },
              stringLength: {
                max: 80,
                message: "拒绝理由不得超过80个字符"
              }
            }
          }
        }
      })
      .on("success.form.bv", function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var afsno = $confirmRefuse.data("afsno"),
          refuse_reason = $("#refuse_reason").val();

        $confirmRefuse.prop("disabled", true);

        $.post(
          __BASEURL__ + "mshop/afs_api/refuse",
          autoCsrf({
            afsno: afsno,
            reason: refuse_reason
          }),
          function (data) {
            if (data.success) {
              new Msg({
                type: "success",
                msg: "拒绝成功"
              });

              getOrderList(cur_page);
            } else {
              new Msg({
                type: "danger",
                msg: data.msg
              });
            }

            $confirmRefuse.prop("disabled", false);
            $refuseModal.modal("hide");
          }
        );
      });
  }

  // 打开退款拒绝弹窗
  function showRefuseModal() {
    $refuseModal.modal("show");

    $("#refuse_reason").val("");
    $confirmRefuse.prop("disabled", false);

    $("#refuse-form")
      .data("bootstrapValidator")
      .destroy();
    $("#refuse-form").data("bootstrapValidator", null);
    validatorRefuseForm();
  }

  // 同意退款
  $confirmRefund.on("click", function () {
    var $this = $(this),
      afsno = $this.data("afsno");

    $this.prop("disabled", true);

    $.post(
      __BASEURL__ + "mshop/afs_api/agree",
      autoCsrf({
        afsno: afsno
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "退款成功"
          });

          getOrderList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
        $this.prop("disabled", false);
        $refundModal.modal("hide");
      }
    );
  });

  // 拒绝退款
  $btnRefuse.on("click", function () {
    $refundModal.modal("hide");
    showRefuseModal();
  });

  // 处理/查看退款订单
  function refundOrder(afsno, is_afs_finished) {
    if (afsno != "0") {
      if (is_afs_finished == "1") {
        $("#refund-footer").hide();
      } else {
        $("#refund-footer").show();
      }
      $confirmRefund.data("afsno", afsno);
      $confirmRefuse.data("afsno", afsno);
      getAfsDetail(afsno);
    }

    $refundModal.modal("show");
  }

  // 接单订单
  function agreeOrder(tradeno) {
    $.post(
      __BASEURL__ + "mshop/order_api/orderAgree",
      autoCsrf({
        tradeno: tradeno
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "接单成功"
          });

          getOrderList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 拒绝订单
  function refuseOrder(tradeno) {
    $.post(
      __BASEURL__ + "mshop/order_api/orderRefuse",
      autoCsrf({
        tradeno: tradeno
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "拒接成功"
          });

          getOrderList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 打印订单
  function printOrder(tradeno) {
    $.post(
      __BASEURL__ + "mshop/order_api/printOrder",
      autoCsrf({
        tradeno: tradeno
      }),
      function (data) {
        if (data.success) {
          if (+data.data.print_type == 2) {
            // USB打印
            usbPrintOrder(tradeno, +data.data.print_times);
          } else {
            // WIFI打印
            new Msg({
              type: "success",
              msg: "打印成功"
            });

            getOrderList(cur_page);
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

  // USB打印
  function usbPrintOrder(tradeno, times) {
    times = times || 1;

    $.getJSON(__BASEURL__ + 'mshop/order_api/detail', {
      tradeno: tradeno
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

  // 骑手接单
  function simTakingOrder(tradeno) {
    $.get(
      __BASEURL__ + "mshop/task/simTakingOrder", {
        tradeno: tradeno
      },
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "接单成功"
          });

          getOrderList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 骑手确认送达
  function simDeliveredOrder(tradeno) {
    $.get(
      __BASEURL__ + "mshop/task/simDeliveredOrder", {
        tradeno: tradeno
      },
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "送达成功"
          });

          getOrderList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 商家确定送达
  function confirmDelivered(tradeno) {
    $.post(
      __BASEURL__ + "mshop/order_api/confirmDelivered", autoCsrf({
        tradeno: tradeno
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "确认成功"
          });

          getOrderList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 达达重新派单
  function rePushOrder(tradeno) {
    $.post(
      __BASEURL__ + "mshop/order_api/rePushOrder", autoCsrf({
        tradeno: tradeno
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "重新派单成功"
          });

          getOrderList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 转换为商家配送
  function turnSellerDelivery(tradeno) {
    $.post(
      __BASEURL__ + "mshop/order_api/turnSellerDelivery", autoCsrf({
        tradeno: tradeno
      }),
      function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: "成功转换为商家配送"
          });

          getOrderList(cur_page);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  function CreatePrintWebPage(data) {
    var html = template(orderPrintTpl, data);

    LODOP = getLodop();
    LODOP.PRINT_INIT("云店宝订单");
    LODOP.ADD_PRINT_HTM(0, 0, "100%", "100%", html);
    LODOP.SET_PRINT_PAGESIZE(3, 570, 5, "CreateCustomPage");
  }

  // 导出订单
  function exportOrder() {
    var url = __BASEURL__ + 'mshop/order_api/export_pick_up?shop_id=' + shop_id + '&current_page=1&page_size=9999' + '&status=' + status + '&create_time=' + create_time + '&tradeno=' + tradeno + '&logistics_code=' + code + '&phone=' + phone;
    window.open(url);
  }

  window.agreeOrder = agreeOrder;
  window.refuseOrder = refuseOrder;
  window.refundOrder = refundOrder;
  window.printOrder = printOrder;
  window.simTakingOrder = simTakingOrder;
  window.simDeliveredOrder = simDeliveredOrder;
  window.confirmDelivered = confirmDelivered;
  window.rePushOrder = rePushOrder;
  window.turnSellerDelivery = turnSellerDelivery;
  window.exportOrder = exportOrder;
  window.onMouseOut = onMouseOut;
  window.onMouse = onMouse;
});
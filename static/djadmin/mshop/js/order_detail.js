/**
 * order_detail.js
 * by liangya
 * date: 2017-10-25
 */
$(function () {
  var tradeno = $('#tradeno').val(),
    $btnRefuse = $('#btn-refuse'),
    $confirmRefund = $('#confirm-refund'),
    $confirmRefuse = $('#confirm-refuse'),
    $refundModal = $('#refundModal'),
    $refuseModal = $('#refuseModal'),
    orderDetailTpl = document.getElementById('orderDetailTpl').innerHTML,
    orderPrintTpl = document.getElementById('orderPrintTpl').innerHTML,
    refundTpl = document.getElementById('refundTpl').innerHTML;

  getOrderDetail();
  validatorRefuseForm();

  // 获取订单详情
  function getOrderDetail() {
    $.getJSON(__BASEURL__ + 'mshop/order_api/detail', {
      tradeno: tradeno
    }, function (data) {
      if (data.success) {
        var afsno = data.data.afsno,
          is_afs_finished = data.data.is_afs_finished

        $('#orderDetail').html(template(orderDetailTpl, data.data));

        if (afsno != '0') {
          if (is_afs_finished == '1') {
            $('#refund-footer').hide();
          } else {
            $('#refund-footer').show();
          }
          $confirmRefund.data('afsno', afsno);
          $confirmRefuse.data('afsno', afsno);
          getAfsDetail(afsno);
        }
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 获取售后详情
  function getAfsDetail(id) {
    $.getJSON(__BASEURL__ + 'mshop/afs_api/detail', {
      id: id
    }, function (data) {
      if (data.success) {
        $('#refundCon').html(template(refundTpl, data.data));
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 验证拒绝表单
  function validatorRefuseForm() {
    $('#refuse-form')
      .bootstrapValidator({
        fields: {
          refuse_reason: {
            validators: {
              notEmpty: {
                message: '拒绝理由不能为空'
              },
              stringLength: {
                max: 80,
                message: '拒绝理由不得超过80个字符'
              }
            }
          }
        }
      })
      .on('success.form.bv', function (e) {
        // 阻止表单默认提交
        e.preventDefault();

        var afsno = $confirmRefuse.data('afsno'),
          refuse_reason = $('#refuse_reason').val();

        $confirmRefuse.prop('disabled', true);

        $.post(__BASEURL__ + 'mshop/afs_api/refuse', autoCsrf({
          afsno: afsno,
          reason: refuse_reason
        }), function (data) {
          if (data.success) {
            new Msg({
              type: 'success',
              msg: '拒绝成功'
            });

            getOrderDetail();
          } else {
            new Msg({
              type: 'danger',
              msg: data.msg
            });
          }

          $confirmRefuse.prop('disabled', false);
          $refuseModal.modal('hide');
        });
      });
  }

  // 打开退款拒绝弹窗
  function showRefuseModal() {
    $refuseModal.modal('show');

    $('#refuse_reason').val('');
    $confirmRefuse.prop('disabled', false);

    $("#refuse-form").data('bootstrapValidator').destroy();
    $('#refuse-form').data('bootstrapValidator', null);
    validatorRefuseForm();
  }

  // 同意退款
  $confirmRefund.on('click', function () {
    var $this = $(this),
      afsno = $this.data('afsno');

    $this.prop('disabled', true);

    $.post(__BASEURL__ + 'mshop/afs_api/agree', autoCsrf({
      afsno: afsno
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '退款成功'
        });

        getOrderDetail();
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      $this.prop('disabled', false);
      $refundModal.modal('hide');
    });
  });

  // 拒绝退款
  $btnRefuse.on('click', function () {
    $refundModal.modal('hide');
    showRefuseModal();
  });

  // 处理、查看退款订单
  function refundOrder() {
    $refundModal.modal('show');
  }

  // 接单订单
  function agreeOrder(tradeno) {
    $.post(__BASEURL__ + 'mshop/order_api/orderAgree', autoCsrf({
      tradeno: tradeno
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '接单成功'
        });

        getOrderDetail();
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 拒绝订单
  function refuseOrder(tradeno) {
    $.post(__BASEURL__ + 'mshop/order_api/orderRefuse', autoCsrf({
      tradeno: tradeno
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: '拒接成功'
        });

        getOrderDetail();
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
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

            getOrderDetail();
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

          getOrderDetail();
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

          getOrderDetail();
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

          getOrderDetail();
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 重新派单
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

          getOrderDetail();
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

          getOrderDetail();
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

  window.agreeOrder = agreeOrder;
  window.refuseOrder = refuseOrder;
  window.refundOrder = refundOrder;
  window.printOrder = printOrder;
  window.simTakingOrder = simTakingOrder;
  window.simDeliveredOrder = simDeliveredOrder;
  window.confirmDelivered = confirmDelivered;
  window.rePushOrder = rePushOrder;
  window.turnSellerDelivery = turnSellerDelivery;
});
/**
 * dinein_order_detail.js
 * by liangya
 * date: 2018-05-07
 */
$(function () {
  var serialNumber = $('#serial_number').val(),
    orderDetailTpl = document.getElementById('orderDetailTpl').innerHTML;

  getOrderDetail();

  // 获取订单详情
  function getOrderDetail() {
    $.getJSON(__BASEURL__ + 'mshop/statements_api/info', {
      serial_number: serialNumber
    }, function (data) {
      if (data.success) {
        $('#orderDetail').html(template(orderDetailTpl, data.data));
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }
});
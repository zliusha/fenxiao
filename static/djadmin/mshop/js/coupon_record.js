/**
 * coupon_record.js
 * by liangya
 * date: 2018-03-29
 */

$(function () {
  var $status = $('#status'),
    $mobile = $('#mobile'),
    $btnSearch = $("#btn-search"),
    $couponType = $('#coupon_type'),
    coupon_type = GetQueryString('coupon_type') || '1';
    couponRecordTpl = document.getElementById('couponRecordTpl').innerHTML;

  // 搜索字段
  var cur_page = 1,
    page_size = 10,
    status = $status.val(),
    mobile = $mobile.val();

  initCouponType();
  getCouponRecord(cur_page);

  // 初始化优惠券类型
  function initCouponType() {
    var coupon_url, coupon_title;

    // 判断优惠券类型
    switch (coupon_type) {
      case '2':
        coupon_url = __BASEURL__ + 'mshop/promotion/coupon_follow';
        coupon_title = '关注优惠券';
        break;
      case '1':
      default:
        coupon_url = __BASEURL__ + 'mshop/promotion/coupon_list';
        coupon_title = '裂变优惠券';
        break;
    }

    $couponType.attr('href', coupon_url).text(coupon_title);
  }

  // 获取领取记录
  function getCouponRecord(curr) {
    $.post(__BASEURL__ + 'mshop/coupon_api/record/' + coupon_type, autoCsrf({
      status: status,
      mobile: mobile,
      current_page: curr || 1,
      page_size: page_size
    }), function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);

        $("#couponRecordTbody").html(template(couponRecordTpl, data.data));

        laypage({
          cont: 'couponRecordPage',
          pages: pages,
          curr: curr || 1,
          skin: '#5aa2e7',
          first: 1,
          last: pages,
          skip: true,
          prev: "&lt",
          next: "&gt",
          jump: function (obj, first) {
            if (!first) {
              getCouponRecord(obj.curr);
              cur_page = obj.curr;
            }
          }
        });
      }
    });
  }

  // 修改状态
  $status.on("change", function () {
    status = $(this).val();

    getCouponRecord(1);
  });


  // 搜索
  $btnSearch.on("click", function () {
    mobile = $mobile.val();

    getCouponRecord(1);
  });
});
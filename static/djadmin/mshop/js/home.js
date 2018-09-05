/**
 * home.js
 * by liangya
 * date: 2017-10-26
 */
$(function() {
  var $shop = $("#shop"),
    noticeTpl = document.getElementById("noticeTpl").innerHTML;

  var shop_id = $shop.val();

  initCalendar();
  getTodayData();
  getNotice();

  // 初始化日历
  function initCalendar() {
    $("#calendar").calendar();
  }

  // 获取公告
  function getNotice() {
    $.get(
      __BASEURL__ + "mshop/notice_api/get_list",
      {
        current_page: 1,
        page_size: 5
      },
      function(data) {
        if (data.success) {
          $("#noticeCon").html(template(noticeTpl, data.data));
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 获取今日营业数据
  function getTodayData() {
    $.getJSON(
      __BASEURL__ + "mshop/order_api/order_statistics",
      {
        shop_id: shop_id
      },
      function(data) {
        if (data.success) {
          $("#pay_order_money").text(data.data.pay_order_money);
          $("#pay_order_count").text(data.data.pay_order_count);
          $("#average_order_price").text(data.data.average_order_price);

          $("#wm_new_order_count").text(data.data.wm_new_order_count);
          $("#wm_selfpick_order_count").text(data.data.wm_selfpick_order_count);
          $("#wm_delivery_order_count").text(data.data.wm_delivery_order_count);

          $("#meal_audit_order_count").text(data.data.meal_audit_order_count);
          $("#meal_cooked_order_money").text(data.data.meal_cooked_order_money);
          $("#meal_done_order_count").text(data.data.meal_done_order_count);

          $("#ls_pay_order_count").text(data.data.ls_pay_order_count);

          $("#not_start_promotion_count").text(
            data.data.not_start_promotion_count
          );
          $("#ing_promotion_count").text(data.data.ing_promotion_count);
        } else {
          new Msg({
            type: "danger",
            msg: data.msg
          });
        }
      }
    );
  }

  // 修改门店
  $shop.on("change", function() {
    shop_id = $(this).val();

    getTodayData();
  });
});

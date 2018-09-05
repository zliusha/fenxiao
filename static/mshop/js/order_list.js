/**
 * order_list.js
 * by liangya
 * date: 2017-10-19
 */
$(function () {
  var shop_id = "",
    cur_page = 1,
    page_size = 20,
    is_loading = false,
    is_has_data = true,
    height = $(window).height(),
    $orderList = $("#order-list"),
    $loadMore = $("#load-more"),
    orderTpl = document.getElementById("orderTpl").innerHTML;

  var server_time = $("#server_time").val().replace(/-/g, "/"),
    difftime = new Date(server_time).getTime() - new Date().getTime();

  getOrderList();

  // 滚动监听
  $(window).on("scroll", function () {
    var scrolltop = $(window).scrollTop();
    var top = $loadMore.offset().top;

    if (scrolltop + height > top && is_has_data) {
      getOrderList();
    }
  });

  // 获取订单列表
  function getOrderList() {
    if (is_loading) {
      return false;
    }

    is_loading = true;

    $.getJSON(
      __BASEURL__ + "api/order/", {
        shop_id: shop_id,
        current_page: cur_page,
        page_size: page_size
      },
      function (data) {
        if (data.success) {
          var l = data.data.rows.length;

          if (l < page_size) {
            $loadMore.text("没有更多了");
            is_has_data = false;
          } else {
            is_has_data = true;
          }

          if (!l && cur_page == 1) {
            $orderList.html('<div class="m-empty"><p>暂无订单!</p></div>');
            $loadMore.hide();
          }

          $orderList.append(template(orderTpl, data.data));
          cur_page++;
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }

        is_loading = false;
      }
    );
  }

  // 查看订单详情
  function viewDetail(tid) {
    window.location.href = __BASEURL__ + "order/detail?tradeno=" + tid;
  }

  // 再来一单
  function againOrder(e, shop_id) {
    e.stopPropagation();

    window.location.href = __BASEURL__ + 'shop/index/' + shop_id;
  }

  window.viewDetail = viewDetail;
  window.againOrder = againOrder;
});
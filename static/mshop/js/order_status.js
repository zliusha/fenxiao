/**
 * myorder.js
 * by liangya
 * date: 2017-10-19
 */
$(function () {
  var shop_id = "",
    status = GetQueryString('status'),
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

  initOrderType();
  getOrderList();

  // 滚动监听
  $(window).on("scroll", function () {
    var scrolltop = $(window).scrollTop();
    var top = $loadMore.offset().top;

    if (scrolltop + height > top && is_has_data) {
      getOrderList();
    }
  });

  // 初始化订单类型
  function initOrderType(){
    var type = '';
    
    switch (status){
      case '1010':
        type = '待付款';
        break;
      case '2020':
        type = '待发货';
        break;
      case '2030':
        type = '待收货';
        break;
      case '5000,5010,5020,5030,6030,6031':
        type = '已完成';
        break;
      case '4020,4030':
        type = '退款/售后';
        break;
    }

    document.title = type;
  }

  // 获取订单列表
  function getOrderList() {
    if (is_loading) {
      return false;
    }

    is_loading = true;

    $.getJSON(
      __BASEURL__ + "api/order/", {
        shop_id: shop_id,
        status: status,
        current_page: cur_page,
        page_size: page_size
      },
      function (data) {
        if (data.success) {
          var l = data.data.rows.length,
            status_txt;

          switch (status) {
            case "1010":
              status_txt = "待付款";
              break;
            case "2020":
              status_txt = "待发货";
              break;
            case "2030":
              status_txt = "已发货";
              break;
            case "6030,6031":
              status_txt = "待收货";
              break;
            case "4020,4030":
              status_txt = "退款/售后";
              break;
            default:
              status_txt = "";
          }

          if (l < page_size) {
            $loadMore.text("没有更多了");
            is_has_data = false;
          } else {
            is_has_data = true;
          }

          if (!l && cur_page == 1) {
            $orderList.html('<div class="m-empty"><p>还没有' + status_txt + "订单!</p><p><a class='u-btn u-btn-primary u-btn-sm' href='"+__BASEURL__+"'>去逛逛</a></p></div>");
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

  // 查看订单
  function viewDetail(tid) {
    window.location.href = __BASEURL__ + "order/detail?tradeno=" + tid;
  }

  // 查看物流
  function viewLogistics(code, company) {
    window.location.href = encodeURI(
      __BASEURL__ + "order/logistics?company=" + company + "&code=" + code
    );
  }

  // 取消订单
  function cancelOrder(tid) {
    layer.open({
      content: "确定取消订单吗？",
      btn: ["确定", "取消"],
      yes: function () {
        $.post(
          __BASEURL__ + "api/order/cancel",
          autoCsrf({
            tradeno: tid
          }),
          function (data) {
            if (data.success) {
              window.location.href = window.location.href;
            } else {
              layer.open({
                content: data.msg,
                skin: "msg",
                time: 1
              });
            }
          }
        );
      }
    });
  }

  // 确认收货
  function confirmOrder(tid) {
    $.post(
      __BASEURL__ + "api/order/confirm",
      autoCsrf({
        tradeno: tid
      }),
      function (data) {
        if (data.success) {
          window.location.href = window.location.href;
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
      }
    );
  }

  // 付款
  function goPay(tid, expire, total) {
    if (new Date().getTime() + difftime > new Date(expire.replace(/-/g, "/")).getTime()) {
      // 已超过支付时间
      window.location.href = window.location.href;
    } else {
      window.location.href =
        __BASEURL__ +
        "pay?tradeno=" +
        tid +
        "&expire=" +
        expire +
        "&total=" +
        total;
    }
  }

  // 再来一单
  function againOrder(shop_id) {
    window.location.href = __BASEURL__;
  }

  window.viewDetail = viewDetail;
  window.viewLogistics = viewLogistics;
  window.confirmOrder = confirmOrder;
  window.cancelOrder = cancelOrder;
  window.againOrder = againOrder;
  window.goPay = goPay;
});
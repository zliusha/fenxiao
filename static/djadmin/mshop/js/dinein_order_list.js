/**
 * order_list.js
 * by liangya
 * date: 2017-10-25
 */
$(function () {
  var $shop = $("#shop"),
    $createTime = $("#create_time"),
    $tableName = $("#table_name"),
    $serialNumber = $("#serial_number"),
    $btnSearch = $("#btn-search"),
    $btnRefresh = $("#btn-refresh"),
    orderTpl = document.getElementById("orderTpl").innerHTML;

  var start = moment().subtract(29, "days"),
    end = moment();

  // 搜索字段
  var cur_page = 1,
    page_size = 10,
    shop_id = $shop.val(),
    create_time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD"),
    tableName = $tableName.val(),
    serialNumber = $serialNumber.val();

  initDateRange();
  getOrderList();

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
    $.getJSON(
      __BASEURL__ + "mshop/meal_statements_api/record_list", {
        current_page: curr || 1,
        page_size: page_size,
        shop_id: shop_id,
        time: create_time,
        serial_number: serialNumber,
        table_name: tableName
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

  // 修改门店
  $shop.on("change", function () {
    shop_id = $(this).val();

    getOrderList(1);
  });

  // 搜索订单
  $btnSearch.on("click", function () {
    tableName = $tableName.val();
    serialNumber = $serialNumber.val();

    getOrderList(1);
  });

  // 刷新订单
  $btnRefresh.on("click", function () {
    window.location.reload();
  });

  // 导出订单
  function exportOrder() {
    var url = __BASEURL__ + 'mshop/statements_api/export?shop_id=' + shop_id + '&current_page=1&page_size=9999' + '&serial_number=' + serialNumber + '&time=' + create_time + '&table_name=' + tableName + '&source_type=1';
    window.open(url);
  }

  window.exportOrder = exportOrder;
});
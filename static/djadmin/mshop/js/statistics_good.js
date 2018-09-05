/**
 * statistics_good.js
 * by liangya
 * date: 2017-11-23
 */

$(function() {
  var $shop = $("#shop"),
    $service = $("#service"),
    $measureType = $("#measure_type"),
    $time = $("#time"),
    $sortCtrl = $(".sort-ctrl"),
    $sortSaleNum = $("#sort-sale-num"),
    $sortSaleMoney = $("#sort-sale-money"),
    goodTpl = document.getElementById("goodTpl").innerHTML;

  var start = moment().subtract(29, "days"),
    end = moment();

  // 搜索字段
  var cur_page = 1,
    page_size = 10,
    shop_id = $shop.val(),
    service = $service.val(),
    measure_type = $measureType.val(),
    time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD"),
    sort_number_type = "desc",
    sort_money_type = "";

  initDateRange();
  getGoodList();

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e) {
      time = s.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD");

      $time.val(time);

      getGoodList(1);
    }

    $time.daterangepicker(
      {
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
          monthNames: [
            "一月",
            "二月",
            "三月",
            "四月",
            "五月",
            "六月",
            "七月",
            "八月",
            "九月",
            "十月",
            "十一月",
            "十二月"
          ],
          firstDay: 1,
          format: "YYYY-MM-DD"
        },
        ranges: {
          今日: [moment(), moment()],
          昨日: [moment().subtract(1, "days"), moment().subtract(1, "days")],
          最近7日: [moment().subtract(6, "days"), moment()],
          最近30日: [moment().subtract(29, "days"), moment()]
        }
      },
      cb
    );
  }

  // 获取商品列表
  function getGoodList(curr) {
    curr = curr || 1;
    $.getJSON(
      __BASEURL__ + "mshop/statistics_api/goods_info",
      {
        current_page: curr,
        page_size: page_size,
        shop_id: shop_id,
        service: service,
        measure_type: measure_type,
        time: time,
        sort_number_type: sort_number_type,
        sort_money_type: sort_money_type
      },
      function(data) {
        if (data.success) {
          var pages = Math.ceil(+data.data.total / page_size);

          $("#goodTbody").html(
            template(goodTpl, {
              rows: data.data.rows,
              current_page: curr,
              page_size: page_size
            })
          );

          laypage({
            cont: "goodPage",
            pages: pages,
            curr: curr || 1,
            skin: "#5aa2e7",
            first: 1,
            last: pages,
            skip: true,
            prev: "&lt",
            next: "&gt",
            jump: function(obj, first) {
              if (!first) {
                getGoodList(obj.curr);
                cur_page = obj.curr;
              }
            }
          });
        }
      }
    );
  }

  // 修改门店
  $shop.on("change", function() {
    shop_id = $shop.val();

    getGoodList(1);
  });

  // 修改服务
  $service.on("change", function() {
    service = $service.val();

    getGoodList(1);
  });

  // 修改商品类型
  $measureType.on("change", function() {
    measure_type = $measureType.val();

    getGoodList(1);
  });

  // 销量排序
  $sortSaleNum.on("click", function() {
    var $this = $(this);

    $sortCtrl.removeClass("sort-up sort-down");

    sort_money_type = "";

    if (!sort_number_type || sort_number_type == "asc") {
      $this.addClass("sort-down");
      sort_number_type = "desc";
    } else if (sort_number_type == "desc") {
      $this.addClass("sort-up");
      sort_number_type = "asc";
    }

    getGoodList(1);
  });

  // 销量额排序
  $sortSaleMoney.on("click", function() {
    var $this = $(this);

    $sortCtrl.removeClass("sort-up sort-down");

    sort_number_type = "";

    if (!sort_money_type || sort_money_type == "asc") {
      $this.addClass("sort-down");
      sort_money_type = "desc";
    } else if (sort_money_type == "desc") {
      $this.addClass("sort-up");
      sort_money_type = "asc";
    }

    getGoodList(1);
  });
});

/**
 * statistics_trade.js
 * by liangya
 * date: 2017-11-23
 */

$(function () {
  var $shop = $('#shop'),
    $service = $('#service'),
    $time = $('#time'),
    $rankTime = $('#rank_time'),
    $rankType = $('#rank_type'),
    trendChart = echarts.init(document.getElementById('trend-chart')),
    tradeShopRankTpl = document.getElementById('tradeShopRankTpl').innerHTML;

  var start = moment().subtract(29, "days"),
    end = moment();

  // 搜索字段
  var shop_id = $shop.val(),
    service = $service.val(),
    time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD"),
    rank_time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD"),
    rank_type = 1;

  initDateRange();
  initRankDateRange();
  getTradeInfo();
  initTrendChart();
  getTrendChartData();
  getTradeShopRank();

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e) {
      time = s.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD");

      $time.val(time);

      getTradeInfo();
    }

    $time.daterangepicker({
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

  // 初始化门店排行时间范围
  function initRankDateRange() {
    function cb(s, e) {
      rank_time = s.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD");

      $rankTime.val(rank_time);

      getTradeShopRank();
    }

    $rankTime.daterangepicker({
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

  // 获取交易信息
  function getTradeInfo() {
    $.get(__BASEURL__ + 'mshop/statistics_api/money_shop_info', {
      shop_id: shop_id,
      service: service,
      time: time
    }, function (data) {
      var is_today = time === moment().format("YYYY-MM-DD") + " - " + moment().format("YYYY-MM-DD");

      if (data.success) {
        // 营业额
        $('#pay_order_money').text(data.data.pay_order_money);

        // 优惠抵扣
        $('#total_discount_money').text(data.data.total_discount_money);

        // 储值卡消费额
        $('#member_order_money').text(data.data.member_order_money);

        // 退款金额
        $('#tk_order_money').text(data.data.tk_order_money);

        // 订单数
        $('#total_count').text(data.data.total_count);

        // 付款订单数
        $('#pay_order_count').text(data.data.pay_order_count);

        // 退款订单数
        $('#tk_order_count').text(data.data.tk_order_count);

        // 无效订单数
        $('#invalid_order_count').text(data.data.invalid_order_count);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        })
      }
    });
  }

  // 修改门店
  $shop.on('change', function () {
    shop_id = $shop.val();

    getTradeInfo();
    getTrendChartData();
  });

  // 修改服务
  $service.on('change', function () {
    service = $service.val();

    getTradeInfo();
    getTrendChartData();
  });

  // 初始化交易趋势数据图表
  function initTrendChart() {
    var option = {
      color: ['#e7587e', '#dd58e7', '#9d58e7', '#58a3e7', '#58c9e7', '#58e79f', '#e7b358', '#e76a58'],
      title: {
        text: ''
      },
      tooltip: {
        trigger: 'axis'
      },
      legend: {
        data: ['营业额', '优惠折扣', '储值卡消费额', '退款金额', '订单数', '付款订单数', '退款订单数', '无效订单数']
      },
      grid: {
        left: '2%',
        right: 36,
        bottom: '2%',
        containLabel: true
      },
      xAxis: {
        type: 'category',
        boundaryGap: false,
        data: ['--', '--', '--', '--', '--', '--', '--']
      },
      yAxis: {
        type: 'value',
        axisLine: {
          show: false
        }
      },
      series: [
        {
          name: '营业额',
          type: 'line',
          smooth: 'ture',
          data: [0, 0, 0, 0, 0, 0, 0]
        },
        {
          name: '优惠折扣',
          type: 'line',
          smooth: 'ture',
          data: [0, 0, 0, 0, 0, 0, 0]
        },
        {
          name: '储值卡消费额',
          type: 'line',
          smooth: 'ture',
          data: [0, 0, 0, 0, 0, 0, 0]
        },
        {
          name: '退款金额',
          type: 'line',
          smooth: 'ture',
          data: [0, 0, 0, 0, 0, 0, 0]
        },
        {
          name: '订单数',
          type: 'line',
          smooth: 'ture',
          data: [0, 0, 0, 0, 0, 0, 0]
        },
        {
          name: '付款订单数',
          type: 'line',
          smooth: 'ture',
          data: [0, 0, 0, 0, 0, 0, 0]
        },
        {
          name: '退款订单数',
          type: 'line',
          smooth: 'ture',
          data: [0, 0, 0, 0, 0, 0, 0]
        },
        {
          name: '无效订单数',
          type: 'line',
          smooth: 'ture',
          data: [0, 0, 0, 0, 0, 0, 0]
        }
      ]
    };

    trendChart.setOption(option);
  }

  // 获取交易趋势数据
  function getTrendChartData() {
    $.get(__BASEURL__ + 'mshop/statistics_api/money_chart_data', {
      shop_id: shop_id,
      service: service
    }, function (data) {
      if (data.success) {
        updateTrendChart(data.data);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 更新交易趋势数据图表
  function updateTrendChart(data) {
    var timeRangeData = [],
      payOrderMoneyData = [],
      totalDiscountMoneyData = [],
      memberOrderMoneyData = [],
      tkOrderMoneyData = [],
      totalCountData = [],
      payOrderCountData = [],
      tkOrderCountData = [],
      invalidOrderCountData = [];

    $.each(data, function (i, e) {
      timeRangeData[i] = e.date;
      payOrderMoneyData[i] = +e.pay_order_money;
      totalDiscountMoneyData[i] = +e.total_discount_money;
      memberOrderMoneyData[i] = +e.member_order_money;
      tkOrderMoneyData[i] = +e.tk_order_money;
      totalCountData[i] = +e.total_count;
      payOrderCountData[i] = +e.pay_order_count;
      tkOrderCountData[i] = +e.tk_order_count;
      invalidOrderCountData[i] = +e.invalid_order_count;
    });

    var option = {
      xAxis: {
        type: 'category',
        boundaryGap: false,
        data: timeRangeData
      },
      series: [
        {
          name: '营业额',
          type: 'line',
          smooth: 'ture',
          data: payOrderMoneyData
        },
        {
          name: '优惠折扣',
          type: 'line',
          smooth: 'ture',
          data: totalDiscountMoneyData
        },
        {
          name: '储值卡消费额',
          type: 'line',
          smooth: 'ture',
          data: memberOrderMoneyData
        },
        {
          name: '退款金额',
          type: 'line',
          smooth: 'ture',
          data: tkOrderMoneyData
        },
        {
          name: '订单数',
          type: 'line',
          smooth: 'ture',
          data: totalCountData
        },
        {
          name: '付款订单数',
          type: 'line',
          smooth: 'ture',
          data: payOrderCountData
        },
        {
          name: '退款订单数',
          type: 'line',
          smooth: 'ture',
          data: tkOrderCountData
        },
        {
          name: '无效订单数',
          type: 'line',
          smooth: 'ture',
          data: invalidOrderCountData
        }
      ]
    };

    trendChart.setOption(option);
  }

  // 获取门店排行
  function getTradeShopRank() {
    $.get(
      __BASEURL__ + "mshop/statistics_api/get_money_shop_statistic_data", {
        rank_type: rank_type,
        rank_time: rank_time
      },
      function (data) {
        if (data.success) {
          $("#tradeTbody").html(template(tradeShopRankTpl, {
            rows: data.data,
            rank_type: rank_type
          }));
        }
      }
    );
  }

  // 修改交易类型
  $rankType.on('change', function () {
    rank_type = $rankType.val();

    $('#type-txt').text($rankType.find('option:selected').text());

    getTradeShopRank();
  });
});
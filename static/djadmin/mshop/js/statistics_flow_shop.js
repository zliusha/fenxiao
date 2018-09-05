/**
 * statistics_flow_shop.js
 * by liangya
 * date: 2017-11-23
 */

$(function () {
  var $shop = $('#shop'),
    $time = $('#time'),
    trendChart = echarts.init(document.getElementById('trend-chart'));

  var start = moment().subtract(29, "days"),
    end = moment();

  // 搜索字段
  var shop_id = $shop.val(),
    time = start.format("YYYY-MM-DD") + " - " + end.format("YYYY-MM-DD");

  initDateRange();
  initTrendChart();
  getFlowInfo();
  getTrendChartData();

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e) {
      time = s.format("YYYY-MM-DD") + " - " + e.format("YYYY-MM-DD");

      $time.val(time);

      getFlowInfo();
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

  // 获取流量信息
  function getFlowInfo() {
    $.get(__BASEURL__ + 'mshop/statistics_api/flow_info', {
      shop_id: shop_id,
      time: time
    }, function (data) {
      if (data.success) {
        var is_today = time === moment().format("YYYY-MM-DD") + " - " + moment().format("YYYY-MM-DD");

        // 访客数
        var $compareUv = $('#compare-uv');
        var compare_uv = parseInt(data.data.c_uv) - parseInt(data.data.y_uv);
        $('#uv').text(data.data.c_uv);
        if (is_today) {
          if (compare_uv < 0) {
            $compareUv.html('<span class="iconfont icon-down"></span>' + Math.abs(compare_uv)).addClass('text-danger');
          } else {
            $compareUv.html('<span class="iconfont icon-up"></span>' + compare_uv).addClass('text-success');
          }
        } else {
          $compareUv.html('--').removeClass('text-danger text-success');
        }

        // 下单人数
        var $compareOrderNum = $('#compare-order-num');
        var compare_order_user = parseInt(data.data.c_order_user) - parseInt(data.data.y_order_user);
        $('#order-num').text(data.data.c_order_user);
        if (is_today) {
          if (compare_order_user < 0) {
            $compareOrderNum.html('<span class="iconfont icon-down"></span>' + Math.abs(compare_order_user)).addClass('text-danger');
          } else {
            $compareOrderNum.html('<span class="iconfont icon-up"></span>' + compare_order_user).addClass('text-success');
          }
        } else {
          $compareOrderNum.html('--').removeClass('text-danger text-success');
        }

        // 下单金额
        var $compareOrderMoney = $('#compare-order-money');
        var compare_order_money = parseFloat(data.data.c_order_money) - parseFloat(data.data.y_order_money);
        $('#order-money').text(data.data.c_order_money);
        if (is_today) {
          if (compare_order_money < 0) {
            $compareOrderMoney.html('<span class="iconfont icon-down"></span>￥' + Math.abs(compare_order_money).toFixed(2)).addClass('text-danger');
          } else {
            $compareOrderMoney.html('<span class="iconfont icon-up"></span>￥' + compare_order_money.toFixed(2)).addClass('text-success');
          }
        } else {
          $compareOrderMoney.html('￥-.--').removeClass('text-danger text-success');
        }

        // 付款人数
        var $comparePayNum = $('#compare-pay-num');
        var compare_pay_order_user = parseInt(data.data.c_pay_order_user) - parseInt(data.data.y_pay_order_user);
        $('#pay-num').text(data.data.c_pay_order_user);
        if (is_today) {
          if (compare_pay_order_user < 0) {
            $comparePayNum.html('<span class="iconfont icon-down"></span>' + Math.abs(compare_pay_order_user)).addClass('text-danger');
          } else {
            $comparePayNum.html('<span class="iconfont icon-up"></span>' + compare_pay_order_user).addClass('text-success');
          }
        } else {
          $comparePayNum.html('--').removeClass('text-danger text-success');
        }

        // 付款金额
        var $comparePayMoney = $('#compare-pay-money');
        var compare_pay_order_money = parseFloat(data.data.c_pay_order_money) - parseFloat(data.data.y_pay_order_money);
        $('#pay-money').text(data.data.c_pay_order_money);
        if (is_today) {
          if (compare_pay_order_money < 0) {
            $comparePayMoney.html('<span class="iconfont icon-down"></span>￥' + Math.abs(compare_pay_order_money).toFixed(2)).addClass('text-danger');
          } else {
            $comparePayMoney.html('<span class="iconfont icon-up"></span>￥' + compare_pay_order_money.toFixed(2)).addClass('text-success');
          }
        } else {
          $comparePayMoney.html('￥-.--').removeClass('text-danger text-success');
        }

        // 客单价
        var $compareCustomerUnitPrice = $('#compare-customer-unit-price');
        var compare_guest_unit_price = parseFloat(data.data.c_guest_unit_price) - parseFloat(data.data.y_guest_unit_price);
        $('#customer-unit-price').text(data.data.c_guest_unit_price);
        if (is_today) {
          if (compare_guest_unit_price < 0) {
            $compareCustomerUnitPrice.html('<span class="iconfont icon-down"></span>￥' + Math.abs(compare_guest_unit_price).toFixed(2)).addClass('text-danger');
          } else {
            $compareCustomerUnitPrice.html('<span class="iconfont icon-up"></span>￥' + compare_guest_unit_price.toFixed(2)).addClass('text-success');
          }
        } else {
          $compareCustomerUnitPrice.html('￥-.--').removeClass('text-danger text-success');
        }

        // 下单转化率
        var $compareOrderConversionRate = $('#compare-order-conversion-rate');
        var compare_turn_order_rate = parseFloat(data.data.c_turn_order_rate) - parseFloat(data.data.y_turn_order_rate);
        $('#order-conversion-rate').text(data.data.c_turn_order_rate);
        if (is_today) {
          if (compare_turn_order_rate < 0) {
            $compareOrderConversionRate.html('<span class="iconfont icon-down"></span>' + Math.abs(compare_turn_order_rate).toFixed(2) + '%').addClass('text-danger');
          } else {
            $compareOrderConversionRate.html('<span class="iconfont icon-up"></span>' + compare_turn_order_rate.toFixed(2) + '%').addClass('text-success');
          }
        } else {
          $compareOrderConversionRate.html('-.--%').removeClass('text-danger text-success');
        }

        // 付款转化率
        var $comparePayConversionRate = $('#compare-pay-conversion-rate');
        var compare_turn_pay_order_rate = parseFloat(data.data.c_turn_pay_order_rate) - parseFloat(data.data.y_turn_pay_order_rate);
        $('#pay-conversion-rate').text(data.data.c_turn_pay_order_rate);
        if (is_today) {
          if (compare_turn_pay_order_rate < 0) {
            $comparePayConversionRate.html('<span class="iconfont icon-down"></span>' + Math.abs(compare_turn_pay_order_rate).toFixed(2) + '%').addClass('text-danger');
          } else {
            $comparePayConversionRate.html('<span class="iconfont icon-up"></span>' + compare_turn_pay_order_rate.toFixed(2) + '%').addClass('text-success');
          }
        } else {
          $comparePayConversionRate.html('-.--%').removeClass('text-danger text-success');
        }

        // 全店转化率
        var $compareShopConversionRate = $('#compare-shop-conversion-rate');
        var compare_turn_shop_rate = parseFloat(data.data.c_turn_shop_rate) - parseFloat(data.data.y_turn_shop_rate);
        $('#shop-conversion-rate').text(data.data.c_turn_shop_rate);
        if (is_today) {
          if (compare_turn_shop_rate < 0) {
            $compareShopConversionRate.html('<span class="iconfont icon-down"></span>' + Math.abs(compare_turn_shop_rate).toFixed(2) + '%').addClass('text-danger');
          } else {
            $compareShopConversionRate.html('<span class="iconfont icon-up"></span>' + compare_turn_shop_rate.toFixed(2) + '%').addClass('text-success');
          }
        } else {
          $compareShopConversionRate.html('-.--%').removeClass('text-danger text-success');
        }
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 修改门店
  $shop.on('change', function () {
    shop_id = $shop.val();

    getFlowInfo();
  });

  // 获取流量趋势数据
  function getTrendChartData() {
    $.get(__BASEURL__ + 'mshop/statistics_api/flow_chart_data', function (data) {
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

  // 初始化流量趋势数据图表
  function initTrendChart() {
    var option = {
      color: ['#59A2E7', '#F96768', '#926EDD', '#57C7D3', '#FA6197'],
      title: {
        text: ''
      },
      tooltip: {
        trigger: 'axis'
      },
      legend: {
        data: ['付款金额', '付款人数', '下单转化率', '付款转化率', '全店转化率']
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
      series: [{
        name: '付款金额',
        type: 'line',
        smooth: 'ture',
        data: [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00]
      }, {
        name: '付款人数',
        type: 'line',
        smooth: 'ture',
        data: [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00]
      }, {
        name: '下单转化率',
        type: 'line',
        smooth: 'ture',
        data: [0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00]
      }, {
        name: '付款转化率',
        type: 'line',
        smooth: 'ture',
        data: [0, 0, 0, 0, 0, 0, 0]
      }, {
        name: '全店转化率',
        type: 'line',
        smooth: 'ture',
        data: [0, 0, 0, 0, 0, 0, 0]
      }]
    };

    trendChart.setOption(option);
  }

  // 更新流量趋势数据图表
  function updateTrendChart(data) {
    var timeRangeData = [],
      payMoneyData = [],
      payNumData = [],
      orderConversionRateData = [],
      payConversionRateData = [],
      shopConversionRateData = [];

    $.each(data, function (i, e) {
      timeRangeData[i] = e.date;
      payMoneyData[i] = +e.pay_order_money;
      payNumData[i] = +e.pay_order_user;
      orderConversionRateData[i] = +e.turn_order_rate;
      payConversionRateData[i] = +e.turn_pay_order_rate;
      shopConversionRateData[i] = +e.turn_shop_rate;
    });

    var option = {
      xAxis: {
        type: 'category',
        boundaryGap: false,
        data: timeRangeData
      },
      series: [{
        name: '付款金额',
        type: 'line',
        smooth: 'ture',
        data: payMoneyData
      }, {
        name: '付款人数',
        type: 'line',
        smooth: 'ture',
        data: payNumData
      }, {
        name: '下单转化率',
        type: 'line',
        smooth: 'ture',
        data: orderConversionRateData
      }, {
        name: '付款转化率',
        type: 'line',
        smooth: 'ture',
        data: payConversionRateData
      }, {
        name: '全店转化率',
        type: 'line',
        smooth: 'ture',
        data: shopConversionRateData
      }]
    };

    trendChart.setOption(option);
  }
});
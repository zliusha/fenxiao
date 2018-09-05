/**
 * finance.js
 * by liangya
 * date: 2017-10-26
 */
$(function () {
  var $shop = $('#shop'),
    $type = $('#type'),
    $time = $('#time'),
    $btnWithdraw = $('#btn-withdraw'),
    $confirmWithdraw = $('#confirm-withdraw'),
    $cancelWithdraw = $('#cancel-withdraw'),
    $withdrawMoney = $('#withdraw-money'),
    $withdrawForm = $('#withdraw-form'),
    $exportFinance = $('#export-finance'),
    withdrawMoney = +$withdrawMoney.attr('max'),
    financeTpl = document.getElementById('financeTpl').innerHTML;

  var start = moment().subtract(29, 'days'),
    end = moment();

  var cur_page = 1,
    page_size = 10,
    shop_id = $shop.val(),
    type = $type.val(),
    time = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');

  initDateRange();
  getAccountMoney();
  getFinanceList();

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e) {
      time = s.format('YYYY-MM-DD') + ' - ' + e.format('YYYY-MM-DD');

      $time.val(time);

      getFinanceList(cur_page);
    }

    $time.daterangepicker({
      startDate: start,
      endDate: end,
      maxDate: end,
      applyClass: 'btn-primary',
      cancelClass: 'btn-default',
      locale: {
        applyLabel: '确认',
        cancelLabel: '取消',
        fromLabel: '起始时间',
        toLabel: '结束时间',
        customRangeLabel: '自定义',
        daysOfWeek: ['日', '一', '二', '三', '四', '五', '六'],
        monthNames: ['一月', '二月', '三月', '四月', '五月', '六月',
          '七月', '八月', '九月', '十月', '十一月', '十二月'
        ],
        firstDay: 1,
        format: 'YYYY-MM-DD'
      },
      ranges: {
        '今日': [moment(), moment()],
        '昨日': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        '最近7日': [moment().subtract(6, 'days'), moment()],
        '最近30日': [moment().subtract(29, 'days'), moment()]
      }
    }, cb);
  }

  // 获取结算账户余额
  function getAccountMoney() {
    $.getJSON(__BASEURL__ + 'mshop/finance_api/info', {
      shop_id: shop_id
    }, function (data) {
      if (data.success) {
        $('#account-money').text(parseFloat(Number(data.data.money) - Number(data.data.on_money)).toFixed(2));
        $('#on-money').text(data.data.on_money);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 获取结算账户流水
  function getFinanceList(curr) {
    $.getJSON(__BASEURL__ + 'mshop/finance_api/moeny_record_list', {
      current_page: curr || 1,
      page_size: page_size,
      shop_id: shop_id,
      pay_type: type,
      time: time
    }, function (data) {
      if (data.success) {
        var pages = Math.ceil(+data.data.total / page_size);

        $('#financeTbody').html(template(financeTpl, data.data));

        laypage({
          cont: 'financePage',
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
              cur_page = obj.curr;
              getFinanceList(obj.curr);
            }
          }
        });
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
    shop_id = $(this).val();

    getAccountMoney(1);
    getFinanceList(1);
  });

  // 修改类型
  $type.on('change', function () {
    type = $(this).val();

    getFinanceList(1);
  });

  // 显示提现表单
  $btnWithdraw.on('click', function () {
    $withdrawForm.show();
  });

  // 取消提现
  $cancelWithdraw.on('click', function () {
    $withdrawForm.hide();
  });

  // 输入提现金额
  $withdrawMoney.on('keyup', function () {
    var money = $withdrawMoney.val().replace(/[^\d.]/g, '').replace(/^\./g, "").replace(/\.{2,}/g, ".").replace(".", "$#$").replace(/\./g, "").replace("$#$", ".").replace(/^(\-)*(\d+)\.(\d\d).*$/, '$1$2.$3');

    if (+money > withdrawMoney) {
      $withdrawMoney.val(withdrawMoney);
    } else {
      $withdrawMoney.val(money);
    }
  });

  // 确定提现
  $confirmWithdraw.on('click', function () {
    var money = $withdrawMoney.val();

    if (money === '') {
      new Msg({
        type: 'danger',
        msg: '输入提现金额'
      });

      return false;
    }

    if (+money <= 0) {
      new Msg({
        type: 'danger',
        msg: '提现金额必须大于0'
      });

      return false;
    }

    $confirmWithdraw.prop('disabled', true).text('提交中...');

    $.post(__BASEURL__ + 'mshop/finance_api/withdraw', autoCsrf({
      money: money
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: data.msg,
          delay: 1
        });

        window.location.reload();
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }

      $confirmWithdraw.prop('disabled', false).text('确定');
    });
  });

  // 导出流水
  $exportFinance.on('click', function () {
    window.open(__BASEURL__+'mshop/finance_api/money_record_export?shop_id='+shop_id+'&pay_type='+type+'&time='+time);
  });
});
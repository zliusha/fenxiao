/**
 * order_detail.js
 * by liangya
 * date: 2017-10-29
 */
var server_time = $("#server_time").val().replace(/-/g, "/"),
  difftime = new Date(server_time).getTime() - new Date().getTime();

var app = new Vue({
  el: '#app',
  data: {
    is_show_order: false,
    tradeno: '',
    order: {
      tid: '',
      order_ext: [],
      total_num: '',
      total_money: '',
      discount_money: '',
      discount_detail: null,      
      freight_money: '',
      pay_money: '',
      receiver_name: '',
      receiver_phone: '',
      receiver_site: '',
      receiver_address: '',
      shop_id: '',
      shop_name: '',
      shop_contact: '',
      afsno: '0',
      is_afs_finished: '',
      is_can_refund: '',
      logistics_code: '',
      logistics_status: '',
      logistics_detail: null,
      logistics_type: {
        type: "enum",
        value: '',
        alias: ''
      },
      pay_type: {
        type: "enum",
        value: '',
        alias: ''
      },
      status: {
        code: '',
        name: '',
        alias: ''
      },
      time: {
        type: "unix_timestamp",
        value: '',
        alias: ''
      },
      fh_time: {
        type: "unix_timestamp",
        value: 0,
        alias: ''
      },
      pay_time: {
        type: "unix_timestamp",
        value: '',
        alias: ''
      },
      pay_expire: {
        type: "unix_timestamp",
        value: '',
        alias: ''
      },
      update_time: {
        type: "unix_timestamp",
        value: '',
        alias: ''
      }
    }
  },
  computed: {
    address: function () {
      return (
        this.order.receiver_site +
        this.order.receiver_address
      );
    }
  },
  mounted: function () {
    var _this = this;

    _this.tradeno = GetQueryString("tradeno");

    // 获取订单信息
    _this.getOrderInfo();
  },
  methods: {
    getOrderInfo: function () {
      var _this = this;

      $.getJSON(
        __BASEURL__ + "api/order/detail", {
          tradeno: _this.tradeno
        },
        function (data) {
          if (data.success) {
            _this.order = data.data;
            _this.is_show_order = true;

            // 如果是待支付订单初始化倒计时
            if (_this.order.status.code == "1010") {
              setTimeout(function(){
                _this.setCountdown();
              }, 100);
            }
          } else {
            layer.open({
              content: data.msg,
              skin: "msg",
              time: 1
            });
          }
        }
      );
    },
    setCountdown: function () {
      var _this = this;

      initCountDown(
        "countdown",
        difftime,
        _this.order.pay_expire.alias.replace(/-/g, "/"),
        function () {
          _this.order.status.code = '5000';
          _this.order.status.alias = '订单关闭';
          _this.order.status.name = '订单关闭-付款超时';
        }
      );
    },
    againOrder: function(){
      var _this = this;
    
      window.location.href = __BASEURL__+'shop/index/' + _this.order.shop_id;
    },
    cancelOrder: function () {
      var _this = this;

      layer.open({
        content: "确定取消订单吗？",
        btn: ["确定", "取消"],
        yes: function () {
          $.post(
            __BASEURL__ + "api/order/cancel",
            autoCsrf({
              tradeno: _this.tradeno
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
    },
    cancelRefund: function(){
      var _this = this;

      $.post(
        __BASEURL__ + "api/afs/cancel",
        autoCsrf({
          afsno: _this.order.afsno
        }),
        function(data) {
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
    },
    goPay: function () {
      var _this = this;

      window.location.href =
        __BASEURL__ +
        "order/pay?tradeno=" +
        _this.order.tid +
        "&expire=" +
        _this.order.pay_expire.alias +
        "&pay_money=" +
        _this.order.pay_money;
    }
  }
});

/**
 * order_refund.js
 * by liangya
 * date: 2017-10-29
 */

var app = new Vue({
  el: '#app',
  data: {
    tradeno: '',
    type: '1',
    available_type: '1',
    pay_money: '0.00',
    reason: '',
    remark: '',
    goods: [],
    is_submiting: false
  },
  computed: {
    money: function () {
      var _this = this;
      var money = 0;

      if (_this.type == '1') {
        money = _this.pay_money;
      } else {
        $.each(_this.goods, function (i, e) {
          money += e.num * e.unit_price;
        });
      }

      return parseFloat(money).toFixed(2);
    }
  },
  mounted: function () {
    this.tradeno = GetQueryString("tradeno");
    
    this.getRefundInfo();
  },
  methods: {
    getRefundInfo: function () {
      var _this = this;

      $.post(
        __BASEURL__ + "api/afs/availableRefund", autoCsrf({
          tradeno: _this.tradeno
        }),
        function (data) {
          if (data.success) {
            var available_type = data.data.available_type.split(',');
            _this.available_type = data.data.available_type;
            _this.pay_money = data.data.order_pay_money;
            _this.goods = data.data.order_ext;
            _this.type = available_type[0];

            $.each(data.data.order_ext, function (i, e) {
              _this.goods[i].max = parseInt(e.num);
              _this.goods[i].num = 0;
            });

            _this.is_show_order = true;
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
    add: function (i) {
      if (this.goods[i].num < this.goods[i].max) {
        this.goods[i].num++;
      }
    },
    reduce: function (i) {
      if (this.goods[i].num > 0) {
        this.goods[i].num--;
      }
    },
    submitRefund: function (e) {
      var _this = this;
      var afs_goods = [];

      $.each(_this.goods, function (i, e) {
        afs_goods.push({
          ext_tid: e.ext_tid,
          num: e.num
        });
      });

      if (!_this.reason) {
        layer.open({
          content: '请选择退款原因',
          skin: "msg",
          time: 1
        });

        return false;
      }

      _this.is_submiting = true;

      $.post(__BASEURL__ + 'api/afs/create', autoCsrf({
        tradeno: _this.tradeno,
        type: _this.type,
        reason: _this.reason,
        remark: _this.remark,
        afs_detail: JSON.stringify(afs_goods)
      }), function (data) {
        if (data.success) {
          window.location.replace(__BASEURL__ + 'order/detail?tradeno=' + _this.tradeno);
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }

        _this.is_submiting = false;
      });
    }
  }
});
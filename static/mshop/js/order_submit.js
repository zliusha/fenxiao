/**
 * order_submit.js
 * by liangya
 * date: 2017-10-30
 */
var app = new Vue({
  el: '#app',
  data: {
    is_show_order: false,
    is_can_pay: true,
    position: {
      lat: '',
      lng: '',
    },
    shop_id: '',
    shop_name: '-',
    arrive_time: 0,
    service_radius: 0,
    addr_id: '',
    addr_name: '',
    addr_phone: '',
    addr_site: '',
    addr_address: '',
    latitude: '',
    longitude: '',
    remark: '',
    goods: [],
    items: [],
    total_num: 0,
    manjian: {
      condition_price: 0,
      reduce_price: 0
    },
    xinren: 0,
    huiyuan: 0,
    tips: '',
    coupon: {
      all_list: [],
      available_list: [],
      disabled_list: [],
      selected: {
        id: '',
        amount: 0
      }
    },
    vouchers: {
      all_list: [],
      available_list: [],
      disabled_list: [],
      selected: {
        id: '',
        amount: 0
      }
    },
    freight_money: 0,
    package_money: 0,
    discount_money: 0,
    total_money: 0
  },
  computed: {
    address: function () {
      return (
        this.addr_site +
        this.addr_address +
        ''
      );
    },
    update_time: function () {
      var _this = this;

      return moment().add(_this.arrive_time, 'minutes').format('HH:mm');
    },
    yh_money: function () {
      return (parseFloat(this.discount_money) + parseFloat(this.coupon.selected.amount) + parseFloat(this.vouchers.selected.amount)).toFixed(2);
    },
    coupon_money: function() {
      return (parseFloat(this.total_money) - parseFloat(this.coupon.selected.amount)).toFixed(2);
    },
    pay_money: function () {
      return (parseFloat(this.total_money) - parseFloat(this.coupon.selected.amount) - parseFloat(this.vouchers.selected.amount)).toFixed(2);
    }
  },
  watch: {
    'pay_money': {
      handler: function (val, oldVal) {
        var _this = this;

        _this.vouchers.available_list = _this.vouchers.all_list.filter(function (val) {
          return parseFloat(val.cardInfo.cash.least_cost/100) <= parseFloat(_this.coupon_money);
        });

        _this.vouchers.disabled_list = _this.vouchers.all_list.filter(function (val) {
          return parseFloat(val.cardInfo.cash.least_cost/100) > parseFloat(_this.coupon_money);
        });
      },
      deep: true
    }
  },
  mounted: function () {
    var _this = this,
      order_good = JSON.parse(localStorage.getItem('ORDERGOOD'));

    _this.shop_id = $('#shop_id').val();
    _this.addr_id = GetQueryString('addr_id');
    _this.latitude = GetQueryString('latitude');
    _this.longitude = GetQueryString('longitude');
    _this.freight_money = order_good.freight_money;
    _this.package_money = order_good.package_money;
    _this.total_money = order_good.pay_money;
    _this.total_num = order_good.total_num;

    $.each(order_good.goods, function (i, e) {
      _this.items.push({
        goods_id: e.id,
        sku_id: e.sku_id,
        quantity: e.amount
      });
    });

    _this.togglePage(_this.getHash());

    // 监听地址栏hash
    $(window).on('hashchange', function () {
      _this.togglePage(_this.getHash());
    });

    _this.getPosition();
    _this.getPromotion();
    _this.getVouchers();
    _this.getAddress();
  },
  methods: {
    getHash: function () {
      var hash = window.location.hash,
        page = hash.split('#')[1] ? hash.split('#')[1] : '';

      return page;
    },
    togglePage: function (page) {
      if (page === 'm-coupon-content') {
        $('#m-coupon-content').show();
      } else if (page === 'm-vouchers-content') {
        $('#m-vouchers-content').show();
      } else {
        $('#m-coupon-content').hide();
        $('#m-vouchers-content').hide();
      }
    },
    getPosition: function () {
      var _this = this,
        geolocation = new qq.maps.Geolocation("GMSBZ-F6VK6-7H6ST-MRTQ2-46L26-SJFD3", "myapp"),
        options = {
          timeout: 8000,
          failTipFlag: true
        };

      geolocation.getLocation(function (pos) {
        _this.position.lat = pos.lat;
        _this.position.lng = pos.lng;

        if(!_this.latitude || !_this.longitude) {
          _this.latitude = pos.lat;
          _this.longitude = pos.lng;
          _this.getPromotion();
        }

        _this.getShopInfo();
      }, function () {
        _this.getShopInfo();
      }, options);
    },
    getShopInfo: function () {
      var _this = this;

      $.getJSON(__BASEURL__ + 'api/shop/info', {
        shop_id: _this.shop_id
      }, function (data) {
        if (data.success) {
          var c = new qq.maps.LatLng(data.data.latitude, data.data.longitude);
          _this.shop_name = data.data.shop_name;
          _this.arrive_time = data.data.arrive_time;
          _this.service_radius = data.data.service_radius;

          if (!_this.addr_id) {
            $.getJSON(__BASEURL__ + 'api/address', function (data) {
              if (data.success) {
                var addrList = [];
                var a = new qq.maps.LatLng(_this.position.lat, _this.position.lng);

                $.each(data.data, function (i, e) {
                  var b = new qq.maps.LatLng(e.latitude, e.longitude);

                  addrList.push({
                    addr_id: e.id,
                    addr_name: e.receiver_name,
                    addr_phone: e.receiver_phone,
                    addr_site: e.receiver_site,
                    addr_address: e.receiver_address,
                    latitude: e.latitude,
                    longitude: e.longitude,
                    distance: qq.maps.geometry.spherical.computeDistanceBetween(a, b) / 1000,
                    shop_distance: qq.maps.geometry.spherical.computeDistanceBetween(c, b) / 1000
                  });
                });

                addrList = addrList.sort(function (a, b) {
                  return a.distance - b.distance;
                });

                addrList = addrList.filter(function (e) {
                  return parseFloat(e.shop_distance) < parseFloat(_this.service_radius);
                });

                if (addrList.length > 0) {
                  _this.addr_id = addrList[0].addr_id;
                  _this.addr_name = addrList[0].addr_name;
                  _this.addr_phone = addrList[0].addr_phone;
                  _this.addr_site = addrList[0].addr_site;
                  _this.addr_address = addrList[0].addr_address;
                  _this.latitude = addrList[0].latitude;
                  _this.longitude = addrList[0].longitude;
                }
              } else {
                layer.open({
                  content: data.msg,
                  skin: 'msg',
                  time: 1
                });
              }

              _this.is_show_order = true;
            });
          }
        } else {
          layer.open({
            content: data.msg,
            skin: 'msg',
            time: 1
          });
        }

        _this.is_show_order = true;
      });
    },
    getAddress: function () {
      var _this = this;

      if (_this.addr_id) {
        $.getJSON(
          __BASEURL__ + 'api/address/info', {
            receiver_address_id: _this.addr_id
          },
          function (data) {
            if (data.success) {
              _this.addr_name = data.data.receiver_name;
              _this.addr_phone = data.data.receiver_phone;
              _this.addr_site = data.data.receiver_site;
              _this.addr_address = data.data.receiver_address;
            } else {
              layer.open({
                content: data.msg,
                skin: 'msg',
                time: 1
              });
            }
            _this.is_show_order = true;
          }
        );
      }
    },
    getPromotion: function () {
      var _this = this;

      if(!_this.latitude || !_this.longitude) {
        return;
      }

      $.post(__BASEURL__ + 'api/order/preorder', autoCsrf({
        shop_id: _this.shop_id,
        latitude: _this.latitude,
        longitude: _this.longitude,
        items: JSON.stringify(_this.items)
      }), function (data) {
        if (data.success) {
          _this.goods = data.data.items;
          _this.discount_money = data.data.discount_money;
          _this.total_money = data.data.pay_money;
          _this.manjian = data.data.discount_detail.manjian;
          _this.xinren = data.data.discount_detail.xinren;
          _this.huiyuan = data.data.discount_detail.huiyuan;
          _this.tips = data.data.discount_detail.tips;
          _this.coupon.all_list = data.data.coupon_list;
          _this.coupon.available_list = data.data.coupon_list.filter(function (val) {
            return !val.disabled;
          });
          _this.coupon.disabled_list = data.data.coupon_list.filter(function (val) {
            return val.disabled;
          });
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
      });
    },
    getVouchers: function () {
      var _this = this;

      $.post(__BASEURL__ + 'api/wecard/coupon_card', autoCsrf({}), function (data) {
        if(data.success){
          _this.vouchers.all_list = data.data;
          _this.vouchers.available_list = data.data.filter(function(val){
            console.log(parseFloat(val.cardInfo.cash.least_cost/100) <= parseFloat(_this.coupon_money))
            return parseFloat(val.cardInfo.cash.least_cost/100) <= parseFloat(_this.coupon_money);
          });

          _this.vouchers.disabled_list = data.data.filter(function(val){
            return parseFloat(val.cardInfo.cash.least_cost/100) > parseFloat(_this.coupon_money);
          });
        }else{
          console.log(data.msg);
        }
      });
    },
    openCoupon: function () {
      $('#m-coupon-content').show();
    },
    selectCoupon: function (id, amount) {
      this.coupon.selected.id = id;
      this.coupon.selected.amount = amount;
      window.history.back();
    },
    openVouchers: function () {
      $('#m-vouchers-content').show();
    },
    selectVouchers: function (id, amount) {
      this.vouchers.selected.id = id;
      this.vouchers.selected.amount = amount;
      window.history.back();
    },
    chooseAddress: function () {
      window.location.href = __BASEURL__ + 'address/choose/' + this.shop_id;
    },
    clearCart: function () {
      var _this = this,
        cart = new Cart();

      $.each(_this.items, function (i, e) {
        cart.delGood(_this.items[i].sku_id);
      });
    },
    goPay: function () {
      var _this = this;
      var mobile = $('#user_mobile').val();

      // 判断地址是否为空
      if (_this.delivery_type == "1" && !_this.addr_id) {
        layer.open({
          content: '请先选择收货地址',
          skin: 'msg',
          time: 1
        });

        return false;
      }

      // 判断是否绑定过手机
      if (!mobile) {
        window.location.href = __BASEURL__ + 'user/bind_phone';

        return false;
      }

      _this.is_can_pay = false;

      // 提交订单去支付
      $.post(
        __BASEURL__ + "api/order/create",
        autoCsrf({
          shop_id: _this.shop_id,
          rec_addr_id: _this.addr_id,
          remark: _this.remark,
          coupon_id: _this.coupon.selected.id,
          card_id: _this.vouchers.selected.id,
          items: JSON.stringify(_this.items)
        }),
        function (data) {
          if (data.success) {
            _this.clearCart();

            window.location.href =
              __BASEURL__ +
              "order/pay?type=order" +
              "&tradeno=" +
              data.data.tradeno +
              "&expire=" +
              data.data.pay_expire +
              "&pay_money=" +
              _this.pay_money;
          } else {
            layer.open({
              content: data.msg,
              skin: "msg",
              time: 1
            });
          }

          _this.is_can_pay = true;
        }
      );
    }
  }
});
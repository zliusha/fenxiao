$(function () {
  var shop_id = $('#shop_id').val(),
    addressTpl = document.getElementById('addressTpl').innerHTML;

  var address = {
      selectAddrList: [],
      diabledAddrList: [],
    },
    shop = {
      service_radius: '',
      lat: '',
      lng: ''
    };

  getAddressList();

  // 获取地址列表
  function getAddressList() {
    $.getJSON(__BASEURL__ + 'api/shop/info', {
      shop_id: shop_id
    }, function (data) {
      if (data.success) {
        shop.service_radius = data.data.service_radius;
        shop.lat = data.data.latitude;
        shop.lng = data.data.longitude;

        $.get(__BASEURL__ + "api/address", function (data) {
          if (data.success) {
            var addrList = [];
            var a = new qq.maps.LatLng(shop.lat, shop.lng);

            $.each(data.data, function (i, e) {
              var b = new qq.maps.LatLng(e.latitude, e.longitude);

              addrList.push({
                id: e.id,
                receiver_name: e.receiver_name,
                receiver_phone: e.receiver_phone,
                receiver_site: e.receiver_site,
                receiver_address: e.receiver_address,
                latitude: e.latitude,
                longitude: e.longitude,
                distance: qq.maps.geometry.spherical.computeDistanceBetween(a, b) / 1000
              });
            });

            addrList = addrList.sort(function (a, b) {
              return a.distance - b.distance;
            });

            $.each(addrList, function (i, e) {
              if (parseFloat(e.distance) > parseFloat(shop.service_radius)) {
                address.diabledAddrList.push({
                  id: e.id,
                  receiver_name: e.receiver_name,
                  receiver_phone: e.receiver_phone,
                  receiver_site: e.receiver_site,
                  receiver_address: e.receiver_address,
                  latitude: e.latitude,
                  longitude: e.longitude,
                  distance: e.distance
                });
              } else {
                address.selectAddrList.push({
                  id: e.id,
                  receiver_name: e.receiver_name,
                  receiver_phone: e.receiver_phone,
                  receiver_site: e.receiver_site,
                  receiver_address: e.receiver_address,
                  latitude: e.latitude,
                  longitude: e.longitude,
                  distance: e.distance
                });
              }
            });

            $("#address-list").html(template(addressTpl, address));
          }
        });
      } else {
        layer.open({
          content: data.msg,
          skin: 'msg',
          time: 1
        });
      }
    });
  }

  // 添加收货地址
  function addAddress() {
    window.location.href = __BASEURL__ + "address/add";
  }

  // 选择收货地址
  function chooseAddress(id, latitude, longitude) {
    if (!id || !latitude || !longitude) {
      return false;
    }

    window.location.href = __BASEURL__ + 'order/submit/' + shop_id + '?addr_id=' + id + '&latitude=' + latitude + '&longitude=' + longitude;
  }

  window.addAddress = addAddress;
  window.chooseAddress = chooseAddress;
});
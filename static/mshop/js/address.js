$(function () {
  var $btnConfirm = $('#btn-confirm');

  var areaData = [],
    address_id = $("#address_id").val();

  var poi = {
    poiname: '',
    poiaddress: '',
    lat: '',
    lng: ''
  };

  //var poi = JSON.parse(localStorage.getItem("poi")),
  var geolocation = new qq.maps.Geolocation(
      "GMSBZ-F6VK6-7H6ST-MRTQ2-46L26-SJFD3",
      "myapp"
    ),
    poiNot = {
      lat: "",
      lng: ''
    },
    receiver_name = "",
    receiver_phone = "",
    receiver_site = "",
    receiver_address = "",
    longitude = "",
    latitude = "",
    tag = "",
    sex = "";

  //showAddress
  function showAddress() {
    $("#address-area").val(poi.poiname);
    /* poiNot.lat = poi.lat;
     poiNot.lng = poi.lng;*/
    longitude = poi.lng;
    latitude = poi.lat;
    console.log(poi)
  }
  showAddress();


  if (address_id) {
    getAddressInfo();
  }


  // 获取地址详情
  function getAddressInfo() {
    $.getJSON(
      __BASEURL__ + "api/address/info", {
        receiver_address_id: address_id
      },
      function (data) {
        if (data.success) {
          var edit = JSON.parse(localStorage.getItem("edit"));

          $("#address-area").val(data.data.receiver_site + " ");
          $("#receiver-name").val(data.data.receiver_name);
          $("#receiver-phone").val(data.data.receiver_phone);
          $("#receiver-address").val(data.data.receiver_address);

          $("#region").val(data.data.region);
          longitude = data.data.longitude;
          latitude = data.data.latitude;
          poi.poiname = data.data.receiver_site;
          poi.lat = data.data.latitude;
          poi.lng = data.data.longitude;
          console.log(poi);

          $('input[name="sex"]').each(function (i, e) {
            if ($(e).val() == data.data.sex) {
              $(e).prop('checked', true);
            } else {
              $(e).prop('checked', false);
            }
          });

          $('input[name="tag"]').each(function (i, e) {
            if ($(e).val() == data.data.tag) {
              $(e).prop('checked', true);
            } else {
              $(e).prop('checked', false);
            }
          });
        }
      }
    );
  }

  // 确定添加/修改地址
  $btnConfirm.on('click', function () {
    receiver_name = $("#receiver-name").val();
    receiver_phone = $("#receiver-phone").val();
    receiver_site = $("#address-area").val();
    receiver_address = $("#receiver-address").val();
    sex = $('[name="sex"]:checked').val();
    tag = $('[name="tag"]:checked').val();
    var postData,
      postUrl;

    postData = {
      receiver_name: receiver_name,
      receiver_phone: receiver_phone,
      receiver_site: receiver_site,
      receiver_address: receiver_address,
      longitude: poi.lng,
      latitude: poi.lat,
      sex: sex,
      tag: tag
    };

    if (!address_id) {
      postUrl = __BASEURL__ + "api/address/create";
    } else {
      postData.receiver_address_id = address_id;
      postUrl = __BASEURL__ + "api/address/edit";
    }

    if (!receiver_name) {
      layer.open({
        content: "联系人不能为空",
        skin: "msg",
        time: 1
      });

      return false;
    }

    if (!receiver_phone) {
      layer.open({
        content: "手机号不能为空",
        skin: "msg",
        time: 2
      });

      return false;
    } else if (!PregRule.Tel.test(receiver_phone)) {
      layer.open({
        content: "手机号格式不正确",
        skin: "msg",
        time: 1
      });

      return false;
    }

    if (!receiver_site) {
      layer.open({
        content: "省市区不能为空",
        skin: "msg",
        time: 1
      });

      return false;
    }

    if (!receiver_address) {
      layer.open({
        content: "门牌号不能为空",
        skin: "msg",
        time: 1
      });

      return false;
    }

    $btnConfirm.prop('disabled', true).text('提交中...');

    $.post(
      postUrl,
      autoCsrf(postData),
      function (data) {
        if (data.success) {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
          if (document.referrer) {
            setTimeout(function () {
              window.location.replace(document.referrer);
            }, 1000)
          } else {
            setTimeout(function () {
              window.location.replace(__BASEURL__ + 'address');
            }, 1000)
          }
        } else {
          layer.open({
            content: data.msg,
            skin: "msg",
            time: 1
          });
        }
        $btnConfirm.prop('disabled', false).text('确定');
      }
    );
  });

  //新增地址
  function addNewddress() {
    $("#iframe").show();
    select();
    console.log(poi)
  }

  function select() {
    adaptHeight(); //动态适配高度
    window.onresize = function () { //横屏、浏览器变全屏模式下的时候，需要重新计算高度
      adaptHeight();
    };

    window.addEventListener('message', function (event) {
      var loc = event.data;

      poi.poiname = loc.poiname;
      poi.poiaddress = loc.poiaddress;
      poi.lat = loc.latlng.lat;
      poi.lng = loc.latlng.lng;

      showAddress();
      $("#iframe").hide();
    }, false);

    function adaptHeight() {
      var winH = $(window).height();
      var bodyH = document.documentElement.clientHeight;

      if (winH > bodyH) {
        window.parent.document.getElementById("iframe").height = winH;
      } else {
        window.parent.document.getElementById("iframe").height = bodyH;
      }
    }
  }

  window.addNewddress = addNewddress;
});
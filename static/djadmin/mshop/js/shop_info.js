/**
 * shop_info.js
 * by jinmu
 * date: 2017-08-028
 */
$(function () {
  var shop_id = $("#shop_id").val(),
    $shopName = $('#shop_name'),
    $shopLogo = $('#shop_logo'),
    $shopStartPrice = $("#shop_startPrice"),
    $shopDispatchPrice = $("#shop_dispatchPrice"),
    $shop_detailAddress = $("#shop_detailAddress"),
    $shopRadius = $("#shop_radius"),
    $shopSendTime = $("#shop_sendTime"),
    $shopArea = $("#shop_area"),
    $shopState = $('#shop_state'),
    $shopCity = $('#shop_city'),
    $shopNotice = $('#shop_notice'),
    $shopDistrict = $('#shop_district'),
    $shopAddress = $('#shop_address'),
    $shopLocation = $('#shop_location'),
    $contact = $('#contact'),
    $openTime = $("#openTime"),
    $btnConfirm = $('#btn-confirm');

  var logoTpl = document.getElementById("logoTpl").innerHTML;  

  var use_flow = 1,
    auto_receiver = 1,
    auto_printer=1,
    longitude = "",
    latitude = "",
    on_time = [],
    goodArr = [],
    logo = {
      list: [{}]
    },
    timeData = {
      list: []
    };

  getShopInfo();

  // 获取门店信息
  function getShopInfo() {
    $btnConfirm.attr("data-shopId", shop_id);

    $.getJSON(__BASEURL__ + '/mshop/shop_api/info', {
      shop_id: shop_id
    }, function (data) {
      if (data.success) {
        var dataInfo = data.data.info;

        use_flow = dataInfo.use_flow;
        auto_receiver = dataInfo.auto_receiver;
        auto_printer=dataInfo.auto_printer;

        var picArr = {
          list:[]
        }

        picArr.list = dataInfo.shop_imgs.split(",");

        $("#logoTbody").html(template(logoTpl, picArr));

        if (auto_receiver == 1) {
          $("#receiver").html("开启");
         // $("#receiver").find('[value="接单"]').prop("checked", true);
        } else {
          //$("#receiver").find('[value="接单"]').prop("checked", false);
          $("#receiver").html("关闭");
        }
        if (auto_printer == 1) {
          $("#make_printer").html("开启");
        } else {
          $("#make_printer").html("关闭");
        }

        latitude = dataInfo.latitude;
        longitude = dataInfo.longitude;
        on_time = dataInfo.on_time;

        var NewTime = [];

        if (dataInfo.on_time == null) {
          NewTime = [];
        } else {
          if (timeData.list.length > 0) {
            timeData.list.splice(0, timeData.list.length);
          }
          NewTime = dataInfo.on_time.split('|');
          for (var i = 0; i < NewTime.length; i++) {
            var times = {};
            times.startTime = NewTime[i].substring(0, 5);
            times.endTime = NewTime[i].substring(6, 12);
            timeData.list.push(times);
          }
          var html = [];
          for (var i = 0; i < timeData.list.length; i++) {
            html += '<p>' + timeData.list[i].startTime + ' - ' + timeData.list[i].endTime + '</p>';
          }
          $openTime.find("span").remove();
          $openTime.append(html);
        }

        regions = dataInfo.region ? dataInfo.region.split('-') : new Array(3);
        province = regions[0] || 0;
        city = regions[1] || 0;
        area = regions[2] || 0;
        halfpath = dataInfo.shop_logo;
        fullpath = __UPLOADURL__ + halfpath;


        //更新地图
        var address = dataInfo.shop_address;
        addr = dataInfo.shop_state + dataInfo.shop_city + dataInfo.shop_district + address;

        $shopName.text(dataInfo.shop_name);
        $shop_detailAddress.text(addr);
        $shopLogo.attr('src', fullpath);
        $contact.text(dataInfo.contact);
        $shopStartPrice.text((dataInfo.send_money));
        $shopDispatchPrice.text(dataInfo.freight_money);
        $shopRadius.text(dataInfo.service_radius);
        $shopSendTime.text(dataInfo.arrive_time);
        $shopArea.text(dataInfo.arrive_range);
        $shopNotice.text(dataInfo.notice);

        $shopAddress.val(dataInfo.shop_address);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }
});
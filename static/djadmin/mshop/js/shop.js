/**
 * shop.js
 * by jinmu
 * date: 2017-08-09
 */
$(function () {
  var mainShopId = GetQueryString('main_shop_id') || '',
    shopId = $('#shop_id').val(),
    isAdmin = $('#is_admin').val(),
    $shopName = $('#shop_name'),
    $shopLogo = $('#shop_logo'),
    $goodLogo = $("#good_logo"),
    $shippingDistance1 = $('#shipping_distance1'),
    $shippingDistance2 = $('#shipping_distance2'),
    $shippingDistance3 = $('#shipping_distance3'),
    $shippingDistance4 = $('#shipping_distance4'),
    $shopStartPrice = $("#shop_startPrice"),
    $fullDispatchPrice = $("#full_dispatchPrice"),
    $shopRadius = $("#shop_radius"),
    $shopSendTime = $("#shop_sendTime"),
    $shopArea = $("#shop_area"),
    $shopRegion = $('#shop_region'),
    $shopState = $('#shop_state'),
    $shopCity = $('#shop_city'),
    $shopDistrict = $('#shop_district'),
    $shopAddress = $('#shop_address'),
    $prepareTime = $('#prepare_time'),
    $shopLocation = $('#shop_location'),
    $notice = $('#notice'),
    $contact = $('#contact'),
    $waimaiBox = $('.waimai-box'),
    $uploadLogoContainer = $('#upload-logo-container'),
    $btnConfirm = $('#btn-confirm'),
    logoTpl = document.getElementById("logoTpl").innerHTML,
    timeTpl = document.getElementById("timeTpl").innerHTML;

  var citylocation, geocoder, map, marker = null;
  var use_flow = 1,
    auto_receiver = 1,
    auto_printer = 1,
    flow_free_money = '',
    shipping_fee = '',
    longitude = "",
    latitude = "",
    on_time = [],
    goodArr = [],
    service = 0,
    logo = {
      list: [{}]
    },
    timeData = {
      list: []
    };
  
  initMap();

  // 判断添加或编辑
  if (!shopId) {
    initLogo();
    if (!mainShopId) {
      $shopRegion.areapicker({
        provinceField: 'shop_state',
        cityField: 'shop_city',
        areaField: 'shop_district'
      });
    } else {
      getMainShopInfo();
    }
  } else {
    getShopInfo();
  }

  // 初始化地图
  function initMap() {
    var center = new qq.maps.LatLng(39.916527, 116.397128),
      url = 'http://chaxun.1616.net/s.php?type=ip&output=json&callback=?&_=' + Math.random();

    map = new qq.maps.Map(document.getElementById('map-container'), {
      center: center,
      zoom: 15
    });

    marker = new qq.maps.Marker({
      map: map
    });

    // 获取当前ip
    $.getJSON(url, function (data) {
      data.Ip && citylocation.searchCityByIP(data.Ip);
    });

    // 根据ip获取城市信息
    citylocation = new qq.maps.CityService({
      //设置地图
      map: map,
      complete: function (result) {
        map.setCenter(result.detail.latLng);
      }
    });

    //调用地址解析类
    geocoder = new qq.maps.Geocoder({
      complete: function (result) {
        map.setCenter(result.detail.location);
        
        marker.setMap(null);
        marker = new qq.maps.Marker({
          map: map,
          position: result.detail.location
        });

        latitude = result.detail.location.lat;
        longitude = result.detail.location.lng;
        $shopLocation.val(result.detail.location.lat + ',' + result.detail.location.lng);
      }
    });
  }

  // 获取地址信息
  function getLocation() {
    var state = $('#shop_state option:selected').data('name'),
      city = $('#shop_city option:selected').data('name'),
      district = $('#shop_district option:selected').data('name'),
      address = $shopAddress.val(),
      location = state + city + district + address;

    geocoder.getLocation(location);
  }

  // 获取门店信息
  function getShopInfo() {
    $.getJSON(__BASEURL__ + '/mshop/shop_api/info', {
      shop_id: shopId
    }, function (data) {
      if (data.success) {
        var dataInfo = data.data.info;

        use_flow = dataInfo.use_flow;
        auto_receiver = dataInfo.auto_receiver;
        auto_printer = dataInfo.auto_printer;
        shipping_fee = dataInfo.shipping_fee;

        if (shipping_fee) {
          shipping_fee = JSON.parse(shipping_fee);
          $shippingDistance1.val(shipping_fee['0_1000']);
          $shippingDistance2.val(shipping_fee['1000_3000']);
          $shippingDistance3.val(shipping_fee['3000_5000']);
          $shippingDistance4.val(shipping_fee['5000_']);
        }

        service = +dataInfo.type;

        initService();

        // 满免配送费
        if (use_flow == 1) {
          $('[value="满"]').attr("checked", true);
        } else {
          $('[value="满"]').attr("checked", false);
        }

        // 自动接单
        if (auto_receiver == 1) {
          $('[value="自动接单"]').prop("checked", true);
        } else {
          $('[value="自动接单"]').prop("checked", false);
        }

        // 自动打单
        if (auto_printer == 1) {
          $('[value="自动打单"]').prop("checked", true);
        } else {
          $('[value="自动打单"]').prop("checked", false);
        }

        goodArr = dataInfo.shop_imgs.split(",");

        logo.list = [];

        for (var i = 0; i < goodArr.length; i++) {
          logo.list.push({
            pic: goodArr[i]
          });
        }

        if (logo.list.length < 3 && dataInfo.shop_imgs) {
          logo.list.push({
            pic: ""
          });
        }

        initLogo();

        $goodLogo.val(logo.list[0].pic).blur();
        $prepareTime.val(dataInfo.prepare_time);

        latitude = dataInfo.latitude;
        longitude = dataInfo.longitude;

        marker.setPosition(new qq.maps.LatLng(latitude, longitude));

        on_time = dataInfo.on_time;
        var new_time = dataInfo.on_time.split('|');

        for (var j = 0; j < new_time.length; j++) {
          var new_times = new_time[j].split('-');
          timeData.list.push({
            startTime: new_times[0],
            endTime: new_times[1]
          });
        }
        
        timeSelect();
        dataTimePick();
        listeInputTime();

        var regions = dataInfo.region ? dataInfo.region.split('-') : new Array(3),
          province = regions[0] || 0,
          city = regions[1] || 0,
          area = regions[2] || 0;

        var halfpath = dataInfo.shop_logo,
          fullpath = halfpath.indexOf('http') > -1 ? halfpath : __UPLOADURL__ + halfpath;

        $shopRegion.areapicker({
          provinceField: 'shop_state',
          cityField: 'shop_city',
          areaField: 'shop_district',
          province: province,
          city: city,
          area: area
        });

        //更新地图
        var addrlocation = dataInfo.shop_state + dataInfo.shop_city + dataInfo.shop_district + dataInfo.shop_address;
        geocoder.getLocation(addrlocation);

        $shopName.val(dataInfo.shop_name);
        $shopLogo.val(halfpath);
        $shopStartPrice.val((dataInfo.send_money));
        $fullDispatchPrice.val(dataInfo.flow_free_money);
        $shopRadius.val(dataInfo.service_radius);
        $shopSendTime.val(dataInfo.arrive_time);
        $shopArea.val(dataInfo.arrive_range);
        $shopAddress.val(dataInfo.shop_address);
        $notice.val(dataInfo.notice);
        $contact.val(dataInfo.contact);

        $uploadLogoContainer.find('.upload-again').show();
        $uploadLogoContainer.find('.upload-pic').attr('src', fullpath);
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  // 获取主门店信息
  function getMainShopInfo() {
    $.getJSON(__BASEURL__ + 'mshop/shop_api/main_shop_info', {
      main_shop_id: mainShopId
    }, function(res) {
      if (res.success) {
        var shop = res.data.info;

        latitude = shop.latitude;
        longitude = shop.longitude;

        marker.setPosition(new qq.maps.LatLng(latitude, longitude));

        var regions = shop.region ? shop.region.split('-') : new Array(3),
          province = regions[0] || 0,
          city = regions[1] || 0,
          area = regions[2] || 0;

        $shopRegion.areapicker({
          provinceField: 'shop_state',
          cityField: 'shop_city',
          areaField: 'shop_district',
          province: province,
          city: city,
          area: area
        });

        // 处理logo
        var halfpath = shop.shop_logo,
          fullpath = halfpath.indexOf('http') > -1 ? halfpath : __UPLOADURL__ + halfpath;

        // 更新地图
        var addrlocation = shop.shop_state + shop.shop_city + shop.shop_district + shop.shop_address;
        geocoder.getLocation(addrlocation);

        $shopName.val(shop.shop_name);
        $shopLogo.val(halfpath);
        $shopAddress.val(shop.shop_address);
        $contact.val(shop.contact);

        $uploadLogoContainer.find('.upload-again').show();
        $uploadLogoContainer.find('.upload-pic').attr('src', fullpath);
      } else {
        new Msg({
          type: 'danger',
          msg: res.msg
        });
      }
    })
  }

  function initLogo() {
    $("#logoTbody").html(template(logoTpl, logo));
    initUploadBanner();
  }

  function initService() {
    var $waimaiService = $('[name="service"][value="1"]');
    var $mealService = $('[name="service"][value="2"]');
    var $retailService = $('[name="service"][value="4"]');
    
    // 开启外卖
    if ((service & 1) > 0) {
      if ($waimaiService.length > 0) {
        $waimaiService.prop('checked', true)
        $waimaiBox.show();
      } else {
        service = service - 1;
      }
    }

    // 开启堂食
    if ((service & 2) > 0) {
      if ($mealService.length > 0) {
        $mealService.prop('checked', true)
      } else {
        service = service - 2;
      }
    }

    // 开启零售
    if ((service & 4) > 0) {
      if ($retailService.length > 0) {
        $retailService.prop('checked', true)
      } else {
        service = service - 4;
      }
    }
  }

  // 初始化门店图上传
  function initUploadBanner() {
    $(".upload-inputs").each(function (i, e) {
      var $this = $(this);
      var $thisParent = $(this).parent(".m-upload");
      var $thisItem = $(this).parents(".logo-item-good");
      var val = $(this).attr("id");
      var parentVal = $thisParent.attr("id");
      uploadFile("wsc_goods", {
        browse_button: val,
        container: parentVal,
        drop_element: parentVal,
        max_file_size: '1mb',
        chunk_size: '1mb',
        init: {
          FileUploaded: function (up, file, info) {
            var domain = up.getOption("domain");
            var res = JSON.parse(info.response);
            var sourceLink = domain + res.key;
            $thisParent.find(".good-logo").val(res.key).blur();
            $thisParent.addClass("m-upload-good");
            $thisParent.find(".upload-again").show();
            $thisParent.find(".upload-plus").hide();
            $thisParent.find(".upload-pic").attr("src", sourceLink);
            logo.list[i].pic = res.key;
            $goodLogo.val(logo.list[0].pic).blur();
            if ($thisItem.length != 0) {} else {
              if (logo.list.length < 3) {
                logo.list.push({
                  pic: ""
                });
              } else {}
            }
            initLogo();
          }
        }
      });
    });
  }

  function delLogo(el, i) {
    $goodLogo.val(logo.list[0].pic).blur();
    if (logo.list.length == 3) {
      if (logo.list[2].pic != "") {
        logo.list.splice(i, 1);
        logo.list.push({
          pic: ""
        });
      } else {
        logo.list.splice(i, 1);
      }
    } else {
      logo.list.splice(i, 1);
    }
    initLogo();
  }

  $shopAddress.on('blur', function () {
    getLocation();
  });

  // 上传门店logo
  function uploadShopImg() {
    uploadFile('main_header', {
      browse_button: 'upload-logo',
      container: 'upload-logo-container',
      drop_element: 'upload-logo-container',
      max_file_size: '1mb',
      chunk_size: '1mb',
      init: {
        'FileUploaded': function (up, file, info) {
          var res = JSON.parse(info.response);
          var halfpath = res.key;
          var fullpath = up.getOption('domain') + halfpath;

          $shopLogo.val(halfpath).blur();
          $uploadLogoContainer.find('.upload-again').show();
          $uploadLogoContainer.find('.upload-plus').hide();
          $uploadLogoContainer.find('.upload-pic').attr('src', fullpath);
        }
      }
    });
  }

  uploadShopImg();

  function timeSelect() {
    $("#timeList").html(template(timeTpl, timeData));
  }

  timeSelect();

  // 时间选择
  function dataTimePick() {
    for (var i = 0; i < timeData.list.length; i++) {
      $('#datetimeStart' + i).datetimepicker({
        datepicker: false,
        format: 'H:i',
        step: 5
      });
      $('#datetimeEnd' + i).datetimepicker({
        datepicker: false,
        format: 'H:i',
        step: 5
      });
      timeData.list[i].startTime = $('#datetimeStart' + i).val();
      timeData.list[i].endTime = $('#datetimeEnd' + i).val();
    }
  }

  dataTimePick();

  function addTime() {
    var new_time = {
      startTime: "23:59",
      endTime: "06:00"
    };
    var $timeView = $(".time-view");
    if ($timeView.length >= 3) {
      new Msg({
        type: "danger",
        msg: "最多只能添加3个时间段"
      });
      return false;
    } else {
      timeData.list.push(new_time);
      timeSelect();
      dataTimePick();
      listeInputTime();
    }
  }

  function delTime(index) {
    for (var i = 0; i < timeData.list.length; i++) {
      if (index == i) {
        timeData.list.splice(i, 1);
      }
    }
    timeSelect();
    dataTimePick();
    listeInputTime();
  }

  function listeInputTime() {
    for (var i = 0; i < timeData.list.length; i++) {
      $('#datetimeStart' + i).blur(function () {});
      $('#datetimeEnd' + i).blur(function () {});
      $('#datetimeStart0').blur(function () {
        timeData.list[0].startTime = $('#datetimeStart0').val();
      });
      $('#datetimeEnd0').blur(function () {
        timeData.list[0].endTime = $('#datetimeEnd0').val();
      });
      $('#datetimeStart1').blur(function () {
        timeData.list[1].startTime = $('#datetimeStart1').val();
      });
      $('#datetimeEnd1').blur(function () {
        timeData.list[1].endTime = $('#datetimeEnd1').val();
      });
      $('#datetimeStart2').blur(function () {
        timeData.list[2].startTime = $('#datetimeStart2').val();
      });
      $('#datetimeEnd2').blur(function () {
        timeData.list[2].endTime = $('#datetimeEnd2').val();
      });
    }
  }

  listeInputTime();

  // 修改自动接单状态
  function changeStatus(el) {
    if ($(el).is(":checked")) {
      auto_receiver = 1;
    } else {

      auto_receiver = 0;
    }
  }
  //修改自动打单状态改变开启还是关闭
  function isMakeOrder(el) {
    if ($(el).is(":checked")) {
      auto_printer = 1;
    } else {
      auto_printer = 0;
    }
  }

  // 修改门店服务
  function changeService(){
    var $service = $('[name="service"]:checked');

    service = 0;

    $service.each(function(index, ele){
      service += Number(ele.value);
    })
    
    // 开启外卖
    if ((service & 1) > 0) {
      $waimaiBox.show();
    } else {
      $waimaiBox.hide();
    }
  }

  // 提交门店信息
  $('#shop-form')
    .bootstrapValidator({
      fields: {
        shop_name: {
          validators: {
            notEmpty: {
              message: '门店名称不能为空'
            },
            stringLength: {
              max: 30,
              message: '门店名称不得超过30个字符'
            }
          }
        },
        shop_logo: {
          validators: {
            notEmpty: {
              message: '请上传门店logo'
            }
          }
        },
        good_logo: {
          validators: {
            notEmpty: {
              message: '请上传门店图片'
            }
          }
        },
        shop_startPrice: {
          validators: {
            notEmpty: {
              message: '起送价不能为空'
            }
          }
        },
        shipping_distance1: {
          validators: {
            notEmpty: {
              message: '配送费不能为空'
            }
          }
        },
        shipping_distance2: {
          validators: {
            notEmpty: {
              message: '配送费不能为空'
            }
          }
        },
        shipping_distance3: {
          validators: {
            notEmpty: {
              message: '配送费不能为空'
            }
          }
        },
        shipping_distance4: {
          validators: {
            notEmpty: {
              message: '配送费不能为空'
            }
          }
        },
        shop_radius: {
          validators: {
            notEmpty: {
              message: '服务半径不能为空'
            }
          }
        },
        shop_sendTime: {
          validators: {
            notEmpty: {
              message: '预计送达时间不能为空'
            }
          }
        },
        shop_state: {
          validators: {
            notEmpty: {
              message: '选择省份'
            }
          }
        },
        shop_city: {
          validators: {
            notEmpty: {
              message: '选择城市'
            }
          }
        },
        shop_district: {
          validators: {
            notEmpty: {
              message: '选择地区'
            }
          }
        },
        shop_address: {
          validators: {
            notEmpty: {
              message: '详细地址不能为空'
            }
          }
        },
        shop_location: {
          validators: {
            notEmpty: {
              message: '经纬度不能为空，请先完善门店地址'
            }
          }
        },
        notice: {
          validators: {
            notEmpty: {
              message: '门店公告不能为空'
            },
            stringLength: {
              max: 140,
              message: '门店公告不得超过140个字符'
            }
          }
        },
        contact: {
          validators: {
            notEmpty: {
              message: '联系电话不能为空'
            },
            phone: {
              country: 'CN',
              message: '联系电话格式不正确'
            }
          }
        },
        send_type: {
          validators: {
            notEmpty: {
              message: '配送方式不能为空'
            }
          }
        }
      }
    })
    .on('success.form.bv', function (e) {
      // 阻止表单默认提交
      e.preventDefault();

      var shop_name = $shopName.val(),
        shop_logo = $shopLogo.val(),
        good_logo = $goodLogo.val(),
        contact = $contact.val(),
        shippingDistance1 = $shippingDistance1.val(),
        shippingDistance2 = $shippingDistance2.val(),
        shippingDistance3 = $shippingDistance3.val(),
        shippingDistance4 = $shippingDistance4.val(),
        shipping_fee,
        shopStartPrice = $shopStartPrice.val(),
        fullDispatchPrice = $fullDispatchPrice.val(),
        shopRadius = $shopRadius.val(),
        prepareTime = $prepareTime.val() || 0,
        shopSendTime = $shopSendTime.val(),
        shopArea = $shopArea.val(),
        shop_state = $shopState.find(':selected').data('name'),
        state = $shopState.find(':selected').data('code'),
        shop_city = $shopCity.find(':selected').data('name'),
        city = $shopCity.find(':selected').data('code'),
        shop_district = $shopDistrict.find(':selected').data('name'),
        district = $shopDistrict.find(':selected').data('code'),
        shop_address = $shopAddress.val(),
        notice = $notice.val(),
        region,
        post_url,
        post_data;

      switch (fullDispatchPrice) {
        case "":
          fullDispatchPrice = "";
          break;
        case "0":
          fullDispatchPrice = "";
          break;
        case "0.00":
          fullDispatchPrice = "";
          break;
      }

      region = new Array(state, city, district).join('-');

      if ($('[value="满"]').is(':checked')) {
        use_flow = 1;
        flow_free_money = fullDispatchPrice;
      } else {
        use_flow = 0;
        flow_free_money = 0;
      }

      if ($('[value="自动接单"]').is(':checked')) {
        auto_receiver = 1;
      } else {
        auto_receiver = 0;
      }

      if ($('[value="自动打单"]').is(':checked')) {
        auto_printer = 1;
      } else {
        auto_printer = 0;
      }

      goodArr.splice(0, goodArr.length);

      for (var i = 0; i < logo.list.length; i++) {
        if(logo.list[i].pic){
          goodArr.push(logo.list[i].pic);
        }
      }

      shipping_fee = {
        '0_1000': shippingDistance1,
        '1000_3000': shippingDistance2,
        '3000_5000': shippingDistance3,
        '5000_': shippingDistance4,
      };

      if ((service & 1) <= 0 && (service & 2) <= 0 && (service & 4) <= 0) {
        new Msg({
          type: "danger",
          msg: "请选择门店服务"
        });

        return false;
      }

      if (service & 1 > 0) {
        if (shopStartPrice < 0) {
          new Msg({
            type: "danger",
            msg: "起送价要大于等于0"
          });

          return false;
        }

        if (shopRadius <= 0) {
          new Msg({
            type: "danger",
            msg: "服务半径要大于0"
          });

          return false;
        }
        if ($('[value="满"]').is(':checked') && fullDispatchPrice == "") {
          new Msg({
            type: "danger",
            msg: "请填写免配送费"
          });

          return false;
        }
        if (good_logo == "") {
          new Msg({
            type: "danger",
            msg: "请上传商品主图"
          });  

          return false;
        }
      }

      on_time = [];

      for (var i = 0; i < timeData.list.length; i++) {
        on_time.push(timeData.list[i].startTime + '-' + timeData.list[i].endTime)
      }

      post_data = {
        shop_name: shop_name,
        shop_logo: shop_logo,
        contact: contact,
        send_money: shopStartPrice,
        shipping_fee: JSON.stringify(shipping_fee),
        use_flow: use_flow,
        flow_free_money: flow_free_money,
        service_radius: shopRadius,
        arrive_time: shopSendTime,
        arrive_range: shopArea,
        prepare_time: prepareTime,
        shop_imgs: goodArr.join(","),
        notice: notice,
        longitude: longitude,
        latitude: latitude,
        shop_state: shop_state,
        shop_city: shop_city,
        shop_district: shop_district,
        shop_address: shop_address,
        region: region,
        on_time: on_time.join('|'),
        auto_receiver: auto_receiver,
        auto_printer: auto_printer,
        type: service
      };

      // 判断是添加或编辑
      if (!shopId) {
        mainShopId && (post_data.main_shop_id = mainShopId);
        post_url = __BASEURL__ + 'mshop/shop_api/add';
      } else {
        post_data.shop_id = shopId;
        post_url = __BASEURL__ + 'mshop/shop_api/edit/' + shopId;
      }

      $btnConfirm.prop('disabled', true);

      $.post(post_url, autoCsrf(post_data), function (data) {
        if (data.success) {
          new Msg({
            type: "success",
            msg: data.msg,
            delay: 1,
            callback: function () {
              if (isAdmin == '1') {
                window.location.href = __BASEURL__ + "mshop/shop";
              } else {
                window.location.href = __BASEURL__ + "mshop/shop/info/" + shopId;
              }
            }
          });
        } else {
          new Msg({
            type: 'danger',
            msg: data.msg
          });
        }

        $btnConfirm.prop('disabled', false);
      });
    });

  //返回
  function Return() {
    if (isAdmin == '1') {
      window.location.href = __BASEURL__ + "mshop/shop";
    } else {
      window.location.href = __BASEURL__ + "mshop/shop/info/" + shopId;
    }
  }

  window.delLogo = delLogo;
  window.addTime = addTime;
  window.delTime = delTime;
  window.Return = Return;
  window.isMakeOrder = isMakeOrder;
  window.changeStatus = changeStatus;
  window.changeService = changeService;
});
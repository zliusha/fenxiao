$(function(){
	var shopTpl = document.getElementById("shopTpl").innerHTML;
  var cart = new Cart();
	var shop = {
		list: []
	};
    var poi = JSON.parse(localStorage.getItem("poi")),
    geolocation = new qq.maps.Geolocation(
      "GMSBZ-F6VK6-7H6ST-MRTQ2-46L26-SJFD3",
      "myapp"
    ),
    poiNot = {
      lat: "",
      lng: ''
    };

  var url = window.location.href;
  var is_poi = false;  

  function getStr(string,str){
    var str_before = string.split(str)[0];
    var str_after = string.split(str)[1];
    is_poi = str_after;
  }

  getStr(url,'?is_poi=')

  function showPosition(position) {
    poi = {
      poiname: "",
      poiaddress: "",
      lat: "",
      lng: ""
    };
    console.info(position)
    poi.poiname = position.nation + position.province + position.city;
    poi.poiaddress = position.nation + position.province + position.city;
    poi.lat = position.lat;
    poi.lng = position.lng;
    localStorage.setItem("poi", JSON.stringify(poi));
    $("#poi-name").val(position.nation + position.province + position.city);
    if(position){
      initShop();
    }else{
      getShop();
    }
  }
  if(!is_poi){
    geolocation.getLocation(showPosition);
  }else{
    if(!poi){
      geolocation.getLocation(showPosition);
    }else{
      initShop();
      $("#poi-name").val(poi.poiname);
    }
  }
	function initShop() {
    $.getJSON(__BASEURL__ + "api/shop/get_all", {}, function (data) {
      if (data.success) {
        shop.list = data.data;
        init(shop);
      }
    });
	}

  function initShipping(){
    $.getJSON(__BASEURL__ + "/api/shop/shipping_method", {
         
    }, function (data) {
      if (data.success){
        $('.shipping').each(function(){
          $(this).html(data.data.shipping_method)
        })
      }
    }); 
  }

	function getShop(){
    $.getJSON(__BASEURL__ + "api/shop/get_all", {}, function (data) {
      if (data.success) {
        shop.list = data.data;
	       $("#shop-list").html(template(shopTpl, shop));
        initShipping();
        $('.m-shop-distance').each(function(){
          $(this).html('');
        })
      }
    });
	}

  function init(shop) {
    var a = new qq.maps.LatLng(poi.lat, poi.lng);
    for (var i = 0; i < shop.list.length; i++) {
      var id = "distance" + i;
      var b = new qq.maps.LatLng(shop.list[i].latitude, shop.list[i].longitude);
      var distance = (qq.maps.geometry.spherical.computeDistanceBetween(a, b) /
        1000).toFixed(2);
      shop.list[i].distance = distance;
      if(Number(distance)>Number(shop.list[i].service_radius)){
        shop.list[i].is_notdis = true;
      }
    }
    shop.list = shop.list.sort(compare("distance"));
    $("#shop-list").html(template(shopTpl, shop));
    initShipping();
	}


	function compare(property) {
    return function (a, b) {
      var value1 = a[property];
      var value2 = b[property];
      return value1 - value2;
    };
	}

  cart.clearCart();


})
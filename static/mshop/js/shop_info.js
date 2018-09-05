/**
 * shop_info.js
 * by jinmu
 * date: 2017-10-29
 */

$(function(){
  var shop_id = $("#shop_id").val(),
    shopInfoTpl = document.getElementById("shopInfoTpl").innerHTML,
    activityTpl=document.getElementById("activityTpl").innerHTML;

  getShopInfo();


  function getShopInfo() {
    $.getJSON(__BASEURL__ + "api/shop/info", {
      shop_id: shop_id
    }, function (data) {
      if (data.success) {
        var shopData={
          list: []
        };
        shopData.list.push(data.data);

        //店铺图片
        var shopImgArr = data.data.shop_imgs.split(",");
        var shopArr=[];
        for(var i=0;i<shopImgArr.length;i++){
          if(shopImgArr[i]!=""){
            shopArr.push( __UPLOADURL__+shopImgArr[i])
          }
        }
        shopData.list[0].shopImgArr=shopArr;

        //营业时间
        var timeData={list:[]};
        var NewTime=data.data.on_time.split('|');
        on_time = data.data.on_time;
        for (var i = 0; i < NewTime.length; i++) {
          var times = {};
            times.startTime = NewTime[i].substring(0, 5);
            times.endTime = NewTime[i].substring(6, 12);
          timeData.list.push(times);
        }
        shopData.list[0].timeData=timeData;
        
        $("#shopInfo").html(template(shopInfoTpl,  shopData));
        $( ".m-shop-background" ).css(
          {
            'background': 'url(' + data.data.shop_logo + ') no-repeat center',
            'background-size': '100%'
          }
        );

        getActivityInfo();
      }
    });
  }

  function getActivityInfo() {
    $.getJSON(__BASEURL__ + "api/shop/promotion_info", {
      shop_id: shop_id
    }, function (data) {
      if (data.success) {
        if(data.data!=null){
          var setting=data.data.setting;
          if(setting.length>0){
           /* $(".activity_view").show();
            var html="";
            for(var i=0;i<setting.length;i++){
              html+= '<span>满'+setting[i].price+'减'+setting[i].red_price+', </span>';
            }
            $("#activitySpan").append(html);*/
            $("#activity_view").html(template(activityTpl,  {list:setting}));
            var lastSpan=$("#activitySpan").find("span").last();
            if(lastSpan.length>0){
              var $val=lastSpan.html();
              var newVal= $val.substring(0,$val.length-2);
              lastSpan.html(newVal);
            }
          }
        }
      }
    });
  }


});
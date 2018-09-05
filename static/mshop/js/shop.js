
  var shop_id = $('#shop_id').val();
  var score_type = '';
  var status = $('#status').val();
  var on_time = $('#on_time').val();
  var timeArr = {
    list:[]
  }
  var cart = new Cart();
  var cartData = cart.getCart();
  var send_money = $('#send_money').val();
  var is_newbie_coupon = $('#is_newbie_coupon').val();
  var use_flow = $('#use_flow').val();
  var flow_free_money = $('#flow_free_money').val();
  var shopInfoTpl = document.getElementById("shopInfoTpl").innerHTML;
  var activityTpl=document.getElementById("activityTpl").innerHTML;
  var poi = {
          poiname: "",
          poiaddress: "",
          lat: "",
          lng: ""
        };
  var geolocation = new qq.maps.Geolocation(
      "GMSBZ-F6VK6-7H6ST-MRTQ2-46L26-SJFD3",
      "myapp"
    );

  var app = new Vue({
    el: "#app",
    data: {
      goods: [],
      comment:[],
      decorate:[],
      decorate_title:'',
      poster:[],
      listHeight: [],
      activity:'',
      foodsScrollY: 0,
      selectedFood: [],
      sku:'',
      recart:'',
      status:'',
      setting_price:'',
      setting_red_price:'',
      is_setting_show:false,
      is_food:true,
      s_height:'',
      cur_page : 1,
      page_size : 10,
      is_loading : false,
      is_has_data : true,
      is_notdis:'',
      is_scroll:true,
      is_poster_show:false,
      is_good_show:false,
      is_decorate_show:false,
      shipping:'达达配送',
      freight_money:''
    },
    created: function() {
      var _this = this;
      this.initGood();
      this.initComment();
      this.initDecorate();
      this.initShopMethod();
      this.getShopInfo();
      _this.status = status;
      var is_open = true;
      console.info(on_time)
      if(on_time!=null){
        var timeArr = on_time.split("|");
        var time_range = function (beginTime, endTime) {
          var strb = beginTime.split (":");
          if (strb.length != 2) {
            return false;
          }
         
          var stre = endTime.split (":");
          if (stre.length != 2) {
            return false;
          }
         
          var b = new Date ();
          var e = new Date ();
          var n = new Date ();
         
          b.setHours (strb[0]);
          b.setMinutes (strb[1]);
          e.setHours (stre[0]);
          e.setMinutes (stre[1]);
          if (n.getTime () - b.getTime () > 0 && n.getTime () - e.getTime () < 0) {
            _this.status = 0;
            is_open = false;
          } else {
            _this.status = 2;
          }
        }
        if(status==0){
          for(var i=0;i<timeArr.length;i++){
            if(is_open){
              var beginTime = timeArr[i].substring(0, 5),
                endTime = timeArr[i].substring(6, 12),
                beginHour = timeArr[i].substring(0, 2),
                endHour = timeArr[i].substring(6, 8),
                beginMinute = timeArr[i].substring(3, 5),
                endMinute = timeArr[i].substring(9, 12);

              var begin = Number(beginHour+'.'+beginMinute).toFixed(2);
              var end = Number(endHour+'.'+endMinute).toFixed(2);
              if(begin-end>0){
                time_range(beginTime,'23:59');
                time_range('00:00',endTime);
              }else{
                time_range(beginTime,endTime);
              }
            }
          }
        }
      }

      var height = $(window).height();
      var url = window.location.href;
      if(url.indexOf('is_notdis')>=0){
        if(_this.status!=2){
          _this.status = 2;
          _this.is_notdis = true;
        }
      }
      this.recart = cartData;
      this.$nextTick(function() {
              
      });

      _this.is_food = true;
      var food = _this.recart.goods;
      for(var b=0;b<food.length;b++){
        if(food[b].dec_price){
          _this.is_food = false;
        }
      }


      setTimeout(function(){
        var ac_number = $('.m-shop-activity-item').find('p').length;
        $('#activity-number').html(ac_number);
        _this.checkSetting(_this);
      },2000)

      var top2 = $('.m-shop-nav-box').offset().top;
      // 滚动监听
      $(window).on("scroll", function () {
        var scrolltop = $(window).scrollTop();
        var is_menu = true;
        $('.food-list').each(function(i){
          if(i!=$('.food-list').length-1){
            var i2 = i+1;
            var div = '.food-list'+i;
            var div2 = '.food-list'+i2;
            var food_top = $(div).offset().top-$('.m-shop-nav-box').height();
            var food_top2 = $(div2).offset().top-$('.m-shop-nav-box').height();
            if(_this.is_scroll && is_menu){
              if(scrolltop>food_top && scrolltop<=food_top2){
                var menu = '.menu-item'+i;
                $(menu).addClass('menu-item-selected');
                $(menu).siblings().removeClass('menu-item-selected');
                is_menu = false;
              }
            }
          }else{
            var div = '.food-list'+i;
            var food_top = $(div).offset().top-$('.m-shop-nav-box').height();
            if(_this.is_scroll && is_menu){
              if(scrolltop>food_top){
                var menu = '.menu-item'+i;
                $(menu).addClass('menu-item-selected');
                $(menu).siblings().removeClass('menu-item-selected');
                is_menu = false;
              }
            }
          }
        })
        var top = $('.goods-box').offset().top;
        if(scrolltop>top){
          $('.menu-wrapper').addClass('active');
          $('.goods-right-box').addClass('active');
        }else{
          $('.menu-wrapper').removeClass('active');
          $('.goods-right-box').removeClass('active');
        }
        var load_top = $("#load-more").offset().top;
        if(!$(".comments-box").is(":hidden")){
          if(scrolltop>_this.s_height){
            $('.m-shop-nav').addClass('active');
          }else{
            $('.m-shop-nav').removeClass('active');
          }
          if (scrolltop + height > load_top && _this.is_has_data) {
            _this.initComment()
          }
        }
        if(!$(".goods-box").is(":hidden")){
          if(scrolltop>top2){
            $('.m-shop-nav').addClass('active');
          }else{
            $('.m-shop-nav').removeClass('active');
          }
        }
      });
    },
    computed: {
      menuCurrentIndex: function() {
        for (var i = 0, l = this.listHeight.length; i < l; i++) {
          var topHeight = this.listHeight[i];
          var bottomHeight = this.listHeight[i + 1];
          if (!bottomHeight ||
            (this.foodsScrollY >= topHeight && this.foodsScrollY < bottomHeight)
          ) {
            return i;
          }
        }
        return 0;
      },
      selectFoods: function() {
        var goods_list = [];
        this.goods.forEach(function(good) {
          good.goods_list.forEach(function(food) {
            if (food) {
              goods_list.push(food);
            }
          });
        });
        return goods_list;
      }
    },
    methods: {
      getShopInfo:function() {
        var _this = this;
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
            $('#shipping').html(_this.shipping)
            _this.getActivityInfo();
            geolocation.getLocation(_this.showPosition);
            _this.getDistance(data.data)
          }
        });
      },
      getDistance:function(shop){
        var poi = JSON.parse(localStorage.getItem("poi"));
        var a = new qq.maps.LatLng(poi.lat, poi.lng);
        var b = new qq.maps.LatLng(shop.latitude, shop.longitude);
        var distance = (qq.maps.geometry.spherical.computeDistanceBetween(a, b) /
        1000).toFixed(2);
        $('#distance').html(distance);
        if(shop.shipping_fee){
          var shipping_fee = JSON.parse(shop.shipping_fee);
          var key = '';
          if(distance>=0 && distance<=1){
            key = '0_1000';
            this.freight_money = shipping_fee[key];
          }else if(distance>1 && distance<=3){
            key = '1000_3000';
            this.freight_money = shipping_fee[key];
          }else if(distance>3 && distance<=5){
            key = '3000_5000';
            this.freight_money = shipping_fee[key];
          }else{
            key = '5000_';
            this.freight_money = shipping_fee[key];
          }
          this.freight_money = parseFloat(this.freight_money).toFixed(2);
        }else{
          this.freight_money = $('#freight_money').val();
        }
        $('#freightMoney').html(this.freight_money);
      },
      showPosition:function(position) {
        poi.poiname = position.nation + position.province + position.city;
        poi.poiaddress = position.nation + position.province + position.city;
        poi.lat = position.lat;
        poi.lng = position.lng;
        localStorage.setItem("poi", JSON.stringify(poi));
      },
      getActivityInfo:function() {
        var _this =this;
        var resactivity = '';
        $.getJSON(__BASEURL__ + "api/shop/promotion_info", {
          shop_id: shop_id
        }, function (data) {
          if (data.success) {
            if(data.data!=null){
              resactivity = data.data;
              _this.activity = resactivity;
              var setting=data.data.setting;
              if(setting.length>0){
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
      },
      initGood: function() {
        var SHOPID = shop_id;
        sessionStorage.setItem('SHOPID', JSON.stringify(SHOPID));
        var _this = this;
        $.getJSON(__BASEURL__ + "api/items/cate_goods_list", {
          shop_id:shop_id
        }, function (data) {
          if (data.success) {
            _this.is_good_show = true;
            if(_this.is_decorate_show){
              $('#loading-box').hide();
            }
            _this.goods = data.data;
            var cartGoods = cart.getCart().goods;
            for (var i = 0; i < _this.goods.length; i++) {
              _this.goodsNumber(_this.goods[i])
            }
            for (var i = 0; i < _this.goods.length; i++) {
              for (var j = 0; j < _this.goods[i].goods_list.length; j++) {
                if(_this.goods[i].goods_list[j].sku_type=='1'){
                    var n =0;
                    for (var g = 0; g < cartGoods.length; g++) {
                      if(cartGoods[g].id==_this.goods[i].goods_list[j].id){
                        n += cartGoods[g].amount;
                      }
                    }
                    _this.goods[i].goods_list[j].amount = n;
                }else{
                    for (var g = 0; g < cartGoods.length; g++) {
                      if(cartGoods[g].sku_id==_this.goods[i].goods_list[j].sku_list[0].id){
                        _this.goods[i].goods_list[j].amount = cartGoods[g].amount;
                      }
                    }
                }
              }
              _this.goodsNumber(_this.goods[i])
            }
            if(_this.goods.length==0){
              $('#empty').show();
              $('.menu-wrapper').hide();
              $('.goods-wrapper').hide();
            }
          }
        });
      },
      initShopMethod:function(){
        _this = this;
        $.getJSON(__BASEURL__ + "/api/shop/shipping_method", {
         
        }, function (data) {
          if (data.success){
            _this.shipping = data.data.shipping_method;
          }
        }); 
      },
      initDecorate:function(){
        var _this = this;
        $.getJSON(__BASEURL__ + "api/decorate/get_modules_data", {
          shop_id:shop_id
        }, function (data) {
          if (data.success) {
            _this.is_decorate_show = true;
            if(_this.is_good_show){
              $('#loading-box').hide();
            }
            var cartGoods = cart.getCart().goods;
            if(data.data.tj_goods_modules!=''){
              _this.decorate = data.data.tj_goods_modules[0];
              _this.decorate_title = data.data.tj_goods_modules[0].module_data.title;
              for(var k=0;k<_this.decorate.sys_data.length;k++){
                if(_this.decorate.sys_data[k].sku_type=='1'){
                    var n =0;
                    for (var g = 0; g < cartGoods.length; g++) {
                      if(cartGoods[g].id==_this.decorate.sys_data[k].id){
                        n += cartGoods[g].amount;
                      }
                    }
                    _this.decorate.sys_data[k].amount = n;
                }else{
                  for (var g = 0; g < cartGoods.length; g++) {
                    if(cartGoods[g].sku_id==_this.decorate.sys_data[k].sku_list[0].id){
                      _this.decorate.sys_data[k].amount = cartGoods[g].amount;
                    }
                  }
                }
              }
            }
            if(data.data.poster_modules!=''){
              _this.poster = data.data.poster_modules;
              
            }
          }
        });
      },
      initComment: function(){
        var _this = this;
        if (_this.is_loading) {
          return false;
        }

        _this.is_loading = true;
        $.getJSON(__BASEURL__+'api/comment/comment_list',{
          shop_id:shop_id,
          score_type:score_type,
          current_page: _this.cur_page,
          page_size: _this.page_size
        },function(data){
          if(data.success){
            for(var a=0;a<data.data.rows.length;a++){
              if(data.data.rows[a].comments[0].picarr!=''){
                data.data.rows[a].picarr = data.data.rows[a].comments[0].picarr.split(",");
                for(var k=0;k<data.data.rows[a].picarr.length;k++){
                  if(data.data.rows[a].picarr[k]!=''){
                    data.data.rows[a].picarr[k] = __UPLOADURL__+data.data.rows[a].picarr[k];
                  }else{
                    data.data.rows[a].picarr[k] = [];
                  }
                }
              }
              if(data.data.rows[a].comments[0].tags!=''){
                data.data.rows[a].comments[0].tags = data.data.rows[a].comments[0].tags.split(",");
                for(var k=0;k<data.data.rows[a].comments[0].tags.length;k++){
                  if(data.data.rows[a].comments[0].tags[k]!=''){
                    data.data.rows[a].comments[0].tags[k] = data.data.rows[a].comments[0].tags[k];
                  }else{
                    data.data.rows[a].comments[0].tags[k] = [];
                  }
                }
              }
              for(var b = 1;b<data.data.rows[a].comments.length;b++){
                if(data.data.rows[a].comments[b].tags!=''){
                  data.data.rows[a].comments[b].tags = data.data.rows[a].comments[b].tags.split(",");
                  for(var k=0;k<data.data.rows[a].comments[b].tags.length;k++){
                    if(data.data.rows[a].comments[b].tags[k]!=''){
                      data.data.rows[a].comments[b].tags[k] = data.data.rows[a].comments[b].tags[k];
                    }else{
                      data.data.rows[a].comments[b].tags[k] = [];
                    }
                  }
                }
              }
            }
            for(var j=0;j<data.data.rows.length;j++){
              _this.comment.push(data.data.rows[j])
            }
            var num = _this.comment.length;
            var l = data.data.rows.length;
            if(num==0){
              $("#load-more").hide();
            }else{
              if (l < _this.page_size) {
                $("#load-more").text("没有更多了");
                _this.is_has_data = false;
              } else {
                _this.is_has_data = true;
              }
            }
            for(var i=0;i<_this.comment.length;i++){
              var n = 0;
              for(var j=0;j<_this.comment[i].comments.length;j++){
                if(_this.comment[i].comments[j].type==='0'){
                  if(Number(_this.comment[i].comments[j].score)>n){
                    n = _this.comment[i].comments[j].score;
                    _this.comment[i].score = n;
                  }
                }
              }
            }
            _this.cur_page++;
          }else{
            layer.open({
              content: data.msg,
              skin: "msg",
              time: 1
            });
          }
          _this.is_loading = false;
        })
      },
      _initScroll: function() {
        var _this = this;
        this.menuWrapper = new BScroll(this.$refs.menuWrapper, {
          click: true
        });
        this.foodsScroll = new BScroll(this.$refs.foodsWrapper, {
          click: true,
          probeType: 3
        });

        this.foodsScroll.on("scroll", function(pos) {
          _this.foodsScrollY = Math.abs(Math.round(pos.y));
        });
      },
      _calculateHeight: function() {
        var foodList = this.$refs.foodsWrapper.querySelectorAll(
          ".food-list-hook"
        );
        var height = 0;
        this.listHeight.push(height);
        for (var i = 0, l = foodList.length; i < l; i++) {
          var item = foodList[i];
          height += item.clientHeight;
          this.listHeight.push(height);
        }
      },
      showMore: function(event){
        var _this =this;
        el = event.currentTarget;
        if($(el).hasClass('active')){
          $(el).removeClass('active');
        }else{
          $(el).addClass('active');
        }    
        $('#m-shop-activity-more').slideToggle();

      },
      commentType: function(event,val){
        var _this = this;
        _this.cur_page = 1,
        _this.page_size = 10,
        _this.is_loading = false,
        _this.is_has_data = true,
        height = $(window).height();
        _this.comment = [];
        el = event.currentTarget;
        $(el).addClass('active');
        $(el).siblings().removeClass('active');
        score_type = val;
        $('#load-more').show();
        $('#load-more').html('加载中');
        function initComment(){
          if (_this.is_loading) {
            return false;
          }
          _this.is_loading = true;
          $.getJSON(__BASEURL__+'api/comment/comment_list',{
            shop_id:shop_id,
            score_type:score_type,
            current_page: _this.cur_page,
            page_size: _this.page_size
          },function(data){
            if(data.success){
            for(var a=0;a<data.data.rows.length;a++){
              if(data.data.rows[a].comments[0].picarr!=''){
                data.data.rows[a].picarr = data.data.rows[a].comments[0].picarr.split(",");
                for(var k=0;k<data.data.rows[a].picarr.length;k++){
                  if(data.data.rows[a].picarr[k]!=''){
                    data.data.rows[a].picarr[k] = __UPLOADURL__+data.data.rows[a].picarr[k];
                  }else{
                    data.data.rows[a].picarr[k] = [];
                  }
                }
              }
              if(data.data.rows[a].comments[0].tags!=''){
                data.data.rows[a].comments[0].tags = data.data.rows[a].comments[0].tags.split(",");
                for(var k=0;k<data.data.rows[a].comments[0].tags.length;k++){
                  if(data.data.rows[a].comments[0].tags[k]!=''){
                    data.data.rows[a].comments[0].tags[k] = data.data.rows[a].comments[0].tags[k];
                  }else{
                    data.data.rows[a].comments[0].tags[k] = [];
                  }
                }
              }
              for(var b = 1;b<data.data.rows[a].comments.length;b++){
                if(data.data.rows[a].comments[b].tags!=''){
                  data.data.rows[a].comments[b].tags = data.data.rows[a].comments[b].tags.split(",");
                  for(var k=0;k<data.data.rows[a].comments[b].tags.length;k++){
                    if(data.data.rows[a].comments[b].tags[k]!=''){
                      data.data.rows[a].comments[b].tags[k] = data.data.rows[a].comments[b].tags[k];
                    }else{
                      data.data.rows[a].comments[b].tags[k] = [];
                    }
                  }
                }
              }
            }
              for(var j=0;j<data.data.rows.length;j++){
                _this.comment.push(data.data.rows[j])
              }
              var num = _this.comment.length;
              var l = data.data.rows.length;
              if(num==0){
                $("#load-more").hide();
              }else{
                if (l < _this.page_size) {
                  $("#load-more").text("没有更多了");
                  _this.is_has_data = false;
                } else {
                  _this.is_has_data = true;
                }
              }
              for(var i=0;i<_this.comment.length;i++){
                var n = 0;
                for(var j=0;j<_this.comment[i].comments.length;j++){
                  if(_this.comment[i].comments[j].type==='0'){
                    if(Number(_this.comment[i].comments[j].score)>n){
                      n = _this.comment[i].comments[j].score;
                      _this.comment[i].score = n;
                    }
                  }
                }
              }
              _this.cur_page++;
            }else{
              layer.open({
                content: data.msg,
                skin: "msg",
                time: 1
              });
            }
            _this.is_loading = false;
          })
        }
        initComment();
      },
      changeType: function(event){
        el = event.currentTarget;
        var id = $(el).attr('data-id');
        $(el).addClass('z-active');
        $(el).siblings().removeClass('z-active');
        if(id==1){
          $('.goods-box').show();
          console.info(1)
          $('body,html').animate({scrollTop:0},1);
          $('.m-shop-nav').removeClass('active');
          $('.comments-box').hide();
          $('.shop-info-box').hide();
          $('.g-footer').show();
          $('body').addClass('has-footer');
          $('.m-comments-label-list').hide();
          $('.recommend-good').show();
        }else if(id==2){
          $('.goods-box').hide();
          $('.comments-box').show();
          $('.shop-info-box').hide();
          $('.recommend-good').hide();
          $('.g-footer').hide();
          $('body').removeClass('has-footer');
          $('.m-comments-label-list').show();
        }else{
          $('.goods-box').hide();
          $('.comments-box').hide();
          $('.shop-info-box').show();
          $('.recommend-good').hide();
          $('.m-shop-nav').removeClass('active');
          $('.g-footer').hide();
          $('body').removeClass('has-footer');
          $('.m-comments-label-list').hide();
        }
      },
      menuClick: function(index, event) {
        var _this = this;
        el = event.currentTarget;
        $(el).addClass('menu-item-selected');
        $(el).siblings().removeClass('menu-item-selected');
        var div = '.food-list'+index;
        console.info(div)
        var top = $(div).offset().top-$('.m-shop-nav-box').height();
        $('body,html').animate({scrollTop:top},300);
        console.info(top)
        this.is_scroll = false;
        setTimeout(function(){
          _this.is_scroll = true;
        },400)
      },
      addRecommendCart: function(item,goods_id){

        this.addCart(item,'',goods_id);
      },
      addCart: function(item, event, goods_id, goods) {
        var sku_number = item.sku_list[0].use_stock_num;
        var good = {
          id: item.id,
          sku_id: item.sku_list[0].id,
          attr_name: item.sku_list[0].attr_names,
          promo_setting:item.promo_setting,
          promo_limit_buy:item.promo_limit_buy,
          title: item.title,
          sku_type: item.sku_type,
          sku_number: sku_number,
          box_fee:item.sku_list[0].box_fee,
          pict_url:item.pict_url,
          price: item.sku_list[0].sale_price,
          amount: 1
        };
        if(!item.amount){
          item.amount = 0;
        }
        if(sku_number>=0){
          if(item.amount>=sku_number){
            layer.open({
              content: "库存不足",
              skin: "msg",
              time: 2
            });
            return false;
          }
        }
        if (!item.amount || item.amount==0) {
          item.amount = 1;
          this.selectedFood.push(item);
          cart.addGood(good);
          this.recart = cart.getCart();
        }else{
          item.amount++;
          cart.updateGood(item.sku_list[0].id, item.amount);
          this.recart = cart.getCart();
        }
        for (var i = 0; i < this.goods.length; i++) {
          for (var j = 0; j < this.goods[i].goods_list.length; j++) {  
            if (this.goods[i].goods_list[j].id == goods_id) {
              if(this.goods[i].goods_list[j].amount!=item.amount){
                this.goods[i].goods_list[j].amount++;
              }
            }
          }
          this.goodsNumber(this.goods[i]) 
        }
        if(this.decorate.sys_data){
          for(var k=0;k<this.decorate.sys_data.length;k++){
            if(this.decorate.sys_data[k].id==goods_id){
              if(this.decorate.sys_data[k].amount!=item.amount){
                this.decorate.sys_data[k].amount++;
              }  
            }
          }
        }
        var _this = this;
        _this.checkSetting(_this);
      },
      reduceCart: function(item, goods_id,goods) {
        console.info(item)
        item.amount--;
        cart.updateGood(item.sku_list[0].id, item.amount);
        this.recart = cart.getCart();
        if(item.amount==0){
          cart.delGood(item.sku_list[0].id);
          this.recart = cart.getCart();
        }
        for (var i = 0; i < this.goods.length; i++) {
          for (var j = 0; j < this.goods[i].goods_list.length; j++) {
            if (this.goods[i].goods_list[j].id == goods_id) {
              if(this.goods[i].goods_list[j].amount!=item.amount){
                this.goods[i].goods_list[j].amount--;
              }
            } 
          }
          this.goodsNumber(this.goods[i]) 
        }
        if(this.decorate.sys_data){
          for(var k=0;k<this.decorate.sys_data.length;k++){
            if(this.decorate.sys_data[k].id==goods_id){
              if(this.decorate.sys_data[k].amount!=item.amount){
                this.decorate.sys_data[k].amount--;
              }  
            }
          }
        }
        var _this = this;
        _this.checkSetting(_this);                 
      },
      reduceFoodCart: function(food,goods_id,sku_type,goods) {
        if(sku_type==0){
          if(food.amount==1){
            food.amount--;
            cart.delGood(food.sku_id);
            this.recart = cart.getCart();
          }else{
            food.amount--;
            cart.updateGood(food.sku_id, food.amount);
            this.recart = cart.getCart();
          }
        }else{
          food.amount--;
          this.sku = {
            title: food.title,
            sku_type:sku_type,
            id:food.id,
            sku_id:food.sku_id,
            price: food.price,
            attr_name: food.attr_names,
            amount: food.amount
          }
          if(food.amount==0){
            cart.delGood(food.sku_id);
            this.recart = cart.getCart();
          }else{
            cart.updateGood(food.sku_id, food.amount);
            this.recart = cart.getCart();
          }

        }
        for (var i = 0; i < this.goods.length; i++) {
          for (var j = 0; j < this.goods[i].goods_list.length; j++) {
            if (this.goods[i].goods_list[j].id == goods_id) {
              this.goods[i].goods_list[j].amount--;
            }                 
          }
          this.goodsNumber(this.goods[i]) 
        }
        if(this.decorate.sys_data){
          for(var k=0;k<this.decorate.sys_data.length;k++){
            if(this.decorate.sys_data[k].id==goods_id){
                this.decorate.sys_data[k].amount--;
            }
          }
        }
        if(this.recart.goods.length==0){
          $('.cart-box').hide();
          $('.m-modal').removeClass('active');
        }
        var _this = this;
        _this.checkSetting(_this);
      },
      addFoodCart: function(food,goods_id,sku_type,goods) {
        if(food.sku_number>=0){
          if(food.amount>=food.sku_number){
            layer.open({
              content: "库存不足",
              skin: "msg",
              time: 2
            });
            return false;
          }
        }
        if(sku_type==0){
          food.amount++;
          cart.updateGood(food.sku_id, food.amount);
          this.recart = cart.getCart();      
        }else{
          food.amount++;
          this.sku = {
            title: food.title,
            sku_number:food.sku_number,
            sku_type:sku_type,
            id:food.id,
            sku_id:food.sku_id,
            price: food.price,
            attr_name: food.attr_names,
            amount: food.amount
          }
          cart.updateGood(food.sku_id, food.amount);
          this.recart = cart.getCart();
        }
        for(var i=0;i<this.goods.length;i++){
          for(var j=0;j<this.goods[i].goods_list.length;j++){
            if(this.goods[i].goods_list[j].id == goods_id){
              this.goods[i].goods_list[j].amount++;
            }            
          }
          this.goodsNumber(this.goods[i]) 
        }
        if(this.decorate.sys_data){
          for(var k=0;k<this.decorate.sys_data.length;k++){
            if(this.decorate.sys_data[k].id==goods_id){
                this.decorate.sys_data[k].amount++;
            }
          }
        }
        var _this = this;
        _this.checkSetting(_this); 
      },
      clearCart: function() {
        this.selectedFood = [];
        for (var i = 0; i < this.goods.length; i++) {
          for (var j = 0; j < this.goods[i].goods_list.length; j++) {
            Vue.set(this.goods[i].goods_list[j], "amount", 0);
            this.goodsNumber(this.goods[i])  
          }
        }
        if(this.decorate.sys_data){
          for(var k=0;k<this.decorate.sys_data.length;k++){
            Vue.set(this.decorate.sys_data[k], "amount", 0);
          }
        }  
        this.sku = []; 
        cart.clearCart();
        this.recart = cart.getCart();
        $('.cart-box').hide();
        $('.m-modal').removeClass('active');
        var _this = this;
        _this.checkSetting(_this);   
      },
      hideModal: function(){
        $('.m-modal').removeClass('active');
        $('.m-modal').removeClass('z-active');
        $('.cart-box').hide();
        $('.cart-modal').hide();
        $('.cart-modal-box').hide();
        $('.good-detail-modal').hide();
        $('.cart-modal-box').hide();
        $('.cart-sku').removeClass('active');
      },
      closePopup: function(){
        $('.m-modal').removeClass('active');
        $('.cart-box').hide();
        $('.cart-modal').hide();
        $('.cart-modal-box').hide();
        $('.cart-sku').removeClass('active');
      },
      showCart: function(){
        if(this.recart.goods.length!=0){
          if($('.cart-box').css("display") == "none"){
            $('.cart-box').show();
            $('.m-modal').addClass('active');
            $('.cart-modal-box').hide();
            $('.good-detail-modal').hide();
          }else{
            $('.cart-box').hide();
            $('.m-modal').removeClass('active');
          }
        }
      },
      goPay: function(){
        var phone = $('#user_mobile').val();
        if(!phone){
          window.location.href = __BASEURL__+'user/bind_phone';
          return false;
        }
        if((Number(this.recart.total_price)+Number(this.recart.total_box))>0){
          if((Number(this.recart.total_price)+Number(this.recart.total_box))>=Number(send_money)){
            var payMoney = $('#pay-money').attr('data-value');
            var freightMoney = $('#freight').attr('data-money');
            var ORDERGOOD = {
              goods:this.recart.goods,
              pay_money:Number(payMoney).toFixed(2),
              freight_money:parseFloat(freightMoney).toFixed(2),
              package_money:this.recart.total_box,
              total_num:this.recart.total_amount
            }

            localStorage.setItem('ORDERGOOD', JSON.stringify(ORDERGOOD));
            window.location.href = __BASEURL__+'order/submit/'+shop_id;
          }
        }
      },
      showDetail: function(item,data){
        var id = data[0].id;
        var cartGoods = cart.getCart().goods;
        var good_amount = 0;
        for (var k = 0; k < cartGoods.length; k++) {
          if (cartGoods[k].sku_id == id) {
            good_amount = cartGoods[k].amount;
          }
        }
        this.sku = {
          goods:item,
          sku_list:data,
          title: item.title,
          description:item.description,
          sku_number: data[0].use_stock_num,
          sku_type:item.sku_type,
          pict_url:item.pict_url,
          sale_num:Number(data[0].sale_num)+Number(item.base_sale_num),
          box_fee:data[0].box_fee,
          list: data,
          price: data[0].sale_price,
          attr_name: data[0].attr_names,
          id:item.id,
          sku_id: data[0].id,
          amount: good_amount
        }
        $('.good-detail-modal').show();
        $('.cart-modal-box').show();
        $('.m-modal').addClass('active');
        $('.m-modal').addClass('z-active');
      },
      chooseSku: function(item,data) {
        console.info(item)
        var id = data[0].id;
        var cartGoods = cart.getCart().goods;
        var good_amount = 0;
        for (var k = 0; k < cartGoods.length; k++) {
          if (cartGoods[k].sku_id == id) {
            good_amount = cartGoods[k].amount;
          }
        }
        this.sku = {
          title: item.title,
          promo_limit_buy:item.promo_limit_buy,
          promo_setting:item.promo_setting,
          sku_number: data[0].use_stock_num,
          sku_type:item.sku_type,
          pict_url:item.pict_url,
          box_fee:data[0].box_fee,
          list: data,
          price: data[0].sale_price,
          attr_name: data[0].attr_names,
          id:item.id,
          sku_id: data[0].id,
          amount: good_amount
        }
        for (var j = 0; j < this.sku.list.length; j++) {
          this.sku.list[j].is_active = false;
        }
        this.sku.list[0].is_active = true;
        $('.cart-modal').show();
        $('.cart-modal-box').show();
        $('.good-detail-modal').hide();
        $('.m-modal').addClass('active');
      },
      changeSku: function(i, id,data) {
        var cartGoods = cart.getCart().goods;
        var good_amount = 0;
        for (var k = 0; k < cartGoods.length; k++) {
          if (cartGoods[k].sku_id == id) {
            good_amount = cartGoods[k].amount;
          }
        }
        this.sku = {
          promo_limit_buy:this.sku.promo_limit_buy,
          promo_setting:this.sku.promo_setting,
          title: this.sku.title,
          sku_number: data.use_stock_num,
          sku_type:this.sku.sku_type,
          list: this.sku.list,
          price: data.sale_price,
          attr_name: data.attr_names,
          pict_url:this.sku.pict_url,
          box_fee:data.box_fee,
          id:this.sku.id,
          sku_id: data.id,
          amount: good_amount
        }
        for (var j = 0; j < this.sku.list.length; j++) {
          this.sku.list[j].is_active = false;
        }
        this.sku.list[i].is_active = true;
      },
      addSkuCart: function(item) {
        console.info(this.sku)
        var good = {
          id: this.sku.id,
          sku_id: this.sku.sku_id,
          promo_setting:this.sku.promo_setting,
          promo_limit_buy:this.sku.promo_limit_buy,
          attr_name: this.sku.attr_name,
          sku_number:this.sku.sku_number,
          sku_type:this.sku.sku_type,
          pict_url:this.sku.pict_url,
          box_fee:this.sku.box_fee,
          title: this.sku.title,
          price: this.sku.price,
          amount: 1
        };

        if(this.sku.sku_number>=0){
          if(this.sku.amount>=this.sku.sku_number){
            layer.open({
              content: "库存不足",
              skin: "msg",
              time: 2
            });
            return false;
          }
        }
        if(this.sku.amount==0){
          this.sku.amount=1;
          cart.addGood(good);
          this.recart = cart.getCart();
        }else{
          this.sku.amount++;
          cart.updateGood(item.sku_id,this.sku.amount);
          this.recart = cart.getCart();
        }
        for(var i=0;i<this.goods.length;i++){
          for(var j=0;j<this.goods[i].goods_list.length;j++){
            if(this.goods[i].goods_list[j].id == item.id){
              if(!this.goods[i].goods_list[j].amount){
                this.goods[i].goods_list[j].amount=1;
              }else{
                this.goods[i].goods_list[j].amount++;
              }
            }
          }
          this.goodsNumber(this.goods[i])  
        }
        if(this.decorate.sys_data){
          for(var k=0;k<this.decorate.sys_data.length;k++){
            if(this.decorate.sys_data[k].id==item.id){
              if(!this.decorate.sys_data[k].amount){
                this.decorate.sys_data[k].amount=1;
              }else{
                this.decorate.sys_data[k].amount++;
              }
            }
          }
        }
        var _this = this;
        _this.checkSetting(_this);    
      },
      addDetailCart: function(item) {
        var good = {
          id: this.sku.id,
          sku_id: this.sku.sku_id,
          attr_name: this.sku.attr_name,
          sku_number:this.sku.sku_number,
          sku_type:this.sku.sku_type,
          pict_url:this.sku.pict_url,
          box_fee:this.sku.box_fee,
          title: this.sku.title,
          price: this.sku.price,
          amount: 1
        };
        if(this.sku.sku_number>=0){
          if(this.sku.amount>=this.sku.sku_number){
            layer.open({
              content: "库存不足",
              skin: "msg",
              time: 2
            });
            return false;
          }
        }
        if(this.sku.amount==0){
          this.sku.amount=1;
          cart.addGood(good);
          this.recart = cart.getCart();
        }else{
          this.sku.amount++;
          cart.updateGood(item.sku_id,this.sku.amount);
          this.recart = cart.getCart();
        }

        if(this.sku.sku_type==0){
          $('.m-modal').removeClass('active');
          $('.good-detail-modal').hide();
          $('.cart-modal-box').hide();
        }
        for(var i=0;i<this.goods.length;i++){
          for(var j=0;j<this.goods[i].goods_list.length;j++){
            if(this.goods[i].goods_list[j].id == item.id){
              this.goods[i].goods_list[j].amount++;
            }  
          }
          this.goodsNumber(this.goods[i])  
        }
        if(this.decorate.sys_data){
          for(var k=0;k<this.decorate.sys_data.length;k++){
            if(this.decorate.sys_data[k].id==item.id){
              this.decorate.sys_data[k].amount++;
            }
          }
        }
        var _this = this;
        _this.checkSetting(_this);    
      },
      reduceSkuCart: function(item) {
        if(this.sku.amount==1){
          this.sku.amount--;
          cart.delGood(item.sku_id);
          this.recart = cart.getCart();
        }else{
          this.sku.amount--;
          cart.updateGood(item.sku_id, item.amount);
          this.recart = cart.getCart();
        }
        for(var i=0;i<this.goods.length;i++){
          for(var j=0;j<this.goods[i].goods_list.length;j++){
            if(this.goods[i].goods_list[j].id == item.id){
              this.goods[i].goods_list[j].amount--;
            }  
          }
          this.goodsNumber(this.goods[i])  
        }
        if(this.decorate.sys_data){
          for(var k=0;k<this.decorate.sys_data.length;k++){
            if(this.decorate.sys_data[k].id==item.id){
              this.decorate.sys_data[k].amount--;
            }
          }
        }
        var _this = this;
        _this.checkSetting(_this);
      },
      goodsNumber: function(goods){
        var n = 0;
        for (var k = 0; k < goods.goods_list.length; k++) {
          if (goods.goods_list[k].amount) {
            n += goods.goods_list[k].amount
          }
        }
        goods.amount = n;          
      },
      checkSetting: function(_this){
        if(_this.activity!=''){
          var payMoney = Number(_this.recart.total_price)+Number(_this.recart.total_box);
          var activity = _this.activity;
          var is_setting = true;
          if(use_flow=='0'){
            if(Number(_this.recart.total_price)+Number(_this.recart.total_box)>=send_money){
              payMoney = (Number(_this.recart.total_price)+Number(_this.recart.total_box)).toFixed(2);
            }
          }else{
            if(Number(_this.recart.total_price)+Number(_this.recart.total_box)<flow_free_money){
              payMoney = (Number(_this.recart.total_price)+Number(_this.recart.total_box)).toFixed(2);
            }
          }
          for(var i=0;i<activity.setting.length;i++){
            if(is_setting){
              if(i==activity.setting.length-1){
                if(payMoney>=Number(activity.setting[i].price)){
                  _this.setting_price = activity.setting[i].price;
                  _this.setting_red_price = activity.setting[i].red_price;
                  _this.is_setting_show = true;
                  is_setting = false;
                }else{
                  _this.is_setting_show = false;
                }
              }else{
                var price1 = Number(activity.setting[i].price);
                var price2 = Number(activity.setting[i+1].price);
                if(payMoney>price1 && payMoney<price2){
                  _this.setting_price = price1;
                  _this.setting_red_price = activity.setting[i].red_price;
                  _this.is_setting_show = true;
                  is_setting = false;
                }else{
                  _this.is_setting_show = false;
                }
              }
            }
          }
        }
        _this.is_food = true;
        var food = _this.recart.goods;
        var v = true;
        for(var b=0;b<food.length;b++){
          if(v){
            if(food[b].dec_price){
              _this.is_food = false;
              v = false;
            }else{
              _this.is_food = true;
            }
          }
        }
      }
    }
  });

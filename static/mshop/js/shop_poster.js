
  var cart = new Cart();
  var cartData = cart.getCart();


  var app = new Vue({
    el: "#app",
    data: {
      goods: [],
      poster_logo:'',
      listHeight: [],
      activity:'',
      foodsScrollY: 0,
      selectedFood: [],
      sku:'',
      recart:'',
      setting_price:'',
      setting_red_price:'',
      is_setting_show:false,
      is_food:true,
      is_notdis:'',
      shop_id:'',
      status:'',
      on_time:'',
      timeArr:{
        list:[]
      },
      send_money:'',
      is_newbie_coupon:'',
      use_flow:'',
      freight_money:'',
      flow_free_money:''
    },
    created: function() {
      var _this = this;
      this.initGood();
      _this.status = status;

      this.recart = cartData;
      this.$nextTick(function() {
                 
      });

      var url = window.location.href;
      if(url.indexOf('is_notdis')>=0){
        if(_this.status!=2){
          _this.status = 2;
          _this.is_notdis = true;
        }
      }
      console.info(this.status,this.is_notdis)

    },
    computed: {

    },
    methods: {
      initGood: function() {

        var _this = this;
        var id = $('#id').val();
        $.getJSON(__BASEURL__ + "api/decorate/get_module_data", {
          id:id
        }, function (data) {
          if (data.success) {
            _this.poster_logo = data.data.module_data.module_data.img;
            _this.goods = data.data.module_data.sys_data;

            _this.shop_id = data.data.shop_model.id;
            _this.status = data.data.shop_model.status;
            _this.on_time = data.data.shop_model.on_time;
            _this.send_money = data.data.shop_model.send_money;
            _this.is_newbie_coupon = data.data.shop_model.is_newbie_coupon;
            _this.use_flow = data.data.shop_model.use_flow;
            _this.freight_money = data.data.shop_model.freight_money;
            _this.flow_free_money = data.data.shop_model.flow_free_money;
            $('title').html(data.data.module_data.module_data.title)
             var SHOPID = _this.shop_id;
              sessionStorage.setItem('SHOPID', JSON.stringify(SHOPID));
            var url = window.location.href;
            if(url.indexOf('is_notdis')>=0){
              if(_this.status!=2){
                _this.status = 2;
                _this.is_notdis = true;
              }
            }
            var cartGoods = cart.getCart().goods;
            for (var i = 0; i < _this.goods.length; i++) {
            if(_this.goods[i].sku_type=='1'){
                var n =0;
                for (var g = 0; g < cartGoods.length; g++) {
                  if(cartGoods[g].id==_this.goods[i].id){
                    n += cartGoods[g].amount;
                  }
                }
                _this.goods[i].amount = n;
              }else{
                  for (var g = 0; g < cartGoods.length; g++) {
                    if(cartGoods[g].sku_id==_this.goods[i].sku_list[0].id){
                      _this.goods[i].amount = cartGoods[g].amount;
                    }
                  }
              }
            }

            var resactivity = '';
            $.getJSON(__BASEURL__ + "/api/shop/promotion_info", {
              shop_id:_this.shop_id
            }, function (data) {
              if (data.success){
                if(data.data!=null){
                  resactivity = data.data;
                  _this.activity = resactivity;
      
                _this.is_food = true;
                var food = _this.recart.goods;
                for(var b=0;b<food.length;b++){
                  if(food[b].dec_price){
                    _this.is_food = false;
                  }
                }

                _this.checkSetting(_this);

                }
              }
            });

            var is_open = true;
            var on_time = _this.on_time;
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
              if(_this.status==0){
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
          }
        });
      },
      addCart: function(item, event, goods_id, goods) {
        var sku_number = item.sku_list[0].use_stock_num;
        var good = {
          id: item.id,
          sku_id: item.sku_list[0].id,
          attr_name: item.sku_list[0].attr_names,
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
            if (this.goods[i].id == goods_id) {
              if(this.goods[i].amount!=item.amount){
                this.goods[i].amount++;
              }
            }     
        }
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
        
            if (this.goods[i].id == goods_id) {
              if(this.goods[i].amount!=item.amount){
                this.goods[i].amount--;
              }
            } 
          
        }                
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
       
            if (this.goods[i].id == goods_id) {
              this.goods[i].amount--;
            }                 
          
        }
        if(this.recart.goods.length==0){
          $('.cart-box').hide();
          $('.m-modal').removeClass('active');
        }
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
          
            if(this.goods[i].id == goods_id){
              this.goods[i].amount++;
            }            
          
        }
      },
      clearCart: function() {
        this.selectedFood = [];
        for (var i = 0; i < this.goods.length; i++) {
         
            Vue.set(this.goods[i], "amount", 0); 
          
        }  
        this.sku = []; 
        cart.clearCart();
        this.recart = cart.getCart();
        $('.cart-box').hide();
        $('.m-modal').removeClass('active');
      },
      hideModal: function(){
        $('.m-modal').removeClass('active');
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
          if((Number(this.recart.total_price)+Number(this.recart.total_box))>=Number(this.send_money)){
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
            window.location.href = __BASEURL__+'order/submit/'+this.shop_id;
          }
        }
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
        console.info(item)
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
         
            if(this.goods[i].id == item.id){
              console.info(1)
              if(!this.goods[i].amount){
                this.goods[i].amount=1;
              }else{
                this.goods[i].amount++;
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
        for(var i=0;i<this.goods.length;i++){
        
            if(this.goods[i].id == item.id){
              this.goods[i].amount++;
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
        
            if(this.goods[i].id == item.id){
              this.goods[i].amount--;
            }  
          
        }
        var _this = this;
        _this.checkSetting(_this);
      }, 
      checkSetting: function(_this){
        console.info(1)
        if(_this.activity!=''){
          var payMoney = Number(_this.recart.total_price)+Number(_this.recart.total_box);
          var activity = _this.activity;
          var is_setting = true;
          if(this.use_flow=='0'){
            if(Number(_this.recart.total_price)+Number(_this.recart.total_box)>=this.send_money){
              payMoney = (Number(_this.recart.total_price)+Number(_this.recart.total_box)).toFixed(2);
            }
          }else{
            if(Number(_this.recart.total_price)+Number(_this.recart.total_box)<this.flow_free_money){
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

/**
 * finance.js
 * by jimmu
 * date: 2017-11-06
 */
$(function () {
  var active_id=$("#active_id").val(),
    $shop = $('#shop'),
    $start_time=$("#start_time"),
    $end_time=$("#end_time"),
    $btnConfirm=$("#btn-confirm"),
    $addGoodModal=$("#addGoodModal"),
    $active_name=$("#active_name"),
    $discount_num=$("#discount_num");


  /* var start = moment().subtract(20, 'days'),
   end = moment(),
   startTime=new Date();*/
  var cur_page = 1,
    page_size = 10,
    limit_buy=0,
    limit_times = 0,
    discount_type=1,
    shop_id="",
    is_save=true,
    is_empty=true;
  //time = start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');

  var addGoodTpl = document.getElementById("addGoodTpl").innerHTML,
    goodsTpl = document.getElementById("goodsTpl").innerHTML;

  var goods_arr=[];
  var goods_sale=[];
  var goods_dec=[];

  var activeGoods={
    list:[
      /*{
      id:1,
      name:"牛肉面",
      src:"http://oydp172vs.bkt.clouddn.com/wsc_goods/1509345192839_8855.jpg",
      price:"20",
      discount_type:"减价",
       sellNum:"50"
    }*/
    ]
  };
  var decGoods={
    list:[]
  };

  // 判断添加或编辑
  if (!active_id) {
    if(discount_type==1){
      $("#shopTbody").html(template(goodsTpl, activeGoods));
    }else{
      $("#shopTbody").html(template(goodsTpl, decGoods));
    }
  } else {
    getEditData();
  }

  //编辑页面获取数据
  function getEditData() {
    $.getJSON(
      __BASEURL__ + "mshop/promotion_api/detail", {
        id:active_id
      },
      function (data) {
        if (data.success) {
          var  dataInfo = data.data;
          $active_name.val(dataInfo.title);
          $start_time.val(dataInfo.start_time.alias);
          $end_time.val(dataInfo.end_time.alias);

          shop_id=dataInfo.shop_id;
          $("#shop option[value='" + shop_id + "']").attr("selected", "selected");

          limit_buy=dataInfo.limit_buy;
          limit_times = dataInfo.limit_times;
          if(limit_buy==0){
            $('[value="不限购"]').attr("checked", true);
          }else{
            $('[value="限购"]').attr("checked", true);
            $discount_num.val(limit_buy);
          }

          if (limit_times == 0) {
            $('[value="不限次数"]').attr("checked", true);
          } else {
            $('[value="仅限1次"]').attr("checked", true);
          }

          discount_type=dataInfo.discount_type.value;
          if(discount_type==1){
            $('[value="打折"]').attr("checked", true);
            activeGoods.list=JSON.parse(dataInfo.setting);
            $("#shopTbody").html(template(goodsTpl, activeGoods));
          }else{
            $('[value="减价"]').attr("checked", true);
            decGoods.list=JSON.parse(dataInfo.setting );
            $("#shopTbody").html(template(goodsTpl, decGoods));
          }

          goods_arr=dataInfo.goods_arr.split(",")
          //$("#goodModal").html(template(addGoodTpl, data.data));

        }
      }
    );
  }

  // 初始化时间范围
  function initDateRange() {
    function cb(s, e) {
      time = s.format('YYYY-MM-DD') + ' - ' + e.format('YYYY-MM-DD');

      $time.val(time);
      //getFinanceList(1);
    }
    $time.daterangepicker({
      startDate: startTime,
      endDate: end,
      //maxDate: 28,
      applyClass: 'btn-primary',
      cancelClass: 'btn-default',
      locale: {
        applyLabel: '确认',
        cancelLabel: '取消',
        fromLabel: '起始时间',
        toLabel: '结束时间',
        customRangeLabel: '自定义',
        daysOfWeek: ['日', '一', '二', '三', '四', '五', '六'],
        monthNames: ['一月', '二月', '三月', '四月', '五月', '六月',
          '七月', '八月', '九月', '十月', '十一月', '十二月'
        ],
        firstDay: 1,
        format: 'YYYY-MM-DD'
      },
      ranges: {
        /*'今日': [moment(), moment()],
        '昨日': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        '最近7日': [moment().subtract(6, 'days'), moment()],
        '最近30日': [moment().subtract(29, 'days'), moment()]*/
      }
    }, cb);
  }

  function selectTime() {
    //时间
    // $(".form_datetime").datetimepicker({
    //   format: "yyyy-mm-dd hh:ii:ss",
    //   autoclose: true,
    //   todayBtn: true,
    //   language:'zh-CN',
    //   pickerPosition:"bottom-right"
    // });
    var start;var end;
    start = {
      elem: '#start_time',
      format: 'yyyy-MM-dd HH:mm:ss',
      theme: '#5aa2e7',
      type: 'datetime',
      done: function (value, date, endDate) {
        end.min=value;  //开始日选好后，重置结束日的最小日期
        end.start = value //将结束日的初始值设定为开始日
      }
    };
    end = {
      elem: '#end_time',
      format: 'yyyy-MM-dd HH:mm:ss',
      max: '2099-06-16 23:59:59',
      min:'',
      type: 'datetime',
      theme: '#5aa2e7',
      choose: function(datas){
        start.max = datas; //结束日选好后，重置开始日的最大日期
        start.min = min;
      },
      done: function (value, date, endDate) {
      }
    };
    laydate.render(start);
    laydate.render(end);
  }
  selectTime();



  getGoodList(cur_page);
  // 获取商品列表
  function getGoodList(curr, cate_id, title) {
    shop_id = shop_id;
    if(shop_id!=0){
      $.getJSON(
        __BASEURL__ + "mshop/items_api/goods_list", {
          current_page: curr || 1,
          page_size: page_size,
          cate_id: cate_id,
          title: title,
          shop_id: shop_id
        },
        function (data) {
          if (data.success) {
            var pages = Math.ceil(+data.data.total / page_size);
            $("#goodModal").html(template(addGoodTpl, data.data));

            laypage({
              cont: "goodModalPage",
              pages: pages,
              curr: curr || 1,
              skin: "#5aa2e7",
              first: 1,
              last: pages,
              skip: true,
              prev: "&lt",
              next: "&gt",
              jump: function (obj, first) {
                if (!first) {
                  getGoodList(obj.curr);
                }
              }
            });
          }
        }
      );
    }else{
      $.getJSON(
        __BASEURL__ + "mshop/store_goods_api/goods_list", {
          current_page: curr || 1,
          page_size: page_size,
        },
        function (data) {
          if (data.success) {
            var pages = Math.ceil(+data.data.total / page_size);
            $("#goodModal").html(template(addGoodTpl, data.data));

            laypage({
              cont: "goodModalPage",
              pages: pages,
              curr: curr || 1,
              skin: "#5aa2e7",
              first: 1,
              last: pages,
              skip: true,
              prev: "&lt",
              next: "&gt",
              jump: function (obj, first) {
                if (!first) {
                  getGoodList(obj.curr);
                }
              }
            });
          }
        }
      );
    }


  }

  //搜索
  function searchModalVal() {
    var val = $("#searchModalVal").val(),
      cate_id=undefined;

    getGoodList(cur_page, cate_id, val);
  }

  //限购
  function discountType(el) {
    if ($(el).val() == "不限购") {
      limit_buy = 0;
    } else {
      limit_buy = $discount_num.val();
    }
  }

  function limitType(el) {
    if ($(el).val() == "不限次数") {
      limit_times = 0;
    } else {
      limit_times = 1;
    }
  }

  //商品标签
  function goodType(el) {
    //activeGoods.list.splice(0,activeGoods.list.length);
    if ($(el).val() == "打折") {
      discount_type = 1;
      $("#shopTbody").html(template(goodsTpl, activeGoods));
    } else {
      discount_type = 2;
      $("#shopTbody").html(template(goodsTpl, decGoods));
    }
  }

  //添加规格
  function addGood() {
    var record=$shop.find('option:selected').val();
    if(record!=shop_id){
      activeGoods={
        list:[]
      };
       decGoods={
        list:[]
      };
    }
    shop_id = $shop.find('option:selected').val();


    if(shop_id==""){
      new Msg({
        type: 'danger',
        msg: '请先选择门店'
      });
      return false;
    }

    if(discount_type==1){
    }else{
    }
    $addGoodModal.modal('show');
    getGoodList(cur_page);

    $('[name="selectAll"]').prop('checked', false); //全选为不选择
    //$("#goodModal").html(template(addGoodTpl, good));
  }

  function batchAddGood(obj) {
    var $this = $(obj),
      selectedItem = $('[name="selectItem"]:checked'),
      ids = [];
    goodIds = '';
    var shop_id = $shop.find('option:selected').val();
    // 判断是否已选商品
    if (selectedItem.length < 1) {
      new Msg({
        type: 'danger',
        msg: '请先选择商品'
      });
      return false;
    }
    if(shop_id==0){
      $.each(selectedItem, function (i) {
        var $this=$(this);
        var id=$(this).val();
        var sku_list=[];
        $.getJSON(__BASEURL__ +"/mshop/store_goods_api/goods_ids_list", {goods_ids: id},
          function (data) {
            if (data.success) {
              sku_list=data.data[0].sku_list;
              for(var k=0;k< sku_list.length;k++){
                sku_list[k].decPrice="";
              }
              var newList={};
              newList.id=$this.val();
              newList.name=$this.data("title");
              newList.src=$this.data("path");
              newList.price=$this.data("price");
              newList.dec_price=0;
              newList.dec_input="";
              newList.good_stock="";
              newList.sku_list=sku_list;

              if(discount_type==1){
                newList.discount_name="打折";
              }else{
                newList.discount_name="减价";
              }

              //判断在个活动中，然后不能选择
              for(var i=0;i<activeGoods.list.length;i++){
                if($this.val()==activeGoods.list[i].id){
                  new Msg({
                    type: "danger",
                    msg: "商品已选择。请选择别的商品"
                  });
                  return false
                }
              }
              for(var j=0;j<decGoods.list.length;j++){
                if($this.val()==decGoods.list[j].id){
                  new Msg({
                    type: "danger",
                    msg: "商品已选择。请选择别的商品"
                  });
                  return false
                }
              }

              if(discount_type==1){
                activeGoods.list.push(newList);
              }else{
                decGoods.list.push(newList);
              }
              ids.push($this.val());

              if(discount_type==1){
                if(activeGoods.list.length<=0){
                }else{
                  goods_arr=ids;
                  $addGoodModal.modal('hide');
                  $("#shopTbody").html(template(goodsTpl, activeGoods));
                }
              }else{
                if(decGoods.list.length<=0){
                }else{
                  goods_arr=ids;
                  $addGoodModal.modal('hide');
                  $("#shopTbody").html(template(goodsTpl, decGoods));
                }
              }


              //商品列表
              if(discount_type==1){
                $("#shopTbody").html(template(goodsTpl, activeGoods));
              }else{
                $("#shopTbody").html(template(goodsTpl, decGoods));
              }
              $addGoodModal.modal('hide');
            }
          }
        );




      });
    }else{
      $.each(selectedItem, function (i) {
        var $this=$(this);
        var id=$(this).val();
        var sku_list=[];
        $.getJSON(__BASEURL__ +"/mshop/items_api/goods_ids_list", {goods_ids: id},
          function (data) {
            if (data.success) {
              sku_list=data.data[0].sku_list;
              for(var k=0;k< sku_list.length;k++){
                sku_list[k].decPrice="";
              }
              var newList={};
              newList.id=$this.val();
              newList.name=$this.data("title");
              newList.src=$this.data("path");
              newList.price=$this.data("price");
              newList.dec_price=0;
              newList.dec_input="";
              newList.good_stock="";
              newList.sku_list=sku_list;

              if(discount_type==1){
                newList.discount_name="打折";
              }else{
                newList.discount_name="减价";
              }

              //判断在个活动中，然后不能选择
              for(var i=0;i<activeGoods.list.length;i++){
                if($this.val()==activeGoods.list[i].id){
                  new Msg({
                    type: "danger",
                    msg: "商品已选择。请选择别的商品"
                  });
                  return false
                }
              }
              for(var j=0;j<decGoods.list.length;j++){
                if($this.val()==decGoods.list[j].id){
                  new Msg({
                    type: "danger",
                    msg: "商品已选择。请选择别的商品"
                  });
                  return false
                }
              }

              if(discount_type==1){
                activeGoods.list.push(newList);
              }else{
                decGoods.list.push(newList);
              }
              ids.push($this.val());

              if(discount_type==1){
                if(activeGoods.list.length<=0){
                }else{
                  goods_arr=ids;
                  $addGoodModal.modal('hide');
                  $("#shopTbody").html(template(goodsTpl, activeGoods));
                }
              }else{
                if(decGoods.list.length<=0){
                }else{
                  goods_arr=ids;
                  $addGoodModal.modal('hide');
                  $("#shopTbody").html(template(goodsTpl, decGoods));
                }
              }


              //商品列表
              if(discount_type==1){
                $("#shopTbody").html(template(goodsTpl, activeGoods));
              }else{
                $("#shopTbody").html(template(goodsTpl, decGoods));
              }
              $addGoodModal.modal('hide');
            }
          }
        );




      });
    }

  }


  function selectGood(id,price,src,title) {
    var sku_list=[];
    var shop_id = $shop.find('option:selected').val();
    if(shop_id==0){
      $.getJSON(__BASEURL__ +"/mshop/store_goods_api/goods_ids_list", {goods_ids: id},
        function (data) {
          if (data.success) {
            sku_list=data.data[0].sku_list;
            for(var k=0;k< sku_list.length;k++){
              sku_list[k].decPrice="";
            }
            var newList={};
            newList.id=id;
            newList.name=title;
            newList.src=src;
            newList.price=price;
            newList.dec_price=0;
            newList.dec_input="";
            newList.good_stock="";
            newList.sku_list=sku_list;

            if(discount_type==1){
              newList.discount_name="打折";
            }else{
              newList.discount_name="减价";
            }

            for(var i=0;i<activeGoods.list.length;i++){
              if(id==activeGoods.list[i].id){
                new Msg({
                  type: "danger",
                  msg: "商品已选择。请选择别的商品"
                });
                return false
              }

            }
            for(var j=0;j<decGoods.list.length;j++){
              if(id==decGoods.list[j].id){
                new Msg({
                  type: "danger",
                  msg: "商品已选择。请选择别的商品"
                });
                return false
              }

            }

            if(discount_type==1){
              activeGoods.list.push(newList);
            }else{
              decGoods.list.push(newList);
            }
            goods_arr.push(id);
            //console.log(goods_arr);
            //商品列表
            if(discount_type==1){
              $("#shopTbody").html(template(goodsTpl, activeGoods));
            }else{
              $("#shopTbody").html(template(goodsTpl, decGoods));
            }
            $addGoodModal.modal('hide');
          }
        }
      );
    }else{
      $.getJSON(__BASEURL__ +"/mshop/items_api/goods_ids_list", {goods_ids: id},
        function (data) {
          if (data.success) {
            sku_list=data.data[0].sku_list;
            for(var k=0;k< sku_list.length;k++){
              sku_list[k].decPrice="";
            }
            var newList={};
            newList.id=id;
            newList.name=title;
            newList.src=src;
            newList.price=price;
            newList.dec_price=0;
            newList.dec_input="";
            newList.good_stock="";
            newList.sku_list=sku_list;

            if(discount_type==1){
              newList.discount_name="打折";
            }else{
              newList.discount_name="减价";
            }

            for(var i=0;i<activeGoods.list.length;i++){
              if(id==activeGoods.list[i].id){
                new Msg({
                  type: "danger",
                  msg: "商品已选择。请选择别的商品"
                });
                return false
              }

            }
            for(var j=0;j<decGoods.list.length;j++){
              if(id==decGoods.list[j].id){
                new Msg({
                  type: "danger",
                  msg: "商品已选择。请选择别的商品"
                });
                return false
              }

            }

            if(discount_type==1){
              activeGoods.list.push(newList);
            }else{
              decGoods.list.push(newList);
            }
            goods_arr.push(id);
            //console.log(goods_arr);
            //商品列表
            if(discount_type==1){
              $("#shopTbody").html(template(goodsTpl, activeGoods));
            }else{
              $("#shopTbody").html(template(goodsTpl, decGoods));
            }
            $addGoodModal.modal('hide');
          }
        }
      );
    }




  }


  //悬停操作
  function onshowTable(index) {
    $(".hover_table"+index).toggle();
  }

  function onMouseOutSku(index) {
    //toggle()
    //$(".hover_table"+index).hide();
  }

  function onMouseSku(index) {
   // $(".hover_table"+index).show();
  }

  //保留2位小数
  var toDecimal2=function (x) {
    var f = parseFloat(x);
    if (isNaN(f)) {
      return '0';
    }
    var f = Math.round(x * 100) / 100;
    var s = f.toString();
    return s;
  };

  function editPrice(el,id,index,price,type) {
    var $val=$(el).val();

    //判断是只能输入数字和小数点
    if(!/^\d*(\.\d{1,2})?$/.test($val)){
      new Msg({
        type: "danger",
        msg: "只能输入数字和小数点"
      });
      is_save=false;
    }else{
      is_save=true;
    }

    //判断是否为空
    if($val==""){
      new Msg({
        type: "danger",
        msg: "输入框不能为空"
      });
      is_empty=false;
    }else{
      is_empty=true;
    }

    if(type=="打折"){
      if($val==0){
        new Msg({
          type: "danger",
          msg: "打折率不能为0"
        });
        $btnConfirm.prop('disabled',true);
        return false;
      }else{
        $btnConfirm.prop('disabled', false);
      }
      if(/^[1-9](\.\d+)?$/.test($val)){
        $btnConfirm.prop('disabled', false);
      }else{
        new Msg({
          type: "danger",
          msg: "打折率只能为1到10的以内的非负数"
        });
        $btnConfirm.prop('disabled', true);
        return false;
      }
    }else{
      //价格区间
      var x=price.indexOf('~');
      if(x>0){
        var startPrice= price.substring(0,x);
        var endPrice= price.substring(x+1,price.length);
        if(parseFloat($val)>parseFloat(startPrice)){
          new Msg({
            type: "danger",
            msg: "减价不能超出原价"
          });
          $btnConfirm.prop('disabled', true);
          return false;
        }else{
          $btnConfirm.prop('disabled', false);
        }
      }else{
        if(parseFloat($val)>=parseFloat(price)){
          new Msg({
            type: "danger",
            msg: "减价不能超出原价"
          });
          $btnConfirm.prop('disabled', true);
          return false;
        }else{
          $btnConfirm.prop('disabled', false);
        }
        }
      }
    $.getJSON(__BASEURL__ +"/mshop/items_api/goods_ids_list", {goods_ids: id},
      function (data) {
        if (data.success) {
          var sku_list=[];
          sku_list=data.data[0].sku_list;
          var $val=$(el).val();
          var decPrice="";
          var skuPrice="";
          //判断是否是选择多规格的
          if(sku_list.length>1){
            for(var k=0;k< sku_list.length;k++){
              if(type=="打折"){
                decPrice= parseFloat(sku_list[k].sale_price)*parseFloat($val*0.1);
                sku_list[k].decPrice=toDecimal2(decPrice);
              }else{
                decPrice= parseFloat(sku_list[k].sale_price)-$val;
                sku_list[k].decPrice=toDecimal2(decPrice);
              }
            }
            //价格区间
            var x=price.indexOf('~');
            if(x>0){
             var startPrice= price.substring(0,x);
              var endPrice= price.substring(x+1,price.length);
              if(type=="打折"){
                startPrice= toDecimal2(parseFloat(startPrice)*parseFloat(($val*0.1)));
                endPrice= toDecimal2(parseFloat(endPrice)*parseFloat(($val*0.1)));
                skuPrice=startPrice.toString()+'~'+endPrice.toString();
              }else{
                startPrice= toDecimal2(parseFloat(startPrice)-$val);
                endPrice= toDecimal2(parseFloat(endPrice)-$val);
                skuPrice=startPrice.toString()+'~'+endPrice.toString();
              }
            }else{
              if(type=="打折"){
                skuPrice=toDecimal2(parseFloat(price)*parseFloat(($val*0.1))) ;
              }else{
                skuPrice= toDecimal2(parseFloat(price)-$val);
              }
            }

            //是打折还减价
            if(discount_type==1){
              for(var i=0;i<activeGoods.list.length;i++){
                if(index==i){
                  activeGoods.list[i].dec_price=(skuPrice);
                  activeGoods.list[i].sku_list=sku_list;
                  activeGoods.list[i].dec_input=toDecimal2($val*0.1);
                }
              }
              $("#shopTbody").html(template(goodsTpl, activeGoods));
            }else{
              for(var i=0;i<decGoods.list.length;i++){
                if(index==i){
                  decGoods.list[i].dec_price=(skuPrice);
                  decGoods.list[i].sku_list=sku_list;
                  decGoods.list[i].dec_input=$val;
                }
              }
              $("#shopTbody").html(template(goodsTpl, decGoods));
            }

          }else{
            if(type=="打折"){
              decPrice= parseFloat(price)*parseFloat($val*0.1);
            }else{
              decPrice= parseFloat(price)-$val;
            }
            if(discount_type==1){
              for(var i=0;i<activeGoods.list.length;i++){
                if(index==i){
                  activeGoods.list[i].dec_price=toDecimal2(decPrice);
                  activeGoods.list[i].dec_input=toDecimal2($val*0.1);
                }
              }
              $("#shopTbody").html(template(goodsTpl, activeGoods));
            }else{
              for(var i=0;i<decGoods.list.length;i++){
                if(index==i){
                  decGoods.list[i].dec_price=  toDecimal2(decPrice);
                  decGoods.list[i].dec_input=$val;
                }
              }
              $("#shopTbody").html(template(goodsTpl, decGoods));
            }
          }
        }
      });
  }

  function stockOnchange(el,index) {
    var $val=$(el).val();
    if(discount_type==1){
      for(var i=0;i<activeGoods.list.length;i++){
        if(index==i){
          activeGoods.list[i].good_stock=$val;
        }
      }
      $("#shopTbody").html(template(goodsTpl, activeGoods));
    }else{
      for(var i=0;i<decGoods.list.length;i++){
        if(index==i){
          decGoods.list[i].good_stock=$val;
        }
      }
      $("#shopTbody").html(template(goodsTpl, decGoods));
    }



  }

  function delgood(index) {
    if(discount_type==1){
      for(var i=0;i<activeGoods.list.length;i++){
        if(index==i){
          activeGoods.list.splice(i,1)
        }
      }
      $("#shopTbody").html(template(goodsTpl, activeGoods));
    }else{
      for(var i=0;i<decGoods.list.length;i++){
        if(index==i){
          decGoods.list.splice(i,1)
        }
      }
      $("#shopTbody").html(template(goodsTpl, decGoods));
    }


  }

  // 提交门店信息
  $('#discount-form')
    .bootstrapValidator({
      fields: {
        active_name: {
          validators: {
            notEmpty: {
              message: '活动名称不能为空'
            },
            stringLength: {
              max: 30,
              message: '活动名称不得超过30个字符'
            }
          }
        },
        shop_id:{
          validators: {
            notEmpty: {
              message: '门店不能为空'
            }
          }
        },
        dec_input:{
          validators: {
            notEmpty: {
              message: '优惠输入框不能为空'
            }
          }
        }
      }
    })
    .on('success.form.bv', function (e) {
      // 阻止表单默认提交
      e.preventDefault();

      var active_name = $active_name.val(),
        start_time=$start_time.val(),
        end_time=$end_time.val(),
        discount_num=$discount_num.val();


      switch (discount_num) {
        case "":
          discount_num = "";
          break;
        case "0":
          discount_num = "";
          break;
        case "0.00":
          discount_num = "";
          break;
      }

      //时间判断
      var startTime = (start_time.replace( /-/g, '/' ));
      var endTime = new Date( end_time.replace( /-/g, '/' ) );
      var t = parseInt( endTime.getTime() ) - (new Date( startTime ).getTime());

      if(t<=0){
        new Msg({
          type: "danger",
          msg: "结束时间不能小于开始时间"
        });
        return false;
      }

      if ($('[value="限购"]').is(':checked')) {
        limit_buy = $discount_num.val();
      } else {
        limit_buy = 0;
      }

      if ($('[value="仅限1次"]').is(':checked')) {
        limit_times = 1;
      } else {
        limit_times = 0;
      }

      if ($('[value="限购"]').is(':checked') && discount_num == "") {
        new Msg({
          type: "danger",
          msg: "请填写限购件数"
        });
        return false;
      }
      if ($('[value="限购"]').is(':checked') && discount_num <=0) {
        new Msg({
          type: "danger",
          msg: "限购件数必须大于0"
        });
        return false;
      }

      if(start_time==""||end_time==""){
        new Msg({
          type: "danger",
          msg: "请填写活动时间"
        });
        return false;
      }

      //获取输入框
      var dec_inputs=$(".dec_input");
      dec_inputs.each(function (index,el) {
        var $val=$(el).val();
        if(discount_type==1){
          activeGoods.list[index].dec_input=toDecimal2($val*0.1);
          //activeGoods.list[index].dec_price= toDecimal2(parseFloat(activeGoods.list[index].price)*parseFloat($val*0.1));
          //$("#shopTbody").html(template(goodsTpl, activeGoods));
        }else{
          decGoods.list[index].dec_input=$val;
          //decGoods.list[index].dec_price= toDecimal2(parseFloat(decGoods.list[index].price)-parseFloat($val));
          //$("#shopTbody").html(template(goodsTpl, decGoods));
        }
        if($val==""){
          new Msg({
            type: "danger",
            msg: "请填写优惠价格"
          });
          is_save=false;
          return false;
        }

      });

      if(discount_type==1&&activeGoods.list.length==0){
        new Msg({
          type: "danger",
          msg: "请添加打折商品"
        });
        return false;
      }
      if(discount_type==2&&decGoods.list.length==0){
        new Msg({
          type: "danger",
          msg: "请添加减价商品"
        });
        return false;
      }

      shop_id = $shop.find('option:selected').val();
      if(shop_id==""){
        new Msg({
          type: 'danger',
          msg: '请先选择门店'
        });
        return false;
      }

      post_data = {
        shop_id:shop_id,
        title: active_name,
        type:1,
        start_time:start_time,
        end_time:end_time,
        discount_type: discount_type,
        limit_buy:limit_buy,
        limit_times: limit_times,
        setting:JSON.stringify(activeGoods.list),
        goods_arr:goods_arr.join(",")
      };
      //JSON.parse( jsonStr );

      if(discount_type==1){
        post_data.setting=JSON.stringify(activeGoods.list);
        for(var i=0;i<activeGoods.list.length;i++){
          goods_sale.push(activeGoods.list[i].id);
        }
        post_data.goods_arr=goods_sale.join(",");
      }else{
        post_data.setting= JSON.stringify(decGoods.list);
        for(var j=0;j<decGoods.list.length;j++){
          goods_dec.push(decGoods.list[j].id);
        }
        post_data.goods_arr=goods_dec.join(",");
      }

      // 判断是添加或编辑
      if (!active_id) {
        post_url = __BASEURL__ + 'mshop/promotion_api/create';
      } else {
        post_data.id = active_id;
        post_url = __BASEURL__ + 'mshop/promotion_api/edit';
      }


      if(is_save&&is_empty){
        $btnConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: "success",
              msg: data.msg,
              delay: 1,
              callback: function () {
                window.location.href = __BASEURL__ + "mshop/promotion/discount_list";
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
      }else{
        new Msg({
          type: "danger",
          msg: "只能输入数字和小数点"
        });
      }


    });


  window.onshowTable=onshowTable;
  window.addGood=addGood;
  window.selectGood=selectGood;
  window.goodType=goodType;
  window.discountType=discountType;
  window.limitType = limitType;
  window.searchModalVal=searchModalVal;
  window.batchAddGood=batchAddGood;
  window.editPrice=editPrice;
  window.delgood=delgood;
  window.onMouseOutSku=onMouseOutSku;
  window.onMouseSku=onMouseSku;
});
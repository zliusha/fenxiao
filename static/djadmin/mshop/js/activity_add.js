/**
 * finance.js
 * by jimmu
 * date: 2017-11-06
 */
$(function () {
  var active_id=$("#active_id").val(),
    $btnConfirm = $("#btn-confirm"),
    $addGoodModal = $("#addGoodModal"),
    $active_name = $("#active_name"),
    $start_time=$("#start_time"),
    $end_time=$("#end_time"),
    $shop=$("#shop");


  var cur_page = 1,
    page_size = 10,
    shop_id="",
    is_orderPrice=true,
    is_decPrice=true;

  var setDiscountTpl = document.getElementById("setDiscountTpl").innerHTML;


  var setDiscount = {
    list: [
    ]
  };

  // 判断添加或编辑
  if (!active_id) {
    $("#discountTbody").html(template(setDiscountTpl, setDiscount));
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
          console.log(dataInfo)
          $active_name.val(dataInfo.title);
          $start_time.val(dataInfo.start_time.alias);
          $end_time.val(dataInfo.end_time.alias);

          setDiscount.list=JSON.parse(dataInfo.setting );

          shop_id=dataInfo.shop_id;
          $("#shop option[value='" + shop_id + "']").attr("selected", "selected");

          $("#discountTbody").html(template(setDiscountTpl, setDiscount));

        }
      }
    );
  }

  function selectTime() {
    //时间
    var start;var end;
    start = {
      elem: '#start_time',
      format: 'yyyy-MM-dd HH:mm:ss',
      theme: '#5aa2e7',
      type: 'datetime',
      istime: true,
      istoday: false,
      choose: function(datas){
        //var min=new Date(datas.replace("-", "/"));
        //min= new Date(min.getTime() - 30*24*60*60*1000); //在日期-30天。
        //min=min.getFullYear() + "/" + (min.getMonth() + 1) + "/"+ min.getDate();
        end.min = datas; //开始日选好后，重置结束日的最小日期
        end.start = datas //将结束日的初始值设定为开始日
      },
      done: function (value, date, endDate) {
        end.min=value;  //开始日选好后，重置结束日的最小日期
        end.start = value; //将结束日的初始值设定为开始日
        console.log(end);
      }
    };
    end = {
      elem: '#end_time',
      format: 'yyyy-MM-dd HH:mm:ss',
      max: '2099-06-16 23:59:59',
      min:'',
      start:'',
      type: 'datetime',
      istime: true,
      istoday: false,
      theme: '#5aa2e7',
      choose: function(datas){
        start.max = datas; //结束日选好后，重置开始日的最大日期
        //start.min = min;
      },
      done: function (value, date, endDate) {
        start.max = value; //结束日选好后，重置开始日的最大日期
      }
    };
    laydate.render(start);
    laydate.render(end);

  }
  selectTime();

  function selecttime() {
    var minDate = null;
    var max = null;

    function fromDate(maxDate) {
      if (!maxDate) {
        max = moment(new Date())
      } else {
        max = maxDate;
      }
      $('input[name="from"]').daterangepicker({
        "autoApply": true, //选择日期后自动提交;只有在不显示时间的时候起作用timePicker:false
        singleDatePicker: true, //单日历
        showDropdowns: true, //年月份下拉框
        timePicker: true, //显示时间
        timePicker24Hour: true, //时间制
        timePickerSeconds: false, //时间显示到秒
        // startDate: moment().hours(0).minutes(0).seconds(0), //设置开始日期
        //maxDate: max , //设置最大日期
        "opens": "center",
        showWeekNumbers: true,
        locale: {
          format: "YYYY-MM-DD", //设置显示格式
          applyLabel: '确定', //确定按钮文本
          cancelLabel: '取消', //取消按钮文本
          daysOfWeek: ['日', '一', '二', '三', '四', '五', '六'],
          monthNames: ['一月', '二月', '三月', '四月', '五月', '六月',
            '七月', '八月', '九月', '十月', '十一月', '十二月'
          ],
          firstDay: 1
        },
      }, function (s) {
        toDate(s);
      });
    }

    fromDate()

    function toDate(minDate) {
      $('input[name="to"]').daterangepicker({
        "autoApply": true, //选择日期后自动提交;只有在不显示时间的时候起作用timePicker:false
        singleDatePicker: true, //单日历
        showDropdowns: true, //年月份下拉框
        timePicker: true, //显示时间
        timePicker24Hour: true, //时间制
        timePickerSeconds: false, //时间显示到秒
        // startDate: moment().hours(0).minutes(0).seconds(0), //设置开始日期
        //maxDate: moment(new Date()), //设置最大日期
        minDate: minDate,
        "opens": "center",
        showWeekNumbers: true,
        locale: {
          format: "YYYY-MM-DD HH:mm:ss", //设置显示格式
          applyLabel: '确定', //确定按钮文本
          cancelLabel: '取消', //取消按钮文本
          daysOfWeek: ['日', '一', '二', '三', '四', '五', '六'],
          monthNames: ['一月', '二月', '三月', '四月', '五月', '六月',
            '七月', '八月', '九月', '十月', '十一月', '十二月'
          ],
          firstDay: 1
        },
      }, function (s) {
        fromDate(s)
      });
    }
  }


  //添加一级优惠
  function addGood() {
    var addNew = {};
    addNew.price = "";
    addNew.red_price = "";
    if (setDiscount.list.length >= 3) {
      new Msg({
        type: 'danger',
        msg: '最多添加3个优惠设置'
      });
    } else {
      setDiscount.list.push(addNew);
      $("#discountTbody").html(template(setDiscountTpl, setDiscount));
    }

  }

  //删除
  function delPrice(el, index) {
    for (var i = 0; i < setDiscount.list.length; i++) {
      if (index == i) {
        setDiscount.list.splice(i, 1);
      }
    }
    $("#discountTbody").html(template(setDiscountTpl, setDiscount));
  }

  //编辑订单金额输入框
  function orderPriceChange(el, index) {
    var $val = $(el).val();
    //判断是否为空
    if($val==""){
      new Msg({
        type: "danger",
        msg: "订单金额输入框不能为空"
      });
      is_orderPrice=false;
    }else{
      is_orderPrice=true;
    }
    for (var i = 0; i < setDiscount.list.length; i++) {
      if (index == i) {
        setDiscount.list[i].price = $val;
      }
    }
    $("#discountTbody").html(template(setDiscountTpl, setDiscount));
  }

  //编辑减免金额输入框
  function redPriceChange(el, index) {
    var $val = $(el).val();
    if($val==""){
      new Msg({
        type: "danger",
        msg: "减免金额输入框不能为空"
      });
      is_decPrice=false;
    }else{
      is_decPrice=true;
    }
    for (var i = 0; i < setDiscount.list.length; i++) {
      if (index == i) {
        setDiscount.list[i].red_price = $val;
      }
    }
    $("#discountTbody").html(template(setDiscountTpl, setDiscount));
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
        order_price: {
          validators: {
            notEmpty: {
              message: '订单金额不能为空'
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
         end_time=$end_time.val();

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

      var orderPrices = $('[name="order_price"]');
      var redPrices = $('[name="red_price"]');
        orderPrices.each(function (index, el) {
        if ($(el).val() == "") {
          new Msg({
            type: "danger",
            msg: "请填写订单金额"
          });
          is_orderPrice=false;
          return false;
        }
      });
        redPrices.each(function (index, el) {
        if ($(el).val() == "") {
          new Msg({
            type: "danger",
            msg: "请填写减免金额"
          });
          is_decPrice=false;
          return false;
        }
      });

      if(start_time==""||end_time==""){
        new Msg({
          type: "danger",
          msg: "请填写活动时间"
        });
        return false;
      }

      if(setDiscount.list.length==0){
        new Msg({
          type: "danger",
          msg: "请添加订单优惠"
        });
        return false;
      }

      var is_price = true
      setDiscount.list.forEach(function (item) {
        if (Number(item.price)<=Number(item.red_price)) {
          is_price = false
        }
      })

      if(!is_price) {
        new Msg({
          type: "danger",
          msg: "订单金额必须大于减免金额"
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
        type:2,
        title: active_name,
        start_time: start_time,
        end_time: end_time,
        discount_type: 1,
        limit_buy:0,
        limit_times: 0,
        goods_arr:"",
        setting:JSON.stringify(setDiscount.list)
      };

      // 判断是添加或编辑
      if (!active_id) {
        post_url = __BASEURL__ + 'mshop/promotion_api/create';
      } else {
        post_data.id = active_id;
        post_url = __BASEURL__ + 'mshop/promotion_api/edit';
      }



      if(is_orderPrice&&is_decPrice){
        $btnConfirm.prop('disabled', true);

        $.post(post_url, autoCsrf(post_data), function (data) {
          if (data.success) {
            new Msg({
              type: "success",
              msg: data.msg,
              delay: 1,
              callback: function () {
                window.location.href = __BASEURL__ + "mshop/promotion/activity_list";
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
        if(!is_orderPrice){
          new Msg({
            type: "danger",
            msg: "请填写订单金额"
          });
        }
        if(!is_decPrice){
          new Msg({
            type: "danger",
            msg: "请填写减免金额"
          });
        }

      }

    });


  window.addGood = addGood;
  window.delPrice = delPrice;
  window.orderPriceChange = orderPriceChange;
  window.redPriceChange = redPriceChange;
});
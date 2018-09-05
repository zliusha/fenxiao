$(function () {

  var $endActiveModal = $("#endActiveModal"),
    $delActiveModal = $("#delActiveModal"),
    $endDiscountModal = $("#endDiscountModal"),
    $endConfirm = $("#end-confirm"),
    $addConfirm = $("#add-confirm"),
    $btnConfirm = $("#btn-confirm"),
    $endActive = $('#endActive'),
    $delActive = $("#delActive"),
    $editActive = $("#editActive"),
    $delConfirm = $("#del-confirm"),
    $delDiscount = $("#delDiscount"),
    $addDisBtn = $(".addDisBtn"),
    $changeTable = $(".changeTable"),
    $coupon_active = $("#coupon_active"),
    $couponForm = $("#coupon-form");

  //弹框数据
  var $discount_name = $("#discount_name"),
    $send_num = $("#send_num"),
    $price = $("#price"),
    $minPrice = $("#minPrice"),
    $maxPrice = $("#maxPrice"),
    $discount_num = $("#discount_num"),
    $accountFrom = $("#accountFrom"),
    $accountTo = $("#accountTo");

  var discountStatus = 1,
    use_limit = 1,
    each_limit = 1,
    condition_limit = -1,
    amount_type = 1, //类型 1固定面额 2面额区间
    amount_region = "", //面额区间
    value = 0;

  //活动数据
  var active_num = 0;
  var activeGetData = null;

  var discount = {
    list: []
  };
  var activeData = {
    list: []
  };

  var discountTpl = document.getElementById('discountTpl').innerHTML,
    activeTpl = document.getElementById('activeTpl').innerHTML;

  // 切换状态
  $('[name="status"]').on('change', function () {
    discountStatus = $('[name="status"]:checked').val();
    if (discountStatus == 1) {
      window.location.href = __BASEURL__ + 'mshop/promotion/coupon_list';
    } else {
      window.location.href = __BASEURL__ + 'mshop/promotion/coupon_follow';
    }
  });

  //获取数据
  function getInfoDiscount() {
    discount = {
      list: []
    };
    activeData = {
      list: []
    };

    $.getJSON(__BASEURL__ + 'mshop/coupon_api/load/1', {}, function (data) {
      if (data.success) {
        var activeData = data.data;
        console.log(activeData);
        activeGetData = data.data;
        if (activeData != null) {
          switch (activeData.status) {
            case "1":
              $("#status_text").html("未开始");
              $endActive.hide();
              $delActive.show();
              break;
            case "2":
              $("#status_text").html("进行中");
              $endActive.show();
              $delActive.hide();
              break;
            case "3":
              $("#status_text").html("已结束");
              $endActive.hide();
              $delActive.show();
              break;
          }

          $(".status_view").show(); //状态

          var quantity = activeData.quantity;
          $("#active_num option[value='" + quantity + "']").attr("selected", "selected");

          $("#changeFrom").val(activeData.start_time);
          $("#changeTo").val(activeData.end_time);
          $discount_name.val(activeData.title);
          amount_region = activeData.amount_region;

          //判断是否限制
          if (activeData.condition_limit != -1) {
            $('[value="金额限制"]').prop("checked", true);
            $('[value="减价"]').prop("checked", false);
            use_limit = 1;
            condition_limit = activeData.condition_limit;
            $discount_num.val(activeData.condition_limit);

          } else {
            $('[value="减价"]').prop("checked", true);
            $('[value="金额限制"]').prop("checked", false);
            $discount_num.val("");
            condition_limit = -1;
            use_limit = -1;
          }

          //判断是固定还是随机
          if (activeData.amount_type == 1) {
            $('[value="fixedPrice"]').prop("checked", true);
            $('[value="randomPrice"]').prop("checked", false);
            amount_type = 1;
            $price.val(activeData.amount);

          } else {
            $('[value="randomPrice"]').prop("checked", true);
            $('[value="fixedPrice"]').prop("checked", false);
            amount_type = 2;
            var str_before = amount_region.split('-')[0];
            var str_after = amount_region.split('-')[1];
            $minPrice.val(Number(str_before));
            $maxPrice.val(Number(str_after));
          }

          $accountFrom.val(activeData.use_start_time);
          $accountTo.val(activeData.use_end_time);

          //限制输入
          $minPrice.attr('disabled', true); //随机金额
          $maxPrice.attr('disabled', true);
          $('[name="price_type"]').attr('disabled', true);
          $price.attr('disabled', true);
          $("#active_state").attr('disabled', true);
          $accountFrom.attr('disabled', true);
          $accountFrom.css({
            "background": "#f7fbfe"
          });

          $("#changeFrom").attr('disabled', true);
          $("#changeFrom").css({
            "background": "#f7fbfe"
          });
          $("#active_num").attr('disabled', true);
          $discount_num.attr('disabled', true);
          $('[name="use_type"]').attr('disabled', true);

          //可以编辑的变为
          $("#changeTo").attr('disabled', true);
          $("#changeTo").css({
            "background": "#f7fbfe"
          });
          $discount_name.attr('disabled', true);
          $accountTo.attr('disabled', true);
          $accountTo.css({
            "background": "#f7fbfe"
          });

          $btnConfirm.hide();
          $btnConfirm.html("保存");

          if (activeData.is_open == 1) {
            $('[value="开启活动"]').prop("checked", true);
            $couponForm.show();
          } else {
            $('[value="开启活动"]').prop("checked", false);
            $couponForm.hide();
          }
        } else {
          $btnConfirm.html("保存");
          $editActive.hide();
          $btnConfirm.show();

          $('[value="开启活动"]').prop("checked", false);
          $couponForm.hide();
          $endActive.hide();
          $delActive.hide();

          //开发输入
          $price.attr('disabled', false);
          $("#active_state").attr('disabled', false);
          $accountFrom.attr('disabled', false);
          $("#changeFrom").attr('disabled', false);
          $("#active_num").attr('disabled', false);
          $discount_num.attr('disabled', false);
          $('[name="use_type"]').attr('disabled', false);

          //可以编辑的变为
          $("#changeTo").attr('disabled', false);
          $discount_name.attr('disabled', false);
          $accountTo.attr('disabled', false);
        }
      }
    });
  }

  getInfoDiscount();

  //开启活动裂变优惠
  function turnOrOff(el) {
    if ($(el).is(":checked")) {
      $couponForm.show();
      value = 1;
    } else {
      value = 0;
      $couponForm.hide();
    }

    $.post(__BASEURL__ + 'mshop/coupon_api/onoff', autoCsrf({
      type: 1,
      value: value
    }), function (data) {
      if (data.success) {
        new Msg({
          type: "success",
          msg: data.msg,
          delay: 1
        });
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
    });
  }

  //时间区间
  function selectTime() {
    //时间选择器
    var changeTo;var changeFrom;
    var accountFrom;var accountTo;
    changeFrom = {
      elem: '#changeFrom',
      format: 'yyyy-MM-dd HH:mm:ss',
      theme: '#5aa2e7',
      type: 'datetime',
      done: function (value, date, endDate) {
        changeTo.min=value;  //开始日选好后，重置结束日的最小日期
        changeTo.start = value //将结束日的初始值设定为开始日
      }
    };
    changeTo = {
      elem: '#changeTo',
      format: 'yyyy-MM-dd HH:mm:ss',
      max: '2099-06-16 23:59:59',
      min:'',
      type: 'datetime',
      theme: '#5aa2e7',
      choose: function(datas){
        changeTo.max = datas; //结束日选好后，重置开始日的最大日期
        changeTo.min = min;
      },
      done: function (value, date, endDate) {
      }
    };

    accountFrom = {
      elem: '#accountFrom',
      format: 'yyyy-MM-dd HH:mm:ss',
      theme: '#5aa2e7',
      type: 'datetime',
      done: function (value, date, endDate) {
        accountTo.min=value;  //开始日选好后，重置结束日的最小日期
        accountTo.start = value //将结束日的初始值设定为开始日
      }
    };
    accountTo = {
      elem: '#accountTo',
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
    laydate.render(changeTo);
    laydate.render(changeFrom);
    laydate.render(accountFrom);
    laydate.render(accountTo);
  }

  selectTime();

  function useType(el) {
    if ($(el).val() == "金额限制") {
      condition_limit = $discount_num.val();
      console.log($discount_num.val());
      use_limit = 1;
    } else {
      condition_limit = -1;
      use_limit = -1;
    }
  }

  //固定金额还是随机
  function usePriceType(el) {
    if ($(el).val() == "fixedPrice") {
      console.log($(el).val());
      amount_type = 1;
    } else {
      console.log($(el).val());
      amount_type = 2;

    }
  }

  function changeActiveNum(el) {
    active_num = $(el).find('option:selected').val();
  }

  //删除优惠券
  function endDiscount(index) {
    $endDiscountModal.modal("show");
  }

  $delDiscount.on("click", function () {
    discount = {
      list: []
    };
    //活动隐藏
    $("#changeFrom").val("");
    $("#changeTo").val("");
    $addDisBtn.show();
    $changeTable.hide();
    $(".activeTbody").html(template(discountTpl, discount));
    $endDiscountModal.modal("hide");
  });

  //结束活动
  function endActive(el, index, id) {
    $endActiveModal.modal("show");
    $endConfirm.attr("data_index", index);
    $endConfirm.attr("data_id", id);
  }

  $endConfirm.on("click", function () {
    var index = $(this).attr("data_index");
    var id = $(this).attr("data_id");
    $endConfirm.prop('disabled', true);
    $.post(__BASEURL__ + 'mshop/coupon_api/close', autoCsrf({
      type: 1
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: data.msg
        });
        $endActiveModal.modal("hide");
        $endActive.hide();
        $delActive.show();
        $("#status_text").html("已结束");
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      $endConfirm.prop('disabled', false);
    });
  });

  //删除活动
  function delActive() {
    $delActiveModal.modal("show")
  }

  $delConfirm.on("click", function () {
    //删除
    discount = {
      list: []
    };
    $delConfirm.prop('disabled', true);
    $.post(__BASEURL__ + 'mshop/coupon_api/delete', autoCsrf({
      type: 1
    }), function (data) {
      if (data.success) {
        new Msg({
          type: 'success',
          msg: data.msg
        });
        $endActive.hide();
        $delActive.hide();
        $btnConfirm.html("保存");
        $btnConfirm.show();
        $editActive.hide();
        $delActiveModal.modal("hide");

        $(".status_view").hide(); //状态


        $("#changeFrom").val("");
        $("#changeTo").val("");
        $discount_name.val("");
        $price.val("");
        $discount_num.val("");
        $accountFrom.val("");
        $accountTo.val("");
        $('[value="金额限制"]').prop("checked", true);
        $('[value="减价"]').prop("checked", false);
        use_limit = 1;
        condition_limit = "";

        $('[value="fixedPrice"]').prop("checked", true);
        $('[value="randomPrice"]').prop("checked", false);
        $('[name="price_type"]').attr('disabled', false);
        $minPrice.attr('disabled', false); //随机金额
        $maxPrice.attr('disabled', false);

        //开发输入
        $price.attr('disabled', false);
        $("#active_state").attr('disabled', false);
        $accountFrom.attr('disabled', false);
        $("#changeFrom").attr('disabled', false);
        $("#active_num").attr('disabled', false);
        $discount_num.attr('disabled', false);
        $('[name="use_type"]').attr('disabled', false);

        $("#changeTo").attr('disabled', false);
        $discount_name.attr('disabled', false);
        $accountTo.attr('disabled', false);
        activeGetData = null;
      } else {
        new Msg({
          type: 'danger',
          msg: data.msg
        });
      }
      $delConfirm.prop('disabled', false);
    });
  });

  //编辑活动
  function editActive() {
    new Msg({
      type: 'success',
      msg: "请编辑"
    });
    $editActive.hide();
    $btnConfirm.show();

    //颜色改变
    $("#changeTo").css({
      "background": "transparent"
    });
    $accountTo.css({
      "background": "transparent"
    });
    $("#changeTo").attr('disabled', false);
    $discount_name.attr('disabled', false);
    $accountTo.attr('disabled', false);
  }

  //活动表单验证
  $('#coupon-form')
    .bootstrapValidator({
      fields: {
        discount_name: {
          validators: {
            notEmpty: {
              message: '优惠名称不能为空'
            },
            stringLength: {
              max: 10,
              message: '优惠名称不得超过10个字符'
            }
          }
        },
        active_num: {
          validators: {
            notEmpty: {
              message: '获取数量不能为空'
            }
          }
        }
      }
    })
    .on('success.form.bv', function (e) {
      // 阻止表单默认提交
      e.preventDefault();

      var activeFromTime = $("#changeFrom").val(),
        activeToTime = $("#changeTo").val();

      var discount_name = $discount_name.val(),
        send_price = $price.val(),
        fullPrice = $discount_num.val(),
        accountFromTime = $accountFrom.val(),
        accountToTime = $accountTo.val(),
        minPrice = $minPrice.val(),
        maxPrice = $maxPrice.val();

      //优惠券时间判断
      var accountStartTime = (accountFromTime.replace(/-/g, '/'));
      var accountEndTime = new Date(accountToTime.replace(/-/g, '/'));
      var t1 = parseInt(accountEndTime.getTime()) - (new Date(accountStartTime).getTime());

      if (t1 <= 0) {
        new Msg({
          type: "danger",
          msg: "优惠使用结束时间不能小于开始时间"
        });
        return false;
      }

      if (use_limit == -1) {
        condition_limit = -1;
      } else {
        condition_limit = fullPrice;
      }

      //活动时间判断
      var startTime = (activeFromTime.replace(/-/g, '/'));
      var endTime = new Date(activeToTime.replace(/-/g, '/'));
      var t = parseInt(endTime.getTime()) - (new Date(startTime).getTime());

      if (t <= 0) {
        new Msg({
          type: "danger",
          msg: "活动结束时间不能小于开始时间"
        });
        return false;
      }

      var option = $('#active_num option:selected'),
        active_num = option.val();


      if (activeFromTime == "" || activeToTime == "") {
        new Msg({
          type: "danger",
          msg: "请填写活动时间"
        });
        return false;
      }
      if (accountFromTime == "" || accountToTime == "") {
        new Msg({
          type: "danger",
          msg: "请填写优惠使用时间"
        });
        return false;
      }

      //判断固定还是随机
      var re = /^(0|[1-9]\d*)(\.\d{1,4})?$/;
      if (amount_type == 1) {
        amount_region = 0;
        if (!re.test(send_price)) {
          new Msg({
            type: "danger",
            msg: "固定金额只能输入数字"
          });
          return false;
        }
      } else {
        send_price = 0;
        if (!re.test(minPrice)) {
          new Msg({
            type: "danger",
            msg: "最小金额只能输入数字"
          });
          return false;
        }
        if (!re.test(maxPrice)) {
          new Msg({
            type: "danger",
            msg: "最大金额只能输入数字"
          });
          return false;
        }
        if (Number(maxPrice) - Number(minPrice) <= 0) {
          new Msg({
            type: "danger",
            msg: "最大金额要大于最小金额"
          });
          return false;
        }
        amount_region = minPrice + '-' + maxPrice;
        console.log(amount_region)
      }

      if (use_limit != -1 && fullPrice == "") {
        new Msg({
          type: "danger",
          msg: "请填写订单满几元可用"
        });
        return false;
      }

      post_data = {
        title: discount_name,
        amount_type: amount_type,
        amount: send_price,
        amount_region: amount_region,
        quantity: active_num,
        start_time: activeFromTime,
        end_time: activeToTime,
        use_start_time: accountFromTime,
        use_end_time: accountToTime,
        type: 1,
        condition_limit: condition_limit
      };

      // 判断是添加或编辑
      post_url = __BASEURL__ + 'mshop/coupon_api/save';
      $btnConfirm.prop('disabled', true);

      $.post(post_url, autoCsrf(post_data), function (data) {
        if (data.success) {
          $btnConfirm.html("保存");
          $(".status_view").show(); //状态

          if (activeGetData != null) {
            var $text = $("#status_text").html();
            $("#status_text").html($text);
          } else {
            $("#status_text").html("未开始");
          }

          if ($("#status_text").html() == "未开始") {
            $delActive.show();
          } else {
            $delActive.hide();
          }

          $editActive.show();
          $btnConfirm.hide();

          //限制输入
          $minPrice.attr('disabled', true); //随机金额
          $maxPrice.attr('disabled', true);
          $('[name="price_type"]').attr('disabled', true);
          $price.attr('disabled', true);
          $("#active_state").attr('disabled', true);
          $accountFrom.attr('disabled', true);
          $("#changeFrom").attr('disabled', true);
          $("#active_num").attr('disabled', true);
          $discount_num.attr('disabled', true);
          $('[name="use_type"]').attr('disabled', true);

          $("#changeTo").attr('disabled', true);
          $discount_name.attr('disabled', true);
          $accountTo.attr('disabled', true);

          //颜色改变
          $("#changeTo").css({
            "background": "#f7fbfe"
          });
          $accountTo.css({
            "background": "#f7fbfe"
          });

          new Msg({
            type: "success",
            msg: data.msg,
            delay: 1
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

  window.turnOrOff = turnOrOff;
  window.useType = useType;
  window.usePriceType = usePriceType;
  window.changeActiveNum = changeActiveNum;
  window.delActive = delActive;
  window.endDiscount = endDiscount;
  window.editActive = editActive;
  window.endActive = endActive;
});
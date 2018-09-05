/**
 * order_list.js
 * by jinmu
 * date: 2017-11-21
 */
$(function () {

  var couponForm = $(".coupon-form"),
    couponEditForm = $(".coupon-edit-form"),
    couponCard = $(".coupon-card"),
    couponLimit = $(".coupon_limit"),
    couponEnd=$(".coupon_end");

  var receive_btn = $("#receive_btn"),
    use_btn = $("#use_btn"),
    edit_btn = $("#edit_btn");


  var myCouponTpl = document.getElementById("myCouponTpl").innerHTML;

  var phone = "",
    limit_num = 4,
    is_receive = true,
    end_tine="2017-11-24 10:10:02",
    tradeno="",
    coupons = {
      list: []
    },
    myCoupon = {
      list: [
        /* {
         full_price:"30",
          price:"8",
          overTime:"2",
           phone:phone
       }*/
      ]
    };

  function getCouponData() {
    $.post(
      __BASEURL__ + "api/coupon/loadFollow", autoCsrf(),
      function (data) {
        if (data.success) {
          var dataInfo=data.data;
          phone=dataInfo.mobile;
          //phone=null;
          if(dataInfo.coupon!=null){
            myCoupon.list.push(dataInfo.coupon);
          }
          var code=data.code;
          //优惠券时间判断
          if(code=="004"){
            //活动结束
            couponEnd.show();
            couponForm.hide();
            couponCard.hide();

            receive_btn.hide();
          }else{
            //活动未结束
            couponEnd.hide();

            if (phone == null) {
              couponForm.show();
              couponCard.hide();
              receive_btn.show();
              use_btn.hide();
            } else {
              $(".default_phone").val(phone);
              //如果手机号存在还有券也没有领取
              if (myCoupon.list.length>0) {
                getMyCoupon();//我的优惠券
                couponForm.hide();
                couponCard.show();
                receive_btn.hide();
                use_btn.show();
              } else {
                $.post( __BASEURL__ + "api/coupon/pickupFollow", autoCsrf({}), function (data) {
                  if (data.success) {
                    layer.open({
                      content: data.msg,
                      skin: "msg",
                      time: 1,
                      success: function(){
                        getCouponData();
                        //window.location.href =  document.referrer;
                      }
                    });
                  } else {
                    layer.open({
                      content: data.msg,
                      skin: "msg",
                      time: 1
                    });
                  }
                  receive_btn.prop('disabled', false);
                })
              }

            }
          }

        }else{
          phone=data.data.mobile;
          //phone=null;
          myCoupon.list.push(data.data.coupon);
          coupons.list=data.data.list;
          if(data.code=="004"){
            //couponEnd.show();
            if(data.msg=="已领取过优惠券"){
              layer.open({
                content: data.msg,
                skin: "msg",
                time: 1
              });
              couponEnd.hide();
              couponForm.hide();
              couponCard.show();
              getMyCoupon();

              receive_btn.hide();
              use_btn.show();
            }else{
              layer.open({
                content: data.msg,
                skin: "msg",
                time: 1
              });
              couponEnd.show();
              couponEnd.find("p").html(data.msg);
              couponForm.hide();
              couponCard.hide();

              receive_btn.hide();
            }

          }
        }
      }
    );

  }
  getCouponData();

  //立即领取
  function receiveClick() {
    phone = $(".default_phone").val();
    if (!(/^(13|15|18|17)[0-9]{9}$/.test(phone))) {
      layer.open({
        content: "请输入正确的手机号码",
        skin: "msg",
        time: 1
      });
      return false;
    }

    if (limit_num >= 5) {
      console.log(limit_num);
      couponForm.hide();
      couponCard.hide();
      couponLimit.show();
      return false;
    } else {
      receive_btn.prop('disabled', true);
      $.post( __BASEURL__ + "api/coupon/pickupFollow", autoCsrf({mobile:phone}), function (data) {
          if (data.success) {
            layer.open({
              content: data.msg,
              skin: "msg",
              time: 1,
              success: function(){
                getCouponData();
                //window.location.href =  document.referrer;
              }
            });
          } else {
            layer.open({
              content: data.msg,
              skin: "msg",
              time: 1
            });
          }
        receive_btn.prop('disabled', false);
        }
      );
    }


  }

  //我获取的优惠券
  function getMyCoupon() {
    $(".coupon-card").html(template(myCouponTpl, myCoupon));
  }

  //修改手机号
  function editPhone() {
    $(".now_phone").html(phone);
    $(".edit_phone").val(phone);

    couponEditForm.show();
    couponCard.hide();

    edit_btn.show();
    use_btn.hide();
  }

  //修改手机号提交表单
  function editFromClick() {
    phone = $(".edit_phone").val();
    //Tel: /^(13|15|18|17)[0-9]{9}$/, //手机
    if (!(/^(13|15|18|17)[0-9]{9}$/.test(phone))) {
      layer.open({
        content: "请输入正确的手机号码",
        skin: "msg",
        time: 1
      });
      return false;
    }

    edit_btn.prop('disabled', true);
    $.post( __BASEURL__ + "api/coupon/changeMobile", autoCsrf({mobile: phone}), function (data) {
      if (data.success) {
        layer.open({
          content: data.msg,
          skin: "msg",
          time: 1
        });
        getMyCoupon();
        //列表显示
        couponEditForm.hide();
        couponCard.show();
        edit_btn.hide();
        use_btn.show();

      } else {
        layer.open({
          content: data.msg,
          skin: "msg",
          time: 1
        });
      }
      edit_btn.prop('disabled', false);
    });


  }


  //getCouponInfo();

  window.receiveClick = receiveClick;
  window.editPhone = editPhone;
  window.editFromClick=editFromClick;
});